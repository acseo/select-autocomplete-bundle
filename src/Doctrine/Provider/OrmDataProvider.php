<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine\Provider;

use Doctrine\Persistence\ObjectManager;

final class OrmDataProvider implements DataProviderInterface
{
    protected const ROOT_ALIAS = 'o';
    protected const PARAM_NAME = 'search';

    public function supports(ObjectManager $objectManager): bool
    {
        return is_a($objectManager, 'Doctrine\ORM\EntityManagerInterface');
    }

    public function getCollection(ObjectManager $manager, string $class, string $property, string $value, string $strategy): array
    {
        $rootAlias = static::ROOT_ALIAS;
        $paramName = static::PARAM_NAME;

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $manager->getRepository($class);
        $qb = $repository->createQueryBuilder($rootAlias);

        switch ($strategy) {
            case 'equal':
                $qb->andWhere(sprintf('%s.%s = :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value)
                ;

                break;
            case 'contains':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value.'%')
                ;

                break;
            case 'ends_with':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value)
                ;

                break;
            default:
            case 'starts_with':
                $qb->andWhere(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value.'%')
                ;

                break;
        }

        return $qb->getQuery()->getResult();
    }
}
