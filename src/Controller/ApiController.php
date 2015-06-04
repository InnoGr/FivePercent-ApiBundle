<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Controller;

use FivePercent\Component\Api\Server\ServerRegistryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Api controller for handle API methods
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ApiController
{
    /**
     * @var ServerRegistryInterface
     */
    private $serverRegistry;

    /**
     * Construct
     *
     * @param ServerRegistryInterface $serverRegistry
     */
    public function __construct(ServerRegistryInterface $serverRegistry)
    {
        $this->serverRegistry = $serverRegistry;
    }

    /**
     * Handle API method
     *
     * @param Request $request
     * @param string  $serverKey
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $serverKey)
    {
        return $this->serverRegistry->getServer($serverKey)
            ->handle($request);
    }
}
