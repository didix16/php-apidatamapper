<?php


namespace didix16\Api\ApiDataMapper\FieldGrammar;


use didix16\Grammar\Lexer;
use didix16\Grammar\Token;

/**
 * Class FieldLexer
 * @package didix16\Api\ApiDataMapper\FieldGrammar
 */
class FieldLexer extends Lexer {
    const T_FIELD = 2; //'stringField'
    const T_FIELDLIST = 3; // 'stringField''[]'
    const T_DOT = 4; // '.'
    const T_LPARENT = 5; // '('
    const T_RPARENT = 6; // ')'
    const T_AGGREGATE_FN_NAME = 7; // aggregateFnName
    const T_WHITESPACE = 8; // ' '
    const T_LBRACKET = 9; // '['
    const T_RBRACKET = 10; // ']'
    const T_COLON = 11; // ':'
    const T_FIELD_FILTER = 12; //fieldFilter
    const T_COMMA = 13; // ','
    const T_LAST_FIELD = 14; // 'stringField'

    //const REG_TOKEN = '/(\.)|\(|\)|\b([A-Za-z_][A-Za-z0-9_]*\[\])|\b[A-Za-z_][A-Za-z0-9_]*(?![\[\]])\b/';

    // token reg exp v4: recognise whitespaces, brackets, colons and commas
    const REG_TOKEN = '/([ ]*,[ ]*)|([ ]*:[ ]*)|\[|\]|( )|(\.)|\(|\)|\b([A-Za-z_][A-Za-z0-9_]*\[\])|\b[A-Za-z_][A-Za-z0-9_]*(?![\[\]])\b/';
    const REG_T_FIELDLIST = '/^[A-Za-z_][A-Za-z0-9_]*\[\]$/';
    const REG_T_FIELD = '/^[A-Za-z_][A-Za-z0-9_]*$/';
    const REG_T_DOT = '/^\.$/';
    const REG_T_LPARENT = '/^\($/';
    const REG_T_RPARENT = '/^\)$/';
    const REG_T_LBRACKET = '/^\[$/';
    const REG_T_RBRACKET = '/^\]$/';
    const REG_T_WHITESPACE = '/^ $/'; // invalid symbol
    const REG_T_COLON = '/^[ ]*:[ ]*$/';
    const REG_T_COMMA = '/^[ ]*,[ ]*$/';
    const REG_T_FN_NAME = self::REG_T_FIELDLIST;
    const REG_T_FILTER = self::REG_T_FIELDLIST;

    static $tokenNames = [
        "n/a", "<EOF>", "T_FIELD", "T_FIELDLIST", "T_DOT",
        "T_LPARENT", "T_RPARENT", "T_AGGREGATE_FN_NAME",
        "T_WHITESPACE", "T_LBRACKET", "T_RBRACKET", "T_COLON",
        "T_FIELD_FILTER" , "T_COMMA", "T_LAST_FIELD" ];

    /**
     * Returns the next word is ahead current lexer pointer
     * @return string
     */
    protected function lookahead(): string
    {
        $word = self::LAMBDA;
        if (0 != preg_match(self::REG_TOKEN, $this->input, $matches, PREG_OFFSET_CAPTURE, $this->p + strlen($this->word))){
            $word = $matches[0][0];
        }
        return $word;
    }

    public function consume(): Lexer
    {
        if (0 != preg_match(self::REG_TOKEN, $this->input, $matches, PREG_OFFSET_CAPTURE, $this->p)){

            $this->word = $matches[0][0];
            $this->p = $matches[0][1] + strlen($this->word);
        } else {
            $this->word = self::LAMBDA;
        }

        return $this;
    }

    /**
     * Check if text is a plain text name
     * NOTE: An alias for isFieldOrFunction and isFilter
     * @param $text
     * @return bool
     */
    protected function isName($text){

        return 1 === preg_match(self::REG_T_FIELD, $text);
    }

    /**
     * Check if text is a field or Function name
     * @param $text
     * @return bool
     */
    protected function isFieldOrFunction($text){

        return 1 === preg_match(self::REG_T_FIELD, $text);
    }

    /**
     * Check if text is a filter. Alias of isFieldOrFunction since share same regexp
     * @param $text
     * @return bool
     */
    protected function isFilter($text){

        return $this->isFieldOrFunction($text);
    }

    /**
     * Check if text is a fieldList
     * @param $text
     * @return bool
     */
    protected function isFieldList($text){
        return 1 === preg_match(self::REG_T_FIELDLIST, $text);
    }

    /**
     * Check if text is a dot
     * @param $text
     * @return bool
     */
    protected function isDot($text){
        return 1 === preg_match(self::REG_T_DOT, $text);
    }

    /**
     * Check if text is a left parenthesis
     * @param $text
     * @return bool
     */
    protected function isLeftParenthesis($text){
        return 1 === preg_match(self::REG_T_LPARENT, $text);
    }

    /**
     * Check if text is a right parenthesis
     * @param $text
     * @return bool
     */
    protected function isRightParenthesis($text){
        return 1 === preg_match(self::REG_T_RPARENT, $text);
    }

    /**
     * Check if text is a left bracket
     * @param $text
     * @return bool
     */
    protected function isLeftBracket($text){
        return 1 === preg_match(self::REG_T_LBRACKET, $text);
    }

    /**
     * Check if text is a right bracket
     * @param $text
     * @return bool
     */
    protected function isRightBracket($text){
        return 1 === preg_match(self::REG_T_RBRACKET, $text);
    }

    /**
     * Chec if text is a whitespace
     * @param $text
     * @return bool
     */
    protected function isWhitespace($text){
        return 1 === preg_match(self::REG_T_WHITESPACE, $text);
    }

    /**
     * Check if text is a colon
     * @param $text
     * @return bool
     */
    protected function isColon($text){
        return 1 === preg_match(self::REG_T_COLON, $text);
    }

    /**
     * Check if text is a comma
     * @param $text
     * @return bool
     */
    protected function isComma($text){
        return 1 === preg_match(self::REG_T_COMMA, $text);
    }

    /**
     * Returns the next token identified by this lexer
     * @return Token
     * @throws \Exception
     */
    public function nextToken(): Token
    {
        // current word being processed
        $word = $this->word;
        if( $this->word != self::LAMBDA) {

            if ( $this->isFieldList($this->word) ) {
                $this->consume();
                return $this->returnToken(self::T_FIELDLIST, $word);
            } else if ( $this->isName($this->word)) {

                /**
                 * name can be diferents things in function of context
                 * If previous token was T_COLON or T_COMMA then name should be a filter
                 * Else if next word is '(' then name should be an aggregate function name
                 * Else it should be a field name
                 * If is a field name then we have to diferentiate if is the last or not
                 */

                $lastTokenType = $this->lastToken()->getType();
                $this->consume();

                if ( in_array($lastTokenType, [ self::T_COLON, self::T_COMMA])){

                    // trim T_COMMA and T_COLON since it could be surrounded by infinite white spaces
                    return $this->returnToken(self::T_FIELD_FILTER, trim($word));
                }

                // check next word. If is a parenthesis then the previous word refers to a function name
                // else to a field
                if (!$this->isLeftParenthesis($this->word)) {

                    if ( trim($this->word) === ":" || in_array($this->word, [self::LAMBDA, " ",")"])){

                        return $this->returnToken(self::T_LAST_FIELD, $word);
                    }else {
                        return $this->returnToken(self::T_FIELD, $word);
                    }

                } else {
                    return $this->returnToken(self::T_AGGREGATE_FN_NAME, $word);
                }
            } else if ($this->isLeftParenthesis($this->word)) {

                $this->consume();
                return $this->returnToken(self::T_LPARENT, $word);

            } else if ($this->isRightParenthesis($this->word)) {

                $this->consume();
                return $this->returnToken(self::T_RPARENT, $word);

            } else if ( $this->isDot($this->word)) {
                $this->consume();
                return $this->returnToken(self::T_DOT, $word);

            } else if ($this->isWhitespace($this->word)){
                $this->consume();
                return $this->returnToken(self::T_WHITESPACE, $word);

            } else if ($this->isLeftBracket($this->word)){
                $this->consume();
                return $this->returnToken(self::T_LBRACKET, $word);

            } else if ($this->isRightBracket($this->word)) {
                $this->consume();
                return $this->returnToken(self::T_RBRACKET, $word);
            } else if ($this->isColon($this->word)) {
                $this->consume();
                return $this->returnToken(self::T_COLON, $word);
            } else if ($this->isComma($this->word)) {
                $this->consume();
                return $this->returnToken(self::T_COMMA, $word);
            } else {
                throw new \Exception("Invalid symbol [" . $word . "]");
            }
        }
        return $this->returnToken(self::EOF_TYPE, self::$tokenNames[1]);

    }

    public function getTokenName($tokenType): string
    {
        return FieldLexer::$tokenNames[$tokenType];
    }
}