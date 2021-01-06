<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine\DataProvider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;

final class ORMDataProvider implements DataProviderInterface
{
    private const ROOT_ALIAS = 'o';

    public function supports(ObjectManager $objectManager): bool
    {
        return is_a($objectManager, 'Doctrine\ORM\EntityManagerInterface');
    }

    public function fetch(ObjectManager $manager, string $class, string $property, string $value, string $strategy): array
    {
        $rootAlias = static::ROOT_ALIAS;
        // Generate random param name
        $paramName = str_replace(['/', '+'], '', substr(base64_encode(random_bytes(50)), 0, 10));

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($class);
        $qb = $repository->createQueryBuilder($rootAlias);

        switch ($strategy) {
            case 'equal':
                $qb->andWhere(sprintf('%s.%s = :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value)
                ;

                break;
            case 'ends_with':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value)
                ;

                break;
            case 'starts_with':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value.'%')
                ;

                break;
            case 'contains':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value.'%')
                ;

                break;
        }

        return $qb->setMaxResults(20)->getQuery()->getResult();
    }
}
