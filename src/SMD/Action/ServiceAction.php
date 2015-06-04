<?php

/**
 * This file is part of the Api package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\SMD\Action;

use FivePercent\Component\Api\SMD\Action\AbstractAction;

/**
 * Service action. Service must be exists in Symfony service container
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ServiceAction extends AbstractAction
{
    /**
     * @var string
     */
    protected $serviceId;

    /**
     * @var string
     */
    protected $method;

    /**
     * Construct
     *
     * @param string $name
     * @param string $serviceId
     * @param string $method
     * @param array  $validationGroups
     * @param array  $securityGroups
     * @param string $requestMappingGroup
     * @param bool   $useStrictValidation
     * @param bool   $checkEnabled
     * @param mixed  $response
     */
    public function __construct(
        $name,
        $serviceId,
        $method,
        array $validationGroups = [ 'Default' ],
        array $securityGroups = [ 'Default' ],
        $requestMappingGroup = 'Default',
        $useStrictValidation = true,
        $checkEnabled = true,
        $response = null
    ) {
        $this->name = $name;
        $this->serviceId = $serviceId;
        $this->method = $method;
        $this->validationGroups = $validationGroups;
        $this->securityGroups = $securityGroups;
        $this->requestMappingGroup = $requestMappingGroup;
        $this->strictValidation = $useStrictValidation;
        $this->checkEnabled = $checkEnabled;
        $this->response = $response;
    }

    /**
     * Get service id
     *
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Get method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}
