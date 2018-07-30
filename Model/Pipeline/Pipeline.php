<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Class Pipeline
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
class Pipeline implements PipelineInterface
{
    /**
     * @var ProcessorInterface
     */
    private $processor;

    /**
     * @var StageInterface[]
     */
    private $stages = [];

    /**
     * @var MatcherInterface
     */
    private $matcher = null;

    /**
     * @var ConfigInterface
     */
    private $config = null;

    /**
     * PipelineInterface constructor.
     *
     * @param ProcessorInterface $processor
     * @param StageInterface[] $stages
     * @param MatcherInterface|null $matcher
     * @param ConfigInterface|null $config
     */
    public function __construct(
        ProcessorInterface $processor,
        array $stages = [],
        MatcherInterface $matcher = null,
        ConfigInterface $config = null
    ) {
        $this->processor = $processor;
        $this->stages = $stages;
        $this->setMatcher($matcher);
        $this->setConfig($config);
    }

    /**
     * @param MatcherInterface|null $matcher
     *
     * @return void
     */
    public function setMatcher(MatcherInterface $matcher = null)
    {
        $this->matcher = $matcher;
    }

    /**
     * @param ConfigInterface|null $config
     *
     * @return void
     */
    public function setConfig(ConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * @param callable $operation
     *
     * @return PipelineInterface
     */
    public function pipe($operation)
    {

    }

    /**
     * @param PayloadInterface $payload
     *
     * @return PayloadInterface
     */
    public function __invoke(PayloadInterface $payload)
    {
        // TODO: Implement __invoke() method.
    }
}
