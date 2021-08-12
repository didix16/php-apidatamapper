<?php

namespace didix16\Api\ApiDataMapper;

use didix16\Api\ApiDataMapper\ApiDataMapper;
use didix16\Api\ApiDataMapper\ModelMapInterface;

class HighOrderModelMapConfiguration {

    protected $apiDataMapper;

    protected $modelMap;

    public function __construct(ApiDataMapper $apiDataMapper, ModelMapInterface $modelMap)
    {
        $this->modelMap = $modelMap;
        $this->apiDataMapper = $apiDataMapper;
    }

    /**
     * Call model map methods through api data mapper
     */
    public function __call($name, $args): self
    {
        if(method_exists($this->modelMap, $name))
            $this->modelMap->{$name}(...$args);
        else {
            throw new \BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $name));
        }

        return $this;
    }

    /**
     * Should be "mapper" to return the api data mapper it self, else an Exception is thrown
     */
    public function __get($name): ApiDataMapper
    {
        if($name === "mapper")
            return $this->apiDataMapper;
        else
            throw new \InvalidArgumentException("Property [$name] does not exists on model map");
    }
}