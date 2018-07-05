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
class type_Text extends core_Type
{
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'text';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 65536;
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (Mode::is('screenMode', 'narrow')) {
            setIfnot($attr['rows'], $this->params['rows'], 5);
        } else {
            setIfnot($attr['rows'], $this->params['rows'], 10);
        }
        
        $attr['class'] .= ' w100';

        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        setIfNot($size, $this->params['size'], $this->params[0], $this->dbFieldLen);
        
        if (!$this->params['noTrim']) {
            $attr['onblur'] .= 'this.value = this.value.trim();';
        }
        
        if ($size > 0) {
            $attr['onblur'] .= "colorByLen(this, {$size}, true); if(this.value.length > {$size}) alert('" .
                 tr('Въведената стойност е дълга') . " ' + this.value.length + ' " . tr('символа, което е над допустимите') . " ${size} " . tr('символа') . "');";
            $attr['onkeyup'] .= "colorByLen(this, {$size});";
        }
        
        $attr['name'] = $name;

        return ht::createElement('textarea', $attr, $value, true);
    }
    
    
    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        // Умножаваме по 2 размера, заради UTF-8, който представя кирилицата с 2 байта
        $size = 2 * $this->getDbFieldSize();
        
        if (!$size) {
            $this->dbFieldType = 'text';
        } elseif ($size < 256) {
            $this->dbFieldType = 'tinytext';
        } elseif ($size < 65536) {
            $this->dbFieldType = 'text';
        } elseif ($size < 16777216) {
            $this->dbFieldType = 'mediumtext';
        } else {
            $this->dbFieldType = 'longtext';
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Връща стойността на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    public function toVerbal_($value)
    {
        if (!Mode::is('text', 'plain')) {
            $value = str_replace(array('&', '<', "\n"), array('&amp;', '&lt;', '<br>'), $value) ;
        }
        
        return $value;
    }
    
    
    /**
     * Разбива произволно дълъг текст на линии с определена максимална дължина
     *
     * @param  string $text      текста за разбиване
     * @param  int    $width     максимален брой символи на линия
     * @param  int    $firstLine отместване на първата линия в текста. Останалите линии ще бъдат
     *                           попълнени отпред с интервали за да се подравнят отляво с първата.
     * @return string
     */
    public static function formatTextBlock($text, $width, $firstLine)
    {
        $lines = explode("\n", $text);
        $splitLines = array();
        
        foreach ($lines as $line) {
            $splitLines = array_merge($splitLines, static::splitToLines(trim($line), $width));
        }
        
        $splitLinesCnt = count($splitLines);
        
        if ($splitLinesCnt > 1) {
            $padStr = str_repeat(' ', $firstLine);
            
            for ($i = 1; $i < $splitLinesCnt; $i++) {
                $splitLines[$i] = $padStr . $splitLines[$i];
            }
        }
        
        return implode("\n", $splitLines);
    }
    
    
    /**
     * Разбива текст на линии с определена макс. дължина на линията
     *
     * @param  string $text
     * @param  int    $width макс. брой символи на линия
     * @return array
     */
    public static function splitToLines($text, $width)
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
     * @param  string $text  извлеченото парче се отрязва от текста.
     * @param  int    $width максимална дължина на парчето
     * @return string
     */
    public static function getChunk(&$text, $width)
    {
        $chunk = mb_substr($text, 0, $width);
        $text = mb_substr($text, $width);
        
        return $chunk;
    }
    
    
    /**
     * Връща масив със всички предложения за този списък
     */
    public function getSuggestions()
    {
        if (!$this->suggestions) {
            $this->prepareSuggestions();
        }

        return $this->suggestions;
    }
    
    
    /**
     * Подготвя предложенията за списъка
     */
    private function prepareSuggestions()
    {
        $this->suggestions = arr::make($this->suggestions);
        
        if ($this->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this)) === false) {
            
            return ;
        }
        
        // Добавяме
        $suggestionsStr = str_replace('|', ',', $this->params['suggestions']);
        $this->suggestions = arr::make($suggestionsStr, true);
        
        $this->invoke('AfterPrepareSuggestions', array(&$this->suggestions, $this));
    }
}
