<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\MapToContext;
use AutoMapper\Exception\CompileException;
use AutoMapper\MapperContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Read accessor tell how to read from a property.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class ReadAccessor
{
    public const TYPE_METHOD = 1;
    public const TYPE_PROPERTY = 2;
    public const TYPE_ARRAY_DIMENSION = 3;
    public const TYPE_SOURCE = 4;

    public function __construct(
        private readonly int $type,
        private readonly string $accessor,
        private readonly ?string $sourceClass = null,
        private readonly bool $private = false,
        private readonly ?string $name = null, // will be the name of the property if different from accessor
    ) {
        if (self::TYPE_METHOD === $this->type && null === $this->sourceClass) {
            throw new \InvalidArgumentException('Source class must be provided when using "method" type.');
        }
    }

    /**
     * Get AST expression for reading property from an input.
     *
     * @throws CompileException
     */
    public function getExpression(Expr\Variable $input): Expr
    {
        if (self::TYPE_METHOD === $this->type) {
            $methodCallArguments = [];

            if (\PHP_VERSION_ID >= 80000 && class_exists($this->sourceClass)) {
                $parameters = (new \ReflectionMethod($this->sourceClass, $this->accessor))->getParameters();

                foreach ($parameters as $parameter) {
                    if ($attribute = ($parameter->getAttributes(MapToContext::class)[0] ?? null)) {
                        /*
                         * Create method call argument to read value from context and throw exception if not found
                         *
                         * $context['map_to_accessor_parameter']['some_key'] ?? throw new \InvalidArgumentException('error message');
                         */
                        $methodCallArguments[] = new Arg(
                            new Expr\BinaryOp\Coalesce(
                                new Expr\ArrayDimFetch(
                                    new Expr\ArrayDimFetch(
                                        new Expr\Variable('context'),
                                        new Scalar\String_(MapperContext::MAP_TO_ACCESSOR_PARAMETER)
                                    ),
                                    new Scalar\String_($attribute->newInstance()->contextName)
                                ),
                                new Expr\Throw_(
                                    new Expr\New_(
                                        new Name\FullyQualified(\InvalidArgumentException::class),
                                        [
                                            new Arg(
                                                new Scalar\String_(
                                                    "Parameter \"\${$parameter->getName()}\" of method \"{$this->sourceClass}\"::\"{$this->accessor}()\" is configured to be mapped to context but no value was found in the context."
                                                )
                                            ),
                                        ]
                                    )
                                )
                            )
                        );
                    } elseif (!$parameter->isDefaultValueAvailable()) {
                        throw new \InvalidArgumentException("Accessors method \"{$this->sourceClass}\"::\"{$this->accessor}()\" parameters must have either a default value or the #[MapToContext] attribute.");
                    }
                }
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
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($this->name ?? $this->accessor)),
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
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($this->accessor)),
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

        if (self::TYPE_ARRAY_DIMENSION === $this->type) {
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
}
