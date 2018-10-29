<?php

namespace LogicItLab\Salesforce\MapperBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class LogicItLabSalesforceMapperExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        //$loader->load('services.xml');

        $yamlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $yamlLoader->load('services.yaml');

        if (isset($config['cache_driver'])) {
            switch ($config['cache_driver']) {
                case 'file':
                    $container->setAlias('logicitlab_salesforce_mapper.cache', 'LogicItLab\Salesforce\MapperBundle\Cache\FileCache');
                    break;
                default:
                    break;
            }
        }

        $yamlLoader->load('param_converter.yml');
        $container->setParameter('logicitlab_salesforce_mapper.param_converter', $config['param_converter']);
    }
}

