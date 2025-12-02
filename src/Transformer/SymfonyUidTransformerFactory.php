<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final class SymfonyUidTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    /** @var array<string, array{0: bool, 1: bool}> */
    private array $reflectionCache = [];

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $sourceUid = $this->getUid($source->type);
        $targetUid = $this->getUid($target->type);

        if ($sourceUid[0] && $targetUid[0]) {
            return new SymfonyUidCopyTransformer();
        }

        if ($sourceUid[0]) {
            return new SymfonyUidToStringTransformer($sourceUid[1]);
        }

        if ($targetUid[0]) {
            return new StringToSymfonyUidTransformer($targetUid[2]);
        }

        return null;
    }

    /**
     * @return array{false, false, null}|array{true, bool, class-string}
     */
    private function getUid(?Type $type): array
    {
        if (!$type instanceof Type\ObjectType) {
            return [false, false, null];
        }

        /** @var class-string|null $typeClassName */
        $typeClassName = $type->getClassName();

        if (null === $typeClassName || !class_exists($typeClassName)) {
            return [false, false, null];
        }

        if (!\array_key_exists($typeClassName, $this->reflectionCache)) {
            $reflClass = new \ReflectionClass($typeClassName);
            $this->reflectionCache[$typeClassName] = [$reflClass->isSubclassOf(AbstractUid::class), $typeClassName === Ulid::class];
        }

        if (!$this->reflectionCache[$typeClassName][0]) {
            return [false, false, null];
        }

        return [...$this->reflectionCache[$typeClassName], $typeClassName];
    }

    public function getPriority(): int
    {
        return 24;
    }
}
