<?php


namespace didix16\Api\ApiDataMapper\FieldInterpreter;

use didix16\Api\ApiDataMapper\FieldGrammar\FieldLexer;
use didix16\Api\ApiDataMapper\FieldGrammar\FieldParser;
use didix16\Api\ApiDataMapper\FieldInterpreter\Filters\BooleanFilter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Filters\DateFilter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Functions\MaxFunction;
use didix16\Api\ApiDataMapper\FieldInterpreter\Functions\MinFunction;
use didix16\Api\ApiDataObject\ApiDataObjectInterface;
use didix16\Grammar\Lexer;
use didix16\Grammar\Token;
use didix16\Interpreter\HasFilter;
use didix16\Interpreter\Interpreter;
use Exception;

/**
 * @Test https://paiza.io/projects/LUNRrpJh0UfNndXalOjn9A?language=php
 * Class FieldInterpreter
 * @package didix16\Api\ApiDataMapper\FieldInterpreter
 */
class FieldInterpreter extends Interpreter
{
    use HasFilter;

    /**
     * A map that key is the Api field and value is the processed value
     * @var array
     */
    protected $mappedData = [];

    /**
     * @var object|ApiDataObjectInterface
     */
    protected $data;

    /**
     * Concatenates tokens values to build the API field
     * @var string
     */
    protected $processedAPIField = "";

    /**
     * Holds the current object/property being processed
     * @var object|array
     */
    protected $currentData;

    public function __construct(FieldParser $parser, object $data)
    {
        parent::__construct($parser, $data);
        $this->currentData = $this->data;

        $this
            ->loadFunction(new MaxFunction())
            ->loadFunction(new MinFunction())
            ->loadFilter(new DateFilter("Y-m-d H:i:s"))
            ->loadFilter(new BooleanFilter([
                "true" => ["active"],
                "false" => ["pending"]
            ], true));
    }

    protected function consume(): Token
    {
        $token = parent::consume();
        if ($token->getValue() !== Lexer::EOF_VALUE)
            $this->processedAPIField .= $token->getValue();
        return $token;
    }

    /**
     * Execute this interpreter using the parsed tokens
     * Returns the mapped data with key being API data field and value the processed data
     * @return array
     * @throws Exception
     */
    public function run()
    {
        /**
         * @var Token $token
         */
        $token = $this->lookahead();
        // special treatment for aggregate functions
        if ($token->getType() === FieldLexer::T_AGGREGATE_FN_NAME){
            $this->processFunction();
        }else{
            // process fields (also last fields) and fieldlists
            do {
                switch ($token->getType()){
                    case FieldLexer::T_FIELD:
                        $this->processField();
                        break;
                    case  FieldLexer::T_FIELDLIST:
                        $this->processFieldList();
                        break;
                    case FieldLexer::T_LAST_FIELD:
                        $this->processField(true);
                        break;
                    default: // ignore whitespaces and dots
                        $this->consume();
                        break;
                }
            }while(($token = $this->lookahead()));

            $this->mappedData[$this->processedAPIField] = $this->currentData;
        }

        return $this->mappedData;

    }

    /**
     * Process the function related tokens
     * @throws Exception
     */
    protected function processFunction(){

        $fnName = $this->consume()->getValue();
        $this->consume(); // '('
        while ($this->lookahead()->getType() === FieldLexer::T_WHITESPACE) $this->consume(); //' '

        // process func arg
        list($fieldList, $field) = $this->processFunctionArg();
        while ($this->lookahead()->getType() === FieldLexer::T_WHITESPACE) $this->consume(); //' '
        $this->consume(); // ')'

        $this->mappedData[$this->processedAPIField] = $this->executeFunction($fnName, $fieldList, $field);
        return;
    }

    /**
     * Process the function argument. It could be a nested
     * @throws Exception
     */
    protected function processFunctionArg(){

        // if token is a field then should be followed by '.'<field|fieldList>
        if ($this->lookahead()->getType() === FieldLexer::T_FIELD) {
            $this->processFieldRecursive();
        }

        // token is a field list, so optionally may be followed by '.'<lastField[:filters]>
        $fieldList = $this->processFieldList();
        $field = null;
        if ($this->lookahead()->getType() === FieldLexer::T_DOT){
            $this->consume(); // '.'
            $field = $this->consume()->getValue();

            // should we apply filters?
            if($this->lookahead()->getType() === FieldLexer::T_COLON){
                $filters = $this->processFilters();

                $this->setCurrentDataWithFilters($field, $filters);

            }

        }

        return [$fieldList, $field];
    }

    /**
     * Return the data value for current processed field
     * @param bool $isLast
     * @return mixed|null
     * @throws Exception
     */
    protected function processField($isLast = false){

        $field = $this->consume()->getValue();

        // check if this field is the last field. if it is then check if has filters
        if (!$isLast){
            return $this->setCurrentData($field);
        } else {
            $token = $this->lookahead();

            if ($token && $token->getType() === FieldLexer::T_COLON){

                // get filters
                $filters = $this->processFilters();

                return $this->setCurrentDataWithFilters($field, $filters);
            } else {
                return $this->setCurrentData($field);
            }

        }

    }

    /**
     * Used only on processFunctionArg
     * @throws Exception
     */
    protected function processFieldRecursive(){

        if ($this->lookahead()->getType() === FieldLexer::T_LAST_FIELD){
            $this->processField(true);
            return;
        }
        $this->processField(false);
        if ($this->lookahead()->getType() === FieldLexer::T_DOT){
            $this->consume(); // '.'
            if ($this->lookahead()->getType() === FieldLexer::T_FIELD) {
                $this->processFieldRecursive();
            }
        }
    }

    /**
     * Return the data value for current processed field list
     * @return mixed|null
     */
    protected function processFieldList(){

        $field = trim($this->consume()->getValue(), " []");
        return $this->setCurrentData($field);

    }

    /**
     * @return array
     */
    protected function processFilters(){
        // get filters
        $filters = [];

        do {

            $this->consume(); // ignore ':' and ','
            $filter = $this->consume()->getValue(); // filterName
            $filters[] = $filter;

        }while($this->lookahead()->getType() === FieldLexer::T_COMMA);
        return $filters;
    }

    /**
     * Forwards on API data tree
     * If current property is an object then get that object
     * else ( is a list ) iterate over the list and pick up the current field from each list item
     * @param $field
     * @return array|ApiDataObjectInterface|mixed|null
     */
    protected function setCurrentData($field) {
        
        // if currentData is null means the desired field is not coming, so we must skip it
        if(is_null($this->currentData))
            return $this->currentData;

        if(is_object($this->currentData)){

            $this->currentData = $this->currentData->{$field};
        } else {

            $items = [];

            if(!is_iterable($this->currentData)){

                throw new \Exception(
                    sprintf(
                        "Invalid list detected while processing filed [%s]: \n
                        FOUND: [%s]",
                        $field, print_r($this->currentData, true)
                    )
                    );
            }

            foreach($this->currentData as $item){

                if(is_array($item)) {

                    foreach($item as $subItem){
                        $items[] = $subItem->{$field};
                    }
                } else {
                    $items[] = $item->{$field};
                }
            }

            $this->currentData = $items;
        }

        return $this->currentData;
    }

    /**
     * Same as setCurrentData but applying filters
     * @param $field
     * @param array $filters
     * @return array|ApiDataObjectInterface|mixed|object|null
     * @throws Exception
     */
    protected function setCurrentDataWithFilters($field, array $filters = []){

        // if currentData is null means the desired field is not coming, so we must skip it
        if(is_null($this->currentData))
            return $this->currentData;

        if (is_object($this->currentData)){

            $this->transformData($filters, $this->currentData->{$field});
            $this->currentData = $this->currentData->{$field};
        }else {

            $items = [];
            foreach ($this->currentData as $item){

                // if item field is an array then flatten the result array
                if ( is_array($item)){

                    foreach ($item as $subItem){
                        $this->transformData($filters, $subItem->{$field});
                        $items[] = $subItem->{$field};
                    }

                }else{
                    $this->transformData($filters, $item->{$field});
                    $items[] = $item->{$field};
                }

            }


            $this->currentData = $items;
        }
        return $this->currentData;
    }

    /**
     * @param array $filters
     * @param $data
     * @throws Exception
     */
    protected function transformData(array &$filters, &$data){

        foreach ($filters as $filter){
            $this->applyFilter($filter, $data);
        }
    }
}