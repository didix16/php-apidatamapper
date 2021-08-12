<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Functions;

/**
 * Class MaxFunction
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Functions
 */
class MaxFunction extends AggregateFunction
{
    public function __construct()
    {
        parent::__construct("MAX");
    }

    /**
     * Returns the highest value within iterable $data
     * If $data is empty, then return null
     * @return mixed
     */
    protected function max(){

        if (empty($this->iterable)) return null;

        if (!$this->field)
            return max($this->iterable);
        else {

            return max(
                array_map(function($obj){
                    return $obj->{$this->field} ?? null;
                }, $this->iterable )
            );

        }
    }

    /**
     * Given an interable, returns the maximum interpreted value
     * @param $args
     * @return mixed
     */
    public function run(...$args)
    {
        parent::run(...$args);
        return $this->max();
    }
}