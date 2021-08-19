<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Filters;

use didix16\Interpreter\InterpreterFilter;

/**
 * Class FieldFilter
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Filters
 */
abstract class FieldFilter extends InterpreterFilter
{

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

}