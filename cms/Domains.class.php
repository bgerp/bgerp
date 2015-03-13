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
    public $loadList = 'plg_RowTools, cms_Wrapper, plg_Created,plg_Current';
                      
    
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
    
    public $canEditsysdata = 'admin';

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
     * Файл с шаблон за единичен изглед на статия
     */
    // public $singleLayoutFile = 'frame/tpl/SingleLayoutReport.shtml';


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
     * Описание на модела
     */
    function description()
    {
        // Домейн
        $this->FLD('domain', 'varchar(64)', 'caption=Домейн,mandatory');
        
        // Език
        $this->FLD('lang', 'varchar(2)', 'caption=Език');
        
        // Споделяне
        $this->FLD('shared', 'userList(roles=cms|admin|ceo)', 'caption=Споделяне');

        // Singleton клас - източник на данните
        $this->FLD('theme', 'class(interface=cms_ThemeIntf, allowEmpty, select=title)', 'caption=Кожа,silent,mandatory,notFilter,refreshForm');

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('form', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('state', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');

        $this->setDbUnique('domain,lang');
    }


    /**
     * Връща id към текущия домейн
     */
    public static function getCmsDomain($part = NULL)
    {
        // Вземаме домейна от текущото URL
        $domain = strtolower($_SERVER['SERVER_NAME']);
        
        // Намираме и алтернативния домейн
        if(strpos($domain, 'www.') !== 0) {
            $altDomain = 'www.' . $domain;
        } else {
            $altDomain = 'www.' . $domain;
        }
    
        // Определяме езика
        $lg = Mode::get(self::CMS_CURRENT_LANG);
        if(!$lg) {
            $cmsLangs = self::getCmsLangs($domain, $altDomain);
            $lg = self::detectLang($cmsLangs);
            Mode::setPermanent(self::CMS_CURRENT_LANG, $lg);
        }
        
        $domainRec = Mode::get(self::CMS_CURRENT_DOMAIN_REC);
 
        if(!isset($domainRec) || $domainRec->lg != $lg || $domainRec->realDomain != $domain) { 
            // Намираме $rec-а на текущия домейн
            $domainRec = self::fetch(array("#domain = '[#1#]' AND #lang = '{$lg}'", $domain));
            if(!$domainRec) {
                $domainRec = self::fetch(array("#domain = '[#1#]' AND #lang = '{$lg}'", $altDomain));
            }
            if(!$domainRec) {
                $domainRec = self::fetch(array("#domain = '[#1#]'", $domain));
            }
            if(!$domainRec) {
                $domainRec = self::fetch(array("#domain = '[#1#]'", $altDomain));
            }
            if(!$domainRec) {
                $domainRec = self::fetch("#domain = 'localhost' AND #lang = '{$lg}'");
            }
            if(!$domainRec) {
                $domainRec = self::fetch("#domain = 'localhost'");
            }
         
           // Подсигуряваме масива с езици за външната част
            if(!isset($cmsLangs)) {
                $cmsLangs = self::getCmsLangs($domain, $altDomain);
            }

            $domainRec->realDomain = $domain;
            $domainRec->cmsLangs   = $cmsLangs;

            Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, $domainRec);
            Mode::setPermanent(self::CMS_CURRENT_LANG, $domainRec->lang);
        }
 
        if($part) {
 
            return $domainRec->{$part};
        } else {

            return $domainRec;
        }
    }


    /**
     *
     */
    public static function getCmsSkin()
    {
        $dRec = self::getCmsDomain();

        $driver = self::getDriver($dRec->id);

        return $driver;
    }


    /**
     * Връща възможните езици за подадените домейни
     */
    public static function getCmsLangs($domain, $altDomain)
    {
        $res = array();
        $query = self::getQuery();
        while($rec = $query->fetch(array("#domain = '[#1#]' OR #domain = '[#2#]'", $domain, $altDomain))) {
            $res[$rec->lang] = $rec->lang;
        }
        if(!count($res)) {
            $query = self::getQuery();
            while($rec = $query->fetch("#domain = 'localhost'")) {
                $res[$rec->lang] = $rec->lang;
            }
        }
        
        return $res;
    }



    /**
     * Определя най-добрия език за този потребител за тази сесия
     */
    static function detectLang($cmsLangs)
    {   
        // Ако имаме само един език - избираме него
        if(count($langArr) == 1) {

            return key($langArr);
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
        
        $countryCode2 = drdata_IpToCountry::get();

        $langsInCountry = arr::make(drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'languages'));
        
        if(count($langsInCountry)) {
            foreach($langsInCountry as $lg) {
                $langArr[$lg]++;
            }
        }
        
        if($langArr['en']) {
            $langArr['en'] *= 0.99;
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
     * След смяната на текущия елемент
     */
    public static function on_AfterChangeCurrent($mvc, $rec)
    {
        cms_content::setLang($rec->lang);
    }


    /**
     * Проверка за коректност на входната форма
     */
    public static function on_AfterInputeditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
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
    public static function on_AfterPrepareeditform($mvc, &$data)
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
        $res = '|* [<font color="green">' . self::getCurrent('domain') . '</font>, <font color="green">' . self::getCurrent('lang') . '</font>]';

        return $res;
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


}