<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\Doctrine;

use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;

/**
 * This class be born because Doctrine doesn't provide any registry which can
 * give you the correct default manager for a specific entity/document class.
 */
final class ManagerRegistry
{
    /**
     * @var AbstractManagerRegistry[]
     */
    private $registries = [];

    public function __construct(ContainerInterface $container, array $registries = [])
    {
        foreach ($registries as $serviceId) {
            if ($container->has($serviceId)) {
                $service = $container->get($serviceId);

                if (!$service instanceof AbstractManagerRegistry) {
                    throw new \RuntimeException(sprintf('Service "%s" is not a valid Doctrine registry.', $serviceId));
                }

                $this->registries[] = $service;
            }
        }
    }

    /**
     * Invoke all registries to get correct manager for specific class.
     */
    public function getManagerForClass(string $class): ObjectManager
    {
        foreach ($this->registries as $registry) {
            $manager = $registry->getManagerForClass($class);
            if (null !== $manager) {
                return $manager;
            }
        }

        throw new \RuntimeException(sprintf('Object manager for class "%s" not found', $class));
    }
}
