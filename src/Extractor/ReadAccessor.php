<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\CompileException;
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

    /**
     * @param array<string, string> $context
     */
    public function __construct(
        private readonly int $type,
        private readonly string $accessor,
        private readonly ?string $sourceClass = null,
        private readonly bool $private = false,
        private readonly ?string $name = null,
        // will be the name of the property if different from accessor
        private readonly array $context = [],
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

            foreach ($this->context as $parameter => $context) {
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
                            new Scalar\String_($context)
                        ),
                        new Expr\Throw_(
                            new Expr\New_(
                                new Name\FullyQualified(\InvalidArgumentException::class),
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

    public function getIsNullExpression(Expr\Variable $input): Expr
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
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractIsNullCallbacks'), new Scalar\String_($this->accessor)),
                    [
                        new Arg($input),
                    ]
                );
            }

            /*
             * Use the property fetch to read the value
             *
             * isset($input->property_name)
             */
            return new Expr\Isset_([new Expr\PropertyFetch($input, $this->accessor)]);
        }

        if (self::TYPE_ARRAY_DIMENSION === $this->type) {
            /*
             * Use the array dim fetch to read the value
             *
             * isset($input['property_name'])
             */
            return new Expr\Isset_([new Expr\ArrayDimFetch($input, new Scalar\String_($this->accessor))]);
        }

        if (self::TYPE_SOURCE === $this->type) {
            return new Expr\BinaryOp\Identical(
                new Expr\ConstFetch(new Name('null')),
                $input,
            );
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
         *      return isset($object->property_name);
         *  }, null, $className)
         */
        return new Expr\StaticCall(new Name\FullyQualified(\Closure::class), 'bind', [
            new Arg(
                new Expr\Closure([
                    'params' => [
                        new Param(new Expr\Variable('object')),
                    ],
                    'stmts' => [
                        new Stmt\Return_(new Expr\Isset_([new Expr\PropertyFetch(new Expr\Variable('object'), $this->accessor)])),
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
        if (self::TYPE_METHOD === $this->type && class_exists($class)) {
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

        if (self::TYPE_PROPERTY === $this->type && class_exists($class)) {
            try {
                $reflectionProperty = new \ReflectionProperty($class, $this->accessor);

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
