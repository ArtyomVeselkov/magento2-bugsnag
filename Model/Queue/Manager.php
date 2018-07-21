<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Queue;

use Optimlight\Bugsnag\Model\Queue\Builder\ClientInterface as BuilderInterface;
use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Optimlight\Bugsnag\Boot\{ExceptionHandler, Runner};

/**
 * Class Manager
 * @package Optimlight\Bugsnag\Model\Queue
 */
class Manager implements ManagerInterface
{
    /**
     * @var BuilderInterface
     */
    private $builder;

    /**
     * @var null|ExceptionHandler
     */
    private $handler;

    /**
     * @var ProcessorInterface
     */
    private $processor;

    /**
     * @var InterfaceVirtualCard[]
     */
    private $cards = [];

    /**
     * @var ClientInterface[]
     */
    private static $clients = [];

    /**
     * Processor constructor.
     * @param BuilderInterface $builder
     * @param ProcessorInterface $processor
     */
    public function __construct(
        BuilderInterface $builder,
        ProcessorInterface $processor
    ) {
        $this->builder = $builder;
        $this->processor = $processor;
        $this->handler = Runner::getExceptionsHandler();
    }

    /**
     * @inheritdoc
     */
    public function dequeue($cardId = [], array $options = [])
    {
        $clients = $this->getClient($cardId);
        foreach ($clients as $client) {
            $this->processor->dequeue($client, $options);
        }
    }

    /**
     * @param int[] $cardId
     * @return ClientInterface[]
     */
    public function getClient($cardId = [])
    {
        $result = [];
        $cards = $this->filterCards($cardId);
        foreach ($cards as $card) {
            if (InterfaceVirtualCard::TYPE_PHP !== $card->getType() || !$card->validate()) {
                continue;
            }
            $singleCardId = $card->getId();
            if (isset(static::$clients[$singleCardId])) {
                $result[$singleCardId] = static::$clients[$singleCardId];
            } else {
                $client = $this->builder->build($card->getConfig());
                $result[$singleCardId] = $client;
                static::$clients[$singleCardId] = $client;
            }
        }
        return $result;
    }

    /**
     * @param int[] $cardId
     * @return InterfaceVirtualCard[]
     */
    private function filterCards($cardId = [])
    {
        if (empty($cardId)) {
            return $this->getCards();
        } else {
            $result = [];
            foreach ($this->getCards() as $card) {
                if (in_array($card->getId(), $cardId)) {
                    $result[] = $card;
                }
            }
            return $result;
        }
    }

    /**
     * @param bool $reload
     * @return InterfaceVirtualCard[]
     */
    private function getCards($reload = false)
    {
        if (empty($this->cards) || $reload) {
            $this->handler->prepareCards();
            $this->cards = $this->handler->getCards();
        }
        return $this->cards;
    }
}
