<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Boot;

use Optimlight\Bugsnag\Logger\Php as Logger;
use Optimlight\Bugsnag\Model\VirtualCard;
use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Optimlight\Bugsnag\Helper\VirtualClass;
use Optimlight\Bugsnag\Model\Client\Bugsnag as BugsnagClient;
use Magento\Framework\DataObject;

/**
 * Interface ExceptionHandlerInterface
 * @package Optimlight\Bugsnag\Boot
 */
interface ExceptionHandlerInterface
{
    /**
     * First level key for env.php file. Common key.
     */
    const CONFIG_KEY = 'opt_handler';

    /**
     * Second level key for env.php file.
     */
    const CONFIG_SUBKEY_EXCEPTIONS = 'exceptions';

    /**
     * Sub-key for exclusion rules.
     */
    const CONFIG_SUBKEY_EXCLUSION = 'exclude';

    /**
     * Sub-key to define if whole extension is enabled.
     */
    const CONFIG_SUBKEY_ACTIVE = 'active';

    /**
     * Sub-key for card that would be initialized during composer auto-loading (collecting modules via registration.php).
     */
    const CONFIG_SUBKEY_EARLY_BIRD = 'early_bird';

    /**
     * Common prefix of virtual types to look for registered cards.
     */
    const VIRTUAL_CARD_TYPE_PREFIX = 'Optimlight\Bugsnag\Model\Card_';

    /**
     * Key for $previousHandlers to set exceptions handler callback (primarily for Magento fallback).
     */
    const HANDLER_EXCEPTION = 'exceptionHandler';

    /**
     * Key for $previousHandlers to set errors handler callback (primarily for Magento fallback).
     */
    const HANDLER_ERROR = 'errorHandler';

    /**
     * Check either we should exclude current exception from being logged to the client.
     *
     * @param $errorNo
     * @param $errorStr
     * @param $errorFile
     * @param $errorLine
     * @return bool
     */
    public function isExcluded($errorNo, $errorStr, $errorFile, $errorLine);

    /**
     * Try to register handlers with preserving previously set handlers (primarily by Magento).
     *
     * @param string $name
     * @param string $method
     * @param string $setHandler
     */
    public function registerHandler($name, $method, $setHandler);

    /**
     * Register error and exception handlers.
     */
    public function registerAllHandlers();

    /**
     * Setup custom error handler.
     * Here we check if corresponded script/eval fragment is excluded from the handler.
     * If not -- process it with all registered handlers.
     *
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws \Exception
     */
    public function customHandleError($errorNo, $errorStr, $errorFile, $errorLine);

    /**
     * Setup custom exception handler.
     * Here we check if corresponded script/eval fragment is excluded from the handler.
     * If not -- process it with all registered handlers.
     *
     * @param \Exception $exception
     *
     * @return bool|mixed
     */
    public function customHandleException($exception);

    /**
     * Get registered cards.
     *
     * @return InterfaceVirtualCard[]
     */
    public function getCards();

    /**
     * If extension is activated.
     *
     * @return bool
     */
    public function isActive();

    /**
     * Try to register all cards.
     */
    public function prepareCards();

    /**
     * Try to add card into stack.
     *
     * @param InterfaceVirtualCard $card
     * @param bool $overwrite
     */
    public function addCard(InterfaceVirtualCard $card, $overwrite = false);
}
