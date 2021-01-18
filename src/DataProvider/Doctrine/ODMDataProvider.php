<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

class ODMDataProvider extends AbstractDoctrineDataProvider
{
    protected const REGISTRY = 'doctrine_mongodb';

    public function findByTerms(string $class, array $properties, string $value, string $strategy): array
    {
        return $this
            ->createSearchAggregationBuilder($class, $properties, $value, $strategy)
            ->execute()
            ->toArray()
        ;
    }

    public function createSearchAggregationBuilder(string $class, array $properties, string $value, string $strategy)
    {
        /** @var \Doctrine\ODM\MongoDB\Repository\DocumentRepository $repository */
        $repository = $this->getRepository($class);
        /** @var \Doctrine\ODM\MongoDB\Aggregation\Builder qb */
        $aggregationBuilder = $repository->createAggregationBuilder()->hydrate($class);

        $fields = [];
        foreach ($properties as $property) {
            $fields[] = $this->addLookup($aggregationBuilder, $class, $property);
        }

        $match = $aggregationBuilder->match();
        foreach ($fields as $field) {
            $match->addOr(
                $this->applyFilter($aggregationBuilder, $field, $value, $strategy)
            );
        }

        $aggregationBuilder->limit(static::SEARCH_LIMIT_RESULTS);

        return $aggregationBuilder;
    }

    /**
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $aggregationBuilder
     */
    protected function addLookup($aggregationBuilder, string $class, string $propertyPath): string
    {
        $properties = explode('.', $propertyPath);
        $alias = '';

        foreach ($properties as $key => $prop) {
            if (\count($properties) - 1 === $key) {
                $alias = '' !== $alias ? "${alias}${prop}" : $prop;

                break;
            }

            /** @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $classMetadata */
            $classMetadata = $this->getManager($class)->getClassMetadata($class);
            /** @var string $targetClass */
            $targetClass = $classMetadata->getAssociationTargetClass($prop);

            if ($classMetadata->hasReference($prop)) {
                $isOwningSide = $classMetadata->associationMappings[$prop]['isOwningSide'];
                $propertyAlias = $prop.'_lkp';
                $localField = $alias.$prop;
                $alias .= $propertyAlias;

                if (!$this->isLookupExist($aggregationBuilder, $alias)) {
                    $aggregationBuilder
                        ->lookup($targetClass)
                        ->localField($isOwningSide ? $localField : '_id')
                        ->foreignField($isOwningSide ? '_id' : $classMetadata->fieldMappings[$prop]['mappedBy'])
                        ->alias($alias)
                    ;

                    $aggregationBuilder->unwind("\$${alias}");
                }

                $class = $targetClass;
                $alias .= '.';
            } elseif ($classMetadata->hasEmbed($prop)) {
                $alias = $prop.'.';
            }
        }

        return $alias;
    }

    /**
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $aggregationBuilder
     */
    protected function applyFilter($aggregationBuilder, string $property, string $value, string $strategy)
    {
        $mongoRegexClass = class_exists('\MongoDB\BSON\Regex') ? '\MongoDB\BSON\Regex' : '\MongoRegex';

        switch ($strategy) {
            case 'ends_with':
                return $aggregationBuilder->matchExpr()->field($property)->equals(new $mongoRegexClass(sprintf('%s$', $value), 'i'));
            case 'starts_with':
                return $aggregationBuilder->matchExpr()->field($property)->equals(new $mongoRegexClass(sprintf('^%s', $value), 'i'));
            case 'contains':
                return $aggregationBuilder->matchExpr()->field($property)->equals(new $mongoRegexClass($value, 'i'));
            case 'equals':
                return $aggregationBuilder->matchExpr()->field($property)->equals($value);
            default:
                throw new \InvalidArgumentException(sprintf('Strategy "%s" is not supported', $strategy));
        }
    }

    /**
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $aggregationBuilder
     */
    protected function isLookupExist($aggregationBuilder, string $alias): bool
    {
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\Exception $e) {
            $pipeline = [];
        }

        foreach ($pipeline as $stage) {
            $stageLookupAlias = $stage['$lookup']['as'] ?? null;

            if ($stageLookupAlias === $alias) {
                return true;
            }
        }

        return false;
    }
}
