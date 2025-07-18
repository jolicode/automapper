<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\CompileException;
use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\MapperContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Symfony\Component\PropertyInfo\Type;

/**
 * Read accessor tell how to read from a property.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class ReadAccessor
{
    use GetTypeTrait;

    public const TYPE_METHOD = 1;
    public const TYPE_PROPERTY = 2;
    public const TYPE_ARRAY_DIMENSION = 3;
    public const TYPE_SOURCE = 4;
    public const TYPE_ARRAY_ACCESS = 5;

    public const EXTRACT_IS_UNDEFINED_CALLBACK = 'extractIsUndefinedCallbacks';
    public const EXTRACT_IS_NULL_CALLBACK = 'extractIsNullCallbacks';
    public const EXTRACT_CALLBACK = 'extractCallbacks';
    public const EXTRACT_TARGET_IS_UNDEFINED_CALLBACK = 'extractTargetIsUndefinedCallbacks';
    public const EXTRACT_TARGET_IS_NULL_CALLBACK = 'extractTargetIsNullCallbacks';
    public const EXTRACT_TARGET_CALLBACK = 'extractTargetCallbacks';

    /**
     * @param array<string, string> $context
     */
    public function __construct(
        public readonly int $type,
        public readonly string $accessor,
        public readonly ?string $sourceClass = null,
        public readonly bool $private = false,
        public readonly ?string $property = null,
        // will be the name of the property if different from accessor
        public readonly array $context = [],
    ) {
        if (self::TYPE_METHOD === $this->type && null === $this->sourceClass) {
            throw new InvalidArgumentException('Source class must be provided when using "method" type.');
        }
    }

    /**
     * Get AST expression for reading property from an input.
     *
     * @throws CompileException
     */
    public function getExpression(Expr $input, bool $target = false): Expr
    {
        if (self::TYPE_METHOD === $this->type) {
            $methodCallArguments = [];

            foreach ($this->context as $parameter => $context) {
                /*
                 * Create method call argument to read value from context and throw exception if not found
                 *
                 * $context['map_to_accessor_parameter']['some_key'] ?? throw new InvalidArgumentException('error message');
                 */
                $methodCallArguments[] = new Arg(
                    new Expr\BinaryOp\Coalesce(
                        new Expr\ArrayDimFetch(
                            new Expr\ArrayDimFetch(
                                new Expr\Variable('context'),
                                new Scalar\String_(MapperContext::MAP_TO_ACCESSOR_PARAMETER)
                            ),
                            new Scalar\String_($context)
                        ),
                        new Expr\Throw_(
                            new Expr\New_(
                                new Name\FullyQualified(InvalidArgumentException::class),
                                [
                                    new Arg(
                                        new Scalar\String_(
                                            "Parameter \"\${$parameter}\" of method \"{$this->sourceClass}\"::\"{$this->accessor}()\" is configured to be mapped to context but no value was found in the context."
                                        )
                                    ),
                                ]
                            )
                        )
                    )
                );
            }

            if ($this->private) {
                /*
                 * When the method is private we use the extract callback that can read this value
                 *
                 * @see \AutoMapper\Extractor\ReadAccessor::getExtractCallback()
                 *
                 * $this->extractCallbacks['method_name']($input)
                 */
                return new Expr\FuncCall(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_CALLBACK : self::EXTRACT_CALLBACK), new Scalar\String_($this->property ?? $this->accessor)),
                    [
                        new Arg($input),
                    ]
                );
            }

            /*
             * Use the method call to read the value
             *
             * $input->method_name(...$args)
             */
            return new Expr\MethodCall($input, $this->accessor, $methodCallArguments);
        }

        if (self::TYPE_PROPERTY === $this->type) {
            if ($this->private) {
                /*
                 * When the property is private we use the extract callback that can read this value
                 *
                 * @see \AutoMapper\Extractor\ReadAccessor::getExtractCallback()
                 *
                 * $this->extractCallbacks['property_name']($input)
                 */
                return new Expr\FuncCall(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_CALLBACK : self::EXTRACT_CALLBACK), new Scalar\String_($this->accessor)),
                    [
                        new Arg($input),
                    ]
                );
            }

            /*
             * Use the property fetch to read the value
             *
             * $input->property_name
             */
            return new Expr\PropertyFetch($input, $this->accessor);
        }

        if (self::TYPE_ARRAY_DIMENSION === $this->type || self::TYPE_ARRAY_ACCESS === $this->type) {
            /*
             * Use the array dim fetch to read the value
             *
             * $input['property_name']
             */
            return new Expr\ArrayDimFetch($input, new Scalar\String_($this->accessor));
        }

        if (self::TYPE_SOURCE === $this->type) {
            return $input;
        }

        throw new CompileException('Invalid accessor for read expression');
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        // It is not possible to check if the underlying data is defined, assumes it is, php will throw an error if it is not
        if (!$nullable && \in_array($this->type, [self::TYPE_METHOD, self::TYPE_SOURCE])) {
            return null;
        }

        if (self::TYPE_PROPERTY === $this->type) {
            if ($this->private) {
                /*
                 * When the property is private we use the extract callback that can read this value
                 *
                 * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsUndefinedCallback()
                 *
                 * !$this->extractIsUndefinedCallbacks['property_name']($input)
                 */
                return new Expr\BooleanNot(new Expr\FuncCall(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_UNDEFINED_CALLBACK : self::EXTRACT_IS_UNDEFINED_CALLBACK), new Scalar\String_($this->accessor)),
                    [
                        new Arg($input),
                    ]
                ));
            }

            /*
             * Use the property fetch to read the value
             *
             * return isset($input->property_name);
             */
            if (!$nullable) {
                return new Expr\Isset_([new Expr\PropertyFetch($input, $this->accessor)]);
            }

            // return property_exists($input, $this->accessor);
            return new Expr\FuncCall(new Name('property_exists'), [new Arg($input), new Arg(new Scalar\String_($this->accessor))]);
        }

        if (self::TYPE_ARRAY_DIMENSION === $this->type) {
            /*
             * Use the array dim fetch to read the value
             *
             * isset($input['property_name'])
             */
            if (!$nullable) {
                return new Expr\Isset_([new Expr\ArrayDimFetch($input, new Scalar\String_($this->accessor))]);
            }

            return new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->accessor)), new Arg($input)]);
        }

        if (self::TYPE_ARRAY_ACCESS === $this->type) {
            return new Expr\MethodCall($input, 'offsetExists', [new Arg(new Scalar\String_($this->accessor))]);
        }

        return null;
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        if (self::TYPE_METHOD === $this->type) {
            $methodCallExpr = $this->getExpression($input);

            /*
             * null !== $methodCallExpr
             */
            return new Expr\BinaryOp\Identical(
                new Expr\ConstFetch(new Name('null')),
                $methodCallExpr,
            );
        }

        if (self::TYPE_PROPERTY === $this->type) {
            if ($this->private) {
                /*
                 * When the property is private we use the extract callback that can read this value
                 *
                 * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsNullCallback()
                 *
                 * $this->extractIsNullCallbacks['property_name']($input)
                 */
                return new Expr\FuncCall(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_NULL_CALLBACK : self::EXTRACT_IS_NULL_CALLBACK), new Scalar\String_($this->accessor)),
                    [
                        new Arg($input),
                    ]
                );
            }

            /*
             * Use the property fetch to read the value
             *
             * isset($input->property_name) && null === $input->property_name
             */
            return new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch($input, $this->accessor)])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\PropertyFetch($input, $this->accessor)));
        }

        if (self::TYPE_ARRAY_DIMENSION === $this->type || self::TYPE_ARRAY_ACCESS === $this->type) {
            /*
             * Use the array dim fetch to read the value
             *
             * isset($input['property_name']) && null === $input->property_name
             */
            return new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\ArrayDimFetch($input, new Scalar\String_($this->accessor))])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\ArrayDimFetch($input, new Scalar\String_($this->accessor))));
        }

        if (self::TYPE_SOURCE === $this->type) {
            return new Expr\BinaryOp\Identical(
                new Expr\ConstFetch(new Name('null')),
                $input,
            );
        }

        throw new CompileException('Invalid accessor for read expression');
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        if (\in_array($this->type, [self::TYPE_METHOD, self::TYPE_SOURCE])) {
            /*
             * false
             */
            return new Expr\ConstFetch(new Name('false'));
        }

        if (self::TYPE_PROPERTY === $this->type) {
            if ($this->private) {
                /*
                 * When the property is private we use the extract callback that can read this value
                 *
                 * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsUndefinedCallback()
                 *
                 * $this->extractIsUndefinedCallbacks['property_name']($input)
                 */
                return new Expr\FuncCall(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_UNDEFINED_CALLBACK : self::EXTRACT_IS_UNDEFINED_CALLBACK), new Scalar\String_($this->accessor)),
                    [
                        new Arg($input),
                    ]
                );
            }

            /*
             * Use the property fetch to read the value
             *
             * !array_key_exists($property_name, (object) $input)
             */
            return new Expr\BooleanNot(new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->accessor)), new Arg(new Expr\Cast\Array_($input))]));
        }

        if (self::TYPE_ARRAY_DIMENSION === $this->type) {
            /*
             * Use the array dim fetch to read the value
             *
             * !array_key_exists('property_name', $input)
             */
            return new Expr\BooleanNot(new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->accessor)), new Arg($input)]));
        }

        if (self::TYPE_ARRAY_ACCESS === $this->type) {
            return new Expr\BooleanNot(new Expr\MethodCall($input, 'offsetExists', [new Arg(new Scalar\String_($this->accessor))]));
        }

        throw new CompileException('Invalid accessor for read expression');
    }

    /**
     * Get AST expression for binding closure when dealing with a private property.
     */
    public function getExtractCallback(string $className): ?Expr
    {
        if (!\in_array($this->type, [self::TYPE_PROPERTY, self::TYPE_METHOD]) || !$this->private) {
            return null;
        }

        /*
         * Create extract callback for this accessor
         *
         *  \Closure::bind(function ($object) {
         *      return $object->property_name;
         *  }, null, $className)
         *
         *  \Closure::bind(function ($object) {
         *      return $object->method_name();
         *  }, null, $className)
         */
        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(
                            $this->type === self::TYPE_PROPERTY
                                ? new Expr\PropertyFetch(new Expr\Variable('object'), $this->accessor)
                                : new Expr\MethodCall(new Expr\Variable('object'), $this->accessor)
                        ),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    /**
     * Get AST expression for binding closure when dealing with a private property.
     */
    public function getExtractIsNullCallback(string $className): ?Expr
    {
        if ($this->type !== self::TYPE_PROPERTY || !$this->private) {
            return null;
        }

        /*
         * Create extract is null callback for this accessor
         *
         *  \Closure::bind(function ($object) {
         *      return !isset($object->property_name) && null === $object->property_name;
         *  }, null, $className)
         */
        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch(new Expr\Variable('object'), $this->accessor)])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\PropertyFetch(new Expr\Variable('object'), $this->accessor)))),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    /**
     * Get AST expression for binding closure when dealing with a private property.
     */
    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        if ($this->type !== self::TYPE_PROPERTY || !$this->private) {
            return null;
        }

        /*
         * Create extract is null callback for this accessor
         *
         *  \Closure::bind(function ($object) {
         *      return !isset($object->property_name);
         *  }, null, $className)
         */
        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch(new Expr\Variable('object'), $this->accessor)]))),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    /**
     * @return Type[]|null
     */
    public function getTypes(string $class): ?array
    {
        if (self::TYPE_METHOD === $this->type && (class_exists($class) || interface_exists($class))) {
            try {
                $reflectionMethod = new \ReflectionMethod($class, $this->accessor);

                if ($types = $this->extractFromDocBlock(
                    $reflectionMethod->getDocComment(),
                    $class,
                    $reflectionMethod->getDeclaringClass()->getName(),
                    $this->accessor,
                    '@return'
                )) {
                    return $types;
                }

                $reflectionReturnType = $reflectionMethod->getReturnType();

                if ($reflectionReturnType === null) {
                    return null;
                }

                return $this->extractFromReflectionType($reflectionReturnType, $reflectionMethod->getDeclaringClass());
            } catch (\ReflectionException $e) {
                return null;
            }
        }

        if (self::TYPE_PROPERTY === $this->type && (class_exists($class) || interface_exists($class))) {
            try {
                $reflectionProperty = new \ReflectionProperty($class, $this->accessor);

                if ($reflectionProperty->isPromoted()) {
                    if ($types = $this->extractFromDocBlock(
                        $reflectionProperty->getDeclaringClass()->getConstructor()?->getDocComment(),
                        $class,
                        $reflectionProperty->getDeclaringClass()->getName(),
                        $this->accessor,
                        '@param'
                    )) {
                        return $types;
                    }
                }

                if ($types = $this->extractFromDocBlock(
                    $reflectionProperty->getDocComment(),
                    $class,
                    $reflectionProperty->getDeclaringClass()->getName(),
                    $this->accessor,
                    '@var'
                )) {
                    return $types;
                }

                $reflectionType = $reflectionProperty->getType();

                if ($reflectionType === null) {
                    return null;
                }

                return $this->extractFromReflectionType($reflectionType, $reflectionProperty->getDeclaringClass());
            } catch (\ReflectionException $e) {
                return null;
            }
        }

        return null;
    }
}
