<?php

namespace PhpArsenal\SalesforceMapperBundle\DependencyInjection;

use PhpArsenal\SalesforceMapperBundle\Builder\SalesforceDocumentClassTreeBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SalesforceDocumentClassesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var SalesforceDocumentClassTreeBuilder $sfDocumentClassTreeBuilder */
        $sfDocumentClassTreeBuilder = $container->get(SalesforceDocumentClassTreeBuilder::class);
        $container->setParameter('salesforce_mapper.document_classes', $sfDocumentClassTreeBuilder->build());
    }
}
