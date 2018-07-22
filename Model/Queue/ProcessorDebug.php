<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Optimlight\Bugsnag\Logger\Php as Logger;

/**
 * Class ProcessorDebug
 * @package Optimlight\Bugsnag\Model\Queue
 */
class ProcessorDebug implements ProcessorCallbackInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * ProcessorDebug constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function execute(array $arguments = [])
    {
        // Elements `event`, `processor` and `index` are always present among arguments.
        $this->logger->debug('Process queue message [' . $arguments['index'] ?? 'N/A' . '] on event "' . $arguments['event'] .'"');
    }
}
