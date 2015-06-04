<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Chain event dispatcher
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ChainEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface[]
     */
    private $eventDispatchers = [];

    /**
     * @var EventDispatcherInterface
     */
    private $primaryEventDispatcher;

    /**
     * Add event dispatcher
     *
     * @param EventDispatcherInterface $dispatcher
     * @param bool                     $primary
     *
     * @return ChainEventDispatcher
     */
    public function addEventDispatcher(EventDispatcherInterface $dispatcher, $primary = false)
    {
        $this->eventDispatchers[spl_object_hash($dispatcher)] = $dispatcher;

        if ($primary) {
            $this->primaryEventDispatcher = $dispatcher;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (!$event) {
            $event = new Event();
        }

        foreach ($this->eventDispatchers as $dispatcher) {
            $dispatcher->dispatch($eventName, $event);

            if ($event->isPropagationStopped()) {
                return $event;
            }
        }

        return $event;
    }

    /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        if (!$this->primaryEventDispatcher) {
            throw new \RuntimeException('Undefined primary event dispatcher.');
        }

        $this->primaryEventDispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        if (!$this->primaryEventDispatcher) {
            throw new \RuntimeException('Undefined primary event dispatcher');
        }

        $this->primaryEventDispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function removeListener($eventName, $listener)
    {
        foreach ($this->eventDispatchers as $dispatcher) {
            $dispatcher->removeListener($eventName, $listener);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($this->eventDispatchers as $dispatcher) {
            $dispatcher->removeSubscriber($subscriber);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners($eventName = null)
    {
        // @todo: collect listeners?
        throw new \RuntimeException('Unsupported operation');
    }

    /**
     * {@inheritDoc}
     */
    public function hasListeners($eventName = null)
    {
        foreach ($this->eventDispatchers as $dispatcher) {
            if ($dispatcher->hasListeners($eventName)) {
                return true;
            }
        }

        return false;
    }
}
