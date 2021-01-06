<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DependencyInjection\Compiler;

use Acseo\SelectAutocomplete\Doctrine\DataProviderRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataProviderExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(DataProviderRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('acseo_select_autocomplete.doctrine.data_provider');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
