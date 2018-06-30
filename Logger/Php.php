<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Logger;

/**
 * Class Php
 * @package Optimlight\Bugsnag\Logger
 */
class Php implements PhpInterface
{
    /**
     * @see error_log documentation.
     *
     * @var int
     */
    protected $errorLogType = self::DEFAULT_LOG_TYPE;

    /**
     * @see error_log documentation.
     *
     * @var string
     */
    protected $errorLogDestination = self::DEFAULT_LOG_DESTINATION;

    /**
     * Php constructor.
     * @param int $errorLogType
     * @param string $errorLogDestination
     */
    public function __construct($errorLogType = null, $errorLogDestination = null)
    {
        $this->errorLogType = $errorLogType ?? $this->errorLogType;
        $this->errorLogDestination = $errorLogDestination ?? $this->errorLogDestination;
    }

    /**
     * @inheritdoc
     */
    public function catchException(\Exception $exception, $additionalMessage = '')
    {
        if (strlen(trim($additionalMessage))) {
            $additionalMessage = sprintf('[%s] ', $additionalMessage);
        }
        error_log($additionalMessage . $exception->getMessage(), $this->errorLogType, $this->errorLogDestination);
    }
}
