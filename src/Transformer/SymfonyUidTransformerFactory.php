<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class SymfonyUidTransformerFactory extends AbstractUniqueTypeTransformerFactory implements PrioritizedTransformerFactoryInterface
{
    /** @var array<string, array{0: bool, 1: bool}> */
    private array $reflectionCache = [];

    protected function createTransformer(Type $sourceType, Type $targetType, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $isSourceUid = $this->isUid($sourceType);
        $isTargetUid = $this->isUid($targetType);

        if ($isSourceUid && $isTargetUid) {
            return new SymfonyUidCopyTransformer();
        }

        if ($isSourceUid) {
            return new SymfonyUidToStringTransformer($this->reflectionCache[$sourceType->getClassName()][1]);
        }

        if ($isTargetUid) {
            return new StringToSymfonyUidTransformer($targetType->getClassName());
        }

        return null;
    }

    private function isUid(Type $type): bool
    {
        if (Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
            return false;
        }

        /** @var class-string|null $typeClassName */
        $typeClassName = $type->getClassName();

        if (null === $typeClassName || !class_exists($typeClassName)) {
            return false;
        }

        if (!\array_key_exists($typeClassName, $this->reflectionCache)) {
            $reflClass = new \ReflectionClass($typeClassName);
            $this->reflectionCache[$typeClassName] = [$reflClass->isSubclassOf(AbstractUid::class), $typeClassName === Ulid::class];
        }

        return $this->reflectionCache[$typeClassName][0];
    }

    public function getPriority(): int
    {
        return 24;
    }
}
