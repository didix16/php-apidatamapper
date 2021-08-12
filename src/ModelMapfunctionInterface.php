<?php


namespace didix16\Api\ApiDataMapper;

/**
 * Interface ModelMapFunctionInterface
 * @package didix16\Api\ApiDataMapper
 */
interface ModelMapfunctionInterface
{
    public function getName(): string;
    public function __invoke(...$args);
}