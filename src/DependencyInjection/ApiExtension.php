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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * FivePercent API extension
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 *
 * @todo: add classes to compiles (For optimize)
 */
class ApiExtension extends Extension
{
    const ALIAS = 'fivepercent_api';

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        // Compile all handlers
        foreach ($config['handlers'] as $handlerKey => $handlerInfo) {
            // Register handler
            $this->registerHandler($handlerKey, $handlerInfo, $container);

            // Register doc generator
            $this->registerDocGenerator($handlerKey, $container);

            // Register server for handler
            $this->registerServer($handlerKey, $handlerInfo['server'], $container);

            // Add info to routing loader
            $container->getDefinition('api.routing_loader')->addMethodCall('addPath', [
                $handlerKey,
                $handlerInfo['host'],
                $handlerInfo['path']
            ]);
        }
    }

    /**
     * Register doc generator for handler
     *
     * @param string           $handlerKey
     * @param ContainerBuilder $container
     */
    private function registerDocGenerator($handlerKey, ContainerBuilder $container)
    {
        $generatorId = 'api.handler.' . $handlerKey . '.doc_generator';

        $generatorDefinition = new DefinitionDecorator('api.doc_generator');
        $generatorDefinition->replaceArgument(0, new Reference('api.handler.' . $handlerKey . '.extractor'));

        $container->setDefinition($generatorId, $generatorDefinition);
    }

    /**
     * Register server instance for handler.
     * Now allowed only JSON-RPC server.
     *
     * @param string           $handlerKey
     * @param string|array     $serverInfo
     * @param ContainerBuilder $container
     *
     * @return string The server service id
     */
    private function registerServer($handlerKey, $serverInfo, $container)
    {
        if (!in_array($serverInfo, ['json-rpc'])) {
            throw new \RuntimeException('Now available only "JSON-RPC" server(s).');
        }

        $serverType = $serverInfo;

        $serverId = 'api.server.' . $handlerKey;

        $definitions = [
            'json-rpc' => 'api.server.json_rpc_abstract'
        ];

        $serverDefinition = new DefinitionDecorator($definitions[$serverType]);

        $serverDefinition->replaceArgument(0, new Reference('api.handler.' . $handlerKey));
        $serverDefinition->replaceArgument(1, new Reference('api.handler.' . $handlerKey . '.doc_generator'));

        $container->setDefinition($serverId, $serverDefinition);

        $container->getDefinition('api.server_registry')->addMethodCall('addServer', [
            $handlerKey,
            $serverId
        ]);

        return $serverId;
    }

    /**
     * Register handler instance
     *
     * @param string           $handlerKey
     * @param array            $handlerInfo
     * @param ContainerBuilder $container
     *
     * @return string The handler service id
     */
    private function registerHandler($handlerKey, array $handlerInfo, ContainerBuilder $container)
    {
        $handlerId = 'api.handler.' . $handlerKey;
        $errorsId = $handlerId . '.error';
        $chainActionLoaderId = $handlerId . '.action_loader.chain';
        $actionLoaderId = $handlerId . '.action_loader';
        $actionManagerId = $handlerId . '.action_manager';

        $handlerDefinition = new DefinitionDecorator('api.handler_abstract');
        $handlerDefinition->setPublic(true);

        // Create action loader and manager
        $chainActionLoaderDefinition = new DefinitionDecorator('api.action_loader_abstract');
        $container->setDefinition($chainActionLoaderId, $chainActionLoaderDefinition);

        if ($handlerInfo['cache']) {
            $cachedActionLoaderDefinition = new DefinitionDecorator('api.action_loader_cached_abstract');
            $cachedActionLoaderDefinition->replaceArgument(0, new Reference($chainActionLoaderId));
            $cachedActionLoaderDefinition->replaceArgument(1, new Reference($handlerInfo['cache']));
            $cachedActionLoaderDefinition->replaceArgument(2, $handlerId . ':actions');
            $container->setDefinition($actionLoaderId, $cachedActionLoaderDefinition);
        } else {
            $actionLoaderId = $chainActionLoaderId;
        }

        $actionManagerDefinition = new DefinitionDecorator('api.action_manager_abstract');
        $actionManagerDefinition->replaceArgument(0, new Reference($actionLoaderId));
        $container->setDefinition($actionManagerId, $actionManagerDefinition);

        $callableActionLoaderId = $handlerId . '.action_loader.callable';
        $callableActionLoaderDefinition = new DefinitionDecorator('api.action_loader_callable_abstract');
        $container->setDefinition($callableActionLoaderId, $callableActionLoaderDefinition);
        $chainActionLoaderDefinition->addMethodCall('addLoader', [new Reference($callableActionLoaderId)]);

        // Add actions loaders to chain action loader
        if ($handlerInfo['enable_service_annotated_loader']) {
            $serviceAnnotatedActionLoaderId = $handlerId . '.action_loader.service_annotated';
            $serviceAnnotatedActionLoaderDefinition = new DefinitionDecorator('api.action_loader_service_annotated_abstract');
            $container->setDefinition($serviceAnnotatedActionLoaderId, $serviceAnnotatedActionLoaderDefinition);
            $chainActionLoaderDefinition->addMethodCall('addLoader', [new Reference($serviceAnnotatedActionLoaderId)]);
        }

        if ($handlerInfo['callable_loader_factory']) {
            if (strpos($handlerInfo['callable_loader_factory'], '::') !== false) {
                list ($serviceId, $method) = explode('::', $handlerInfo['callable_loader_factory'], 2);
            } else {
                $serviceId = $handlerInfo['callable_loader_factory'];
                $method = 'configure';
            }

            $callableActionLoaderDefinition->setConfigurator([
                new Reference($serviceId),
                $method
            ]);
        }

        // Create error system
        $errorDefinition = new DefinitionDecorator('api.error_abstract');
        $container->setDefinition($errorsId, $errorDefinition);

        // Create extractor
        $actionExtractorId = $handlerId . '.action_extractor';
        $extractorId = $handlerId . '.extractor';
        $actionExtractorDefinition = new DefinitionDecorator('api.handler.action_extractor_abstract');
        if ($handlerInfo['parameter_extractor']) {
            $actionExtractorDefinition->replaceArgument(1, new Reference($handlerInfo['parameter_extractor']));
        } else {
            $actionExtractorDefinition->replaceArgument(1, null);
        }
        if ($handlerInfo['response_extractor']) {
            $actionExtractorDefinition->replaceArgument(2, new Reference($handlerInfo['response_extractor']));
        } else {
            $actionExtractorDefinition->replaceArgument(2, null);
        }
        $extractorDefinition = new DefinitionDecorator('api.handler.extractor_abstract');
        $extractorDefinition->replaceArgument(0, new Reference($actionExtractorId));
        $container->setDefinition($actionExtractorId, $actionExtractorDefinition);

        if ($handlerInfo['cache']) {
            // Use cache system for extractor (Replace services for use cached)
            $extractorId .= '_delegate';
            $extractorDefinition->setPublic(false);
            $container->setDefinition($extractorId, $extractorDefinition);

            $cachedExtractorId = $handlerId . '.extractor';
            $cachedExtractorDefinition = new DefinitionDecorator('api.handler.cached_extractor_abstract');
            $cachedExtractorDefinition->replaceArgument(0, new Reference($extractorId));
            $cachedExtractorDefinition->replaceArgument(1, new Reference($handlerInfo['cache']));
            $cachedExtractorDefinition->replaceArgument(2, $handlerKey);
            $cachedExtractorDefinition->addTag('api.handler.extractor', [
                'handler' => $handlerKey
            ]);
            $container->setDefinition($cachedExtractorId, $cachedExtractorDefinition);
        } else {
            $container->setDefinition($extractorId, $extractorDefinition);
            $extractorDefinition->addTag('api.handler.extractor', [
                'handler' => $handlerKey
            ]);
        }

        // Create event dispatcher
        $eventDispatcherId = $handlerId . '.event_dispatcher';
        $primaryEventDispatcherId = $handlerId . '.event_dispatcher_primary';
        $eventDispatcherDefinition = new DefinitionDecorator('api.event_dispatcher_chain_abstract');
        $primaryEventDispatcher = new Definition('Symfony\Component\EventDispatcher\EventDispatcher');
        $primaryEventDispatcher->setPublic(false);
        $eventDispatcherDefinition->addMethodCall('addEventDispatcher', [
            new Reference('event_dispatcher')
        ]);
        $eventDispatcherDefinition->addMethodCall('addEventDispatcher', [
            new Reference($primaryEventDispatcherId),
            true
        ]);
        $container->setDefinition($primaryEventDispatcherId, $primaryEventDispatcher);
        $container->setDefinition($eventDispatcherId, $eventDispatcherDefinition);

        // Inject handler to container
        $container->setDefinition($handlerId, $handlerDefinition);

        // Replace handler arguments
        $handlerDefinition->replaceArgument(0, new Reference($actionManagerId));
        $handlerDefinition->replaceArgument(2, new Reference($handlerInfo['parameter_resolver']));
        $handlerDefinition->replaceArgument(3, new Reference($eventDispatcherId));
        $handlerDefinition->replaceArgument(4, new Reference($errorsId));

        // Add common tags
        $handlerDefinition->addTag('monolog.logger', [
            'channel' => $this->getLoggerChannelForHandler($handlerKey)
        ]);

        $handlerDefinition->addTag('api.handler', [
            'key' => $handlerKey,
            'service_loader' => !empty($serviceAnnotatedActionLoaderId) ? $serviceAnnotatedActionLoaderId : null,
            'event_dispatcher' => $eventDispatcherId,
            'errors' => $errorsId
        ]);

        $subscribers = $handlerInfo['subscribers'];

        // Register common systems for handlers
        if ($subscribers['response_transformable']) {
            $this->registerTransformableSubscriber($handlerKey, $container);
        }

        if ($subscribers['object_security_authorization']) {
            $this->registerObjectSecurityAuthorizationSubscriber($handlerKey, $container);
        }

        if ($subscribers['enabled_checker']) {
            $this->registerEnabledCheckerSubscriber($handlerKey, $container);
        }

        if ($subscribers['logger']) {
            $this->registerLoggerSubscriber($handlerKey, $container);
        }

        // Register common methods
        $methods = $handlerInfo['methods'];
        $methods = array_filter($methods, function ($value) {
            return $value;
        });

        if (count($methods)) {
            $commonServiceActionLoaderId = 'api.handler.' . $handlerId . '.extra_method_service_loader';
            $commonServiceActionLoaderDefinition = new DefinitionDecorator('api.action_loader.service_method');
            $container->setDefinition($commonServiceActionLoaderId, $commonServiceActionLoaderDefinition);
            $chainActionLoaderDefinition->addMethodCall('addLoader', [
                new Reference($commonServiceActionLoaderId)
            ]);

            if ($methods['error_list']) {
                $errorListServiceId = 'api.handler.' . $handlerId . '.extra_service_method.error_list';
                $errorListMethodDefinition = new Definition('FivePercent\Component\Api\Api\ErrorList');
                $errorListMethodDefinition->setArguments([new Reference('api.handler_registry'), $handlerKey]);
                $container->setDefinition($errorListServiceId, $errorListMethodDefinition);

                $commonServiceActionLoaderDefinition->addMethodCall('addServiceMethod', [
                    is_string($methods['error_list']) ? $methods['error_list'] : 'error.list',
                    $errorListServiceId,
                    'getErrors'
                ]);
            }
        }

        return $handlerId;
    }

    /**
     * Register transformable subscriber for handler
     *
     * @param string           $handlerKey
     * @param ContainerBuilder $container
     */
    private function registerTransformableSubscriber($handlerKey, ContainerBuilder $container)
    {
        if (!interface_exists('FivePercent\Component\ModelTransformer\ModelTransformerManagerInterface')) {
            throw new \RuntimeException(
                'Can not use response transformable subscriber, because the package ' .
                '"fivepercent/model-transformer" not installed.'
            );
        }

        if (!interface_exists('FivePercent\Component\ModelNormalizer\ModelNormalizerManagerInterface')) {
            throw new \RuntimeException(sprintf(
                'Can not use response transformable subscriber, because the package ' .
                '"fivepercent/model-normalizer" not installed.'
            ));
        }

        $eventDispatcherId = 'api.handler.' . $handlerKey . '.event_dispatcher';
        $subscriberId = 'api.handler.' . $handlerKey . '.subscriber.transformable_subscriber';
        $subscriberDefinition = new DefinitionDecorator('api.subscriber.response_transformable_abstract');

        $container->setDefinition($subscriberId, $subscriberDefinition);

        $container->getDefinition($eventDispatcherId)->addMethodCall('addSubscriber', [
            new Reference($subscriberId)
        ]);
    }

    /**
     * Register object security authorization checker subscriber
     *
     * @param string           $handlerKey
     * @param ContainerBuilder $container
     */
    private function registerObjectSecurityAuthorizationSubscriber($handlerKey, ContainerBuilder $container)
    {
        if (!interface_exists('FivePercent\Component\ObjectSecurity\ObjectSecurityAuthorizationCheckerInterface')) {
            throw new \RuntimeException(
                'Can not use object security authorization subscriber, because the package ' .
                '"fivepercent/object-security" not installed.'
            );
        }

        $eventDispatcherId = 'api.handler.' . $handlerKey . '.event_dispatcher';
        $subscriberId = 'api.handler' . $handlerKey . '.subscriber.object_security_authorization_subscriber';
        $subscriberDefinition = new DefinitionDecorator('api.subscriber.object_security_authorization_abstract');

        $container->setDefinition($subscriberId, $subscriberDefinition);

        $container->getDefinition($eventDispatcherId)->addMethodCall('addSubscriber', [
            new Reference($subscriberId)
        ]);
    }

    /**
     * Register enabled checker subscriber
     *
     * @param string           $handlerKey
     * @param ContainerBuilder $container
     */
    private function registerEnabledCheckerSubscriber($handlerKey, ContainerBuilder $container)
    {
        if (!interface_exists('FivePercent\Component\EnabledChecker\EnabledCheckerInterface')) {
            throw new \RuntimeException(
                'Can not use enabled checker subscriber, because the package ' .
                '"fivepercent/enabled-checker" not installed.'
            );
        }

        $eventDispatcherId = 'api.handler.' . $handlerKey . '.event_dispatcher';
        $subscriberId = 'api.handler.' . $handlerKey . '.subscriber.enabled_checker';
        $subscriberDefinition = new DefinitionDecorator('api.subscriber.enabled_checker_abstract');

        $container->setDefinition($subscriberId, $subscriberDefinition);

        $container->getDefinition($eventDispatcherId)->addMethodCall('addSubscriber', [
            new Reference($subscriberId)
        ]);
    }

    /**
     * Register logger subscriber
     *
     * @param string $handlerKey
     * @param ContainerBuilder $container
     */
    private function registerLoggerSubscriber($handlerKey, ContainerBuilder $container)
    {
        $eventDispatcherId = 'api.handler.' . $handlerKey . '.event_dispatcher';
        $subscriberId = 'api.handler.' . $handlerKey . '.subscriber.logger';
        $subscriberDefinition = new DefinitionDecorator('api.subscriber.logger_abstract');
        $subscriberDefinition->addTag('monolog.logger', [
            'channel' => $this->getLoggerChannelForHandler($handlerKey)
        ]);

        $container->setDefinition($subscriberId, $subscriberDefinition);

        $container->getDefinition($eventDispatcherId)->addMethodCall('addSubscriber', [
            new Reference($subscriberId)
        ]);
    }

    /**
     * Get logger channel for handler
     *
     * @param string $handlerKey
     *
     * @return string
     */
    private function getLoggerChannelForHandler($handlerKey)
    {
        return 'API.' . $handlerKey;
    }
}
