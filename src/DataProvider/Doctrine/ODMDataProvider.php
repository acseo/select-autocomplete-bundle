<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DataProvider\Doctrine;

final class ODMDataProvider extends AbstractDoctrineDataProvider
{
    public const REGISTRY = 'doctrine_mongodb';

    public function findByTerms(string $class, string $property, string $value, string $strategy): array
    {
        /** @var \Doctrine\ODM\MongoDB\Repository\DocumentRepository $repository */
        $repository = $this->getRepository($class);
        $qb = $repository->createQueryBuilder();
        $mongoRegexClass = class_exists('\MongoDB\BSON\Regex') ? '\MongoDB\BSON\Regex' : '\MongoRegex';

        switch ($strategy) {
            case 'ends_with':
                $qb->field($property)->equals(new $mongoRegexClass(sprintf('%s$', $value), 'i'));

                break;
            case 'starts_with':
                $qb->field($property)->equals(new $mongoRegexClass(sprintf('^%s', $value), 'i'));

                break;
            case 'contains':
                $qb->field($property)->equals(new $mongoRegexClass(sprintf('%s', $value), 'i'));

                break;
            case 'equals':
                $qb->field($property)->equals($value);

                break;
            default:
                throw new \RuntimeException(sprintf('Strategy "%s" is not supported', $strategy));
        }

        /** @var \Doctrine\ODM\MongoDB\Iterator\Iterator $results */
        $results = $qb->limit(self::SEARCH_LIMIT_RESULTS)->getQuery()->execute();

        return $results->toArray();
    }
}
