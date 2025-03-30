<?php

declare(strict_types=1);

namespace AutoMapper\Provider\Doctrine;

use AutoMapper\Provider\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param class-string<object> $targetType The class name of the
     */
    public function provide(string $targetType, mixed $source, array $context, /* mixed $id */): object|array|null
    {
        $repository = $this->entityManager->getRepository($targetType);
        $id = 4 <= \func_num_args() ? func_get_arg(3) : null;

        if ($id !== null) {
            return $repository->find($id);
        }

        return null;
    }
}
