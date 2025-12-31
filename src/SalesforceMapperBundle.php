<?php

namespace PhpArsenal\SalesforceMapperBundle;

use PhpArsenal\SalesforceMapperBundle\DependencyInjection\SalesforceDocumentClassesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SalesforceMapperBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SalesforceDocumentClassesCompilerPass());
    }
}
