<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\SMD\Loader;

use FivePercent\Bundle\ApiBundle\SMD\Action\ServiceAction;
use FivePercent\Component\Api\SMD\Loader\LoaderInterface;
use FivePercent\Component\Api\SMD\Action\ActionCollection;

/**
 * Get actions from services
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ServiceMethodLoader implements LoaderInterface
{
    /**
     * @var array
     */
    private $actions;

    /**
     * Add service method
     *
     * @param string $name
     * @param string $serviceId
     * @param string $methodName
     */
    public function addServiceMethod($name, $serviceId, $methodName)
    {
        $this->actions[$name] = [
            'service' => $serviceId,
            'method' => $methodName
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function loadActions()
    {
        $actions = new ActionCollection();

        foreach ($this->actions as $actionName => $actionInfo) {
            $action = new ServiceAction(
                $actionName,
                $actionInfo['service'],
                $actionInfo['method']
            );

            $actions->addAction($action);
        }

        return $actions;
    }
}
