<?php



/**
 * Мениджър на домейни на които отговаря CSM подсистемата
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Domains extends core_Embedder
{
    /**
     * Име под което записваме в сесията текущия език на CMS изгледа
     */
    const CMS_CURRENT_LANG = 'CMS_CURRENT_LANG';
    
    
    /**
     * Име под което записваме в сесията текущия домейн на CMS изгледа
     */
    const CMS_CURRENT_DOMAIN_REC = 'CMS_CURRENT_DOMAIN_REC';

    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, cms_Wrapper, plg_Created,plg_Current';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Домейн';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Домейни";

    
    /**
     * Права за писане
     */
    public $canWrite = 'admin';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, cms, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, cms, admin';
    
    public $canSelect = 'ceo, admin, cms';
    
    // Админа може да редактира и изтрива създадените от системата записи
    public $canEditsysdata = 'admin';


    public $canDeletesysdata = 'admin';

	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, cms, admin';
    
    
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canChangestate = 'ceo, cms, admin';
	
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/domain_names_advanced.png';

    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'domain';


    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $innerObjectInterface = 'cms_ThemeIntf';
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $innerClassField = 'theme';
    
    
    /**
     * Как се казва полето за данните от формата на драйвъра
     */
    public $innerFormField = 'form';
    
    
    /**
     * Как се казва полето за записване на вътрешните данни
     */
    public $innerStateField = 'state';
    

    /**
     * Текущият публичен домейн
     */
    static $publicDomainRec;
    

    /**
     * Описание на модела
     */
    function description()
    {
        // Домейн
        $this->FLD('domain', 'varchar(64)', 'caption=Домейн,mandatory');
        
        // Език
        $this->FLD('lang', 'varchar(2)', 'caption=Език');
        

        // Singleton клас - източник на данните
        $this->FLD('theme', 'class(interface=cms_ThemeIntf, allowEmpty, select=title)', 'caption=Кожа,silent,mandatory,notFilter,refreshForm');

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('form', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('state', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');
        
        // Споделяне
        $this->FLD('shared', 'userList(roles=cms|admin|ceo)', 'caption=Споделяне');

        $this->setDbUnique('domain,lang');

        // SEO Заглавие
        $this->FLD('seoTitle', 'varchar(15)', 'caption=SEO->Title,autohide');
        
        // SEO Описание
        $this->FLD('seoDescription', 'text(255,rows=3)', 'caption=SEO->Description,autohide');
        
        // SEO Ключови думи
        $this->FLD('seoKeywords', 'text(255,rows=3)', 'caption=SEO->Keywords,autohide');

    }


    /**
     * Вземаме всички публични домейни
     */
    public static function findPublicDomainRecs()
    {
        // Вземаме домейна от текущото URL
        $domain = strtolower(trim($_SERVER['SERVER_NAME']));

        // Най-добре е да имаме запис за точно този домейн
        $query = self::getQuery();
        $domainRecs = $query->fetchAll(array("#domain = '[#1#]'", $domain));
        
        if(!$domainRecs || count($domainRecs) == 0) {
             
            // Намираме и алтернативния домейн
            if(strpos($domain, 'www.') === 0) {
                $altDomain = substr($domain, 4);
            } else {
                $altDomain = 'www.' . $domain;
            }

            $query = self::getQuery();
            $domainRecs = $query->fetchAll(array("#domain = '[#1#]'", $altDomain));
        }
        
        if(!$domainRecs || count($domainRecs) == 0) {
            $query = self::getQuery();
            $domainRecs = $query->fetchAll(array("#domain = '[#1#]'", 'localhost'));
        }

        return $domainRecs;
    }


    /**
     * Връща id към текущия домейн
     */
    public static function getPublicDomain($part = NULL, $lang = NULL)
    {   
        $domainRec = Mode::get(self::CMS_CURRENT_DOMAIN_REC);

        $domainId = cms_Domains::getCurrent('id', FALSE);

        if($domainId && (!isset($domainRec) || ($domainRec->id != $domainId))) {
            self::setPublicDomain($domainId);
            $domainRec = Mode::get(self::CMS_CURRENT_DOMAIN_REC);
        }
        
        // Вземаме домейна от текущото URL
        $domain = strtolower(trim($_SERVER['SERVER_NAME']));

        if(!$domainRec || (isset($lang) && $domainRec->lang != $lang) || ($domainRec->actualDomain != $domain)) {
            
            $domainRecs = self::findPublicDomainRecs();
                
            $cmsLangs = self::getCmsLangs($domainRecs);
            
            // Определяме езика, ако не е зададен или е зададен неправилно
            if(!$lang || !$cmsLangs[$lang]) {
                $lang = self::detectLang($cmsLangs);
            }
            
            // Определяме домейна, който отговаря на езика
            $domainRecsCnt = count($domainRecs);
            foreach($domainRecs as $dRec) {
                if($dRec->lang == $lang || !$domainRec ||  ($domainRecsCnt == 1)) {
                    $domainRec = $dRec;
                }
            }
            
            if ($domainRec) {
                
                // Задаваме действителния домейн, на който е намерен този
                $domainRec->actualDomain = $domain;
        
                Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, $domainRec);

                if($domainRec->id) {
                    self::selectCurrent($domainRec->id);
                }
            }
        }
        
        if (!$domainRec || ($part == 'id' && !$domainRec->{$part})) {
            wp($domainRec);
        }
              
        if($part) {
 
            return $domainRec->{$part};
        } else {

            return $domainRec;
        }
    }


    /**
     * Задава текущия публичен домейн
     */
    public static function setPublicDomain($id)
    {
        $rec = self::fetch($id);
        
        // Задаваме действителния домейн, на който е намерен този
        $rec->actualDomain = strtolower(trim($_SERVER['SERVER_NAME']));

        Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, $rec);
    }


    /**
     * Връща възможните езици за подадените домейни
     */
    public static function getCmsLangs($domainRecs = NULL)
    {
        if(!$domainRecs) {
            $domainRecs = self::findPublicDomainRecs();
        }

        foreach($domainRecs as $rec) {
            $cmsLangs[$rec->lang] = $rec->lang;
        }
  
        return $cmsLangs;
    }
    
    
    /**
     * Подготвя поле за въвеждане на домейн
     */
    public static function setFormField($form, $field = 'domainId')
    {
        $query = self::getQuery();
        while($rec = $query->fetch("#state = 'active'")) {
            if(self::haveRightfor('select', $rec) || $rec->id == $form->rec->{$field}) {
                $opt[$rec->id] = self::getRecTitle($rec);
            }
        }
        expect($form instanceof core_Form);
        $form->setOptions($field, $opt);
        if(!$form->rec->{$field}) {
            $form->rec->{$field} = self::getCurrent();
        }

    }

 
    /**
     * Определя най-добрия език за този потребител за тази сесия
     */
    static function detectLang($cmsLangs)
    {   
        // Ако имаме само един език - избираме него
        if(is_array($cmsLangs) && count($cmsLangs) == 1) {

            return key($cmsLangs);
        }
        
        // Парсираме Accept-Language съгласно:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        preg_match_all(
           '/([a-z]{1,8})' .       // M1 - First part of language e.g en
           '(-[a-z]{1,8})*\s*' .   // M2 -other parts of language e.g -us
           // Optional quality factor M3 ;q=, M4 - Quality Factor
           '(;\s*q\s*=\s*((1(\.0{0,3}))|(0(\.[0-9]{0,3}))))?/i',
           $_SERVER['HTTP_ACCEPT_LANGUAGE'],
           $langParse);
        
        $langs = $langParse[1]; // M1 - First part of language
        $quals = $langParse[4]; // M4 - Quality Factor
 
        $numLanguages = count($langs);
        $langArr = array();

        for($num = 0; $num < $numLanguages; $num++) {
           $newLang = strtolower($langs[$num]);
           $newQual = isset($quals[$num]) ?
              (empty($quals[$num]) ? 1.0 : floatval($quals[$num])) : 0.0;

           // Choose whether to upgrade or set the quality factor for the
           // primary language.
           $langArr[$newLang] = (isset($langArr[$newLang])) ?
              max($langArr[$newLang], $newQual) : $newQual;
        }
        
        if($countryCode2 = drdata_IpToCountry::get()) {

            $langsInCountry = arr::make(drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'languages'));
            
            if(count($langsInCountry)) {
                foreach($langsInCountry as $lg) {
                    $langArr[$lg]++;
                }
            } 
        }

        setIfNot($langArr['en'], 0.01);

        if($langArr['en']) {
            $langArr['en'] *= 0.99;
        }
        if($langArr['bg']) {
            $langArr['bg'] *= 1.80;
        }

        // sort list based on value
        // langArr will now be an array like: array('EN' => 1, 'ES' => 0.5)
        arsort($langArr, SORT_NUMERIC);
        
        foreach($langArr as $lg => $q) {
            if($cmsLangs[$lg]) {               

                return $lg;
            }
        }
        
        // Ако не сме определили езика - връщаме първия срещнат
        return key($langArr);
    }


    /**
     * Връща темата за външния изглед
     */
    public static function getCmsSkin()
    {
        $dRec = self::getPublicDomain();
		if($dRec){
			$driver = self::getDriver($dRec->id);
		}

        return $driver;
    }


    /**
     * Проверка за коректност на входната форма
     */
    public static function on_AfterInputeditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            $form->rec->domain = trim(strtolower($form->rec->domain));
            if(!core_Url::isValiddomainName($form->rec->domain)) {
                $form->setError('domain', 'Невалидно име на домейн');
            }
        }
    }

    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    }
    

    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    	
    	$Driver->invoke('AfterActivation', array(&$rec->data, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterReject($mvc, &$res, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    	
    	$Driver->invoke('AfterReject', array(&$rec->data, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterRestore($mvc, &$res, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    
    	$Driver->invoke('AfterRestore', array(&$rec->data, &$rec));
    }


    /**
     * Подготвя формата
     * - Прави списъка с езиците
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $langQuery = drdata_Languages::getQuery();
        $langOpt = array();
        while($lRec = $langQuery->fetch()) {
            $langOpt[$lRec->code] = $lRec->languageName;
        }
        $data->form->setOptions('lang', $langOpt);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete') {
            if(isset($rec) && isset($rec->id) && cms_Content::fetch("#domainId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща заглавието на дадения запис (името на параметъра)
     */
    static function getRecTitle($rec, $escape = TRUE)
    {
        if(!$rec->domain || !$rec->lang) {
            $rec = self::fetch($rec->id);
        }
        
        $title = "{$rec->domain}, {$rec->lang}";

        if($escape) {
            $title = type_Varchar::escape($title);
        }

        return $title;
    }


    /**
     * Връща добавка за домейна в листовия изглед на други модели
     */
    public static function getCurrentDomainInTitle()
    {
        $res = '|* [<span style="color:green">' . self::getCurrent('domain') . '</span>, <span style="color:green">' . self::getCurrent('lang') . '</span>]';

        return $res;
    }


    /**
     * Унищожава кеша след запис
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, NULL);
    }


    /**
     * Поне един домейн
     */
    function on_AfterSetupMVC()
    {
        if(!self::count()) {
            core_Classes::add('cms_DefaultTheme');
            $rec = (object) array('domain' => 'localhost', 'theme' => core_Classes::getId('cms_DefaultTheme'), 'lang' => 'bg');
            self::save($rec);
        }
    }


    /**
     * Връща SEO залгавието за текущия домейн
     */
    public static function getSeoTitle()
    {
        $rec = self::getPublicDomain();

        if($rec->seoTitle) {
            $res = self::getVerbal($rec, 'seoTitle');
        } else {
            $res = core_Setup::get('EF_APP_TITLE', TRUE);
        }

        return $res;
    }


}
