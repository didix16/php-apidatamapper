<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Functions;

use didix16\Api\ApiDataObject\UndefinedField;

/**
 * Class MinFunction
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Functions
 */
class MinFunction extends AggregateFunction
{
    public function __construct()
    {
        parent::__construct("MIN");
    }

    /**
     * Returns the lowerest value within iterable $data (removes null values)
     * If $data is empty, then return null
     * @return mixed
     */
    protected function min(){

        if (empty($this->iterable)) return new UndefinedField();

        if (!$this->field) {
            // removes null
            $this->removeNull($this->iterable);
            if (empty($this->iterable)) return new UndefinedField();
            return min($this->iterable);

        }else {

            $values = array_map(function($obj){
                return $obj->{$this->field} ?? null;
            }, $this->iterable );

            // removes null
            $this->removeNull($values);
            if (empty($values)) return new UndefinedField();

            return min($values);

        }
    }
    /**
     * Code to run when called
     * @param $args
     * @return mixed
     */
    public function run(...$args)
    {
        parent::run(...$args);
        return $this->min();
    }

    /**
     * Removes null values
     * @param iterable $value
     */
    private function removeNull(iterable &$value){
        $value = array_filter((array) $value, function ($item){ return !is_null($item); });
    }
}