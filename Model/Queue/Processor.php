<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Interop\Queue\PsrMessage;
use Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle\RequestSerializer;
use GuzzleHttp\{
    Client as GuzzleClient, Pool, Promise\Promise, TransferStats
};

/**
 * Class Processor
 * @package Optimlight\Bugsnag\Model\Queue
 */
class Processor implements ProcessorInterface
{
    /**
     * @var GuzzleClient
     */
    private $guzzle;

    /**
     * @var RequestSerializer
     */
    private $requestSerializer;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $callbacks = [];

    /**
     * Processor constructor.
     * @param GuzzleClient $client
     * @param RequestSerializer $requestSerializer
     * @param array $options
     * @param array $callbacks
     */
    public function __construct(
        GuzzleClient $client,
        RequestSerializer $requestSerializer,
        array $options = [],
        array $callbacks = []
    ) {
        $this->guzzle = $client;
        $this->requestSerializer = $requestSerializer;
        $this->options = $this->getDefaultOptions() + $options;
        $this->callbacks = $callbacks;
    }

    /**
     * @return array
     */
    private function getDefaultOptions()
    {
        return [static::OPTION_LIMIT => static::OPTION_LIMIT_DEFAULT];
    }

    /**
     * @param ClientInterface $client
     * @param array $options
     */
    public function dequeue($client, $options)
    {
        $inc = 0;
        $prefix = uniqid('bq');
        $messages = [];
        $limit = $this->options[static::OPTION_LIMIT];
        /** @var RecordMediatorInterface $record */
        while ($record = $client->dequeue()) {
            $id = $prefix . '-' . ++$inc;
            $messages[$id] = $this->pullMessage($record, $id);
            if ($inc >= $limit) {
                break;
            }
        }
        $pool = $this->createPool($messages);
        /** @var Promise $promise */
        $promise = $pool->promise();
        $promise->wait();
    }

    /**
     * @param RecordMediatorInterface $record
     * @param string $id
     * @return array
     */
    private function pullMessage(RecordMediatorInterface $record, $id)
    {
        $result = [
            'options' => [],
            'request' => '',
            'current_hash' => $id,
            'message_hash' => '',
            'record' => $record
        ];
        $message = $record->getMessageRaw();
        if (is_a($message, PsrMessage::class)) {
            $body = $message->getBody();
            if (is_array($body)) {
                $result['request'] = $body['request'] ?? null;
                $result['options'] = $body['options'] ?? null;
            } else {
                $result['request'] = $body;
            }
            if ($hash = $message->getHeader(ClientInterface::HEADER_BUGSNAG_QUEUE_ID)) {
                $result['message_hash'] = $hash;
            }
        } else {
            $body = $record->getMessage();
            if (is_array($body)) {
                $result['request'] = $body['request'] ?? null;
                $result['options'] = $body['options'] ?? null;
            } else {
                $result['request'] = $body;
            }
        }
        return $result;
    }

    /**
     * @param array $messages
     * @param array $options
     * @return Pool
     */
    private function createPool(array $messages, array $options = [])
    {
        if (!isset($options['fulfilled'])) {
            $options['fulfilled'] = $this->getCallableFullfilled();
        };
        if (!isset($options['rejected'])) {
            $options['rejected'] = $this->getCallableRejected();
        };
        return new Pool($this->guzzle, $this->getCallableRequests()($messages), $options);
    }

    /**
     * @param string $event
     * @param array $arguments
     */
    private function executeCallbacks($event, array $arguments = [])
    {
        if (isset($this->callbacks[$event])) {
            $arguments['event'] = $event;
            $arguments['processor'] = $this;
            $callbacks = $this->callbacks[$event];
            if (is_array($callbacks)) {
                foreach ($callbacks as $position => $object) {
                    if (is_a($object, ProcessorCallbackInterface::class)) {
                        /** @var ProcessorCallbackInterface $object */
                        $object->execute($arguments);
                    }
                }
            }
        }
    }

    /**
     * @return \Closure
     */
    public function getCallableFullfilled()
    {
        return function($response, $index) {
            $this->executeCallbacks('on_fulfilled', ['response' => &$response, 'index' => $index]);
        };
    }

    /**
     * @return \Closure
     */
    public function getCallableRejected()
    {
        return function($response, $index) {
            $this->executeCallbacks('on_rejected', ['response' => &$response, 'index' => $index]);
        };
    }

    /**
     * This callback function is executed right after response was obtained and corresponded response object was created.
     * It is used as a point when we can acknowledge or reject queue's message.
     *
     * @param $message
     * @param string|null $index
     * @return \Closure
     */
    public function getCallableOnStats($message, $index = null)
    {
        return function(TransferStats $stats) use ($message, $index) {
            if (
                $stats->hasResponse() &&
                isset($message['record']) &&
                is_a($message['record'], RecordMediatorInterface::class)
            ) {
                $response = $stats->getResponse();
                /** @var RecordMediatorInterface $record */
                $record = $message['record'];
                // Probably we can use either reasonPhrase or comparative conditions.
                switch ($response->getStatusCode()) {
                    case 200:
                        $record->acknowledge();
                        break;
                    default:
                        $record->reject();
                        break;
                }
            }
            $this->executeCallbacks('on_stats', ['stats' => $stats, 'message' => $message, 'index' => $index]);
        };
    }

    /**
     * @return \Closure
     */
    private function getCallableRequests()
    {
        $client = $this->guzzle;
        return function ($messages) use ($client) {
            foreach ($messages as $index => $message) {
                yield function() use ($client, $message, $index) {
                    $request = $message['request'];
                    $options = $message['options'];
                    $options = $this->populateOptions($options, $message, $client, $index);
                    $request = $this->requestSerializer->unserialize($request);
                    return $this->guzzle->sendAsync($request, $options);
                };
            }
        };
    }

    /**
     * @param array $options
     * @param array $message
     * @param GuzzleClient $client
     * @param string|null $index
     * @return array
     */
    private function populateOptions($options, $message, GuzzleClient $client, $index = null)
    {
        $options = is_array($options) ? $options : [];
        $options['handler'] = $client->getConfig('handler');
        if (!isset($options['on_stats'])) {
            $options['on_stats'] = $this->getCallableOnStats($message, $index);
        }
        return $options;
    }
}
