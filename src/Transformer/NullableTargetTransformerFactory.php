<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class NullableTargetTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface, ChainTransformerFactoryAwareInterface
{
    use ChainTransformerFactoryAwareTrait;

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (null === $target->type) {
            return null;
        }

        if (!$target->type instanceof Type\NullableType) {
            return null;
        }

        $newTarget = $target->withType($target->type->getWrappedType());

        return $this->chainTransformerFactory->getTransformer($source, $newTarget, $mapperMetadata);
    }

    public function getPriority(): int
    {
        return 128;
    }
}
