<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

final readonly class PropertyReadAccessor implements ReadAccessorInterface
{
    public const string EXTRACT_CALLBACK = 'extractCallbacks';
    public const string EXTRACT_IS_UNDEFINED_CALLBACK = 'extractIsUndefinedCallbacks';
    public const string EXTRACT_IS_NULL_CALLBACK = 'extractIsNullCallbacks';

    public const string EXTRACT_TARGET_CALLBACK = 'extractTargetCallbacks';
    public const string EXTRACT_TARGET_IS_UNDEFINED_CALLBACK = 'extractTargetIsUndefinedCallbacks';
    public const string EXTRACT_TARGET_IS_NULL_CALLBACK = 'extractTargetIsNullCallbacks';

    public function __construct(
        public string $property,
        public bool $private = false,
    ) {
    }

    public function getExpression(Expr $input, bool $target = false): Expr
    {
        if ($this->private) {
            /*
             * When the property is private we use the extract callback that can read this value
             *
             * @see \AutoMapper\Extractor\ReadAccessor::getExtractCallback()
             *
             * $this->extractCallbacks['property_name']($input)
             */
            return new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_CALLBACK : self::EXTRACT_CALLBACK), new Scalar\String_($this->property)),
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
        return new Expr\PropertyFetch($input, $this->property);
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        if ($this->private) {
            /*
             * When the property is private we use the extract callback that can read this value
             *
             * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsUndefinedCallback()
             *
             * !$this->extractIsUndefinedCallbacks['property_name']($input)
             */
            return new Expr\BooleanNot(new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_UNDEFINED_CALLBACK : self::EXTRACT_IS_UNDEFINED_CALLBACK), new Scalar\String_($this->property)),
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
            return new Expr\Isset_([new Expr\PropertyFetch($input, $this->property)]);
        }

        // return property_exists($input, $this->accessor);
        return new Expr\FuncCall(new Name('property_exists'), [new Arg($input), new Arg(new Scalar\String_($this->property))]);
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        if ($this->private) {
            /*
             * When the property is private we use the extract callback that can read this value
             *
             * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsNullCallback()
             *
             * $this->extractIsNullCallbacks['property_name']($input)
             */
            return new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_NULL_CALLBACK : self::EXTRACT_IS_NULL_CALLBACK), new Scalar\String_($this->property)),
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
        return new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch($input, $this->property)])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\PropertyFetch($input, $this->property)));
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        if ($this->private) {
            /*
             * When the property is private we use the extract callback that can read this value
             *
             * @see \AutoMapper\Extractor\ReadAccessor::getExtractIsUndefinedCallback()
             *
             * $this->extractIsUndefinedCallbacks['property_name']($input)
             */
            return new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_IS_UNDEFINED_CALLBACK : self::EXTRACT_IS_UNDEFINED_CALLBACK), new Scalar\String_($this->property)),
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
        return new Expr\BooleanNot(new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->property)), new Arg(new Expr\Cast\Array_($input))]));
    }

    public function getExtractCallback(string $className): ?Expr
    {
        if (!$this->private) {
            return null;
        }

        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\PropertyFetch(new Expr\Variable('object'), $this->property)),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    public function getExtractIsNullCallback(string $className): ?Expr
    {
        if (!$this->private) {
            return null;
        }

        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch(new Expr\Variable('object'), $this->property)])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\PropertyFetch(new Expr\Variable('object'), $this->property)))),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        if (!$this->private) {
            return null;
        }

        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\BooleanNot(new Expr\Isset_([new Expr\PropertyFetch(new Expr\Variable('object'), $this->property)]))),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }
}
