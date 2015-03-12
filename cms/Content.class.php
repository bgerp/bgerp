<?php



/**
 * Публично съдържание, подредено в меню
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Content extends core_Manager
{

    /**
     * Име под което записваме в сесията текущия език на CMS изгледа
     */
    const CMS_CURRENT_LANG = 'CMS_CURRENT_LANG';
    
    /**
     * Заглавие
     */
    public $title = "Основно меню";
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = "Елемент от менюто";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting, plg_Search,plg_AutoFilter';


    /**
     * Полета, които ще се показват в листов изглед
     */
   // var $listFields = ' ';
    
     
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'cms,admin,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    

    /**
     * Полета за листовия изглед
     */
    var $listFields = 'order,✍,menu,source,state';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * По кои полета ще се търси
     */
    var $searchFields = 'menu';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('order', 'int(min=0)', 'caption=№,tdClass=rowtools-column');
        $this->FLD('menu',    'varchar(64)', 'caption=Меню,mandatory');
        $this->EXT('lang',    'cms_Domains', 'caption=Език,externalKey=domainId,input=none');
        $this->FLD('domainId',    'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
        $this->FLD('source',  'class(interface=cms_SourceIntf, allowEmpty, select=title)', 'caption=Източник');
        $this->FLD('url',  'varchar(128)', 'caption=URL');
        $this->FLD('layout', 'html', 'caption=Лейаут');

        $this->setDbUnique('menu,domainId');
    }


    /**
     * Връща масива с възможните езици за CMS частта
     */
    static function getLangsArr()
    {
        static $langsArr;
        
        if(!$langsArr) {
            $langsArr = array();
            $conf = core_Packs::getConfig('cms');
            $langsArr = array($conf->CMS_BASE_LANG => $conf->CMS_BASE_LANG);
            foreach(keylist::toArray($conf->CMS_LANGS) as $langId) {
                $lg = drdata_Languages::fetchField($langId, 'code');
                
                if (!$lg) continue;
                
                $langsArr[$lg] = $lg;
            }
        }

        return $langsArr;
    }


    /**
     * Връща масив с езиците за които има елементи от менюто
     */
    static function getUsedLangsArr()
    {
        static $langsArr;
        
        if(!$langsArr) {
            $langsArr = array();
            $query = self::getQuery();
            $query->groupBy('lang');
            while($rec = $query->fetch()) {
                
                if (!$rec->lang) continue;
                
                $langsArr[$rec->lang] = $rec->lang;
            }
        }

        return $langsArr;
    }

    
    /**
     * Връша текущия език за CMS часта
     */
    static function getLang()
    {
        $langsArr = self::getUsedLangsArr();
        
        $lang = Mode::get(self::CMS_CURRENT_LANG);

        if(!$langsArr[$lang] && !(haveRole('ceo,cms') && $lang)) {
            
            $lang = self::detectLang();
            
            // За пазваме езика
            self::setLang($lang);
        }
        
        return $lang;
    }


    /**
     * Записва в сесията текущия език на CMS изгледа
     */
    static function setLang($lang)
    {
        Mode::setPermanent(self::CMS_CURRENT_LANG, $lang);

        core_Lg::set($lang, FALSE);
    }


    /**
     * Определя най-добрия език за този потребител за тази сесия
     */
    static function detectLang()
    {   
        $conf = core_Packs::getConfig('cms');

        $cmsLangs = self::getUsedLangsArr();

        if(!count($cmsLangs)) {

            return $conf->CMS_BASE_LANG;
        }

        // Parse the Accept-Language according to:
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

        for($num = 0; $num < $numLanguages; $num++)
        {
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
        
        // Ако не сме определили езика - връщаме базовия
        return $conf->CMS_BASE_LANG;
    }


    /**
     * Екшън за избор на език на интерфейса за CMS часта
     */
    function act_SelectLang()
    {
        $langsArr = self::getUsedLangsArr();

        $lang = $langsArr[Request::get('lang')];

        if($lang) {
            self::setLang($lang);
            
            core_Lg::set($lang);

            followRetUrl();
        }

        $lang = self::getLang();
 
        $res = new ET(getFileContent('cms/themes/default/LangSelect.shtml'));
        
        $s = $res->getBlock('SELECTOR');

        foreach($langsArr as $lg) {
        	
            if($lg == $lang) {
                $attr = array('class' => 'selected');
            } else {
                $attr = array('class' => '');
            }
            
            $filePath = getFullPath("img/flags/" . $lg . ".png");
            $img = " ";
            
            if($filePath){
            	$imageUrl = sbf("img/flags/" . $lg . ".png", "");
            	$img = ht::createElement("img", array('src' => $imageUrl, 'alt' => $lg . ' language'));
            }
            
            $url = array($this, 'SelectLang', 'lang' => $lg);
            $s->replace(ht::createLink($img . drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName'), $url, NULL, $attr), 'SELECTOR');
            $s->append2master();
        }
        
        Mode::set('wrapper', 'cms_page_External');

        return $res;
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search';
        
        $form->input('search', 'silent');

        $domainId = cms_Domains::getCurrent();
       
        $data->query->where("#domainId = {$domainId}");
        
        $data->query->orderBy('#order');
    }


    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->rec->domainId = cms_Domains::getCurrent();
        $data->form->setReadOnly('domainId');
     }

    
    /**
     * Подготвя данните за публичното меню
     */
    function prepareMenu_($data)
    {
        $query = self::getQuery();
        
        $query->orderBy('#order');

        $domainId = cms_Domains::getCmsDomain()->id;

        $data->items = $query->fetchAll("#state = 'active' && #domainId = {$domainId}");
        
    }

    
    /**
     * Рендира публичното меню
     */
    function renderMenu_($data)
    {   
        $tpl = new ET();
        
        $cMenuId = Mode::get('cMenuId');
        if(!$cMenuId) {
            $cMenuId = Request::get('cMenuId');
            Mode::set('cMenuId', $cMenuId);
        }
        

        if (is_array($data->items)) {
            foreach($data->items as $rec) {
                
                list($f, $s) = explode(' ', $rec->menu, 2);

                if(is_Numeric($f)) {
                    $rec->menu = $s;
                }

                $attr = array();
                if( ($cMenuId == $rec->id)) {
                    $attr['class'] = 'selected';
                } 
                
                $url = $this->getContentUrl($rec);

                if(!$url) $url = '#';
                
                $tpl->append(ht::createLink($rec->menu, $url, NULL, $attr));
            }    
        }
        
        // Ако имаме действащи менюта на повече от един език, показваме бутон за избор на езика
        $usedLangsArr = self::getUsedLangsArr();
        
        if(count($usedLangsArr) == 2) {

            // Премахваме текущия език
            $lang = self::getLang();

            foreach($usedLangsArr as $lg) {
                
                $attr = array('title' => drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName'), 'id' => 'set-lang-' . $lg);

                if($lg == $lang) continue;
                
                $filePath = getFullPath("img/flags/" . $lg . ".png");
                $img = " ";

                if($filePath){
                    $imageUrl = sbf("img/flags/" . $lg . ".png", "");
                    $img = ht::createElement("img", array('src' => $imageUrl, 'alt' => $lg));
                }
                
                $url = array($this, 'SelectLang', 'lang' => $lg);
 

                $tpl->append(ht::createLink($img, $url, NULL, $attr));
            }
        } elseif(count($usedLangsArr) > 1) {
            $attr['class'] = 'selectLang';
            $attr['title'] = tr('Смяна на езика');
            $tpl->append(ht::createLink(ht::createElement('img', array('src' => sbf('img/24/globe.png', ''))), array($this, 'selectLang'), NULL, $attr));
        }

        return $tpl;
    }


    /**
     * Връща URL към съдържанието, което отговаря на този запис
     */
    function getContentUrl($rec) 
    {
        if($rec->source) {
            $source = cls::get($rec->source);
            $url = $source->getUrlByMenuId($rec->id);
        } elseif($rec->url) {
            $url = arr::make($rec->url);
        } else {
            // expect(FALSE);
            $url = '';
        }

        return $url;
    }
    

    /**
     * Връща кратко URL отговарящо на текущото
     */
    static function getShortUrl()
    {
        $cUrl = getCurrentUrl();
        
        // За да не влезе в безкраен цикъл, да не вика себе си
        if (strtolower($cUrl['Ctr']) == 'cms_content') {
            
            return $cUrl;
        }
        
        if(cls::existsMethod($cUrl['Ctr'], 'getShortUrl')) {
            $man = cls::get($cUrl['Ctr']);
            $cUrl = $man->getShortUrl($cUrl);
        }
 
        return $cUrl;
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        if($rec->source) {
            $Source = cls::getInterface('cms_SourceIntf', $rec->source);
            $workUrl = $Source->getWorkshopUrl($rec->id);
            $row->source = ht::createLink($row->source, $workUrl); 
        }
        $publicUrl = $mvc->getContentUrl($rec);
        $row->menu = ht::createLink($row->menu, $publicUrl, FALSE, 'ef_icon=img/16/monitor.png'); 
    }

    
    /**
     * Връща основното меню
     */
    static function getMenu()
    {
        $data = new stdClass();
        $self = cls::get('cms_Content');
        $self->prepareMenu($data);
        
        return  $self->renderMenu($data);
    }

    
    /**
     * Връща футера на страницата
     */
    static function getFooter()
    {
        if(self::getLang() !== 'bg') {
            $footer =  new ET(getFileContent("cms/tpl/FooterEn.shtml"));
        } else {
            $footer =  new ET(getFileContent("cms/tpl/Footer.shtml"));
        }
        $footer->replace(getBoot() . '/' . EF_SBF . '/' . EF_APP_NAME, 'boot');

        return $footer;
    }
    
     
    /**
     * Връща футера на страницата
     */
    static function getLayout()
    {
        $layoutPath = Mode::get('cmsLayout');

        $layout = new ET($layoutPath ? tr('|*' . getFileContent($layoutPath)) : '[#PAGE_CONTENT#]');
    
        return $layout;
    }


    /**
     * Задава текущото меню
     */
    static function setCurrent($menuId = NULL, $layout = NULL)
    {
        if($menuId && $rec = cms_Content::fetch($menuId)) {
            Mode::set('cMenuId', $menuId);
            self::setLang($lg = $rec->lang);
        } else {
            $lg = self::getLang();
        }

        $langsArr = arr::make(core_Lg::getLangs());

        if($langsArr[$lg]) {
            core_Lg::push($lg);
        }

        Mode::set('wrapper', 'cms_page_External');
        
    }

    
    /**
     * Показва посоченото меню, а ако няма такова - показва менюто с най-малък номер
     */
    function act_Show()
    {  
        $menuId = Request::get('id', 'int');
        
        if(!$menuId) {
            $query = self::getQuery();
            $lang = self::getLang();
            $query->where("#state = 'active' AND #lang = '{$lang}'");
            $query->orderBy("#order");
            $rec = $query->fetch();
        } else {
            $rec = $this->fetch($menuId);
        }
        
        Mode::set('cMenuId', $menuId);
        
        if ($rec && ($content = $this->getContentUrl($rec))) {

            return new Redirect($content);
        } else {

            return new Redirect(array('bgerp_Portal', 'Show'));
        }
    }

    static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        
        $data->title .= '|* [<font color="green">' . cms_Domains::getCurrent('domain') . '</font>, <font color="green">' . cms_Domains::getCurrent('lang') . '</font>]';
    }


    /**
     * Връща опциите от менюто, които отговарят на текущия домейн и клас
     */
    public static function getMenuOpt($class)
    {   
        $classId = core_Classes::getId($class);
        $domainId = cms_Domains::getCurrent();
        $query = self::getQuery();
        $query->orderBy('#order');
        while($rec = $query->fetch("#domainId = {$domainId} && #source = {$classId}")) {
            $res[$rec->id] = $rec->menu;
        }

        return $res;
    }
   



    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		//  Кой може да обобщава резултатите
		if($action == 'delete' && isset($rec->id) ) {
   			if($rec->state != 'closed') {
    		    $res = 'no_one';
            }
        }
   	}

 }