<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface PipelineInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface PipelineInterface extends StageInterface
{
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
    );

    /**
     * @param MatcherInterface|null $matcher
     *
     * @return void
     */
    public function setMatcher(MatcherInterface $matcher = null);

    /**
     * @param ConfigInterface|null $config
     *
     * @return void
     */
    public function setConfig(ConfigInterface $config = null);

    /**
     * @param callable $operation
     *
     * @return PipelineInterface
     */
    public function pipe($operation);
}
