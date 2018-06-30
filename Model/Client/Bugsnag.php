<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

use Optimlight\Bugsnag\Boot\Runner;
use Optimlight\Bugsnag\Boot\ExceptionHandler;
use Magento\Framework\DataObject;
use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\ErrorTypes;
use Bugsnag\Report;

/**
 * Class Bugsnag
 * @package Optimlight\Bugsnag\Client
 *
 * Properties and methods are defined as protected as class supposed to be inherited by other classes.
 */
class Bugsnag extends AbstractClient
{
    /**
     * Application type send to Bugsnag.
     */
    const APP_TYPE = 'Magento 2.x Backend';

    /**
     * Levels of exceptions to be tracked.
     *
     * @var string
     */
    protected $severities = 'fatal,error';

    /**
     * ID of the used client / card.
     * Is not used now. See comments below.
     *
     * @var array
     */
    protected $identification = [];

    /**
     * Bugsnag client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * API key for Bugsnag.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $notifySeverities;

    /**
     * @var string
     */
    protected $environment = 'development';

    /**
     * @var bool
     */
    protected $customerWasSet = false;

    /**
     * @var
     */
    protected $filterFields;

    /**
     * Bugsnag constructor.
     * @param array $configuration
     * @throws \Exception
     */
    public function __construct(array $configuration = [])
    {
        parent::__construct($configuration);
        $apiKey = $configuration['apikey'] ?? '';
        $notifySeverities = $configuration['severities'] ?? $this->severities;
        $filterFields = $configuration['filter_fields'] ?? '';
        $environment = $configuration['environment'] ?? 'development';
        $this->initConfiguration($apiKey, $notifySeverities, $filterFields, $environment);
        $this->initBugsnag();
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
            throw new \Exception('Invalid length of the API key.');
        }
        $client = new Client($this->generateConfiguration($apiKey));
        $client->notifyError(
            'BugsnagTest',
            'Testing BugSnag',
            ['notifier' => $this->severities]
        );
    }


    /**
     * Get client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Main function for tracking exceptions.
     *
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
        $report = Report::fromPHPError($this->client->getConfig(), $errorNo, $errorStr, $errorFile, $errorLine);
        $this->postProcessStackFrames($report);
        $this->client->notify($report);
        return false;
    }

    /**
     * @return Client|null
     * @throws \Exception
     */
    protected function initBugsnag()
    {
        if (!class_exists('Bugsnag\\Client')) {
            throw new \Exception('Error: Couldn\'t activate Bugsnag Error Monitoring due to missing Bugsnag PHP library!');
        }
        // Activate the BugSnag client.
        if (!empty($this->apiKey)) {
            $this->client = Client::make($this->apiKey);
            $this->client->getConfig()->setReleaseStage($this->releaseStage());
            // This option shouldn't be really used until correct value is populated. Specifing wrong value can prevent
            // errors from being tracked by Bugsnag.
            // $this->client->getConfig()->setNotifier($this->identification);
            $filters = $this->filterFields();
            if (is_array($filters)) {
                $this->client->getConfig()->setFilters($filters);
            }
            $this->client->getConfig()->setErrorReportingLevel($this->errorReportingLevel());
            $this->client->getConfig()->setAppType(self::APP_TYPE);
            // Do not set handler here as in case of "early bird" Magento will overwrite handler.
            // set_error_handler([$this->client, 'errorHandler']);
            // set_exception_handler([$this->client, 'exceptionHandler']);
        }
        return $this->client;
    }


    /**
     * Set main configuration properties.
     *
     * @param string $apiKey
     * @param string $notifySeverities
     * @param array $filterFields
     * @param string $environment
     */
    protected function initConfiguration($apiKey, $notifySeverities, $filterFields, $environment)
    {
        $this->apiKey = $apiKey;
        $this->notifySeverities = $notifySeverities;
        $this->filterFields = $filterFields;
        is_null($environment) ? : $this->environment = $environment;
    }

    /**
     * @param string $apiKey
     * @return Configuration
     */
    protected function generateConfiguration($apiKey = '')
    {
        if (is_null($apiKey)) {
            $apiKey = $this->apiKey;
        }
        $config = new Configuration($apiKey);
        $config->setReleaseStage($this->releaseStage());
        $config->setErrorReportingLevel($this->errorReportingLevel());
        $filters = $this->filterFields();
        if (is_array($filters)) {
            $config->setFilters($filters);
        }
        $config->setNotifier($this->identification);
        $config->setAppType(self::APP_TYPE);
        return $config;
    }

    /**
     * Add additional tab with user info if present.
     *
     */
    protected function addUserTab()
    {
        if (!$this->customerWasSet) {
            $customerSession = Runner::getCustomerSession();
            if ($customerSession) {
                try {
                    $customer = $customerSession->getCustomer();
                    if (is_a($customer, DataObject::class)) {
                        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
                        $data = [
                            'customer_id' => $customerSession->getCustomerId(),
                            'email' => $customer->getEmail(),
                            'first_name' => $customer->getFirstname(),
                            'last_name' => $customer->getLastname()
                        ];
                        $config = $this->client->getConfig();
                        $config->setMetaData(['user' => $data], true);
                        $this->customerWasSet = true;
                    }
                } catch (\Exception $exception) {
                    $this->phpLogger->catchException($exception);
                }
            }
        }
    }

    /**
     * Return release stage.
     *
     * @return string
     */
    protected function releaseStage()
    {
        return $this->environment;
    }

    /**
     * Convert string representation of severities to int mask.
     *
     * @return int
     */
    protected function errorReportingLevel()
    {
        if (empty($this->notifySeverities)) {
            $notifySeverities = 'fatal,error';
        } else {
            $notifySeverities = $this->notifySeverities;
        }
        $level = 0;
        $severities = explode(',', $notifySeverities);
        foreach ($severities as $severity) {
            $level |= ErrorTypes::getLevelsForSeverity($severity);
        }
        return $level;
    }

    /**
     * Get filtering fields.
     *
     * @return array|bool
     */
    protected function filterFields()
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
     * Exclude from trace frames with Bugsnag tracking frames (to make report pure).
     *
     * @param Report $report
     */
    protected function postProcessStackFrames($report)
    {
        $map = $this->getStackFramesFilter();
        $limit = 3;
        $remove = [];
        $trace = null;
        if (is_a($report, Report::class)) {
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

    /**
     * Return array of ['class::method'] entries to be removed from final report.
     *
     * @return string[]
     */
    protected function getStackFramesFilter()
    {
        return [
            __CLASS__ . '::execute',
            \Optimlight\Bugsnag\Model\InterfaceVirtualCard::class . '::execute', // TODO Maybe change this to the foreach
            \Optimlight\Bugsnag\Model\VirtualCard::class . '::execute',
            ExceptionHandler::class . '::customHandleError'
        ];
    }
}
