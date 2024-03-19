<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Util\PhpStanTypeHelper;

/**
 * @internal
 */
trait GetTypeTrait
{
    /**
     * @return Type[]
     */
    private function extractFromDocBlock(string|false $rawDocNode, string $class, string $declaringClass, string $property, string $tagName): ?array
    {
        if (false === $rawDocNode) {
            return null;
        }

        if (!class_exists(PhpDocParser::class)) {
            return null;
        }

        if (!class_exists(ContextFactory::class)) {
            return null;
        }

        static $phpDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        static $lexer = new Lexer();
        static $nameScopeFactory = new NameScopeFactory();
        static $phpStanTypeHelper = new PhpStanTypeHelper();

        $tokens = new TokenIterator($lexer->tokenize($rawDocNode));
        $docNode = $phpDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);
        $nameScope = $nameScopeFactory->create($class, $declaringClass);

        $types = [];

        foreach ($docNode->getTagsByName($tagName) as $tagDocNode) {
            if ($tagDocNode->value instanceof InvalidTagValueNode) {
                continue;
            }

            if (
                $tagDocNode->value instanceof ParamTagValueNode
                && $tagName !== '@param'
                && $tagDocNode->value->parameterName !== '$' . $property
            ) {
                continue;
            }

            foreach ($phpStanTypeHelper->getTypes($tagDocNode->value, $nameScope) as $type) {
                switch ($type->getClassName()) {
                    case 'self':
                    case 'static':
                        $resolvedClass = $class;
                        break;

                    case 'parent':
                        if (false !== $resolvedClass = $parentClass ??= get_parent_class($class)) {
                            break;
                        }

                        // no break
                    default:
                        $types[] = $type;
                        continue 2;
                }

                $types[] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->isNullable(), $resolvedClass, $type->isCollection(), $type->getCollectionKeyTypes(), $type->getCollectionValueTypes());
            }
        }

        if (!isset($types[0])) {
            return null;
        }

        return $types;
    }

    /**
     * @param \ReflectionClass<object> $declaringClass
     *
     * @return Type[]
     */
    private function extractFromReflectionType(\ReflectionType $reflectionType, \ReflectionClass $declaringClass): array
    {
        $types = [];
        $nullable = $reflectionType->allowsNull();

        foreach (($reflectionType instanceof \ReflectionUnionType || $reflectionType instanceof \ReflectionIntersectionType) ? $reflectionType->getTypes() : [$reflectionType] as $type) {
            if (!$type instanceof \ReflectionNamedType) {
                // Nested composite types are not supported yet.
                return [];
            }

            $phpTypeOrClass = $type->getName();
            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass) {
                continue;
            }

            if (Type::BUILTIN_TYPE_ARRAY === $phpTypeOrClass) {
                $types[] = new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true);
            } elseif ('void' === $phpTypeOrClass) {
                $types[] = new Type(Type::BUILTIN_TYPE_NULL, $nullable);
            } elseif ($type->isBuiltin()) {
                $types[] = new Type($phpTypeOrClass, $nullable);
            } else {
                $types[] = new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $this->resolveTypeName($phpTypeOrClass, $declaringClass));
            }
        }

        return $types;
    }

    /**
     * @param \ReflectionClass<object> $declaringClass
     */
    private function resolveTypeName(string $name, \ReflectionClass $declaringClass): string
    {
        if ('self' === $lcName = strtolower($name)) {
            return $declaringClass->name;
        }
        if ('parent' === $lcName && $parent = $declaringClass->getParentClass()) {
            return $parent->name;
        }

        return $name;
    }
}
