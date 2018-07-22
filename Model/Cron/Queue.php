<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Cron;

use Optimlight\Bugsnag\Model\Queue\ManagerInterface;

/**
 * Class Queue
 * @package Optimlight\Bugsnag\Model\Cron
 */
class Queue
{
    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var array
     */
    private $cardId = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * Queue constructor.
     * @param ManagerInterface $manager
     * @param array $cardId
     * @param array $options
     */
    public function __construct(
        ManagerInterface $manager,
        array $cardId = [],
        array $options = []
    ) {
        $this->manager = $manager;
        $this->cardId = $cardId;
        $this->options = $options;
    }

    /**
     * Cron job method to process queened items.
     *
     * @return void
     */
    public function execute()
    {
        $this->manager->dequeue($this->cardId, $this->options);
    }
}
