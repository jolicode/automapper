<?php

declare(strict_types=1);

namespace AutoMapper\CustomTransformer;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Attribute\PropertyAttribute;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\ReadAccessor;
use AutoMapper\MapperMetadata\MapperType;
use PhpParser\Node\Scalar\String_;
use AutoMapper\Extractor\AstExtractor;
use PhpParser\Builder;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

final readonly class MapToAttributeCustomTransformerGenerator implements AttributeCustomTransformerGenerator
{
    public function __construct(
        private AstExtractor $astExtractor = new AstExtractor()
    )
    {
    }

    public function supports(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): bool
    {
        return $propertyAttribute instanceof MapTo
            && $propertyMapping->mapperMetadata->mapperType() === MapperType::SOURCE_TARGET;
    }

    /**
     * @return class-string<CustomTransformerInterface>
     */
    public function implementedClass(): string
    {
        return CustomPropertyTransformerInterface::class;
    }

    /**
     * ```php
     * public function supports(string $source, string $target, string $propertyName): bool
     * {
     *     return $source === [source type] && $target === [target type] && $propertyName === [target property];
     * }
     * ```
     */
    public function generateSupportsStatement(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): Stmt\ClassMethod
    {
        $supportsMethodInInterface = $this->astExtractor
            ->extractClassLike(CustomPropertyTransformerInterface::class)
            ->getMethod('supports') ?? throw new \LogicException('Cannot find "supports" method in interface ' . CustomPropertyTransformerInterface::class);

        return (new Builder\Method('supports'))
            ->makePublic()
            ->setReturnType($supportsMethodInInterface->getReturnType())
            ->addParams($supportsMethodInInterface->getParams())
            ->addStmt(
                new Stmt\Return_(
                    new Expr\BinaryOp\BooleanAnd(
                        new Expr\BinaryOp\Identical(
                            $supportsMethodInInterface->getParams()[0]->var,
                            new String_($propertyMapping->mapperMetadata->getSource())
                        ),
                        new Expr\BinaryOp\BooleanAnd(
                            new Expr\BinaryOp\Identical(
                                $supportsMethodInInterface->getParams()[1]->var,
                                new String_($propertyMapping->mapperMetadata->getTarget())
                            ),
                            new Expr\BinaryOp\Identical(
                                $supportsMethodInInterface->getParams()[2]->var,
                                new String_($propertyAttribute->propertyName)
                            ),
                        )
                    )
                )
            )
            ->getNode();
    }

    /**
     * ```php
     * public function transform(object|array $source): mixed
     * {
     *     return $source->[sourceProperty accessor];
     * }
     * ```
     */
    public function generateTransformStatement(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): Stmt\ClassMethod
    {
        $source = $propertyMapping->mapperMetadata->getSource();

        $transformMethodInInterface = $this->astExtractor
            ->extractClassLike(CustomTransformerInterface::class)
            ->getMethod('transform') ?? throw new \LogicException('Cannot find "transform" method in interface ' . CustomTransformerInterface::class);

        return (new Builder\Method('transform'))
            ->makePublic()
            ->setReturnType($transformMethodInInterface->getReturnType())
            ->setDocComment(
                <<<PHPDOC
                /**
                 * @param $source \$source
                 */
                PHPDOC
            )
            ->addParams($transformMethodInInterface->getParams())
            ->addStmt(
                new Stmt\Return_(
                    $propertyMapping->readAccessor->getExpression($transformMethodInInterface->getParams()[0]->var)
                )
            )
            ->getNode();
    }
}
