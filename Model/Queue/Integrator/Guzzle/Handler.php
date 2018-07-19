<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle;

use Optimlight\Bugsnag\Model\Queue\{Client, ClientInterface};
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
        try {
            $serialized = $this->serializer->serialize($request);
            $array = [
                'request' => $serialized,
                'options' => $options
            ];
            $this->queue->enqueue($array);
        } catch (\Exception $exception) {
            $this->logger->catchException($exception, 'Enqueue error');
        }
    }

    /**
     * @param $options
     * @return
     * @throws \Exception
     */
    private function createClient($options)
    {
        return Client::getInstance([]);
    }
}
