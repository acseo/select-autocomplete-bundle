<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

class ORMDataProvider extends AbstractDoctrineDataProvider
{
    protected const REGISTRY = 'doctrine';
    protected const DEFAULT_QUERY_ALIAS = 'o';

    public function findByTerms(string $class, array $properties, string $value, string $strategy): array
    {
        return $this
            ->createSearchQueryBuilder(static::DEFAULT_QUERY_ALIAS, $class, $properties, $value, $strategy)
            ->getQuery()
            ->getResult()
        ;
    }

    public function createSearchQueryBuilder(string $rootAlias, string $class, array $properties, string $value, string $strategy)
    {
        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getRepository($class);
        $qb = $repository->createQueryBuilder($rootAlias)->distinct(true);

        foreach ($properties as $property) {
            $path = explode('.', $property);
            $currentAlias = $rootAlias;

            foreach ($path as $i => $prop) {
                if (\count($path) - 1 === $i) {
                    $this->applyFilter($qb, $currentAlias, $prop, 'param_'.$i, $value, $strategy);
                } else {
                    $newAlias = sprintf('%s_%s', $currentAlias, $prop);
                    if (!\in_array($newAlias, $qb->getAllAliases(), true)) {
                        $qb->leftJoin(sprintf('%s.%s', $currentAlias, $prop), $newAlias);
                    }
                    $currentAlias = $newAlias;
                }
            }
        }

        return $qb->setMaxResults(static::SEARCH_LIMIT_RESULTS);
    }

    protected function applyFilter($qb, string $alias, string $property, string $paramName, string $value, string $strategy): void
    {
        switch ($strategy) {
            case 'equals':
                $qb->orWhere(sprintf('%s.%s = :%s', $alias, $property, $paramName))
                    ->setParameter($paramName, $value)
                ;

                break;
            case 'ends_with':
                $qb->orWhere(sprintf('%s.%s LIKE :%s', $alias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value)
                ;

                break;
            case 'starts_with':
                $qb->orWhere(sprintf('%s.%s LIKE :%s', $alias, $property, $paramName))
                    ->setParameter($paramName, $value.'%')
                ;

                break;
            case 'contains':
                $qb->orWhere(sprintf('%s.%s LIKE :%s', $alias, $property, $paramName))
                    ->setParameter($paramName, '%'.$value.'%')
                ;

                break;
            default:
                throw new \InvalidArgumentException(sprintf('Strategy "%s" is not supported', $strategy));
        }
    }
}
