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
    public $loadList = 'spcheck_Wrapper, plg_RowTools2, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'pattern';
    
    
    /**
     * Минимална дължина, под която няма да се проверяват
     */
    protected static $minLen = 1;
    
    
    /**
     * Думи, които ще се игнорират и няма да се проверяват
     * 
     * [0] - думи с цифри
     * [1] - думи с две или повече главни букви
     * [2] - думи, които след малка буква имат главна
     * [3] - думи с nbsp
     * [4] - думи с lt
     * [4] - думи с @
     */
    protected static $ignorePatternArr = array('/[0-9]/', '/\p{Lu}{2}/u', '/\p{Ll}+\p{Lu}/u', '/nbsp/i', '/^lt$/i', '/\@/');
    
    
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
            $pspellLink = @pspell_new($lg);
            
            if (!$pspellLink) {
                self::logWarning('Не е инсталиран речник за езика - ' . $lg);
                
                return TRUE;
            }
            
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
        $string = $html;
        
        if ($html instanceof core_ET) {
            $string = $html->getContent(NULL, 'CONTENT', FALSE, FALSE);
        }
        
        if (isset($lg)) {
            core_Lg::push($lg);
        }
        
        str::parseWords($string, $out, array(get_called_class(), 'getWord'));
        
        if (isset($lg)) {
            core_Lg::pop();
        }
        
        if ($html instanceof core_ET) {
            $html->setContent($out);
        } else {
            $html = $out;
        }
        
        return $html;
    }
    
    
    /**
     * Колбек функция, която ако е необходимо проверява и маркира стринга
     * 
     * @param string $out
     * @param integer $len
     * @param string $lastTag
     */
    public static function getWord(&$out, $len, $lastTag)
    {
        $w = substr($out, $len);
        
        $check = TRUE;
        
        if (mb_strlen($w) <= self::$minLen) {
            $check = FALSE;
        }
        
        // Ако в обграждащия таг има class=no-spell-check|linkWithIcon
        if ($check && $lastTag) {
            if (preg_match('/class\s*=\s*("|\')\s*(.+?|"|\')(no-spell-check|linkWithIcon)/i', $lastTag)) {
                $check = FALSE;
            }
        }
        
        // Игнорираме думи, които отговарят на шаблоните ни
        if ($check) {
            foreach (self::$ignorePatternArr as $pattern) {
                if (preg_match($pattern, $w)) {
                    $check = FALSE;
                    break;
                }
            }
        }
        
        if ($check) {
            // Опитваме се да не проверяваме имената
            if (preg_match('/^\p{Lu}/u', $w)) {
                $l = $len-1;
                while ($l) {
                    $p = $l;
                    $b = str::nextChar($out, $p);
                    $l--;
                    
                    $ord = ord($b);
                    if ($ord > 127) {
                        
                        $check = FALSE;
                        
                        break;
                    }
                    
                    if ($b == ' ') continue;
                    
                    if ($b == "\n") break;
                    
                    if (($b != '.') && ($b != '!') && ($b != '?')) {
                        $check = FALSE;
                    }
                    
                    break;
                }
            }
            
            // Ако думата трябва да се проверява
            if ($check && !self::checkWord($w)) {
                $w = "<span class='err-word'>" . $w . '</span>';
            }
        }
        
        $out = substr($out, 0, $len) . $w;
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
