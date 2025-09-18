<?php

declare(strict_types=1);

namespace AutoMapper\Provider\Doctrine;

use AutoMapper\Provider\ProviderInterface;
use Doctrine\Persistence\ObjectManager;

final readonly class DoctrineProvider implements ProviderInterface
{
    public function __construct(
        private ObjectManager $objectManager,
    ) {
    }

    /**
     * @param class-string<object> $targetType The class name of the
     */
    public function provide(string $targetType, mixed $source, array $context, /* mixed $id */): object|array|null
    {
        $id = 4 <= \func_num_args() ? func_get_arg(3) : null;

        if ($id !== null) {
            return $this->objectManager->find($targetType, $id);
        }

        return null;
    }
}
