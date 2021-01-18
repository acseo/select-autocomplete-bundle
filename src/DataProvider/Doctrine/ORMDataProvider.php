<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

final class ORMDataProvider extends AbstractDoctrineDataProvider
{
    public const REGISTRY = 'doctrine';

    public function findByTerms(string $class, array $properties, string $value, string $strategy): array
    {
        $rootAlias = 'o';
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

        return $qb->setMaxResults(self::SEARCH_LIMIT_RESULTS)->getQuery()->getResult();
    }

    private function applyFilter($qb, string $alias, string $property, string $paramName, string $value, string $strategy): void
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
