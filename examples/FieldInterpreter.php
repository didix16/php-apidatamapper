<?php

require_once __DIR__ .'/../vendor/autoload.php';

use didix16\Api\ApiDataMapper\FieldGrammar\FieldLexer;
use didix16\Api\ApiDataMapper\FieldGrammar\FieldParser;
use didix16\Api\ApiDataMapper\FieldInterpreter\FieldInterpreter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Filters\FieldFilter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Functions\AggregateFunction;
use didix16\Api\ApiDataObject\ApiDataObject;


class MyApiDataObject extends ApiDataObject {}

/**
 * Custom FieldField: allows capitilize strings
 */
class CapitalizeFilter extends FieldFilter {

    protected function transform(&$value)
    {
        if($this->assertString($value))
            $value = strtoupper($value);
    }
}

/**
 * Custom FieldFilter: adds '_thisIsASuffix' as a string suffix
 */
class SuffixerFilter extends FieldFilter {

    protected function transform(&$value)
    {
        if($this->assertString($value))
            $value = $value . '_thisIsASuffix';
    }
}

class AvgFunction extends AggregateFunction {

    public function __construct()
    {
        parent::__construct("AVG");
    }

    /**
     * Returns the average value within iterable $data
     * If $data is empty, then return null
     * @return mixed
     */
    protected function avg(){

        if (empty($this->iterable)) return null;

        if (!$this->field)
            return array_sum($this->iterable)/ count($this->iterable);
        else {

            $values = array_map(function($obj){
                return $obj->{$this->field} ?? null;
            }, $this->iterable );

            return array_sum($values) / count($values);

        }
    }

    /**
     * Given an interable, returns the avergage interpreted value
     * @param $args
     * @return mixed
     */
    public function run(...$args)
    {
        parent::run(...$args);
        return $this->avg();
    }
}


/**
 * Example of how to parse a field from api data and turn into a boolean
 */
$input = "warrior.active:boolean";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);

$data = '{"warrior": {"name": "Lancelot", "active": "no"}}';
$data = MyApiDataObject::fromJson($data);

$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 *    ["warrior.active:boolean"]=>
 *    bool(false)
 * }
 * 
 */


/**
 * Example of how to load custom FieldFilters
 */

$input = "warrior.name:capitalize,suffixer";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);

$data = '{"warrior": {"name": "Lancelot", "active": "no"}}';
$data = MyApiDataObject::fromJson($data);

$fi = new FieldInterpreter($parser, $data);
$fi
    ->loadFilter(new CapitalizeFilter('capitalize'))
    ->loadFilter(new SuffixerFilter('suffixer'));

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 *    ["warrior.name:capitalize,suffixer"]=>
 *    string(22) "LANCELOT_thisIsASuffix"
 * }
 * 
 */


 /**
 * Example of how to get fields from a list
 */

$data = <<<JSON
{
    "comes_from": "Camelot",
    "warrior_list": [
        {
            "name": "Lancelot",
            "is_active": 0,
            "weapon": "Spear",
            "kills": 90
        },
        {
            "name": "Arthur",
            "is_active": 1,
            "weapon": "Sword",
            "kills": 50
        },
        {
            "name": "Merlin",
            "is_active": 0,
            "weapon": "Catalyst",
            "kills": 5
        }
    ]
    
}
JSON;
$data = MyApiDataObject::fromJson($data);

$input = "warrior_list[].name";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 * ["warrior_list[].name"]=>
 * array(3) {
 *   [0]=>
 *   string(8) "Lancelot"
 *   [1]=>
 *   string(6) "Arthur"
 *   [2]=>
 *   string(6) "Merlin"
 * }
 *}
 * 
 */

$input = "MAX(warrior_list[].kills)";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);

/**
 * array(1) {
 * ["MAX(warrior_list[].kills)"]=>
 * int(90)
 *}
 */

$input = "AVG(warrior_list[].kills)";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);
$fi
    ->loadFunction(new AvgFunction());

$res = $fi->run();

var_dump($res);

/**
 * array(1) {
 * ["AVG(warrior_list[].kills)"]=>
 * float(48.333333333333)
 *}
 */