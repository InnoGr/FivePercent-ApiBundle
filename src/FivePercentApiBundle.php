<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle;

use FivePercent\Bundle\ApiBundle\Command\ActionDebugCommand;
use FivePercent\Bundle\ApiBundle\DependencyInjection\ApiExtension;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddCallableResolverPass;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddDocFormatterPass;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddErrorFactoryPass;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddHandlerExtractorPass;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddHandlerPass;
use FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler\AddServiceActionPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Api bundle
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class FivePercentApiBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $addHandlerPass = new AddHandlerPass();

        $container->addCompilerPass(new AddCallableResolverPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass($addHandlerPass, PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new AddServiceActionPass($addHandlerPass), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new AddErrorFactoryPass($addHandlerPass), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new AddDocFormatterPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new AddHandlerExtractorPass(), PassConfig::TYPE_OPTIMIZE);
    }

    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new ApiExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $application)
    {
        $application->addCommands([
            new ActionDebugCommand()
        ]);
    }
}
