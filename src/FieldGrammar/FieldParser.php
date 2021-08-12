<?php


namespace didix16\Api\ApiDataMapper\FieldGrammar;


use didix16\Grammar\Lexer;
use didix16\Grammar\Parser;
use Exception;

/**
 * Grammar
 * ------------------------------------------------------
 * S            := LastField | Item [NestedItem] | AggregateFn
 * Item         := Field | FieldList
 * Field        := ^[A-Za-z_][A-Za-z0-9_]*$
 * FieldList    := Field'[]'
 * LastField    := Field [Colon FilterList]
 * Filter       := ^[A-Za-z_][A-Za-z0-9_]*$
 * FilterList   := Filter [Comma FilterList]
 * NestedItem   := '.' (Field [NestedItem] | FieldList [NestedItem] | LastField )
 * AggregateFn  := FnName'('WhiteSpace FnArgs WhiteSpace')'
 * FnName       := ^[A-Za-z_][A-Za-z0-9_]*$
 * FnArgs       := [NestedFields]FieldList['.'LastField]
 * NestedFields := [Field'.'NestedFields]
 * Whitespace   := [' 'Whitespace]
 * Colon        := ^[ ]*:[ ]*$
 * Comma        := ^[ ]*,[ ]*$
 *
 * Test: https://paiza.io/projects/qDK_HeHYugyxX_p8G3k1lA
 * Class FieldParser
 * @package didix16\Api\ApiDataMapper\FieldGrammar
 */
class FieldParser extends Parser {

    /**
     * Item := Field | FieldList
     * @throws Exception
     */
    public function item(){

        if ($this->lookahead->getType() === FieldLexer::T_FIELD){
            return $this->field();
        }

        if ($this->lookahead->getType() === FieldLexer::T_FIELDLIST){
            return $this->fieldList();
        }

        throw new Exception("Invalid Syntax. Expected Field or FieldList");
    }

    /**
     * NestedItem := '.'Item [NestedItem]
     * NestedItem   := '.' (Field [NestedItem] | FieldList [NestedItem] | LastField )
     * @throws Exception
     */
    public function nestedItem(){

        // '.'
        $this->match(FieldLexer::T_DOT);

        //$this->item();
        switch($this->lookahead->getType()){

            case FieldLexer::T_FIELD:
                $this->field();
                // [NestedItem]
                if ($this->lookahead->getType() === FieldLexer::T_DOT){
                    $this->nestedItem();
                }
                break;
            case FieldLexer::T_FIELDLIST:
                $this->fieldList();
                // [NestedItem]
                if ($this->lookahead->getType() === FieldLexer::T_DOT){
                    $this->nestedItem();
                }
                break;
            case FieldLexer::T_LAST_FIELD:
                $this->lastField();
                break;
            default:
                throw new Exception("Invalid Syntax. Expected Field, FieldList or LastField");
        }

    }

    /**
     * Field := ^[A-Za-z_][A-Za-z0-9_]*$
     * @throws Exception
     */
    public function field(){

        $this->match(FieldLexer::T_FIELD);
    }

    /**
     * FieldList :=  Field'[]'
     * @throws Exception
     */
    public function fieldList(){

        $this->match(FieldLexer::T_FIELDLIST);

    }

    /**
     * LastField := Field [Colon FilterList]
     * @throws Exception
     */
    public function lastField(){

        $this->match(FieldLexer::T_LAST_FIELD);
        if ($this->lookahead->getType() === FieldLexer::T_COLON){

            $this->match(FieldLexer::T_COLON);
            $this->filterList();
        }
    }

    /**
     * Filter := ^[A-Za-z_][A-Za-z0-9_]*$
     * @throws Exception
     */
    public function filter(){

        $this->match(FieldLexer::T_FIELD_FILTER);
    }

    /**
     * FilterList := Filter [Comma FilterList]
     * @throws Exception
     */
    public function filterList(){

        $this->filter();
        if ($this->lookahead->getType() === FieldLexer::T_COMMA){
            $this->match(FieldLexer::T_COMMA);
            $this->filterList();
        }

    }

    /**
     * AggregateFn  := FnName'('WhiteSpace FnArgs WhiteSpace')'
     * @throws Exception
     */
    public function aggregateFunction(){

        // FnName
        $this->functionName();
        // '('
        $this->match(FieldLexer::T_LPARENT);
        // Whitespace
        $this->whitespace();
        // FnArgs
        $this->functionArgs();
        // Whitespace
        $this->whitespace();
        // ')'
        $this->match(FieldLexer::T_RPARENT);

    }

    /**
     * Whitespace := [' 'Whitespace]
     */
    public function whitespace(){

        if ($this->lookahead->getType() === FieldLexer::T_WHITESPACE) {

            $this->match(FieldLexer::T_WHITESPACE);
            $this->whitespace();
        }
    }

    /**
     * FnArgs:= [NestedFields]FieldList['.'LastField]
     * @throws Exception
     */
    public function functionArgs(){

        if ($this->lookahead->getType() === FieldLexer::T_RPARENT) {

            throw new \Exception("Invalid Syntax. Function argument expected!");
        }

        if ($this->lookahead->getType() === FieldLexer::T_FIELD) {

            $this->nestedFields();
        }

        // FieldList['.'LastField]
        $this->fieldList();
        if ($this->lookahead->getType() === FieldLexer::T_DOT){

            $this->match(FieldLexer::T_DOT);
            $this->lastField();
        }
    }

    /**
     * NestedFields := [Field'.'NestedFields]
     */
    public function nestedFields(){

        if ( $this->lookahead->getType() === FieldLexer::T_FIELD){

            // Field
            $this->field();

            // '.'NestedFields
            $this->match(FieldLexer::T_DOT);
            $this->nestedFields();
        }
    }

    /**
     * FnName := Field
     */
    public function functionName(){

        $this->match(FieldLexer::T_AGGREGATE_FN_NAME);
    }

    /**
     * Colon := ^[ ]*:[ ]*$
     * @throws Exception
     */
    public function colon(){

        $this->match(FieldLexer::T_COLON);
    }

    /**
     * Comma := ^[ ]*,[ ]*$
     * @throws Exception
     */
    public function comma(){
        $this->match(FieldLexer::T_COMMA);
    }

    /**
     * The main entry point of the grammar expression
     * Returns the list of tokens founded
     * Syntax := LastField | Item [NestedItem] | AggregateFn
     * @return array
     * @throws Exception
     */
    public function parse(): array
    {
        if ($this->lookahead->getType() === FieldLexer::T_LAST_FIELD){
            $this->lastField();
        } else if ($this->lookahead->getType() === FieldLexer::T_AGGREGATE_FN_NAME){
            $this->aggregateFunction();
        } else {
            $this->item();
            if ($this->lookahead->getType() === FieldLexer::T_DOT) {
                $this->nestedItem();
            }
        }

        $this->match(Lexer::EOF_TYPE);

        return $this->getTokens();
    }
}