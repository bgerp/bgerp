<?php



/**
 * Клас  'core_NT' (New Templates)
 *
 * @title     Нова система от шаблони
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_NT extends core_BaseClass
{
    
    
    /**
     * Съдържание на шаблона
     */
    private $content;
    

    /**
     * Масив с имена и хеш-стойности на блокове
     */
    private $blocks = array();
    

    /**
     * Флаг за готовност
     */
    private $ready;
 
    
    /**
     * Конструктор на шаблона
     */
    function __construct($str)
    {
        $this->content = $str;
        $this->prepareContent();
   
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдери с имена [#1#], [#2#] ...
        $args = func_get_args();
        
        if(count($args) > 1) {
            array_unshift($args);
            $this->render($args, NULL, 'replaceArray');
        }

        $this->ready = TRUE;
    }


    /**
     * Подготвя шаблона за заместване
     */
    function prepareContent()
    {
        // Определя съдържанието на блоковете
        $blocks = preg_match_all("#\\<\\!([a-z0-9\\_]{0,32})\>#si", $this->content, $matches);  
        if(is_array($matches[1])) {
            foreach($matches[1] as $block) {
                expect(!isset($this->blocks[$block]), $block, $this);
                $this->blocks[$block] = $this->getBlock($block);
            }
        }
    }


    /**
     * Замества данните в шаблона
     */
    public function render($data, $place = NULL, $mode = 'replace')
    {   
        // Само един път може да се прави заместване;
        expect($this->ready);

        if(is_array($data) && empty($place) && $mode == 'replace') { 
            foreach($data as $placeHolder => $value) {
                if($value !== NULL) {
                    $fromTo[$this->toPlace($placeHolder)] = $this->escape($value);
                }
            } 
            $this->content = strtr($this->content, $fromTo);
        } else {
            // Все още не е реализирано
            error('Все още не е реализирано', $this, $data, $place, $mode);
        }

        $this->removeUnchangedBlocks();
        $this->removePlaces();
        $this->ready = FALSE;

        return $this->content;
    }


    /**
     * Връща  съдържанието на блока
     */
    function getBlock($block)
    {
        $openTag = $this->getOpenTag($block);
        $closeTag = $this->getCloseTag($block);
        $startPos = strpos($this->content, $openTag) + strlen($openTag);
        expect($stopPos  = strpos($this->content, $closeTag), $closeTag, $this);
        $res = substr($this->content, $startPos, $stopPos - $startPos);

        return $res;
    }
    
    
    /**
     * Добава обграждащите символи към даден стринг,
     * за да се получи означение на плейсхолдър
     */
    static function toPlace($name)
    {
        
        return "[#{$name}#]";
    }
    
    
    /**
     * Превръща име към означение за начало на блок
     */
    function getOpenTag($blockName)
    {
        return "<!{$blockName}>";
    }
    
    
    /**
     * Превръща име към означение за край на блок
     */
    function getCloseTag($blockName)
    {
        return "</!{$blockName}>";
    }
    
    
    /**
     * Премахва непопълнените блокове
     */
    function removeUnchangedBlocks()
    {
        foreach($this->blocks as $block => $content) {
            $openTag = $this->getOpenTag($block);
            $closeTag = $this->getCloseTag($block);
            if($content == $this->getBlock($block)) {
                $content =  $openTag . $content . $closeTag;
                $this->content = str_replace($content, '', $this->content);
            } else {
                $this->content = str_replace(array($openTag, $closeTag), array('', ''), $this->content);
            }
        }
    }


    /**
     * Конвертира към стринг
     */
    function toString()
    {
        return $this->content;
    }


    /**
     * Премахва плейсхолдерите
     */
    function removePlaces()
    {
        $placeHolders = $this->getPlaceholders();
        $fromTo = array();
        foreach($placeHolders as $ph) {
            $fromTo[$ph] = '';
        }
        $this->content = strtr($this->content, $fromTo);
    }


    /**
     * Замества контролните символи в текста (начало на плейсхолдер)
     * с други символи, които не могат да се разчетат като контролни
     */
    static function escape($str)
    {
        expect(!($str instanceof stdClass), $str);
        return str_replace('[#', '&#91;#', $str);
    }
    

    static function unEscape($str)
    {
    	expect(!($str instanceof stdClass), $str);
        return str_replace('&#91;#', '[#', $str);
    }
    
    
    /**
     * Връща плейсхолдерите на шаблона
     */
    function getPlaceholders()
    {
        preg_match_all('/\[#([a-zA-Z0-9_:]{1,})#\]/', $this->content, $matches);
        
        return $matches[0];
    }
    
}