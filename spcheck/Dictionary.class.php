<?php 


/**
 * Модел за думи/шаблони, които ще се използват за правопис
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spcheck_Dictionary extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Речник';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Кой може да променя състоянието на документите
     * 
     * @see plg_State2
     */
    public $canChangestate = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'spcheck_Wrapper, plg_RowTools, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'pattern';
    
    
    /**
     * Масив със стрингове, които няма да се проверяват
     */
    protected static $ignoreStrArr = array('div', 'span', 'src', 'li', 'img', 'href', 'replace_string', 'nbsp');
    
    
    /**
     * Минимална дължина, под която няма да се проверяват
     */
    protected static $minLen = 1;
    
    
    /**
     * Масив с шаблони, които ще се заместят и няма да се проверяват
     * [0] => линкове
     * [1] => думи само с главни букви и цифри
     * [2] => евристика за определяне на имена
     */
    protected static $maskPattern = array("/\<a.+?(<\/a>)/iu", "/[^\p{L}0-9][\p{Lu}0-9]{2,}([^\p{L}0-9])/u", "/([^\S\x0a\x0d]|\,|\:|\;|\-){1}((\p{Lu}+)|(\p{Lu}+\p{L}+))(\p{L})*/u");
    
    
    /**
     * Масив, в който ще се добавят заместените стрингове
     */
    protected static $replacedArr = array();
    
    
    /**
     * Шаблон, който ще се използва за заместване на стрингове
     */
    protected static $replaceStr = '__#1replace_string1#__';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('pattern', 'varchar(128, ci)', 'caption=Шаблон');
        $this->FLD('isCorrect', 'enum(yes=Да, no=Не)', 'caption=Коректност, notNull');
        
        $this->setDbUnique('pattern');
    }
    
    
    /**
     * Проверява дали подадена дума е коректна
     * 
     * @param string $word
     * @param string|NULL $lg
     * 
     * @return boolean
     */
    public static function checkWord($word, $lg = NULL)
    {
        static $wArr = array();
        
        if (!isset($lg)) {
            $lg = core_Lg::getCurrent();
        }
        
        $key = $word . '|' . $lg;
        
        if (isset($wArr[$key])) return $wArr[$key];
        
        // Ако е зададено в модела
        $rec = self::fetch(array("#pattern = '[#1#]' AND #state = 'active'", $word));
        
        if ($rec) {
            if ($rec->isCorrect == 'no') {
                $wArr[$key] = FALSE;
            } else {
                $wArr[$key] = TRUE;
            }
        } else {
            $pspellLink = pspell_new($lg);
            if (pspell_check($pspellLink, $word)) {
                $wArr[$key] = TRUE;
            } else {
                $wArr[$key] = FALSE;
            }
        }
        
        return $wArr[$key];
    }
    
    
    /**
     * Обхожда всички думи и маркира грешните
     * 
     * @param string|core_Et $html
     * @param string|NULL $lg
     * 
     * @return string
     */
    public static function highliteWrongWord($html, $lg = NULL)
    {
        $strippedHtml = strip_tags($html);
        
        $htmlArr = preg_split("/\s/", $strippedHtml);
        
        $checkedArr = array();
        
        $errWordArr = array();
        
        foreach ($htmlArr as $w) {
            $w = trim($w);
            
            if (!$w) continue;
            
            // Премахваме всички символи освен текст и числа
            $cW = preg_replace('/[^\p{L}0-9]+/iu', ' ', $w);
            
            $cW = trim($cW);
            
            $cWArr = explode(' ', $cW);
            foreach ($cWArr as $cWF) {
                // Ако в тескта има числа, не се минава през проверка
                if (preg_match('/[0-9]+/', $cWF)) continue;
                
                $cWF = trim($cWF);
                
                if (!$cWF) continue;
                
                if ($checkedArr[$cWF]) continue;
                
                if (self::$minLen && (mb_strlen($cWF) <= self::$minLen)) continue;
                
                if (in_array($cWF, self::$ignoreStrArr)) continue ;
                
                $checkedArr[$cWF] = TRUE;
                
                if (self::checkWord($cWF, $lg)) continue;
                
                $errWordArr[$cWF] = $cWF;
            }
        }
        
        // Ако има думи, които сме маркирали като грешни
        if (!empty($errWordArr)) {
            if ($html instanceof core_ET) {
                $content = $html->getContent(NULL, 'CONTENT', FALSE, FALSE);
            } else {
                $content = $html;
            }
            
            // Маскираме стринговете, които не трябва да се заместват
            $content = self::maskString($content);
            
            // Заместваме вскички маркирани думи със специален стринг
            foreach ($errWordArr as $errW) {
                $content = preg_replace("/(?<=([^\p{L}0-9]))(" . preg_quote($errW, '/') . "){1}(?=([^\p{L}0-9]))/iu", "<span class='err-word'>$2</span>", $content);
            }
            
            // Връщаме маскираните стрингове
            $content = self::unmaskString($content);
            
            if ($html instanceof core_ET) {
                $html->setContent($content);
            } else {
                $html = $content;
            }
        }
        
        return $html;
    }
    
    
    /**
     * Замества стринга с уникален стринг
     * 
     * @param string $content
     * 
     * @return string
     */
    protected static function maskString($content)
    {
        foreach (self::$maskPattern as $pattern) {
            
            $content = preg_replace_callback($pattern, 'self::replacePattern', $content);
        }
        
        return $content;
    }
    
    
    /**
     * Замества уникалния стринг със стойността в масива
     * 
     * @param string $content
     * 
     * @return string
     */
    protected static function unmaskString($content)
    {
        self::$replacedArr = array_reverse(self::$replacedArr);
        
        foreach (self::$replacedArr as $key => $str) {
            $content = str_replace($key, $str, $content);
        }
        
        self::$replacedArr = array();
        
        return $content;
    }
    
    
    /**
     * Функция, която се вика от preg_replace_callback и замества мачнатия стринг с уникален такъв
     * 
     * @param array $matches
     * 
     * @return string
     */
    protected static function replacePattern($matches)
    {
        static $cnt = 0;
        
        $rStr = ' ' . self::$replaceStr . $cnt++ . ' ';
        
        self::$replacedArr[$rStr] = $matches[0];
        
        return $rStr;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param spcheck_Dictionary $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec) {
            if ($action == 'edit' || $action == 'delete') {
                if ($rec->createdBy != $userId) {
                    if (!haveRole('admin, ceo')) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     * 
     * @param spcheck_Dictionary $mvc
     * @param stdObject $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->view = 'horizontal';
        
        $data->query->orderBy('createdOn', 'DESC');
    }
}
