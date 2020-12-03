<?php


/**
 * Мениджър на домейни на които отговаря CSM подсистемата
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
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
     * Име за sitemap.xml, когато се показва в robots.txt. 
     * Ако името е различно, няма да се показва в robots.txt. 
     */
    const CMS_PUBLIC_SITEMAP_NAME = 'public-sitemap.xml';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, cms_Wrapper, plg_Created, plg_Current';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Домейн';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Домейни';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, admin, cms';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'ceo, admin, cms';
    
    
    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'ceo,admin,cms';
    
    
    /**
     * Кой може да изтрива системните данни
     */
    public $canDeletesysdata = 'ceo,admin,cms';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, cms, admin';
    
    
    /**
     * Кой може да избира текущ домейн
     */
    public $canSelect = 'ceo, admin, cms';
    
    
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
    public static $publicDomainRec;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'domain,lang,theme,createdOn,createdBy';
    
    
    /**
     * Възможна ли е смяната на вече избран драйвер?
     */
    public $allowDriverChange = true;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        // Домейн
        $this->FLD('domain', 'varchar(64)', 'caption=Домейн,mandatory');
        
        // Език
        $this->FLD('lang', 'varchar(2)', 'caption=Език');
        
        // Заглавие
        $this->XPR('titleExt', 'varchar(70)', "CONCAT(#domain, ', ', #lang)");
        
        // Singleton клас - източник на данните
        $this->FLD('theme', 'class(interface=cms_ThemeIntf, allowEmpty, select=title)', 'caption=Кожа,silent,mandatory,notFilter,refreshForm');
        
        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('form', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');
        
        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('state', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');
        
        // Споделяне
        $this->FLD('shared', 'userList(roles=cms|admin|ceo)', 'caption=Споделяне');
        
        $this->setDbUnique('domain,lang');
        
        // Съгласие с ОУ
        $this->FLD('mandatoryAgreeText', 'richtext(rows=1)', 'caption=Задължителен текст за съгласие->Текст,autohide');
        
        // SEO Заглавие
        $this->FLD('seoTitle', 'varchar(15)', 'caption=SEO->Title,autohide');
        
        // SEO Описание
        $this->FLD('seoDescription', 'text(255,rows=3)', 'caption=SEO->Description,autohide');
        
        // SEO Ключови думи
        $this->FLD('seoKeywords', 'text(255,rows=3)', 'caption=SEO->Keywords,autohide');
        
        // Sitemap.xml
        $this->FLD('sitemap', 'varchar(64)', 'caption=Статични файлове->SiteMap.xml,placeholder=Няма,suggestions=|' . self::CMS_PUBLIC_SITEMAP_NAME);
        
        // Икона за сайта
        $this->FLD('favicon', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Статични файлове->Икона за сайта');
        
        // Други
        $this->FLD('wrFiles', 'fileman_FileType(bucket=cmsFiles)', 'caption=Статични файлове->Други(zip)');
        
        // Други
        $this->FLD('toRemove', 'blob(serialize)', 'caption=Статични файлове->За премахване,input=none,column=none');
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
        
        if (!$domainRecs || countR($domainRecs) == 0) {
            
            // Намираме и алтернативния домейн
            if (strpos($domain, 'www.') === 0) {
                $altDomain = substr($domain, 4);
            } else {
                $altDomain = 'www.' . $domain;
            }
            
            $query = self::getQuery();
            $domainRecs = $query->fetchAll(array("#domain = '[#1#]'", $altDomain));
        }
        
        if (!$domainRecs || countR($domainRecs) == 0) {
            $query = self::getQuery();
            $domainRecs = $query->fetchAll(array("#domain = '[#1#]'", 'localhost'));
        }
        
        return $domainRecs;
    }
    
    
    /**
     * Връща id към текущия домейн
     */
    public static function getPublicDomain($part = null, $lang = null)
    {
        $domainRec = Mode::get(self::CMS_CURRENT_DOMAIN_REC);
        
        $domainId = cms_Domains::getCurrent('id', false);
        
        if ($domainId && (!isset($domainRec) || ($domainRec->id != $domainId))) {
            self::setPublicDomain($domainId);
            $domainRec = Mode::get(self::CMS_CURRENT_DOMAIN_REC);
        }
        
        // Вземаме домейна от текущото URL
        $domain = strtolower(trim($_SERVER['SERVER_NAME']));
        
        if (!$domainRec || (isset($lang) && $domainRec->lang != $lang) || ($domainRec->actualDomain != $domain)) {
            $domainRecs = self::findPublicDomainRecs();
            
            $cmsLangs = self::getCmsLangs($domainRecs);
            
            // Определяме езика, ако не е зададен или е зададен неправилно
            if (!$lang || !$cmsLangs[$lang]) {
                $lang = self::detectLang($cmsLangs);
            }
            
            // Определяме домейна, който отговаря на езика
            $domainRecsCnt = countR($domainRecs);
            foreach ($domainRecs as $dRec) {
                if ($dRec->lang == $lang || !$domainRec || ($domainRecsCnt == 1)) {
                    $domainRec = $dRec;
                }
            }
            
            if ($domainRec) {
                
                // Задаваме действителния домейн, на който е намерен този
                $domainRec->actualDomain = $domain;
                
                Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, $domainRec);
                
                if ($domainRec->id) {
                    self::selectCurrent($domainRec->id);
                }
            }
        }
        
        if (!$domainRec || ($part == 'id' && !$domainRec->{$part})) {
            wp($domainRec);
        }
        
        if ($part) {
            
            return $domainRec->{$part};
        }
        
        return $domainRec;
    }
    
    
    /**
     * Задава текущия публичен домейн
     */
    public static function setPublicDomain($id)
    {
        $rec = self::fetch($id);
        
        // Задаваме действителния домейн, на който е намерен този
        $rec->actualDomain = strtolower(trim($_SERVER['SERVER_NAME']));
        
        if(defined('BGERP_ABSOLUTE_HTTP_HOST')) {
            $mainHost = BGERP_ABSOLUTE_HTTP_HOST;
        } else {
            $mainHost = $rec->actualDomain;
        }
        
        $newHost = ($rec->domain == 'localhost') ? $mainHost : $rec->domain;
        
        if(($newHost != $rec->actualDomain) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $newUrl = core_Url::change($url, array(), $newHost);
            
            redirect($newUrl, false, null,'notice', true);
        }
        
        Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, $rec);
    }
    
    
    /**
     * Връща възможните езици за подадените домейни
     */
    public static function getCmsLangs($domainRecs = null)
    {
        if (!$domainRecs) {
            $domainRecs = self::findPublicDomainRecs();
        }
        
        $cmsLangs = array();
        foreach ($domainRecs as $rec) {
            $cmsLangs[$rec->lang] = $rec->lang;
        }
        
        return $cmsLangs;
    }
    
    
    /**
     * Подготвя поле за въвеждане на домейн
     */
    public static function setFormField($form, $field = 'domainId')
    {
        $opt = array();
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            if (self::haveRightfor('select', $rec)) {
                $opt[$rec->id] = self::getRecTitle($rec);
            }
        }
        
        expect($form instanceof core_Form);
        $form->setOptions($field, $opt);
        $form->setDefault($field, self::getCurrent());
    }
    
    
    /**
     * Определя най-добрия език за този потребител за тази сесия
     */
    public static function detectLang($cmsLangs)
    {
        // Ако имаме само един език - избираме него
        if (is_array($cmsLangs) && countR($cmsLangs) == 1) {
            
            return key($cmsLangs);
        }
        
        $langParse = array();
        
        // Парсираме Accept-Language съгласно:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        preg_match_all(
           '/([a-z]{1,8})' .       // M1 - First part of language e.g en
           '(-[a-z]{1,8})*\s*' .   // M2 -other parts of language e.g -us
           // Optional quality factor M3 ;q=, M4 - Quality Factor
           '(;\s*q\s*=\s*((1(\.0{0,3}))|(0(\.[0-9]{0,3}))))?/i',
           $_SERVER['HTTP_ACCEPT_LANGUAGE'],
           $langParse
        );
        
        $langs = $langParse[1]; // M1 - First part of language
        $quals = $langParse[4]; // M4 - Quality Factor
        
        $numLanguages = countR($langs);
        $langArr = array();
        
        for ($num = 0; $num < $numLanguages; $num++) {
            $newLang = strtolower($langs[$num]);
            $newQual = isset($quals[$num]) ?
              (empty($quals[$num]) ? 1.0 : floatval($quals[$num])) : 0.0;
            
            // Choose whether to upgrade or set the quality factor for the
            // primary language.
            $langArr[$newLang] = (isset($langArr[$newLang])) ?
              max($langArr[$newLang], $newQual) : $newQual;
        }
        
        if ($countryCode2 = drdata_IpToCountry::get()) {
            $langsInCountry = arr::make(drdata_Countries::fetchField("#letterCode2 = '{$countryCode2}'", 'languages'));
            
            if (countR($langsInCountry)) {
                foreach ($langsInCountry as $lg) {
                    $langArr[$lg]++;
                }
            }
        }
        
        setIfNot($langArr['en'], 0.01);
        
        if ($langArr['en']) {
            $langArr['en'] *= 0.99;
        }
        if ($langArr['bg']) {
            $langArr['bg'] *= 1.80;
        }
        
        // sort list based on value
        // langArr will now be an array like: array('EN' => 1, 'ES' => 0.5)
        arsort($langArr, SORT_NUMERIC);
        
        foreach ($langArr as $lg => $q) {
            if ($cmsLangs[$lg]) {
                
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
        if ($dRec) {
            $driver = self::getDriver($dRec->id);
        }
        
        return $driver;
    }
    
    
    /**
     * Проверка за коректност на входната форма
     */
    public static function on_AfterInputeditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->domain = trim(strtolower($form->rec->domain));
            if (!core_Url::isValiddomainName($form->rec->domain)) {
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
        while ($lRec = $langQuery->fetch()) {
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
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete') {
            if (isset($rec, $rec->id) && cms_Content::fetch("#domainId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'select') {
            if(isset($rec)) {
                if(!keylist::isIn($userId, $rec->shared)) {
                    $requiredRoles = 'ceo,admin';
                }
            }
        }
    }
    
    
    /**
     * Връща заглавието на дадения запис (името на параметъра)
     */
    public static function getRecTitle($rec, $escape = true)
    {
        if (!$rec->domain || !$rec->lang) {
            $rec = self::fetch($rec->id);
        }
        
        $title = "{$rec->domain}, {$rec->lang}";
        
        if ($escape) {
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
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Инвалидираме сесийния кеш
        Mode::setPermanent(self::CMS_CURRENT_DOMAIN_REC, null);
        
        if(is_array($rec->toRemove) && countR($rec->toRemove)) {
            foreach($rec->toRemove as $filename) {
                core_Webroot::remove($filename, $id);
            }
        }
        $rec->toRemove = array();
        
        if($rec->domain != 'localhost') {
            Mode::push('BGERP_CURRENT_DOMAIN', $rec->domain);
        }
        
        // robots.txt
        $fiContent = $mvc->getRobotsTxt($rec);
        
        if($rec->sitemap) {
            
            if($rec->sitemap == self::CMS_PUBLIC_SITEMAP_NAME) {
                $fiContent .= "\nSitemap: " . rtrim(toURL(array(self::CMS_PUBLIC_SITEMAP_NAME), 'absolute'), '/');
            }
            
            // Регистриране на sitemap.xml
            cms_Content::registerSitemap($rec);
            
            $rec->toRemove[$rec->sitemap] = $rec->sitemap;
        }
        
        
        core_Webroot::register($fiContent, '', 'robots.txt', $id);
        $rec->toRemove['robots.txt'] = 'robots.txt';
        
        // Всички останали файлове
        if($rec->wrFiles) {
            
            $inst = cls::get('archive_Adapter', array('fileHnd' => $rec->wrFiles));
            
            $entries = $inst->getEntries();
            
            if(is_array($entries) && countR($entries)) {
                foreach($entries as $i => $e) {
                    if(preg_match("/[a-z0-9\\-\\_\\.]+/i", $e->path)) {
                        $fh = $inst->getFile($i);
                        $fiContent = fileman_Files::getContent($fh);
                        core_Webroot::register($fiContent, '', strtolower($e->path), $id);
                        $rec->toRemove[$e->path] = $e->path;
                    }
                }
            }
        }
        
        $fiContent = null;
        
        // favicon.ico
        if($rec->favicon) {
            $iconContent = $fiContent = fileman_Files::getContent($rec->favicon);
            core_Webroot::register($fiContent, '', 'favicon.png', $id);
        
        } elseif(!in_array('favicon.ico', $rec->toRemove)) {
            $iconContent = getFileContent('img/favicon.png');
            $fiContent = getFileContent('img/favicon.ico');
        }
        
        if(core_Webroot::isExists('android-chrome-512x512.png', $id)) {
            $iconContent = core_Webroot::getContents('android-chrome-512x512.png', $id);
        }
        
        if($iconContent) {
            core_Webroot::register($iconContent, '', 'favicon.png', $id);
        }
        
        if($fiContent) {
            core_Webroot::register($fiContent, '', 'favicon.ico', $id);
            $rec->toRemove[$e->path] = $e->path;
        }
        
        if(countR($rec->toRemove)) {
            $mvc->save_($rec, 'toRemove');
        }
        
        if($rec->domain != 'localhost') {
            Mode::pop('BGERP_CURRENT_DOMAIN');
        }
    }
    
    
    /**
     * Замества хост
     */
    public static function getReal($domain)
    {
        if($domain == 'localhost') {
            
            $host = strtolower($_SERVER['SERVER_NAME']);
            
            if(self::fetch(array("#domain = '[#1#]'", $host))) {
                
                if(defined('BGERP_ABSOLUTE_HTTP_HOST')) {
                    $host = parse_url(BGERP_ABSOLUTE_HTTP_HOST, PHP_URL_HOST);
                } else {
                    $host = '';
                }
            }
            
            if($host && !preg_match("/^[0-9\\.]+$/", $host)) {
                $domain = $host;
            }
        }
        
        return $domain;
    }
    
    
    /**
     * Генерира съдържанието за robots.txt
     */
    public function getRobotsTxt_($rec)
    {
        $res = "\nUser-agent: *";
        $res .= "\nAllow: /";
        
        return trim($res);
    }
    
    
    /**
     * Поне един домейн
     */
    public function on_AfterSetupMVC()
    {
        if (!self::count()) {
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
        
        if ($rec->seoTitle) {
            $res = self::getVerbal($rec, 'seoTitle');
        } else {
            $res = core_Setup::get('EF_APP_TITLE', true);
        }
        
        return $res;
    }
    
    
    /**
     * Опции от наличните домейни
     * 
     * @param boolean $uniqDomains - уникални домейни
     * @param int|null $cu         - текущ потребител, ако има
     * 
     * @return array $options      - намерените домейни
     */
    public static function getDomainOptions($uniqDomains = false, $cu = null)
    {
        $options = $domains = array();
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            if(isset($cu)){
                if(!self::haveRightFor('select', $rec, $cu)){
                    
                    continue;
                }
            }
            
            if($uniqDomains) {
                if($domains[$rec->domain]) continue;
                
                $domains[$rec->domain] = true;
                $options[$rec->id] = $rec->domain;
            } else {
                $options[$rec->id] = $rec->domain . " ({$rec->lang})";
            }
        }
        
        return $options;
    }
    
    
    /**
     * Какви са настройките на домейна
     *
     * @param int           $domainId
     * @param datetime|NULL $date     - към коя дата
     *
     * @return array
     */
    public static function getSettings($domainId = null, $date = null)
    {
        if (!core_Packs::isInstalled('eshop')) {
            
            return array();
        }
        $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        
        return eshop_Settings::getSettings('cms_Domains', $domainId, $date);
    }
    
    
    /**
     * Добавя задължителенно поле за съгласяване с въведените условия
     *
     * @param core_FieldSet $form
     * @param int|NULL $domainId
     * @return void
     */
    public static function addMandatoryText2Form(core_FieldSet &$form, $domainId = NULL)
    {
        $domainId = ($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        $mandatoryAgreeText = self::fetchField($domainId, 'mandatoryAgreeText');
        
        if(empty($mandatoryAgreeText)) {
            
            return;
        }
        
        $form->FLD('mandatoryAgreeText', cls::get('type_Check', array('params' => array('label' => "|*" . $mandatoryAgreeText, 'displayAsRichtext' => true, 'errorIfNotChecked' => 'За да продължите, трябва да сте съгласни с общите условия'))), 'mandatory,displayInBottom,noCaption');
    }
    
    
    /**
     * Връща абсолютното урл към домейна
     * 
     * @param int $id           - ид на домейна
     * @param string|null $name - името на домейна
     * @return string $url      - урл-то към домейна
     */
    public static function getAbsoluteUrl($id, &$name = null)
    {
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $slashPos = strpos($_SERVER['SERVER_PROTOCOL'], '/');
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, $slashPos) . $s;
        
        $name = cms_Domains::fetchField($id, 'domain');
        if($name == 'localhost'){
            $name = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
        }
        
        $url = "{$protocol}://{$name}";
        
        return $url;
    }
}
