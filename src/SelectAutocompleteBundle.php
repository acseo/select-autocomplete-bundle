<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete;

use Acseo\SelectAutocomplete\DependencyInjection\Compiler\ProviderExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SelectAutocompleteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProviderExtensionPass());
    }
}
