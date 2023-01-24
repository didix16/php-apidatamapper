<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Functions;


use didix16\Interpreter\InterpreterFunction;

/**
 * Class AggregateFunction
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Functions
 */
abstract class AggregateFunction extends InterpreterFunction
{
    protected $iterable = [];
    protected $field = null;

    /**
     * Should return a value or didix16\Api\ApiDataObject\UndefinedField if null or not specified value
     * @param ...$args
     * @return mixed|void
     */
    protected function run(...$args){

        $this->iterable = $args[0];

        if (isset($args[1])){
            $this->field = $args[1];
        }
    }

}