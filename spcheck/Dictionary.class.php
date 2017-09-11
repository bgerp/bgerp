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
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin, ceo';
    
    
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
        $this->FLD('pattern', 'varchar(128, ci)', 'caption=Шаблон->Дума');
        $this->FLD('isCorrect', 'enum(yes=Да, no=Не)', 'caption=Шаблон->Състояние, notNull');
        $this->FLD('lg', 'varchar(2)', 'caption=Език');
        $this->FLD('cnt', 'int', 'caption=Срещане, input=none, notNull');
        
        $this->setDbUnique('pattern, lg');
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
        $rec = self::fetch(array("#pattern = '[#1#]' AND #state = 'active' AND #lg = '[#2#]'", $word, $lg));
        
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
            
            if ($check) {
                if (preg_match('/^(\s*\<)a\s+.*?href\s*=\s*.*?(\>\s*)$/i', $lastTag)) {
                    $check = FALSE;
                }
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
	 * Екшън за промяна на състоянието
	 * 
	 * @return Redirect
	 */
	function act_ChangeCorrect()
	{
	    $id = Request::get('id', 'int');
	    
	    expect($id);
	    
	    expect($rec = $this->fetch($id));
	    
	    $this->requireRightFor('edit', $rec);
	    
	    if ($rec->isCorrect == 'no') {
	        $rec->isCorrect = 'yes';
	    } else {
	        $rec->isCorrect = 'no';
	    }
	    $this->save($rec, 'isCorrect');
	    
	    $this->logWrite('Смяна на коректност', $rec->id);
	    
	    $retUrl = getRetUrl();
	    
	    if (empty($retUrl)) {
	        $retUrl = array($this, 'list');
	    }
	    
	    return new Redirect($retUrl);
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
        $data->listFilter->showFields = 'lg, search';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->view = 'horizontal';
        
        $data->query->orderBy('cnt', 'DESC');
        $data->query->orderBy('createdOn', 'DESC');
        
        // Добавяме всички езици за които има запис в масива
        $cQuery = self::getQuery();
        $cQuery->groupBy('lg');
        $cQuery->orderBy('createdOn');
        $langArr = array('' => '');
        while ($rec = $cQuery->fetch()) {
            if (isset($langArr[$rec->lg])) continue;
            if (!$rec->lg) continue;
            $langArr[$rec->lg] = $rec->lg;
        }
        
        if ($langArr) {
            $data->listFilter->setOptions('lg', $langArr);
        }
        
        $filterRec = $data->listFilter->input('lg, search');
        
        if ($filterRec->lg) {
            $data->query->where(array("#lg = '[#1#]'", $filterRec->lg));
        }
    }
		
	
	/**
	 * 
	 * Добавя бутон на файловете, които са за клишета
	 */
	static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
	    if ($mvc->haveRightFor('edit', $rec)) {
	        if ($rec->isCorrect == 'yes') {
	            $btnName = 'Коректен';
	            $efIcon = 'img/16/accept.png';
	            $title = 'Отбелязване на думата като некоректна';
	        } else {
	            $btnName = 'Некоректен';
	            $efIcon = 'img/16/red-back.png';
	            $title = 'Отбелязване на думата като коректна';
	        }
	        
	        $row->isCorrect = HT::createBtn($btnName, array($mvc, 'changeCorrect', $rec->id, 'ret_url' => TRUE), FALSE, FALSE, "ef_icon={$efIcon}, title={$title}");
	    }
	}
	
	
	/**
	 * Проверява коректността на думите в речника и ги добавя в модела
	 */
    static function cron_AddWordsToDict()
    {
        $cQuery = doc_Containers::getQuery();
        $before = dt::subtractSecs(3600);
        $cQuery->where("#createdOn >= '{$before}'");
        
        $cQuery->in('createdBy', core_Users::getByRole('powerUser'));
        
        $cQuery->orderBy('modifiedOn', 'DESC');
        
        while ($cRec = $cQuery->fetch()) {
            try {
                $doc = doc_Containers::getDocument($cRec->id);
                $text = $doc->getInlineDocumentBody('plain');
            } catch (core_exception_Expect $e) {
                reportException($e);
                
                continue;
            }
            
            $text = strip_tags($text);
            
            $text = preg_replace('/[^\p{L}]/u', ' ', $text); // Махаме символите
            $text = preg_replace('/(\p{Lu}+\p{L}*\s)/u', ' ', $text); // Махаме думите с главна буква
            $text = preg_replace('/\s+/u', ' ', $text);
            
            $text = trim($text);
            
            $textArr = explode(' ', $text);
            
            $systemLg = core_Lg::getDefaultLang();
            
            foreach ($textArr as $str) {
                $str = trim($str);
                
                if (!$str) continue;
                
                if (mb_strlen($str) <= self::$minLen) continue;
                
                if (i18n_Charset::is7Bit($str)) {
                    $lg = 'en';
                } else {
                    $lg = $systemLg;
                }
                
                if (!self::checkWord($str, $lg)) {
                    $rec = self::fetch(array("#pattern = '[#1#]' AND #lg = '[#2#]'", $str, $lg));
                    
                    if (!$rec) {
                        $rec = new stdClass();
                        $rec->lg = $lg;
                        $rec->isCorrect = 'no';
                        $rec->pattern = $str;
                        $rec->cnt = 1;
                        $saveF = NULL;
                    } else {
                        $rec->cnt++;
                        $saveF = 'cnt';
                    }
                    
                    self::save($rec, $saveF);
                }
            }
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     * @param stdClass $res
     * @param array $fields
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec, $fields = array())
    {
        if (!$rec->cnt) {
            $rec->cnt = 1;
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     * 
     * @param spcheck_Dictionary $mvc
     * @param NULL|string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'addWordsToDict';
        $rec->description = 'Добавяне на думите в речника';
        $rec->controller = $mvc->className;
        $rec->action = 'addWordsToDict';
        $rec->period = 60;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
    }
}
