<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model;

use Optimlight\Bugsnag\Model\Client\InterfaceClient;

/**
 * Interface InterfaceVirtualCard
 * @package Optimlight\Bugsnag
 */
interface InterfaceVirtualCard
{
    /**
     * Identifier for JavaScript exceptions' tracking card
     */
    const TYPE_JS = 'js';

    /**
     * Identifier for JavaScript exceptions' tracking card
     */
    const TYPE_PHP = 'php';

    /**
     * @return bool
     */
    public function validate();

    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @param $lastError
     * @return bool
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);

    /**
     * @return InterfaceClient
     */
    public function getClient();

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getSecondary();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getApikey();

    /**
     * @param InterfaceClient $client
     * @return $this
     */
    public function setClient(InterfaceClient $client);
}
