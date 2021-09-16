<?php

namespace PhpArsenal\SalesforceMapperBundle\DependencyInjection;

use PhpArsenal\SalesforceMapperBundle\Builder\SalesforceDocumentClassTreeBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SalesforceMapperExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $yamlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $yamlLoader->load('services.yaml');

        if (isset($config['cache_driver'])) {
            switch ($config['cache_driver']) {
                case 'file':
                    $container->setAlias('salesforce_mapper.cache',
                        'PhpArsenal\SalesforceMapperBundle\Cache\FileCache');
                    break;
                default:
                    break;
            }
        }

        $yamlLoader->load('param_converter.yml');
        $container->setParameter('salesforce_mapper.param_converter', $config['param_converter']);
    }
}

