<?php



/**
 * Клас  'type_Text' - Тип за дълъг текст
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Text extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'text';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 65536;
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) {
            setIfnot($attr['rows'], $this->params['rows'], 5);
            setIfnot($attr['cols'], $this->params['cols'], 20);
        } else {
            setIfnot($attr['rows'], $this->params['rows'], 10);
            setIfnot($attr['cols'], $this->params['cols'], 30);
        }
        
        return ht::createTextArea($name, $value, $attr);
    }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    function getMysqlAttr()
    {
        // Умножаваме по 2 размера, заради UTF-8, който представя кирилицата с 2 байта
        $size = 2 * ($this->params['size'] ? $this->params['size'] : $this->params[0]);
        
        if(!$size) {
            $this->dbFieldType = "text";
        } elseif($size <256) {
            $this->dbFieldType = "tinytext";
        } elseif($size <65536) {
            $this->dbFieldType = "text";
        } elseif($size <16777216) {
            $this->dbFieldType = "mediumtext";
        } else {
            $this->dbFieldType = "longtext";
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Връща стойността на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    function toVerbal_($value)
    {
        if (!Mode::is('text', 'plain')) {
            $value = str_replace(array('&', '<', "\n"), array('&amp;', '&lt;', '<br>'), $value) ;
        }
        
        return $value;
    }
    
    
    /**
     * Разбива произволно дълъг текст на линии с определена максимална дължина
     *
     * @param string $text текста за разбиване
     * @param int $width максимален брой символи на линия
     * @param int $firstLine отместване на първата линия в текста. Останалите линии ще бъдат
     * попълнени отпред с интервали за да се подравнят отляво с първата.
     * @return string
     */
    static function formatTextBlock($text, $width, $firstLine)
    {
        $lines = explode("\n", $text);
        $splitLines = array();
        
        foreach ($lines as $line) {
            $splitLines = array_merge($splitLines, static::splitToLines(trim($line), $width));
        }
        
        if (count($splitLines) > 1) {
            $padStr = str_repeat(' ', $firstLine);
            
            for ($i = 1; $i < count($splitLines); $i++) {
                $splitLines[$i] = $padStr . $splitLines[$i];
            }
        }
        
        return implode("\n", $splitLines);
    }
    
    
    /**
     * Разбива текст на линии с определена макс. дължина на линията
     *
     * @param string $text
     * @param int $width макс. брой символи на линия
     * @return array
     */
    static function splitToLines($text, $width)
    {
        $lines = array();
        
        while (!empty($text)) {
            $line = static::getChunk($text, $width);
            $line .= str_repeat(' ', $width - mb_strlen($line));
            $lines[] = $line;
        }
        
        return $lines;
    }
    
    
    /**
     * Извлича парче от началото на текст със зададена макс. дължина на парчето
     *
     * @TODO да се ограничи на кои символи може да завършва парчето, за да не реже думите по
     * средата.
     *
     * @param string $text извлеченото парче се отрязва от текста.
     * @param int $width максимална дължина на парчето
     * @return string
     */
    static function getChunk(&$text, $width) {
        $chunk = mb_substr($text, 0, $width);
        $text = mb_substr($text, $width);
        
        return $chunk;
    }
}