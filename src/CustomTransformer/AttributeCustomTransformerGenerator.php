<?php

declare(strict_types=1);

namespace AutoMapper\CustomTransformer;

use AutoMapper\Attribute\PropertyAttribute;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\ReadAccessor;
use PhpParser\Node\Stmt;

interface AttributeCustomTransformerGenerator
{
    public function supports(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): bool;

    /**
     * @return class-string<CustomTransformerInterface>
     */
    public function implementedClass(): string;

    public function generateSupportsStatement(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): Stmt\ClassMethod;

    public function generateTransformStatement(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): Stmt\ClassMethod;
}
