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
    var $loadList = 'plg_Created,plg_SystemWrapper,plg_RowTools,plg_AutoFilter';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('lg', 'varchar(2)', 'caption=Език,export,mandatory,autoFilter,optionsFunc=core_Lg::getLangOptions, suggestions=');
        $this->FLD('kstring', 'varchar', 'caption=Стринг,export, width=100%, mandatory');
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
        // Празните стрингове не се превеждат
        if (is_Object($kstring) || !trim($kstring)) return $kstring;
        
        if (!$key) {
            // Разбиваме стринга на участъци, който са разделени със символа '|'
            $strArr = explode('|', $kstring);
            
            if (count($strArr) > 1) {
                $translated = '';
                
                // Ако последната или първата фраза за празни - махаме ги
                if($strArr[count($strArr)-1] == '') {
                    unset($strArr[count($strArr)-1]);
                }
                
                if($strArr[0] == '') {
                    unset($strArr[0]);
                }
                
                foreach ($strArr as $i => $phrase) {
                    
                    // Две черти една до друга, ескейпват една
                    if ($phrase === '') {
                        $translated .= '|';
                        continue;
                    }
                    
                    $isFirst = FALSE;
                    
                    // Ако фразата започва с '*' не се превежда
                    if ($phrase{0} === '*') {
                        $translated .= substr($phrase, 1);
                        continue;
                    }

                    $translated .= $this->translate($phrase);
                }
                
                return $translated;
            }
            
            $key = $kstring;
        }
        
        // Заместваме празните редове, за да може да превеждаме и multiline текстове
        $key = str_ireplace(array("\n\r", "\r\n", "\n", "\r"), '<br />', $key);
        
        $key = static::prepareKey($key);
        
        // Ако не е зададен език, превеждаме на текущия
        if (!$lg) {
            $lg = core_LG::getCurrent();
        }
        
        if(!count($this->dict)) {
            $this->dict = core_Cache::get('translation', $lg, 2 * 60 * 24, array('core_Lg'));
            
            if(!$this->dict) {
                $query = self::getQuery();
                
                while($rec = $query->fetch(array("#lg = '[#1#]'", $lg))) {
                    $this->dict[$rec->kstring][$lg] = type_Varchar::escape($rec->translated);
                }
                core_Cache::set('translation', $lg, $this->dict, 2 * 60 * 24, array('core_Lg'));
            }
        }
        
        // Ако имаме превода в речника, го връщаме
        if (isset($this->dict[$key][$lg])) return $this->dict[$key][$lg];
        
        // Попълваме речника от базата
        $rec = $this->fetch(array(
                "#kstring = '[#1#]' AND #lg = '[#2#]'",
                $key,
                $lg
            ));
        
        if ($rec) {
            $this->dict[$key][$lg] = $rec->translated;
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
            
            // Записваме в модела
            $this->save($rec);
            
            // Записваме в кеш-масива
            $this->dict[$key][$lg] = $rec->translated;
        }
        
        return $rec->translated;
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
        	$filterRec->lg = core_Lg::getCurrent();
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
     * 
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
        $key = str::convertToFixedKey($key, 32, 4);
        
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
}
