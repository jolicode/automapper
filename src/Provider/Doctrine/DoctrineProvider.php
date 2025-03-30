<?php

namespace AutoMapper\Provider\Doctrine;

use AutoMapper\Provider\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        $metadata = $this->entityManager->getClassMetadata($targetType);
        $entity = $metadata->identifier;

        $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($targetType, 'e')
            ->where('e.' . $entity . ' = :id')
            ->setParameter('id', $source)
            ->getQuery()
            ->getOneOrNullResult();

    }
}