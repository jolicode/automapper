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

    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        $metadata = $this->entityManager->getClassMetadata($targetType);
        // @TODO support multiple identifiers
        $identifier = $metadata->identifier;

        $result = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($targetType, 'e')
            ->where('e.' . $identifier[0] . ' = :id')
            ->setParameter('id', $source[$identifier[0]])
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
