<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\PropertyMapping;
use PhpParser\Node\Expr\Variable;

/**
 * @internal
 */
final class VariableRegistry
{
    private UniqueVariableScope $uniqueVariableScope;

    private Variable $sourceInput;
    private Variable $result;
    private Variable $hashVariable;
    private Variable $contextVariable;

    /** @var array<string, array<string, Variable>> */
    private array $fieldValueVariables = [];

    public function __construct()
    {
        $this->uniqueVariableScope = new UniqueVariableScope();

        $this->sourceInput = new Variable($this->uniqueVariableScope->getUniqueName('value'));
        $this->result = new Variable($this->uniqueVariableScope->getUniqueName('result'));
        $this->hashVariable = new Variable($this->uniqueVariableScope->getUniqueName('sourceHash'));
        $this->contextVariable = new Variable($this->uniqueVariableScope->getUniqueName('context'));
    }

    public function getUniqueVariableScope(): UniqueVariableScope
    {
        return $this->uniqueVariableScope;
    }

    public function getSourceInput(): Variable
    {
        return $this->sourceInput;
    }

    public function getResult(): Variable
    {
        return $this->result;
    }

    public function getHash(): Variable
    {
        return $this->hashVariable;
    }

    public function getContext(): Variable
    {
        return $this->contextVariable;
    }

    public function getFieldValueVariable(PropertyMapping $propertyMapping): Variable
    {
        return $this->fieldValueVariables[$propertyMapping->mapperMetadata->getMapperClassName()][$propertyMapping->property]
            ??= $this->getVariableWithUniqueName('fieldValue');
    }

    public function getVariableWithUniqueName(string $name): Variable
    {
        return new Variable($this->uniqueVariableScope->getUniqueName($name));
    }
}
