<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

final readonly class PropertyWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public string $property,
        public bool $private = false,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        if ($this->private) {
            /*
             * Use hydrate callback to write value
             *
             * $this->hydrateCallbacks['propertyName']($output, $value);
             */
            return new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'hydrateCallbacks'), new Scalar\String_($this->property)),
                [
                    new Arg($output),
                    new Arg($value),
                ]
            );
        }

        /*
         * Create property expression to write value
         *
         * $output->propertyName &= $value;
         */
        if ($byRef) {
            return new Expr\AssignRef(new Expr\PropertyFetch($output, $this->property), $value);
        }

        return new Expr\Assign(new Expr\PropertyFetch($output, $this->property), $value);
    }

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr
    {
        return null;
    }

    /**
     * Get AST expression for binding closure when dealing with private property.
     */
    public function getHydrateCallback(string $className): ?Expr
    {
        if (!$this->private) {
            return null;
        }

        /*
         * Create hydrate callback for this mutator
         *
         * \Closure::bind(function ($object, $value) {
         *    $object->propertyName = $value;
         * }, null, $className)
         */
        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(new Expr\Closure([
                'params' => [
                    new Param(new Expr\Variable('object')),
                    new Param(new Expr\Variable('value')),
                ],
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign(new Expr\PropertyFetch(new Expr\Variable('object'), $this->property), new Expr\Variable('value'))),
                ],
            ])),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    public function isAdderRemover(): bool
    {
        return false;
    }
}
