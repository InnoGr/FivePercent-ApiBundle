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

use Doctrine\Common\Annotations\Reader;
use FivePercent\Bundle\ApiBundle\SMD\Action\ServiceAction;
use FivePercent\Component\Api\SMD\Action\ObjectResponse;
use FivePercent\Component\Api\SMD\Loader\LoaderInterface;
use FivePercent\Component\Reflection\Reflection;
use FivePercent\Component\Api\Annotation\Action as ActionAnnotation;
use FivePercent\Component\Api\SMD\Action\ActionCollection;

/**
 * Read data from annotations
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ServiceAnnotationLoader implements LoaderInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $classes = array();

    /**
     * Construct
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Add service to loader.
     *
     * @param string $serviceId
     * @param string $class
     *
     * @return ServiceAnnotationLoader
     */
    public function addService($serviceId, $class)
    {
        $this->classes[$serviceId] = $class;

        return $this;
    }

    /**
     * {@inheritDoc}
     */


    /**
     * {@inheritDoc}
     */
    public function loadActions()
    {
        $actions = new ActionCollection();

        foreach ($this->classes as $id => $class) {
            $reflection = Reflection::loadClassReflection($class);

            // Get all methods from class
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                $methodAnnotations = $this->reader->getMethodAnnotations($method);

                foreach ($methodAnnotations as $annotation) {
                    if ($annotation instanceof ActionAnnotation) {
                        if ($method->isStatic()) {
                            throw new \RuntimeException('The static method not supported (@todo).');
                        }

                        if ($annotation->response) {
                            $response = new ObjectResponse($annotation->response->class);
                        } else {
                            $response = null;
                        }

                        $action = new ServiceAction(
                            $annotation->name,
                            $id,
                            $method->getName(),
                            $annotation->validationGroups,
                            $annotation->securityGroups,
                            $annotation->requestMappingGroup,
                            $annotation->useStrictValidation,
                            $annotation->checkEnabled,
                            $response
                        );

                        $actions->addAction($action);
                    }
                }
            }
        }

        return $actions;
    }
}
