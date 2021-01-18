<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

final class ODMDataProvider extends AbstractDoctrineDataProvider
{
    public const REGISTRY = 'doctrine_mongodb';

    public function findByTerms(string $class, array $properties, string $value, string $strategy): array
    {
        /** @var \Doctrine\ODM\MongoDB\Repository\DocumentRepository $repository */
        $repository = $this->getRepository($class);
        /** @var \Doctrine\ODM\MongoDB\Aggregation\Builder qb */
        $qb = $repository->createAggregationBuilder()->hydrate($class);

        $fields = [];
        foreach ($properties as $property) {
            $fields[] = $this->addLookup($qb, $class, $property);
        }

        $match = $qb->match();
        foreach ($fields as $field) {
            $match->addOr(
                $this->applyFilter($qb, $field, $value, $strategy)
            );
        }

        return $qb->limit(self::SEARCH_LIMIT_RESULTS)->execute()->toArray();
    }

    /**
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $qb
     */
    private function addLookup($qb, string $class, string $propertyPath): string
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

                if (!$this->isLookupExist($qb, $alias)) {
                    $qb->lookup($targetClass)
                        ->localField($isOwningSide ? $localField : '_id')
                        ->foreignField($isOwningSide ? '_id' : $classMetadata->fieldMappings[$prop]['mappedBy'])
                        ->alias($alias)
                    ;

                    $qb->unwind("\$${alias}");
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
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $qb
     */
    private function applyFilter($qb, string $property, string $value, string $strategy)
    {
        $mongoRegexClass = class_exists('\MongoDB\BSON\Regex') ? '\MongoDB\BSON\Regex' : '\MongoRegex';

        switch ($strategy) {
            case 'ends_with':
                return $qb->matchExpr()->field($property)->equals(new $mongoRegexClass(sprintf('%s$', $value), 'i'));
            case 'starts_with':
                return $qb->matchExpr()->field($property)->equals(new $mongoRegexClass(sprintf('^%s', $value), 'i'));
            case 'contains':
                return $qb->matchExpr()->field($property)->equals(new $mongoRegexClass($value, 'i'));
            case 'equals':
                return $qb->matchExpr()->field($property)->equals($value);
            default:
                throw new \InvalidArgumentException(sprintf('Strategy "%s" is not supported', $strategy));
        }
    }

    /**
     * @param \Doctrine\ODM\MongoDB\Aggregation\Builder $qb
     */
    private function isLookupExist($qb, string $alias): bool
    {
        try {
            $pipeline = $qb->getPipeline();
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
