<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\MapperContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

final readonly class MethodReadAccessor implements ReadAccessorInterface
{
    public const string EXTRACT_CALLBACK = 'extractCallbacks';
    public const string EXTRACT_TARGET_CALLBACK = 'extractTargetCallbacks';

    /**
     * @param array<string, string> $context
     */
    public function __construct(
        public string $property,
        public string $method,
        public string $sourceClass,
        public bool $private = false,
        public array $context = [],
    ) {
    }

    public function getExpression(Expr $input, bool $target = false): Expr
    {
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
                                        "Parameter \"\${$parameter}\" of method \"{$this->sourceClass}\"::\"{$this->method}()\" is configured to be mapped to context but no value was found in the context."
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
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? self::EXTRACT_TARGET_CALLBACK : self::EXTRACT_CALLBACK), new Scalar\String_($this->property ?? $this->method)),
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
        return new Expr\MethodCall($input, $this->method, $methodCallArguments);
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        return null;
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        $methodCallExpr = $this->getExpression($input);

        /*
         * null !== $methodCallExpr
         */
        return new Expr\BinaryOp\Identical(
            new Expr\ConstFetch(new Name('null')),
            $methodCallExpr,
        );
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        return new Expr\ConstFetch(new Name('false'));
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
                        new Stmt\Return_(new Expr\MethodCall(new Expr\Variable('object'), $this->method)),
                    ],
                ])
            ),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Scalar\String_($className)),
        ]);
    }

    public function getExtractIsNullCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        return null;
    }
}
