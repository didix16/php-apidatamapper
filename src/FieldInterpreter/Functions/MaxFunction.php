<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Functions;

use didix16\Api\ApiDataObject\UndefinedField;

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

        if (empty($this->iterable)) return new UndefinedField();

        if (!$this->field)
            return max($this->iterable);
        else {

            $values = array_map(function($obj){
                return $obj->{$this->field} ?? null;
            }, $this->iterable );

            // removes null
            $this->removeNull($values);
            if (empty($values)) return new UndefinedField();

            return max($values);

        }
    }

    /**
     * Given an iterable, returns the maximum interpreted value
     * @param $args
     * @return mixed
     */
    public function run(...$args)
    {
        parent::run(...$args);
        return $this->max();
    }

    private function removeNull(iterable &$value){
        $value = array_filter((array) $value, function ($item){ return !is_null($item); });
    }
}