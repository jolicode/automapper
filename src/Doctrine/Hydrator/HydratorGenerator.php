<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\AliasPlan;
use AutoMapper\Doctrine\Hydrator\Plan\AssociationPlan;
use AutoMapper\Doctrine\Hydrator\Plan\ColumnPlan;
use AutoMapper\Doctrine\Hydrator\Plan\EntityPlan;
use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use AutoMapper\Doctrine\Hydrator\Plan\PropertyWrite;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\PersistentCollection;
use PhpParser\BuilderFactory;
use PhpParser\Modifiers;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Generates the hydrator class of a {@see HydrationPlan}.
 *
 * The generated doHydrate() method is a single loop over the rows, with the column names, the type conversions
 * and the association handling of each alias written inline. Entities are filled by a closure bound to their
 * class, so private properties are written directly. doHydrateRow() is the same code for a single row, used
 * when iterating.
 *
 * @internal
 */
final class HydratorGenerator
{
    private HydrationPlan $plan;
    private DbalTypeConverter $converter;

    /** @var list<array{int, string}> */
    private array $associations = [];

    /** @var list<array{int, string}> */
    private array $accessors = [];

    public function generate(HydrationPlan $plan, string $className, AbstractPlatform $platform): Stmt\Class_
    {
        $this->plan = $plan;
        $this->converter = new DbalTypeConverter($platform);
        $this->associations = [];
        $this->accessors = [];

        $factory = new BuilderFactory();
        $initialize = [];

        foreach ($plan->aliases as $alias) {
            foreach ($alias->entities as $entity) {
                $initialize[] = self::assign(self::dim(self::prop('fills'), self::int($alias->index), self::int($entity->classIndex)), $this->fillClosure($alias, $entity));
                $initialize[] = self::assign(self::dim(self::prop('gathers'), self::int($alias->index), self::int($entity->classIndex)), $this->gatherClosure($entity));
            }
        }

        $doHydrate = $this->hydrateMethod(true);
        $doHydrateRow = $this->hydrateMethod(false);

        $aliases = [];
        $slow = [];
        $discriminators = [];

        foreach ($plan->aliases as $alias) {
            $aliases[$alias->index] = [$alias->alias, array_keys($alias->entities)];

            foreach ($alias->entities as $entity) {
                if (null !== $entity->slowReason) {
                    $slow[$alias->index][$entity->classIndex] = $entity->slowReason;
                }
            }

            if (null !== $alias->discriminator) {
                $discriminators[$alias->index] = [
                    'map' => $alias->discriminator->map,
                    'class' => $alias->className,
                    'name' => $alias->discriminator->name,
                    'alias' => $alias->alias,
                ];
            }
        }

        $parameters = static fn (\PhpParser\Builder\Method $method): \PhpParser\Builder\Method => $method
            ->addParam($factory->param('hints')->setType('array')->makeByRef())
            ->addParam($factory->param('slow')->setType('array'))
            ->addParam($factory->param('postLoadFlags')->setType('array'))
            ->addParam($factory->param('readOnly')->setType('bool'))
            ->addParam($factory->param('postLoad')->setType('array')->makeByRef())
            ->setReturnType('array');

        return $factory->class($className)
            ->makeFinal()
            ->extend(new Name\FullyQualified(GeneratedHydrator::class))
            ->addStmt($this->constant('MODE', $plan->mode))
            ->addStmt($this->constant('CLASSES', $plan->classes))
            ->addStmt($this->constant('TYPES', $this->converter->getUsedTypes()))
            ->addStmt($this->constant('ALIASES', $aliases))
            ->addStmt($this->constant('FETCHED', $plan->fetched))
            ->addStmt($this->constant('SLOW', $slow))
            ->addStmt($this->constant('DISCRIMINATORS', $discriminators))
            ->addStmt($this->constant('ASSOCIATIONS', $this->associations))
            ->addStmt($this->constant('ACCESSORS', $this->accessors))
            ->addStmt($factory->method('initialize')->makeProtected()->setReturnType('void')->addStmts($initialize))
            ->addStmt($parameters($factory->method('doHydrate')
                ->makeProtected()
                ->addParam($factory->param('stmt')->setType(new Name\FullyQualified(Result::class))))
                ->addStmts($doHydrate))
            ->addStmt($parameters($factory->method('doHydrateRow')
                ->makeProtected()
                ->addParam($factory->param('row')->setType('array')))
                ->addStmts($doHydrateRow))
            ->getNode();
    }

    /**
     * @return Stmt[]
     */
    private function hydrateMethod(bool $loop): array
    {
        $stmts = [
            self::assign(self::var('uow'), self::prop('uow')),
            self::assign(self::var('result'), new Expr\Array_()),
            self::assign(self::var('snapshots'), new Expr\Array_()),
            self::assign(self::var('uninitialized'), new Expr\Array_()),
            self::assign(self::var('own'), new Expr\Array_()),
            self::assign(self::var('hc'), $this->isObjectMode() ? new Expr\BinaryOp\Coalesce(self::dim(self::var('hints'), self::str('collection')), self::null()) : self::null()),
        ];

        foreach ($this->plan->aliases as $alias) {
            $i = $alias->index;
            $stmts[] = self::assign(self::var("e$i"), self::null());

            if ($alias->isPolymorphic()) {
                $stmts[] = self::assign(self::var("fill$i"), self::dim(self::prop('fills'), self::int($i)));
                $stmts[] = self::assign(self::var("gather$i"), self::dim(self::prop('gathers'), self::int($i)));
                $stmts[] = self::assign(self::var("slow$i"), self::dim(self::var('slow'), self::int($i)));
                $stmts[] = self::assign(self::var("pl$i"), new Expr\Array_(array_map(
                    static fn (EntityPlan $entity) => new ArrayItem(new Expr\BinaryOp\NotIdentical(self::int(0), self::dim(self::var('postLoadFlags'), self::int($entity->classIndex))), self::int($entity->classIndex)),
                    array_values($alias->entities),
                )));
                $stmts[] = self::assign(self::var("proto$i"), self::prop('prototypes'));
            } else {
                $entity = $alias->firstEntity();
                $stmts[] = self::assign(self::var("fill$i"), self::dim(self::prop('fills'), self::int($i), self::int($entity->classIndex)));
                $stmts[] = self::assign(self::var("gather$i"), self::dim(self::prop('gathers'), self::int($i), self::int($entity->classIndex)));
                $stmts[] = self::assign(self::var("slow$i"), self::dim(self::var('slow'), self::int($i), self::int($entity->classIndex)));
                $stmts[] = self::assign(self::var("pl$i"), new Expr\BinaryOp\NotIdentical(self::int(0), self::dim(self::var('postLoadFlags'), self::int($entity->classIndex))));

                if ($entity->cloneable) {
                    $stmts[] = self::assign(self::var("proto$i"), self::dim(self::prop('prototypes'), self::int($entity->classIndex)));
                }
            }
        }

        $stmts = [...$stmts, ...$this->resetState()];

        $body = [];

        foreach ($this->plan->aliases as $alias) {
            $body = [...$body, ...match ($alias->relationKind) {
                AliasPlan::ROOT => $this->rootBlock($alias, $loop),
                AliasPlan::TO_MANY => $this->toManyBlock($alias, $this->parent($alias)),
                default => $this->toOneBlock($alias, $this->parent($alias)),
            }];
        }

        if (!$loop) {
            return [...$stmts, ...$body, new Stmt\Return_(self::var('result'))];
        }

        // an onClear event detached every entity seen so far, like ObjectHydrator::onClear() they are hydrated again
        array_unshift($body, new Stmt\If_(self::prop('cleared'), ['stmts' => [
            self::assign(self::prop('cleared'), new Expr\ConstFetch(new Name('false'))),
            ...$this->resetState(),
        ]]));

        $stmts[] = new Stmt\While_(
            new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('false')), new Expr\Assign(self::var('row'), new Expr\MethodCall(self::var('stmt'), 'fetchAssociative'))),
            $body,
        );

        // same final state as the ObjectHydrator, which moves the internal pointer after each hydrateAdd()
        foreach ($this->plan->aliases as $alias) {
            if (AliasPlan::TO_MANY !== $alias->relationKind || null !== $alias->indexByColumn) {
                continue;
            }

            $stmts[] = new Stmt\Foreach_($this->collections($this->parent($alias), (string) $alias->relationField), self::var('collection'), ['stmts' => [
                new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('false')), self::var('collection')), ['stmts' => [
                    new Stmt\Expression(new Expr\MethodCall(new Expr\MethodCall(self::var('collection'), 'unwrap'), 'last')),
                ]]),
            ]]);
        }

        $stmts[] = new Stmt\Foreach_(self::var('snapshots'), self::var('collection'), ['stmts' => [
            new Stmt\Expression(new Expr\MethodCall(self::var('collection'), 'takeSnapshot')),
        ]]);
        $stmts[] = new Stmt\Foreach_(self::var('uninitialized'), self::var('collection'), ['stmts' => [
            new Stmt\If_(new Expr\BooleanNot(new Expr\MethodCall(self::var('collection'), 'isInitialized')), ['stmts' => [
                new Stmt\Expression(new Expr\MethodCall(self::var('collection'), 'setInitialized', self::args(new Expr\ConstFetch(new Name('true'))))),
            ]]),
        ]]);
        $stmts[] = new Stmt\Return_(self::var('result'));

        return $stmts;
    }

    /**
     * State of the hydration keyed by the identifier of the rows.
     *
     * @return Stmt[]
     */
    private function resetState(): array
    {
        $stmts = [];

        foreach ($this->plan->aliases as $alias) {
            $i = $alias->index;
            $stmts[] = self::assign(self::var("ent$i"), new Expr\Array_());
            $stmts[] = self::assign(self::var("fr$i"), new Expr\Array_());

            if (AliasPlan::TO_MANY === $alias->relationKind) {
                $stmts[] = self::assign(self::var("pair$i"), new Expr\Array_());
                $stmts[] = self::assign(self::var("emp$i"), new Expr\Array_());
            } elseif (AliasPlan::TO_ONE === $alias->relationKind) {
                $stmts[] = self::assign(self::var("one$i"), new Expr\Array_());
            }

            foreach ($alias->fetchedCollections as $field) {
                $stmts[] = self::assign($this->collections($alias, $field), new Expr\Array_());
            }
        }

        return $stmts;
    }

    /**
     * @return Stmt[]
     */
    private function rootBlock(AliasPlan $alias, bool $loop): array
    {
        $i = $alias->index;
        $raw = self::var("r$i");
        $entity = self::var("e$i");

        if (!$this->isObjectMode()) {
            // the SimpleObjectHydrator appends one entity per row, even when rows share an identifier
            return [
                ...$this->readKey($alias),
                new Stmt\If_(new Expr\Isset_([self::dim(self::var("ent$i"), $raw)]), [
                    'stmts' => [self::assign($entity, self::dim(self::var("ent$i"), $raw))],
                    'else' => new Stmt\Else_([
                        ...$this->materialize($alias, null),
                        self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                    ]),
                ]),
                self::assign(new Expr\ArrayDimFetch(self::var('result')), $entity),
            ];
        }

        $append = null === $alias->indexByColumn
            ? new Expr\Assign(new Expr\ArrayDimFetch(self::var('result')), $entity)
            : new Expr\Assign(self::dim(self::var('result'), self::dim(self::var('row'), self::str($alias->indexByColumn))), $entity);

        // collections loaded by the entity persisters receive the root entities directly
        $addToCollection = new Stmt\If_(new Expr\BinaryOp\NotIdentical(self::null(), self::var('hc')), ['stmts' => [
            new Stmt\Expression(null === $alias->indexByColumn
                ? new Expr\MethodCall(self::var('hc'), 'hydrateAdd', self::args($entity))
                : new Expr\MethodCall(self::var('hc'), 'hydrateSet', self::args(self::dim(self::var('row'), self::str($alias->indexByColumn)), $entity))),
        ]]);

        return [
            ...$this->readKey($alias),
            // the joined aliases of a row without root entity are skipped, like the ObjectHydrator does
            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $raw), ['stmts' => [
                new Stmt\Expression(new Expr\Assign(new Expr\ArrayDimFetch(self::var('result')), self::null())),
                $loop ? new Stmt\Continue_() : new Stmt\Return_(self::var('result')),
            ]]),
            new Stmt\If_(new Expr\Isset_([self::dim(self::var("ent$i"), $raw)]), [
                'stmts' => [self::assign($entity, self::dim(self::var("ent$i"), $raw))],
                'else' => new Stmt\Else_([
                    ...$this->materialize($alias, null),
                    self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                    $addToCollection,
                    new Stmt\Expression($append),
                ]),
            ]),
        ];
    }

    /**
     * @return Stmt[]
     */
    private function toManyBlock(AliasPlan $alias, AliasPlan $parent): array
    {
        $i = $alias->index;
        $raw = self::var("r$i");
        $entity = self::var("e$i");
        $parentRaw = self::var("r{$parent->index}");
        $parentEntity = self::var("e{$parent->index}");
        $field = (string) $alias->relationField;
        $collections = $this->collections($parent, $field);
        $collection = self::var('c');
        $this_ = self::var('this');
        $backReference = null !== $alias->firstEntity()->backReference;

        $initCollection = new Expr\AssignOp\Coalesce(self::dim($collections, $parentRaw), new Expr\MethodCall($this_, 'initRelatedCollection', self::args(
            $parentEntity,
            self::int($parent->classIndex),
            self::str($field),
            self::str($parent->alias),
            self::var('hints'),
            self::var('own'),
            self::var('snapshots'),
        )));

        $add = null !== $alias->indexByColumn
            ? static fn (Expr $element) => new Expr\MethodCall($collection, 'hydrateSet', self::args(self::dim(self::var('row'), self::str($alias->indexByColumn)), $element))
            : static fn (Expr $element) => new Expr\MethodCall($collection, 'hydrateAdd', self::args($element));

        if ($backReference) {
            // a child created here already has its back reference, only an existing one needs hydrateAdd()
            $addCreated = new Stmt\If_(self::dim(self::var("fr$i"), $raw), [
                'stmts' => [new Stmt\Expression(new Expr\MethodCall(new Expr\MethodCall($collection, 'unwrap'), 'add', self::args($entity)))],
                'else' => new Stmt\Else_([new Stmt\Expression($add($entity))]),
            ]);
        } elseif (null === $alias->indexByColumn) {
            // many-to-many: hydrateAdd() has no back reference to set
            $addCreated = new Stmt\Expression(new Expr\MethodCall(new Expr\MethodCall($collection, 'unwrap'), 'add', self::args($entity)));
        } else {
            $addCreated = new Stmt\Expression($add($entity));
        }

        return [
            ...$this->readKey($alias),
            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $parentRaw), [
                'stmts' => [self::assign($entity, self::null())],
                'elseifs' => [
                    new Stmt\ElseIf_(new Expr\BinaryOp\Identical(self::null(), $parentEntity), $this->orphan($alias, $parent)),
                    new Stmt\ElseIf_(new Expr\BinaryOp\Identical(self::null(), $raw), [
                        self::assign($entity, self::null()),
                        new Stmt\If_(new Expr\BinaryOp\BooleanAnd(
                            new Expr\BooleanNot(new Expr\Isset_([self::dim($collections, $parentRaw)])),
                            new Expr\BooleanNot(new Expr\Isset_([self::dim(self::var("emp$i"), $parentRaw)])),
                        ), ['stmts' => [
                            new Stmt\Expression(new Expr\MethodCall($this_, 'emptyCollection', self::args(
                                $parentEntity,
                                self::int($parent->classIndex),
                                self::str($field),
                                self::str($parent->alias),
                                self::var('hints'),
                                self::var('own'),
                                self::var('snapshots'),
                                self::var('uninitialized'),
                            ))),
                            self::assign(self::dim(self::var("emp$i"), $parentRaw), new Expr\ConstFetch(new Name('true'))),
                        ]]),
                    ]),
                    new Stmt\ElseIf_(new Expr\Isset_([self::dim(self::var("pair$i"), $parentRaw, $raw)]), [
                        self::assign($entity, new Expr\BinaryOp\Coalesce(self::dim(self::var("ent$i"), $raw), self::null())),
                    ]),
                ],
                'else' => new Stmt\Else_([
                    self::assign(self::dim(self::var("pair$i"), $parentRaw, $raw), new Expr\ConstFetch(new Name('true'))),
                    self::assign($collection, $initCollection),
                    new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('false')), $collection), [
                        // existing collection, the element is only looked up in the identity map
                        'stmts' => [
                            ...$this->identifier($alias),
                            self::assign($entity, new Expr\BinaryOp\Coalesce(
                                self::dim(self::var("ent$i"), $raw),
                                new Expr\Ternary(new Expr\MethodCall(self::var('uow'), 'tryGetByIdHash', self::args($this->identifierHash($alias), self::str($alias->rootEntityName))), null, self::null()),
                            )),
                            new Stmt\If_(new Expr\BinaryOp\NotIdentical(self::null(), $entity), ['stmts' => [
                                self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                                new Stmt\Expression(new Expr\AssignOp\Coalesce(self::dim(self::var("fr$i"), $raw), new Expr\ConstFetch(new Name('false')))),
                            ]]),
                        ],
                        'elseifs' => [
                            new Stmt\ElseIf_(new Expr\Isset_([self::dim(self::var("ent$i"), $raw)]), [
                                self::assign($entity, self::dim(self::var("ent$i"), $raw)),
                                new Stmt\Expression($add($entity)),
                            ]),
                        ],
                        'else' => new Stmt\Else_([
                            ...$this->materialize($alias, $backReference ? $parentEntity : null),
                            self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                            $addCreated,
                        ]),
                    ]),
                ]),
            ]),
        ];
    }

    /**
     * @return Stmt[]
     */
    private function toOneBlock(AliasPlan $alias, AliasPlan $parent): array
    {
        $i = $alias->index;
        $raw = self::var("r$i");
        $entity = self::var("e$i");
        $parentRaw = self::var("r{$parent->index}");
        $parentEntity = self::var("e{$parent->index}");
        $field = (string) $alias->relationField;
        $this_ = self::var('this');

        return [
            ...$this->readKey($alias),
            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $parentRaw), [
                'stmts' => [self::assign($entity, self::null())],
                'elseifs' => [
                    new Stmt\ElseIf_(new Expr\BinaryOp\Identical(self::null(), $parentEntity), $this->orphan($alias, $parent)),
                    new Stmt\ElseIf_(new Expr\Isset_([self::dim(self::var("one$i"), $parentRaw)]), [
                        self::assign($entity, new Expr\Ternary(self::dim(self::var("one$i"), $parentRaw), null, self::null())),
                    ]),
                ],
                'else' => new Stmt\Else_([
                    new Stmt\If_(self::dim(self::var("fr{$parent->index}"), $parentRaw), [
                        'stmts' => [
                            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $raw), [
                                'stmts' => [self::assign($entity, self::null())],
                                'elseifs' => [
                                    new Stmt\ElseIf_(new Expr\Isset_([self::dim(self::var("ent$i"), $raw)]), [
                                        self::assign($entity, self::dim(self::var("ent$i"), $raw)),
                                    ]),
                                ],
                                'else' => new Stmt\Else_([
                                    ...$this->materialize($alias, null),
                                    self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                                ]),
                            ]),
                            new Stmt\Expression(new Expr\MethodCall($this_, 'linkToOne', self::args(self::int($parent->classIndex), self::str($field), $parentEntity, $entity))),
                        ],
                        'else' => new Stmt\Else_([
                            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $raw), [
                                'stmts' => [
                                    self::assign(self::var('cd'), self::null()),
                                    self::assign(self::var("k$i"), self::int($alias->firstEntity()->classIndex)),
                                ],
                                'else' => new Stmt\Else_([
                                    ...$this->discriminate($alias),
                                    self::assign(self::var('cd'), new Expr\FuncCall($this->perClass($alias, 'gather'), self::args(self::var('row'), $this_))),
                                ]),
                            ]),
                            self::assign($entity, new Expr\MethodCall($this_, 'toOneOfExisting', self::args(
                                self::int($parent->classIndex),
                                self::str($field),
                                $parentEntity,
                                self::str($alias->alias),
                                $alias->isPolymorphic() ? self::var("k$i") : self::int($alias->firstEntity()->classIndex),
                                self::var('cd'),
                                self::var('hints'),
                            ))),
                            new Stmt\If_(new Expr\BinaryOp\NotIdentical(self::null(), $entity), ['stmts' => [
                                new Stmt\Expression(new Expr\AssignOp\Coalesce(self::dim(self::var("fr$i"), $raw), new Expr\ConstFetch(new Name('false')))),
                            ]]),
                        ]),
                    ]),
                    self::assign(self::dim(self::var("one$i"), $parentRaw), new Expr\BinaryOp\Coalesce($entity, new Expr\ConstFetch(new Name('false')))),
                ]),
            ]),
        ];
    }

    /**
     * The parent entity is in the row but was not hydrated, which happens when an existing collection does not
     * contain it: the ObjectHydrator then creates the child alone and stops treating the relation as fetched.
     *
     * @return Stmt[]
     */
    private function orphan(AliasPlan $alias, AliasPlan $parent): array
    {
        $i = $alias->index;
        $raw = self::var("r$i");
        $entity = self::var("e$i");
        $parentSlow = self::var("slow{$parent->index}");

        return [
            new Stmt\If_(new Expr\BinaryOp\Identical(self::null(), $raw), [
                'stmts' => [self::assign($entity, self::null())],
                'else' => new Stmt\Else_([
                    ...$this->discriminate($alias),
                    self::assign($entity, $this->createEntity($alias)),
                    self::assign(self::dim(self::var("ent$i"), $raw), $entity),
                    new Stmt\Expression(new Expr\AssignOp\Coalesce(self::dim(self::var("fr$i"), $raw), new Expr\ConstFetch(new Name('false')))),
                ]),
            ]),
            new Stmt\Unset_([self::dim(self::var('hints'), self::str('fetched'), self::str($parent->alias), self::str((string) $alias->relationField))]),
            // entities of the parent alias now go through the unit of work, which reads the updated hints
            self::assign($parentSlow, $parent->isPolymorphic()
                ? new Expr\FuncCall(new Name\FullyQualified('array_fill_keys'), self::args(new Expr\FuncCall(new Name\FullyQualified('array_keys'), self::args($parentSlow)), new Expr\ConstFetch(new Name('true'))))
                : new Expr\ConstFetch(new Name('true'))),
        ];
    }

    /**
     * Statements creating the entity of the current row, or getting it from the unit of work when it is already
     * managed. Sets $e{i} and $fr{i}[$r{i}], true when the entity was created and filled by this hydration.
     *
     * @return Stmt[]
     */
    private function materialize(AliasPlan $alias, ?Expr $parent): array
    {
        $i = $alias->index;
        $raw = self::var("r$i");
        $entity = self::var("e$i");
        $data = self::var('d');
        $uow = self::var('uow');
        $classIndex = $alias->isPolymorphic() ? self::var("k$i") : self::int($alias->firstEntity()->classIndex);

        $created = [
            self::assign($entity, $this->instantiate($alias)),
            self::assign($data, new Expr\FuncCall($this->perClass($alias, 'fill'), self::args($entity, self::var('row'), self::var("id$i"), self::var('this'), ...(null !== $parent ? [$parent] : [])))),
            new Stmt\Expression(new Expr\MethodCall($uow, 'registerManaged', self::args(
                $entity,
                $alias->hasSimpleIdentifier() ? new Expr\Array_([new ArrayItem(self::var("id$i"), self::str($alias->identifier[0]->field))]) : self::var("id$i"),
                $data,
            ))),
            new Stmt\If_(self::var('readOnly'), ['stmts' => [new Stmt\Expression(new Expr\MethodCall($uow, 'markReadOnly', self::args($entity)))]]),
            new Stmt\If_($this->perClass($alias, 'pl'), ['stmts' => [
                self::assign(new Expr\ArrayDimFetch(self::var('postLoad')), new Expr\Array_([new ArrayItem($classIndex), new ArrayItem($entity)])),
            ]]),
        ];

        foreach ($alias->fetchedCollections as $field) {
            $value = self::dim($data, self::str($field));
            $created[] = self::assign(self::dim($this->collections($alias, $field), $raw), new Expr\Assign(new Expr\ArrayDimFetch(self::var('snapshots')), $value));

            if ($alias->sharedCollections) {
                $created[] = self::assign(
                    self::dim(self::var('own'), new Expr\BinaryOp\Concat(new Expr\FuncCall(new Name\FullyQualified('spl_object_id'), self::args($entity)), self::str($field))),
                    $value,
                );
            }
        }

        $created[] = self::assign(self::dim(self::var("fr$i"), $raw), new Expr\ConstFetch(new Name('true')));

        return [
            ...$this->discriminate($alias),
            ...$this->identifier($alias),
            new Stmt\If_(new Expr\BinaryOp\BooleanOr(
                $this->perClass($alias, 'slow'),
                new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('false')), new Expr\Assign($entity, new Expr\MethodCall($uow, 'tryGetByIdHash', self::args($this->identifierHash($alias), self::str($alias->rootEntityName))))),
            ), [
                'stmts' => [
                    self::assign($entity, $this->createEntity($alias)),
                    self::assign(self::dim(self::var("fr$i"), $raw), new Expr\ConstFetch(new Name('false'))),
                ],
                'else' => new Stmt\Else_($created),
            ]),
        ];
    }

    private function createEntity(AliasPlan $alias): Expr
    {
        return new Expr\MethodCall(self::var('this'), 'createEntity', self::args(
            self::str($alias->alias),
            $alias->isPolymorphic() ? self::var("k{$alias->index}") : self::int($alias->firstEntity()->classIndex),
            new Expr\FuncCall($this->perClass($alias, 'gather'), self::args(self::var('row'), self::var('this'))),
            self::var('hints'),
        ));
    }

    private function instantiate(AliasPlan $alias): Expr
    {
        $i = $alias->index;

        if (!$alias->isPolymorphic()) {
            $entity = $alias->firstEntity();

            return $entity->cloneable
                ? new Expr\Clone_(self::var("proto$i"))
                : new Expr\MethodCall(self::dim(self::prop('classes'), self::int($entity->classIndex)), 'newInstance');
        }

        return new Expr\Ternary(
            new Expr\Isset_([self::dim(self::var("proto$i"), self::var("k$i"))]),
            new Expr\Clone_(self::dim(self::var("proto$i"), self::var("k$i"))),
            new Expr\MethodCall(self::dim(self::prop('classes'), self::var("k$i")), 'newInstance'),
        );
    }

    /**
     * Resolves the concrete class of the row into $k{i}.
     *
     * @return Stmt[]
     */
    private function discriminate(AliasPlan $alias): array
    {
        $discriminator = $alias->discriminator;

        if (null === $discriminator) {
            return [];
        }

        $value = self::var('v');
        $stmts = [self::assign($value, new Expr\BinaryOp\Coalesce(self::dim(self::var('row'), self::str($discriminator->column)), self::null()))];

        if ($this->isObjectMode()) {
            $stmts[] = self::assign($value, $this->converter->convert($value, $discriminator->type, self::var('this')));

            if (null !== $discriminator->enumType) {
                $stmts[] = self::assign($value, new Expr\Ternary(
                    new Expr\BinaryOp\Identical(self::null(), $value),
                    self::null(),
                    new Expr\MethodCall(self::var('this'), 'enum', self::args($value, new Expr\ClassConstFetch(new Name\FullyQualified($discriminator->enumType), 'class'))),
                ));
            }
        }

        $stmts[] = self::assign(self::var("k{$alias->index}"), new Expr\MethodCall(self::var('this'), 'discriminate', self::args(self::int($alias->index), $value)));

        return $stmts;
    }

    /**
     * Reads the key of the entity of the alias in the result set into $r{i}, null when the row has no entity for it.
     *
     * @return Stmt[]
     */
    private function readKey(AliasPlan $alias): array
    {
        $raw = self::var("r{$alias->index}");

        if (1 === \count($alias->keyColumns)) {
            return [self::assign($raw, self::dim(self::var('row'), self::str($alias->keyColumns[0])))];
        }

        // built like AbstractHydrator::gatherRowData(): the non null identifier values, each prefixed by "|"
        $stmts = [self::assign($raw, self::null())];

        foreach ($alias->keyColumns as $column) {
            $stmts[] = new Stmt\If_(new Expr\BinaryOp\NotIdentical(self::null(), new Expr\Assign(self::var('v'), self::dim(self::var('row'), self::str($column)))), ['stmts' => [
                new Stmt\Expression(new Expr\AssignOp\Concat($raw, new Expr\BinaryOp\Concat(self::str('|'), self::var('v')))),
            ]]);
        }

        return $stmts;
    }

    /**
     * Computes $id{i}: the converted identifier for a single field, or the identifier flattened like the unit of
     * work does it.
     *
     * @return Stmt[]
     */
    private function identifier(AliasPlan $alias): array
    {
        $i = $alias->index;

        if ($alias->hasSimpleIdentifier()) {
            $column = $alias->identifierColumn($alias->identifier[0]->key);

            return [
                self::assign(self::var('v'), self::dim(self::var('row'), self::str($column->column))),
                self::assign(self::var("id$i"), $this->converter->convert(self::var('v'), $column->type, self::var('this'))),
            ];
        }

        $stmts = [];
        $items = [];

        foreach ($alias->identifier as $n => $part) {
            $column = $alias->identifierColumn($part->key);
            $stmts[] = self::assign(self::var('v'), self::dim(self::var('row'), self::str($column->column)));
            $stmts[] = self::assign(self::var("idp$n"), $this->converter->convert(self::var('v'), $column->type, self::var('this')));
            // an association identifier is flattened to the string of its join column
            $items[] = new ArrayItem($part->association ? new Expr\Cast\String_(self::var("idp$n")) : self::var("idp$n"), self::str($part->field));
        }

        $stmts[] = self::assign(self::var("id$i"), new Expr\Array_($items));

        return $stmts;
    }

    private function identifierHash(AliasPlan $alias): Expr
    {
        if ($alias->hasSimpleIdentifier()) {
            return self::var("id{$alias->index}");
        }

        return new Expr\FuncCall(new Name\FullyQualified('implode'), self::args(self::str(' '), self::var("id{$alias->index}")));
    }

    /**
     * Local variable holding a closure or flag of the alias, indexed by the concrete class when polymorphic.
     */
    private function perClass(AliasPlan $alias, string $name): Expr
    {
        $var = self::var($name . $alias->index);

        return $alias->isPolymorphic() ? self::dim($var, self::var("k{$alias->index}")) : $var;
    }

    private function fillClosure(AliasPlan $alias, EntityPlan $entity): Expr
    {
        $o = self::var('o');
        $h = self::var('h');
        $id = self::var('id');
        $stmts = [];
        $items = [];
        $columnVariables = [];
        $identifierFields = [];

        foreach ($alias->identifier as $part) {
            if (!$part->association) {
                $identifierFields[$part->key] = $alias->hasSimpleIdentifier() ? $id : self::dim($id, self::str($part->key));
            }
        }

        foreach ($entity->columns as $n => $column) {
            if (isset($identifierFields[$column->key])) {
                $value = $identifierFields[$column->key];
            } else {
                $value = self::var("c$n");
                $stmts = [...$stmts, ...$this->readColumn($column, $value, $h, $entity)];
            }

            $columnVariables[$column->key] = $value;

            if (null !== $column->write) {
                $stmts = [...$stmts, ...$this->write($column->write, $entity->classIndex, $column->key, $o, $value, $h, isset($identifierFields[$column->key]) && $alias->hasSimpleIdentifier())];
            }

            $items[] = new ArrayItem($value, self::str($column->key));
        }

        foreach ($entity->associations as $n => $association) {
            $value = self::var("a$n");

            if (AssociationPlan::REFERENCE === $association->kind) {
                $stmts[] = self::assign($value, $this->reference($association, $columnVariables, $h));
                $stmts = [...$stmts, ...$this->write($association->write, $association->classIndex, $association->field, $o, $value, $h, false)];
            } else {
                $stmts[] = self::assign($value, new Expr\New_(new Name\FullyQualified(PersistentCollection::class), self::args(
                    new Expr\PropertyFetch($h, 'em'),
                    self::dim(new Expr\PropertyFetch($h, 'classes'), self::int($association->targetClassIndex)),
                    new Expr\New_(new Name\FullyQualified(ArrayCollection::class)),
                )));
                $stmts[] = new Stmt\Expression(new Expr\MethodCall($value, 'setOwner', self::args(
                    $o,
                    self::dim(new Expr\PropertyFetch($h, 'associations'), self::int($this->association($association->classIndex, $association->field))),
                )));

                if (AssociationPlan::LAZY_COLLECTION === $association->kind) {
                    $stmts[] = new Stmt\Expression(new Expr\MethodCall($value, 'setInitialized', self::args(new Expr\ConstFetch(new Name('false')))));
                }

                $stmts = [...$stmts, ...$this->write($association->write, $association->classIndex, $association->field, $o, $value, $h, true)];
            }

            $items[] = new ArrayItem($value, self::str($association->field));
        }

        $params = [
            new Param($o, type: new Name\FullyQualified($entity->className)),
            new Param(self::var('row'), type: new Identifier('array')),
            new Param($id),
            new Param($h, type: new Name\FullyQualified(GeneratedHydrator::class)),
        ];

        if (null !== $entity->backReference) {
            $params[] = new Param(self::var('parent'), type: new Identifier('object'));
            $stmts = [...$stmts, ...$this->write($entity->backReference, $entity->classIndex, (string) $entity->backReferenceField, $o, self::var('parent'), $h, true)];
            $items[] = new ArrayItem(self::var('parent'), self::str((string) $entity->backReferenceField));
        }

        $stmts[] = new Stmt\Return_(new Expr\Array_($items));

        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', self::args(
            new Expr\Closure(['static' => true, 'params' => $params, 'returnType' => new Identifier('array'), 'stmts' => $stmts]),
            self::null(),
            new Expr\ClassConstFetch(new Name\FullyQualified($entity->className), 'class'),
        ));
    }

    /**
     * @param array<string, Expr> $columnVariables
     */
    private function reference(AssociationPlan $association, array $columnVariables, Expr\Variable $h): Expr
    {
        $values = [];

        foreach ($association->foreignKeys as $targetField => $key) {
            // a foreign key missing from the result set leaves the association empty, like UnitOfWork::createEntity()
            if (!isset($columnVariables[$key])) {
                return self::null();
            }

            $values[$targetField] = $columnVariables[$key];
        }

        $isNull = null;

        foreach ($values as $value) {
            $check = new Expr\BinaryOp\Identical(self::null(), $value);
            $isNull = null === $isNull ? $check : new Expr\BinaryOp\BooleanOr($isNull, $check);
        }

        \assert(null !== $isNull);

        $reference = 1 === \count($values)
            ? new Expr\MethodCall($h, 'reference', self::args(self::int($association->targetClassIndex), reset($values)))
            : new Expr\MethodCall($h, 'referenceComposite', self::args(self::int($association->targetClassIndex), new Expr\Array_(array_map(
                static fn (string $field, Expr $value) => new ArrayItem($value, self::str($field)),
                array_keys($values),
                array_values($values),
            ))));

        return new Expr\Ternary($isNull, self::null(), $reference);
    }

    private function gatherClosure(EntityPlan $entity): Expr
    {
        $h = self::var('h');
        $stmts = [];
        $items = [];

        foreach ($entity->columns as $n => $column) {
            $value = self::var("c$n");
            $stmts = [...$stmts, ...$this->readColumn($column, $value, $h, $entity)];
            $items[] = new ArrayItem($value, self::str($column->key));
        }

        $stmts[] = new Stmt\Return_(new Expr\Array_($items));

        return new Expr\Closure([
            'static' => true,
            'params' => [new Param(self::var('row'), type: new Identifier('array')), new Param($h, type: new Name\FullyQualified(GeneratedHydrator::class))],
            'returnType' => new Identifier('array'),
            'stmts' => $stmts,
        ]);
    }

    /**
     * @return Stmt[]
     */
    private function readColumn(ColumnPlan $column, Expr\Variable $target, Expr\Variable $hydrator, EntityPlan $entity): array
    {
        $raw = self::var('v');
        $converted = $this->converter->convert($raw, $column->type, $hydrator);

        $stmts = $converted === $raw
            ? [self::assign($target, self::dim(self::var('row'), self::str($column->column)))]
            : [self::assign($raw, self::dim(self::var('row'), self::str($column->column))), self::assign($target, $converted)];

        if (null !== $column->enumType) {
            $enumClass = new Expr\ClassConstFetch(new Name\FullyQualified($column->enumType), 'class');

            $stmts[] = self::assign($target, new Expr\Ternary(
                new Expr\BinaryOp\Identical(self::null(), $target),
                self::null(),
                $this->isObjectMode()
                    ? new Expr\MethodCall($hydrator, 'enum', self::args($target, $enumClass))
                    : new Expr\MethodCall($hydrator, 'simpleEnum', self::args($target, $enumClass, self::str($entity->className), self::str($column->key))),
            ));
        }

        return $stmts;
    }

    /**
     * Writes a value like the Doctrine property accessors do.
     *
     * @return Stmt[]
     */
    private function write(PropertyWrite $write, int $classIndex, string $field, Expr\Variable $object, Expr $value, Expr\Variable $hydrator, bool $nonNull): array
    {
        if (!$write->direct) {
            return [new Stmt\Expression(new Expr\MethodCall(
                self::dim(new Expr\PropertyFetch($hydrator, 'accessors'), self::int($this->accessor($classIndex, $field))),
                'setValue',
                self::args($object, $value),
            ))];
        }

        $assign = new Stmt\Expression(new Expr\Assign(new Expr\PropertyFetch($object, $write->property), $value));

        if ($nonNull || !$write->unsetOnNull) {
            return [$assign];
        }

        // a non-nullable typed property is left uninitialized instead of receiving null
        return [new Stmt\If_(new Expr\BinaryOp\NotIdentical(self::null(), $value), [
            'stmts' => [$assign],
            'else' => $write->hasDefault ? new Stmt\Else_([new Stmt\Unset_([new Expr\PropertyFetch($object, $write->property)])]) : null,
        ])];
    }

    private function parent(AliasPlan $alias): AliasPlan
    {
        return $this->plan->aliases[$alias->parentIndex ?? 0];
    }

    /**
     * Collections of the entities of an alias for a fetch joined field, keyed by the entity key.
     */
    private function collections(AliasPlan $alias, string $field): Expr\Variable
    {
        $index = array_search($field, $alias->fetchedCollections, true);

        if (false === $index) {
            throw new \LogicException(\sprintf('No fetched collection "%s" on alias "%s".', $field, $alias->alias));
        }

        return self::var(\sprintf('col%d_%d', $alias->index, $index));
    }

    private function isObjectMode(): bool
    {
        return HydrationPlan::OBJECT === $this->plan->mode;
    }

    private function association(int $classIndex, string $field): int
    {
        $index = array_search([$classIndex, $field], $this->associations, true);

        if (false === $index) {
            $this->associations[] = [$classIndex, $field];
            $index = \count($this->associations) - 1;
        }

        return $index;
    }

    private function accessor(int $classIndex, string $field): int
    {
        $index = array_search([$classIndex, $field], $this->accessors, true);

        if (false === $index) {
            $this->accessors[] = [$classIndex, $field];
            $index = \count($this->accessors) - 1;
        }

        return $index;
    }

    /**
     * @param string|mixed[] $value
     */
    private function constant(string $name, string|array $value): Stmt\ClassConst
    {
        return new Stmt\ClassConst([new Const_($name, (new BuilderFactory())->val($value))], Modifiers::PROTECTED);
    }

    private static function prop(string $name): Expr\PropertyFetch
    {
        return new Expr\PropertyFetch(self::var('this'), $name);
    }

    private static function var(string $name): Expr\Variable
    {
        return new Expr\Variable($name);
    }

    private static function str(string $value): Scalar\String_
    {
        return new Scalar\String_($value);
    }

    private static function int(int $value): Scalar\Int_
    {
        return new Scalar\Int_($value);
    }

    private static function null(): Expr\ConstFetch
    {
        return new Expr\ConstFetch(new Name('null'));
    }

    private static function dim(Expr $var, Expr ...$keys): Expr\ArrayDimFetch
    {
        foreach ($keys as $key) {
            $var = new Expr\ArrayDimFetch($var, $key);
        }

        \assert($var instanceof Expr\ArrayDimFetch);

        return $var;
    }

    private static function assign(Expr $var, Expr $expr): Stmt\Expression
    {
        return new Stmt\Expression(new Expr\Assign($var, $expr));
    }

    /**
     * @return Arg[]
     */
    private static function args(Expr ...$exprs): array
    {
        return array_map(static fn (Expr $expr) => new Arg($expr), array_values($exprs));
    }
}
