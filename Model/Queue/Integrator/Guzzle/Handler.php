<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle;

use Optimlight\Bugsnag\Model\Queue\{ClientInterface, Builder\Client as ClientBuilder};
use Optimlight\Bugsnag\Logger\Php as Logger;
use Psr\Http\Message\RequestInterface;

/**
 * Class Handler
 * @package Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle
 */
class Handler
{
    /**
     * @var array
     */
    private $options = [];

    /**
     * @var RequestSerializer
     */
    private $serializer;

    /**
     * @var ClientInterface
     */
    private $queue;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Handler constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->serializer = new RequestSerializer();
        $this->queue = $this->createClient($options);
        $this->logger = new Logger();
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        // If queue client wasn't created - return.
        if (!$this->queue) {
            return ;
        }
        try {
            $serialized = $this->serializer->serialize($request);
            $array = [
                'request' => $serialized,
                'options' => $this->prepareQueueRecord($options)
            ];
            $this->queue->enqueue($array);
        } catch (\Exception $exception) {
            $this->logger->catchException($exception, 'Enqueue error');
        }
    }

    /**
     * @param array $array
     * @return array
     */
    private function prepareQueueRecord($array)
    {
        foreach ($array as $key => &$value) {
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    $value = null;
                }
            }
        }
        return $array;
    }

    /**
     * @param $options
     * @return ClientInterface
     * @throws \Exception
     */
    private function createClient($options)
    {
        $builder = new ClientBuilder();
        return $builder->build($options);
    }
}
