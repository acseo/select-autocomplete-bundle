<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

final class ORMDataProvider extends AbstractDoctrineDataProvider
{
    public const REGISTRY = 'doctrine';

    public function findByTerms(string $class, string $property, string $value, string $strategy): array
    {
        $rootAlias = 'o';
        $paramName = 'q';

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getRepository($class);
        $qb = $repository->createQueryBuilder($rootAlias);

        switch ($strategy) {
            case 'equals':
                $qb->where(sprintf('%s.%s = :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value)
                ;

                break;
            case 'ends_with':
                $qb->where(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value)
                ;

                break;
            case 'starts_with':
                $qb->where(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, $value.'%')
                ;

                break;
            case 'contains':
                $qb->where(sprintf('%s.%s LIKE :%s', $rootAlias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value.'%')
                ;

                break;
            default:
                throw new \RuntimeException(sprintf('Strategy "%s" is not supported', $strategy));
        }

        return $qb->setMaxResults(self::SEARCH_LIMIT_RESULTS)->getQuery()->getResult();
    }
}
