<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

use Optimlight\Bugsnag\Boot\Runner;
use Optimlight\Bugsnag\Boot\ExceptionHandler;

/**
 * Class Bugsnag
 * @package Optimlight\Bugsnag\Client
 */
class Bugsnag extends AbstractClient
{
    const APP_TYPE = 'Magento 2.x';
    
    /**
     * @var string
     */
    private static $severities = 'fatal,error';

    /**
     * @var array
     */
    private static $identification = [];

    /**
     * @var \Bugsnag\Client|null
     */
    private $client = null;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $notifySeverities;

    /**
     * @var string
     */
    private $environment = 'development';

    /**
     * @var bool
     */
    private $customerWasSet = false;

    /**
     * @var
     */
    private $filterFields;

    /**
     * Bugsnag constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);
        $apiKey = @$configuration['apikey'];
        $notifySeverities = isset($configuration['severities']) ? $configuration['severities'] : self::$severities;
        $filterFields = isset($configuration['filter_fields']) ? $configuration['filter_fields'] : '';
        $environment = isset($configuration['environment']) ? $configuration['environment'] : 'development';
        $this->initConfiguration($apiKey, $notifySeverities, $filterFields, $environment);
        $this->initBugsnag();
    }

    /**
     * @param $apiKey
     * @param $notifySeverities
     * @param $filterFields
     * @param $environment
     */
    public function initConfiguration($apiKey, $notifySeverities, $filterFields, $environment)
    {
        $this->apiKey = $apiKey;
        $this->notifySeverities = $notifySeverities;
        $this->filterFields = $filterFields;
        is_null($environment) ? : $this->environment = $environment;
    }

    /**
     * @param null $apiKey
     * @param null $config
     * @return \Bugsnag\Configuration|null
     */
    public function generateConfiguration($apiKey = null, $config = null)
    {
        if (is_null($apiKey)) {
            $apiKey = $this->apiKey;
        }
        $config = new \Bugsnag\Configuration($apiKey);
        $config->setReleaseStage($this->releaseStage());
        $config->setErrorReportingLevel($this->errorReportingLevel());
        $filters = $this->filterFields();
        if (is_array($filters)) {
            $config->setFilters($filters);
        }
        $config->setNotifier(self::$identification);
        $config->setAppType(self::APP_TYPE);
        return $config;
    }

    /**
     * @param null $apiKey
     * @throws \Exception
     */
    public function fireTestEvent($apiKey = null) {
        if (is_null($apiKey)) {
            $apiKey = $this->apiKey;
        }
        if (strlen($this->apiKey) != 32) {
            throw new \Exception('Invalid length of the API key');
        }
        $client = new \Bugsnag\Client($this->generateConfiguration($apiKey));
        $client->notifyError(
            'BugsnagTest',
            'Testing BugSnag',
            array('notifier' => self::$severities)
        );
    }

    /**
     * @return bool|\Bugsnag\Client|null
     */
    public function initBugsnag()
    {
        if (!class_exists('Bugsnag\\Client')) {
            error_log("Bugsnag Error: Couldn't activate Bugsnag Error Monitoring due to missing Bugsnag PHP library!");
            return false;
        }
        // Activate the BugSnag client.
        if (!empty($this->apiKey)) {
            $this->client = \Bugsnag\Client::make($this->apiKey);
            $this->client->getConfig()->setReleaseStage($this->releaseStage());
            $this->client->getConfig()->setNotifier(self::$identification);
            $filters = $this->filterFields();
            if (is_array($filters)) {
                $this->client->getConfig()->setFilters($filters);
            }
            $this->client->getConfig()->setErrorReportingLevel($this->errorReportingLevel());
            $this->client->getConfig()->setAppType(self::APP_TYPE);
            // Do not set handler here as in case of "early bird" Magento will overwrite handler.
            // // set_error_handler(array($this->client, 'errorHandler'));
            // // set_exception_handler(array($this->client, 'exceptionHandler'));
        }
        return $this->client;
    }

    /**
     *
     */
    private function addUserTab()
    {
        if (!$this->customerWasSet) {
            $customerSession = Runner::getCustomerSession();
            if ($customerSession) {
                try {
                    $customer = $customerSession->getCustomer();
                    $data = [
                        'customer_id' => $customerSession->getCustomerId(),
                        'email' => $customer->getEmail(),
                        'first_name' => $customer->getFirstname(),
                        'last_name' => $customer->getLastname()
                    ];
                    $config = $this->client->getConfig();
                    $config->setMetaData(['user' => $data], true);
                    $this->customerWasSet = true;
                } catch (\Exception $exception) {}
            }
        }
    }

    /**
     * @return string
     */
    private function releaseStage()
    {
        return $this->environment;
    }

    /**
     * @return int
     */
    private function errorReportingLevel()
    {
        if (empty($this->notifySeverities)) {
            $notifySeverities = 'fatal,error';
        } else {
            $notifySeverities = $this->notifySeverities;
        }
        $level = 0;
        $severities = explode(',', $notifySeverities);
        foreach ($severities as $severity) {
            $level |= \Bugsnag\ErrorTypes::getLevelsForSeverity($severity);
        }
        return $level;
    }

    /**
     * @return array|bool
     */
    private function filterFields()
    {
        if ($this->filterFields && is_string($this->filterFields) && strlen($this->filterFields())) {
            $buffer = array_map('trim', explode('\n', $this->filterFields));
            if ($buffer && is_array($buffer) && count($buffer) && strlen(@$buffer[0])) {
                return $buffer;
            }
        }
        return false;
    }

    /**
     * @return \Bugsnag\Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $errorNo
     * @param $errorStr
     * @param $errorFile
     * @param $errorLine
     * @param $lastError
     * @return bool
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError)
    {
        if (!$this->client || !$this->client->getConfig()) {
            return false;
        }
        if ($this->client->getConfig()->shouldIgnoreErrorCode($errorNo)) {
            return false;
        }
        if (Runner::getCustomerSession()) {
            $this->addUserTab();
        }
        $report = \Bugsnag\Report::fromPHPError($this->client->getConfig(), $errorNo, $errorStr, $errorFile, $errorLine);
        $this->postProcessStackFrames($report);
        $this->client->notify($report);
        return false;
    }

    /**
     * @param \Bugsnag\Report $report
     */
    public function postProcessStackFrames($report)
    {
        $map = [
            __CLASS__ . '::execute',
            ExceptionHandler::CLASS_NAME . '::customHandleError'
        ];
        $limit = 2;
        $remove = [];
        $trace = null;
        if (is_a($report, 'Bugsnag\\Report')) {
            $trace = $report->getStacktrace();
            foreach ($trace->getFrames() as $index => $frame) {
                if (is_array($frame) && isset($frame['method']) && in_array($frame['method'], $map)) {
                    $remove[] = $index;
                }
                $limit--;
                if (0 >= $limit) {
                    break;
                }
            }
        }
        if (count($remove) && is_object($trace)) {
            foreach ($remove as $index) {
                // remove frame $index, but after each remove it is again "0"
                $trace->removeFrame(0);
            }
        }
    }
}