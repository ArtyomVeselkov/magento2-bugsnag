<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Logger;

/**
 * Interface PhpInterface
 * @package Optimlight\Bugsnag\Logger
 */
interface PhpInterface
{
    /**
     * Default type of logging errors.
     * @see error_log for more information.
     */
    const DEFAULT_LOG_TYPE = 0;

    /**
     * Default location of destination logs writing.
     * @see error_log for more information.
     */
    const DEFAULT_LOG_DESTINATION = 'var' . DIRECTORY_SEPARATOR . 'log' .
                                    DIRECTORY_SEPARATOR . 'bugsnag.log';

    /**
     * For logging exceptions.
     * As Magento's framework can be not loaded yet -- relay on PHP error_log function.
     *
     * @param \Exception $exception
     * @param string $additionalMessage
     */
    public function catchException(\Exception $exception, $additionalMessage = '');

    /**
     * @param string $message
     * @return void
     */
    public function debug($message);
}
