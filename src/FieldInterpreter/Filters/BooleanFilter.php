<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter\Filters;

/**
 * Class BooleanFilter
 * @package didix16\Api\ApiDataMapper\FieldInterpreter\Filters
 */
class BooleanFilter extends FieldFilter
{
    /**
     * @var array
     */
    protected $boolValues;
    /**
     * If is set to true, any value which is not inside $boolValues nor is PHP boolean value then $value will be forced to be false
     * @var bool
     */
    protected $forceFalse;

    /**
     * Accepts an associative array which keys can be "true" and/or "false" and values are arrays which contains strings values representing the boolean values to be transformed into
     * BooleanFilter constructor.
     * @param array $boolValues
     * @param bool $forceFalse
     */
    public function __construct($boolValues = [], $forceFalse = false)
    {
        parent::__construct("boolean");
        $this->boolValues = $boolValues;
        $this->forceFalse = $forceFalse;
    }

    protected function transform(&$value)
    {
        if (empty($value)){
            $value = false;
            return;
        }

        $false = (preg_match('#^([fF][aA][lL][sS][eE]|0|[Nn][oO])$#', $value) === 1);
        $true =  (preg_match('#^([tT][rR][uU][eE]|1|[yY]([eE][sS]))$#', $value) === 1);

        if ($false) $value = false;
        else if ($true) $value = true;
        else {

            if (isset($this->boolValues["true"])){

                if (in_array($value, $this->boolValues["true"])){
                    $value = true;
                    return;
                }
            }

            if (isset($this->boolValues["false"])){

                if (in_array($value, $this->boolValues["false"])){
                    $value = false;
                    return;
                }
            }

            // if finally we could not convert the value then check if we have to force to be false.
            if ($this->forceFalse){
                $value = false;
            }

            throw new \Exception("Value [$value] could not be converted to boolean");

        }
    }


}