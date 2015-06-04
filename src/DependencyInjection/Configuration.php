<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Console\ConsoleEvents;

/**
 * FivePercent Core configuration
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fivepercent_api');

        $node = $rootNode->children();

        $this->configHandlersSection($node);

        return $treeBuilder;
    }

    /**
     * Build handlers section
     *
     * @param NodeBuilder $node
     */
    public function configHandlersSection(NodeBuilder $node)
    {
        $node
            ->arrayNode('handlers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('server')
                            ->isRequired()
                            ->info('The server for run this handler (Service ID or server type. Not allowed only json-rpc).')
                        ->end()

                        ->scalarNode('path')
                            ->isRequired()
                            ->info('The path for run this handler.')
                            ->example('/foo')
                        ->end()

                        ->scalarNode('host')
                            ->defaultValue(null)
                            ->info('The host for run this handler.')
                            ->example('foo.domain.com')
                        ->end()

                        ->scalarNode('cache')
                            ->defaultValue(null)
                            ->info('Service ID for use caching system')
                            ->example('cache')
                        ->end()

                        ->scalarNode('parameter_resolver')
                            ->defaultValue('api.parameter.method_parameter_resolver_and_extractor')
                            ->info('The parameter resolver for resolve input parameters.')
                        ->end()

                        ->scalarNode('parameter_extractor')
                            ->defaultValue('api.parameter.method_parameter_resolver_and_extractor')
                            ->info('The parameter extractor for extract parameter for generate doc.')
                        ->end()

                        ->scalarNode('response_extractor')
                            ->defaultValue('api.response_extractor.object_extractor')
                            ->info('The response extractor for extract response for generate doc.')
                        ->end()

                        ->scalarNode('callable_loader_factory')
                            ->defaultValue(null)
                            ->info('The factory service id for populate closure loader.')
                        ->end()

                        ->booleanNode('enable_service_annotated_loader')
                            ->defaultValue(true)
                            ->info('Enable service annotated loader for load actions from container (With "api.action" tag).')
                        ->end()

                        ->arrayNode('subscribers')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('response_transformable')
                                    ->defaultValue(false)
                                    ->info('Use response transformable subscriber for transform and normalize responses.')
                                ->end()

                                ->booleanNode('object_security_authorization')
                                    ->defaultValue(false)
                                    ->info('Use object security authorization checker subscriber.')
                                ->end()

                                ->booleanNode('enabled_checker')
                                    ->defaultValue(false)
                                    ->info('Use enabled checker for check input arguments of enabled.')
                                ->end()

                                ->booleanNode('logger')
                                    ->defaultValue('%kernel.debug%')
                                    ->info('Log API actions')
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('methods')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('error_list')
                                    ->defaultValue(true)
                                    ->info('Use "error.list" method in handler')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
