<?php


/**
 * Интерфейс на какви езици поддържа системата?
 */
defIfNot('EF_LANGUAGES', 'bg=Български,en=Английски');


/**
 * Клас 'core_Lg' - Мениджър за многоезичен превод на интерфейса
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Lg extends core_Manager
{
    
    
    /**
     * Речник
     */
    var $dict = array();
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Превод на интерфейса';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Превод на интерфейса";
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'translator,admin';
    
    
    /**
     * Кой може да записва?
     */
    var $canEditsysdata = 'translator,admin';
    
    
    /**
     * Кой може да записва?
     */
    var $canWrite = 'translator,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_Created,plg_SystemWrapper,plg_RowTools2';
    
    
    /**
     * 
     */
    protected static $keyStringLen = 32;
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('lg', 'varchar(2)', 'caption=Език,export,mandatory,optionsFunc=core_Lg::getLangOptions, suggestions=');
        $this->FLD('kstring', 'varchar(' . static::$keyStringLen . ')', 'caption=Стринг,export, width=100%, mandatory');
        $this->FLD('translated', 'text',  'caption=Превод,export, width=100%, class=translated, mandatory');

        $this->setDbUnique('kstring,lg');
    }
    
    
    /**
     * Екшън за задаване на текущия език на вътрешната част
     */
    function act_Set()
    {
        $lg = Request::get('lg');
        
        $this->set($lg);
        
        followRetUrl();
    }
    
    
    /**
     * Привежда модела за превод в начално състояние
     */
    function act_ResetDB()
    {
        requireRole('debug');

        bgerp_data_Translations::loadData('everytime');

        redirect(array($this));
    }


    /**
     * Експортира непопълнените данни, за съответния език
     */
    function act_ExportCSV()
    {
        requireRole('debug');

        $lg = Request::get('lg');
        if($lg == 'bg') {
            $lg = 'en';
        }
        
        $res = array();
        $query = self::getQuery();
 
        while($rec = $query->fetch()) {
            if(($rec->lg == $lg) && !preg_match("/[а-я]/iu", $rec->translated)) {
                $res[$rec->kstring] = $rec;
                $res[$rec->kstring]->remove = TRUE;
                continue;
            }
            if(isset($res[$rec->kstring])) continue;
            $res[$rec->kstring] = $rec;
        }

        foreach($res as $key => $rec) {
            if($rec->remove) {
                unset($res[$key]);
            } else {
                $res[$key]->lg = $lg;
            }
        }

        $csv = csv_Lib::createCsv($res, $this, array('lg'=>'lg', 'kstring'=>'kstring', 'translated'=>'translated'));
        
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename=bgERP_translation.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	 
    	echo $csv;
    
    	shutdown();
    }

    /**
     * Задава за текущия език на интерфейса, валиден за сесията
     */
    static function set($lg, $force = TRUE)
    {   
        $langArr = arr::make(EF_LANGUAGES, TRUE);

        if($langArr[$lg] && ($force || !Mode::get('lg'))) {
            Mode::setPermanent('lg', $lg);
        }
    }
    
    
    /**
     * Временно (до извикването на self::pop()) променя текущия език
     */
    static function push($lg)
    {
        Mode::push('lg', $lg);
    }
    
    
    /**
     * Връща старата стойност на текущия език
     */
    static function pop()
    {
        Mode::pop('lg');
    }
    
    
    /**
     * Превежда зададения ключов стринг
     */
    function translate($kstring, $key = FALSE, $lg = NULL)
    {
        // Празните стрингове и обектите не се превеждат
        if (is_object($kstring) || !trim($kstring)) return $kstring;
        
        // Ако не е зададен език, превеждаме на текущия
        if (!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        $this->prepareDictForLg($lg);


        if (!$key) {
            // Разбиваме стринга на участъци, който са разделени със символа '|'
            $strArr = explode('|', $kstring);
            
            if (count($strArr) > 1) {
                
                $translated = array();
                
                // Ако последната или първата фраза са празни - махаме ги
                if($strArr[count($strArr)-1] == '') {
                    unset($strArr[count($strArr)-1]);
                }
                
                if($strArr[0] == '') {
                    unset($strArr[0]);
                }
                
                // Подготвяме масива с английските думи
                $this->prepareDictForLg('en');
                
                // Обикаляме и добавяме в речника фразите на английски и фразите, които не се превеждат
                foreach ($strArr as $i => $phrase) {
                    if ($phrase === '' && $i >= 1) {
                        $pKey = static::prepareKey($strArr[$i-1]);
                        $this->dict['en'][$pKey] = $strArr[$i+1];
                        if ($lg != 'en' && !isset($this->dict[$lg][$pKey])) {
                            $this->dict[$lg][$pKey] = $strArr[$i-1];
                        }
                        unset($strArr[$i], $strArr[$i+1]);
                        continue;
                    }
                }
                
                foreach ($strArr as $i => $phrase) {
                    
                    if($phrase{0} === '*') {
                        $translated[] = substr($phrase, 1);
                        continue;
                    }
           
                    $ascii = (mb_detect_encoding($phrase, 'ASCII', TRUE) == 'ASCII');

                    if($ascii && (!preg_match("/[a-z]/i", $phrase) || $lg != 'en') ) {
                        $translated[] = $phrase;
                    }  else {
                        $translated[] = $this->translate($phrase);
                    }
                }
                
                return implode('', $translated);
            }
            
            $key = $kstring;
        }
        
        // Заместваме празните редове, за да може да превеждаме и multiline текстове
        $key = str_ireplace(array("\n\r", "\r\n", "\n", "\r"), '<br />', $key);
        
        $key = static::prepareKey($key);
        
        // Ако имаме превода в речника, го връщаме
        if (isset($this->dict[$lg][$key])) {
            $res = $this->dict[$lg][$key];
        } elseif(is_array($this->dict[$lg]) && in_array($kstring, $this->dict[$lg])) {
            $res = $kstring;
        } else {
            // Ако и в базата нямаме превода, тогава приемаме, 
            // че превода не променя ключовия стринг
            if (!$translated) {
                $translated = $kstring;
            }
            
            $rec = new stdClass();
            $rec->kstring = $key;
            $rec->translated = $translated;
            $rec->lg = $lg;
            
            // Само потребители с определена роля могат да добавят (автоматично) в превода
            if (haveRole('translate') || !haveRole('powerUser')) {
                $this->save($rec, NULL, 'IGNORE');
            }
            
            // Записваме в кеш-масива
            $this->dict[$lg][$key] = type_Varchar::escape($rec->translated);
            
            $res = $this->dict[$lg][$key];
        }
        
        // Ако превеждаме на английски и в крайния текст има все-пак думи с кирилски символи,
        // опитваме се да преведем фразите // /\b([а-яА-Я ]*[а-яА-Я][а-яА-Я ]*)\b/u
        
        return $res;
    }
    
    function act_Test()
    {
        bp(tr("|*<small>|Произведено|*</small>"));
    }

    /**
     * Подготвяме думите в речника
     * 
     * @param string|NULL $lg
     */
    protected function prepareDictForLg($lg = NULL)
    {
        if (!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        if (!is_array($this->dict[$lg]) || empty($this->dict[$lg])) {
            $this->dict[$lg] = core_Cache::get('translationLG', $lg, 2 * 60 * 24, array('core_Lg'));
        
            if(!$this->dict[$lg]) {
                $query = self::getQuery();
        
                while($rec = $query->fetch(array("#lg = '[#1#]'", $lg))) {
                    $this->dict[$lg][$rec->kstring] = type_Varchar::escape($rec->translated);
                }
                core_Cache::set('translationLG', $lg, $this->dict[$lg], 2 * 60 * 24, array('core_Lg'));
            }
        }
    }
    
    
    /**
     * Връща текущия език
     */
    static function getCurrent()
    {
        $lg = Mode::get('lg');
        
        if (!$lg) {
            $lg = self::getDefaultLang();
        }
        
        return $lg;
    }


    /**
     * Връща езика по подразбиране за системата
     */
    static function getDefaultLang()
    {
        $conf = core_Packs::getConfig('core');
        $lg = $conf->EF_DEFAULT_LANGUAGE;
        
        return $lg;
    }


    /**
     * Изтрива кеша при ъпдейт
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        core_Cache::remove('translation', $rec->lg);  
        core_Cache::remove('Menu', "menuObj_{$rec->lg}");
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    static function on_AfterPrepareListFilter($invoker, &$data)
    {
        // Подрежда словосъчетанията по обратен на постъпването им ред
        $data->query->orderBy(array(
                'id' => 'DESC'
            ));

        $langArr = arr::make(EF_LANGUAGES, TRUE);

        // Превод
        foreach($langArr as $lg => &$lgName) {
            $lgName = tr($lgName);
        }
        
        // Добавяме всички езици за които има запис в масива
        $cQuery = core_Lg::getQuery();
        $cQuery->groupBy('lg');
        $cQuery->orderBy('createdOn');
        while ($rec = $cQuery->fetch()) {
            if (isset($langArr[$rec->lg])) continue;
            if (!$rec->lg) continue;
            $langArr[$rec->lg] = $rec->lg;
        }
        
  		$data->listFilter->view = 'horizontal';
  		$data->listFilter->FNC('filter', 'varchar', 'caption=Филтър,input');          
        $data->listFilter->setOptions('lg', $langArr);
        
        $data->listFilter->showFields = 'filter,lg';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $filterRec = $data->listFilter->input();
      
        if(!$filterRec->lg) {
        	$data->listFilter->rec->lg = $filterRec->lg = core_Lg::getCurrent();
        }
        
        if ($filterRec) {
            if ($filterRec->lg) {
                $data->query->where("#lg = '{$filterRec->lg}'");
            }
            
            if ($filterRec->filter) {
                $data->query->where(array(
                        "#kstring LIKE '%[#1#]%'",
                        $filterRec->filter
                    ));
            }
        }
        
    }
    
    
    /**
     * Връща последователност от хипервръзки (<а>) за установяване
     * на езиците, посочени в аргумента array ('language' => 'title')
     */
    static function getLink($lgArr)
    {
        $tpl = new ET();
        
        foreach ($lgArr as $lg => $title) {
            if (core_Lg::getCurrent() != $lg) {
                if ($div)
                $tpl->append(' | ');
                $tpl->append(ht::createLink($title, array(
                            'core_Lg',
                            'Set',
                            'lg' => $lg,
                            'ret_url' => TRUE
                        )));
                $div = TRUE;
            }
        }
        
        return $tpl;
    }


    /**
     * Изпълнява се след подготовка на листовия тулбар
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if(haveRole('debug')) {
            $data->toolbar->addBtn('Reset', array($mvc, 'resetDB'));
            $lg = $data->listFilter->rec->lg;
            setIfNot($lg, 'en');
            $data->toolbar->addBtn('Export CSV', array($mvc, 'exportCSV', 'lg' => $lg));
        }
    }
    
    
    /**
     * Изпълнява се след подготовка на формата за въвеждане
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, &$data)
    {
        // Ако едитваме
        if ($data->form->rec->id) {
            
            // Полето за език и стринг да не могат да се променят
            $data->form->setReadOnly('lg');
            $data->form->setReadOnly('kstring');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            
            // Подготвяме стринга
            $form->rec->kstring = static::prepareKey($form->rec->kstring);
        }
    }
    
    
    /**
     * Подготвя стринга за ключа
     * 
     * @param string $key
     * 
     * @return string
     */
    static function prepareKey($key)
    {
        $key = str::convertToFixedKey($key, self::$keyStringLen, 4);
        
        return $key;
    }
    
    
    /**
     * Транслитерира подадения стринг в латиница, ако текущия език не е кирилски и в текста има поне един символ на кирилица
     * 
     * @param string $str - Стринга, който ще се транслитерира
     * 
     * @retunr string $str - Резултата след обработката
     */
    static function transliterate($str)
    {
        // Ако е обект, връщаме
        if (is_object($str)) return ($str);
        
        // Ако е празен стринг
        if (!trim($str)) return $str;
        
        // Езици, които използват кирилица
        $cyrillicLangArr = array('bg', 'ru', 'md', 'sr');

        // Текущия език
        $currLg = static::getCurrent();
        
        // Ако текущия език не е в масива с кирилицте
        if (!in_array($currLg, $cyrillicLangArr)) {
            
            // Ако в текста има кирилица
            if (preg_match('/\p{Cyrillic}/ui', $str)) {
                
                // Преобразуваме текста в ascii
                $str = str::utf2ascii($str);
            }    
        }
        
        return $str;
    }
 
 	
	public static function on_BeforeImportRec($mvc, $rec)
    {
    	$rec->kstring = static::prepareKey($rec->kstring);
		
		if (isset($rec->csv_createdBy)) {
    		
    		$rec->createdBy = -1;
    	}
    }

   
    /**
     * Проверява подадения език, дали е добър за използване
     * 
     * @param string $lg - Езика, който ще се проверява
     * 
     * @return boolean
     */
    static function isGoodLg($lg)
    {
        // Езика в долен регистър
        $lg = strtolower($lg);
        
        $langArr = arr::make(EF_LANGUAGES);
        
        foreach ($langArr as $lgKey => $verbLg) {
            if (strtolower($lgKey) == $lg) return TRUE;
        }
        
        // Проверяваме дали са еднакви
        return FALSE;
    }


    /**
     * Връща масив от езиците на системата
     */
    static function getLangs()
    {
        $res = arr::make(EF_LANGUAGES, TRUE);

        return $res;
    }
    
    
    /**
     * Връща позволените езици за работа в системата
     * 
     * @param core_Type $type
     * 
     * @return array
     */
    static function getLangOptions($type, $otherParams = array())
    {
        $otherParams = arr::make($otherParams);
        $langArr = static::getLangs();
        
        $langArr = $otherParams + $langArr;
        
        if ($type instanceof type_Varchar) {
            // Да е в началото на стринга
            if (isset($langArr[''])) {
                unset($langArr['']);
                $langArr = array('' => '') + (array)$langArr;
            }
        } elseif (isset($langArr[''])) {
            $langArr[''] = 'От системата';
        }
        
        return $langArr;
    }
    
    
    /**
     * Екшън за изтриване на записи от превода
     */
    function act_DeleteUsersTr()
    {
        requireRole('admin');
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        $form = cls::get('core_Form');
        
        $form->title = "Изтриване на преводи";
         
        $form->FLD('users', 'users', 'caption=Тип,mandatory,silent');
        
        $form->toolbar->addSbBtn('Изтриване', 'save', 'ef_icon = img/16/delete.png, title = Изтрива преводите за съответния потребител');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->input();
        
        // Ако е събмитната формата
        if($form->isSubmitted()){
            $rec = $form->rec;
            
            // Премахваме системния потребител
            $rec->users = type_Keylist::removeKey($rec->users, -1);
            
            $in = type_Keylist::toArray($rec->users);
            
            $delCnt = 0;
            
            if (!empty($in)) {
                $inStr = implode(',', $in);
                
                $delCnt = $this->delete("#createdBy IN ({$inStr}) AND LEFT(#kstring, 10) = LEFT(#translated, 10)");
            }
            
            return new Redirect($retUrl, "Изтрити записи: {$delCnt}");
            
        }
        
        $tpl = $this->renderWrapping($form->renderHtml());
         
        return $tpl;
    }
}
