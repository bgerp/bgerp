<?php


/**
 * Публично съдържание, подредено в меню
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
    public $title = 'Основно меню';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Елемент от менюто';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2, plg_RowTools2, plg_Printing, cms_Wrapper, plg_Sorting, plg_Search,cms_DomainPlg';
    
    
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
    public $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cms,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'order,menu,source,state';
    
    
    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = '✍';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'menu';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('order', 'order(min=0)', 'caption=№,tdClass=rowtools-column');
        $this->FLD('menu', 'varchar(64)', 'caption=Меню,mandatory');
        
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
        $this->FLD('source', 'class(interface=cms_SourceIntf, allowEmpty, select=title)', 'caption=Източник,mandatory');
        $this->FLD('url', 'varchar(128)', 'caption=URL,input=none');
        $this->FLD('layout', 'html', 'caption=Лейаут,input=none');
        
        $this->setDbUnique('menu,domainId');
    }
    
    
    /**
     * Връша текущия език за CMS часта
     */
    public static function getLang()
    {
        $lang = cms_Domains::getPublicDomain('lang');
        
        return $lang;
    }
    
    
    /**
     * Записва в сесията текущия език на CMS изгледа
     */
    public static function setLang($lang)
    {
        core_Lg::set($lang, !haveRole('user'));
        cms_Domains::getPublicDomain(null, $lang);
        
        $langsArr = arr::make(core_Lg::getLangs());
        if ($langsArr[$lang]) {
            core_Lg::push($lang);
        }
    }
    
    
    /**
     * Екшън за избор на език на интерфейса за CMS часта
     */
    public function act_SelectLang()
    {
        $langsArr = cms_Domains::getCmsLangs();
        
        $lang = $langsArr[Request::get('lang')];
        
        if ($lang) {
            self::setLang($lang);
            
            return new Redirect(array('cms_Content', 'Show', 'lg' => $lang));
        }
        
        $lang = self::getLang();
        
        $res = new ET(getFileContent('cms/themes/default/LangSelect.shtml'));
        
        $s = $res->getBlock('SELECTOR');
        
        foreach ($langsArr as $lg) {
            if ($lg == $lang) {
                $attr = array('class' => 'selected');
            } else {
                $attr = array('class' => '');
            }
            
            $filePath = getFullPath('img/flags/' . $lg . '.png');
            $img = ' ';
            
            if ($filePath) {
                $imageUrl = sbf('img/flags/' . $lg . '.png', '');
                $img = ht::createElement('img', array('src' => $imageUrl, 'alt' => $lg . ' language'));
            }
            
            $url = array($this, 'SelectLang', 'lang' => $lg);
            $s->replace(ht::createLink($img . drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName'), $url, null, $attr), 'SELECTOR');
            $s->append2master();
        }
        
        Mode::set('wrapper', 'cms_page_External');
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#order', 'ASC');
    }
    
    
    /**
     * Подготвя данните за публичното меню
     */
    public function prepareMenu_($data)
    {
        $query = self::getQuery();
        $query->orderBy('#order');
        
        $data->domainId = cms_Domains::getPublicDomain('id');
        
        if ($data->domainId) {
            $data->items = $query->fetchAll("#state = 'active' AND #domainId = {$data->domainId}");
        }
    }
    
    
    /**
     * Рендира публичното меню
     */
    public function renderMenu_($data)
    {
        $tpl = new ET();
        
        $cMenuId = Mode::get('cMenuId');
        if (!$cMenuId) {
            $cMenuId = Request::get('cMenuId', 'int');
            Mode::set('cMenuId', $cMenuId);
        }
        
        $loginLink = false;
        
        if (is_array($data->items)) {
            foreach ($data->items as $rec) {
                list($f, $s) = explode(' ', $rec->menu, 2);
                
                if (is_Numeric($f)) {
                    $rec->menu = $s;
                }
                
                $attr = array();
                if (($cMenuId == $rec->id)) {
                    $attr['class'] = 'selected';
                }
                
                $url = $this->getContentUrl($rec);
                
                if (!$url) {
                    $url = '#';
                }
                $urlS = toUrl($url);
                if (strpos($urlS, '/core_Users/') !== false || strpos($urlS, 'Portal/Show/') !== false) {
                    $loginLink = true;
                }
                
                $tpl->append(ht::createLink($rec->menu, $url, null, $attr));
            }
        }
        
        
        // Ако имаме действащи менюта на повече от един език, показваме бутон за избор на езика
        $usedLangsArr = cms_Domains::getCmsLangs();
        
        if (count($usedLangsArr) == 2) {
            
            // Премахваме текущия език
            $lang = self::getLang();
            
            foreach ($usedLangsArr as $lg) {
                $attr = array('title' => drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName'), 'id' => 'set-lang-' . $lg);
                
                if ($lg == $lang) {
                    continue;
                }
                
                $filePath = getFullPath('img/flags/' . $lg . '.png');
                $img = ' ';
                
                if ($filePath) {
                    $imageUrl = sbf('img/flags/' . $lg . '.png', '');
                    $img = ht::createElement('img', array('src' => $imageUrl, 'alt' => $lg));
                }
                
                $url = array($this, 'SelectLang', 'lang' => $lg);
                
                
                $tpl->append(ht::createLink($img, $url, null, $attr));
            }
        } elseif (count($usedLangsArr) > 1) {
            $attr['class'] = 'selectLang';
            $attr['title'] = implode(', ', $usedLangsArr);
            if (Request::get('Ctr') == 'cms_Content' && Request::get('Act') == 'selectLang') {
                $attr['class'] = 'selected';
            }
            $tpl->append(ht::createLink(ht::createElement('img', array('src' => sbf('img/24/globe.png', ''))), array($this, 'selectLang'), null, $attr));
        }
        
        
        // Поставяне на иконка за Вход
        if ($loginLink == false) {
            $dRec = cms_Domains::getPublicDomain('form');
            
            if (haveRole('user')) {
                $filePath = 'img/32/inside';
                $title = 'Меню||Menu';
            } else {
                $filePath = 'img/32/login';
                $title = 'Вход||Log in';
            }
            
            if ((isset($dRec->baseColor) && phpcolor_Adapter::checkColor($dRec->baseColor) && Request::get('Ctr') != 'core_Users') ||
                (isset($dRec->activeColor) && phpcolor_Adapter::checkColor($dRec->activeColor) && Request::get('Ctr') == 'core_Users')) {
                $filePath .= 'Dark';
            } else {
                $filePath .= 'Light';
            }
            
            if (Mode::is('screenMode', 'narrow')) {
                $filePath .= 'M';
            }
            
            $filePath .= '.png';
            
            $tpl->append(ht::createLink(
                ht::createImg(array('path' => $filePath)),
                array('Portal', 'Show'),
                null,
                array('title' => $title, 'class' => Request::get('Ctr') == 'core_Users' ? 'selected' : '')
            ));
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща URL към съдържанието, което отговаря на този запис
     */
    public static function getContentUrl($rec, $absolute = false)
    {
        $rec = self::fetchRec($rec);
        if ($rec->source && cls::load($rec->source, true) && cls::haveInterface('cms_SourceIntf', $rec->source)) {
            $source = cls::get($rec->source);
            $url = $source->getUrlByMenuId($rec->id);
        } elseif ($rec->url) {
            if (strpos($rec->url, ',')) {
                $url = arr::make($rec->url);
            } else {
                $url = core_App::parseLocalUrl('/' . ltrim($rec->url, '/'));
            }
        } else {
            $url = '';
        }
        
        core_Request::addUrlHash($url);
        
        if ($absolute && is_array($url)) {
            $domain = cms_Domains::fetch($rec->domainId)->domain;
            if ($domain != 'localhost' || in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
                $url = Url::change(toUrl($url, 'absolute'), null, $domain);
            }
        }
        
        return $url;
    }
    
    
    /**
     * Връща кратко URL отговарящо на текущото
     */
    public static function getShortUrl($cUrl = null)
    {
        if (!$cUrl) {
            $cUrl = getCurrentUrl();
        }
        
        // За да не влезе в безкраен цикъл, да не вика себе си
        if (strtolower($cUrl['Ctr']) == 'cms_content') {
            
            return $cUrl;
        }
        
        if (!$cUrl['Ctr']) {
            $query = self::getQuery();
            $domainId = cms_Domains::getPublicDomain('id');
            $query->where("#state = 'active' AND #domainId = {$domainId}");
            $query->orderBy('#order');
            
            $rec = $query->fetch();
            if ($rec) {
                $cUrl = self::getContentUrl($rec);
                
                // Преобразуваме от поредни към именовани параметри
                if (isset($cUrl[0]) && !isset($cUrl['Ctr'])) {
                    $cUrl['Ctr'] = $cUrl[0];
                }
                if (isset($cUrl[1]) && !isset($cUrl['Act'])) {
                    $cUrl['Act'] = $cUrl[1];
                }
                if (isset($cUrl[2]) && !isset($cUrl['id'])) {
                    $cUrl['id'] = $cUrl[2];
                }
            }
        }
        
        if ($cUrl['Ctr'] && cls::existsMethod($cUrl['Ctr'], 'getShortUrl')) {
            $man = cls::get($cUrl['Ctr']);
            $cUrl = $man->getShortUrl($cUrl);
        }
        
        return $cUrl;
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->source) {
            if (cls::load($rec->source, true) && cls::haveInterface('cms_SourceIntf', $rec->source)) {
                $Source = cls::getInterface('cms_SourceIntf', $rec->source);
                $workUrl = $Source->getWorkshopUrl($rec->id);
                $row->source = ht::createLink($row->source, $workUrl);
            } else {
                $row->source = "<span class='red'>" . tr('Проблем с показването') . '<span>';
            }
        }
        
        $publicUrl = $mvc->getContentUrl($rec, true);
        $row->menu = ht::createLink($row->menu, $publicUrl, false, 'ef_icon=img/16/monitor.png');
    }
    
    
    /**
     * Връща основното меню
     */
    public static function getMenu()
    {
        $data = new stdClass();
        $self = cls::get('cms_Content');
        $self->prepareMenu($data);
        
        return  $self->renderMenu($data);
    }
    
    
    /**
     * Връща футера на страницата
     */
    public static function getLayout()
    {
        $layoutPath = Mode::get('cmsLayout');
        
        $layout = new ET($layoutPath ? tr('|*' . getFileContent($layoutPath)) : '[#PAGE_CONTENT#]');
        
        return $layout;
    }
    
    
    /**
     * Задава текущото меню
     */
    public static function setCurrent($menuId = null, $layout = null)
    {
        if ($menuId && ($rec = cms_Content::fetch($menuId))) {
            Mode::set('cMenuId', $menuId);
            cms_Domains::setPublicDomain($rec->domainId);
        }
        
        $lg = cms_Domains::getPublicDomain('lang');
        
        self::setLang($lg);
        
        Mode::set('wrapper', 'cms_page_External');
    }
    
    
    /**
     * Връща менюто по подразбиране за съответния тип източник на съдържание
     *
     * @param mixed $class Името на класа
     *
     * @return int $menuId id-то на менюто
     */
    public static function getDefaultMenuId($class)
    {
        $classId = core_Classes::getId($class);
        $domainId = cms_Domains::getPublicDomain('id');
        $query = self::getQuery();
        $query->orderBy('#order', 'ASC');
        $rec = $query->fetch("#source = {$classId} AND #domainId = {$domainId}");
        if ($rec) {
            
            return $rec->id;
        }
    }
    
    
    /**
     * Връща футера на страницата
     */
    public static function getFooter()
    {
        if (core_Lg::getCurrent() !== 'bg') {
            $footer = new ET(getFileContent('cms/tpl/FooterEn.shtml'));
        } else {
            $footer = new ET(getFileContent('cms/tpl/Footer.shtml'));
        }
        $footer->replace(getBoot() . '/' . EF_SBF . '/' . EF_APP_NAME, 'boot');
        
        return $footer;
    }
    
    
    /**
     * Показва посоченото меню, а ако няма такова - показва менюто с най-малък номер
     */
    public function act_Show()
    {
        $menuId = Request::get('id', 'int');
        
        if (!$menuId) {
            $query = self::getQuery();
            $domainId = cms_Domains::getPublicDomain('id');
            $query->where("#state = 'active' AND #domainId = {$domainId}");
            $query->orderBy('#order');
            $rec = $query->fetch();
        } else {
            $rec = $this->fetch($menuId);
        }
        
        Mode::set('cMenuId', $menuId);
        
        if ($rec && ($url = $this->getContentUrl($rec))) {
            
            return Request::forward($url);
        }
        
        if (!Mode::get('lg')) {
            $lang = cms_Domains::detectLang(cms_Domains::getCmsLangs());
            core_Lg::set($lang);
        }
        
        return new Redirect(array('bgerp_Portal', 'Show'));
    }
    
    
    /**
     * Титлата за листовия изглед
     * Съдържа и текущия домейн
     */
    public static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $data->title .= cms_Domains::getCurrentDomainInTitle();
    }
    
    
    /**
     * Връща опциите от менюто, които отговарят на текущия домейн и клас
     */
    public static function getMenuOpt($class, $domainId = null)
    {
        $classId = core_Classes::getId($class);
        if (!$domainId) {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        $query = self::getQuery();
        $query->orderBy('#order');
        while ($rec = $query->fetch("#domainId = {$domainId} AND #source = {$classId}")) {
            $res[$rec->id] = $rec->menu;
        }
        
        return $res;
    }
    
    
    /**
     * Модификация на ролите, които могат да видят избраната тема
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        //  Кой може да обобщава резултатите
        if ($action == 'delete' && isset($rec->id, $rec->source)) {
            if (isset($rec->source) && cls::load($rec->source, true) && cls::haveInterface('cms_SourceIntf', $rec->source)) {
                $source = cls::get($rec->source);
                if ($source->getUrlByMenuId($rec->id) != '#') {
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се преди запис в модела
     * - Ако полето за подредба не е попълнено, попълва стойност, която поставя менюто последно
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null)
    {
        if (!$rec->order) {
            $lastOrder = 0;
            $query = self::getQuery();
            $query->orderBy('#order', 'DESC');
            $cd = cms_Domains::getCurrent();
            
            $typeOrder = cls::get('type_Order');
            if ($lastOrder = $query->fetch("#state = 'active' AND #domainId = {$cd}")->order) {
                list($lastOrder, ) = explode('.', $typeOrder->toVerbal_($lastOrder));
            }
            $rec->order = $typeOrder->fromVerbal($lastOrder + 10);
        }
    }
    
    
    /**
     * Добавя към шаблона каноничното URL
     */
    public static function addCanonicalUrl($url, $tpl)
    {
        $selfUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . rtrim($_SERVER['HTTP_HOST'], '/') . '/' . ltrim($_SERVER['REQUEST_URI'], '/');
        
        if ($url != $selfUrl) {
            $tpl->append("\n<link rel=\"canonical\" href=\"{$url}\">", 'HEAD');
        }
    }
    
    
    /**
     * Добавя параметрите за SEO оптимизация
     */
    public static function setSeo($content, $sRec)
    {
        expect(is_object($sRec), $sRec);
        
        $rec = clone($sRec);
        
        if ($rec->seoTitle) {
            $content->prependOnce(type_Varchar::escape(trim(html_entity_decode(strip_tags($rec->seoTitle)))) . ' » ', 'PAGE_TITLE');
        }
        
        if (!$rec->seoDescription) {
            $rec->seoDescription = cms_Domains::getPublicDomain('seoDescription');
        }
        
        if ($rec->seoDescription) {
            $content->replace(ht::escapeAttr(trim(strip_tags(html_entity_decode($rec->seoDescription)))), 'META_DESCRIPTION');
        }
        
        if (!$rec->seoKeywords) {
            $rec->seoKeywords = cms_Domains::getPublicDomain('seoKeywords');
        }
        
        if ($rec->seoKeywords) {
            $content->replace(ht::escapeAttr(trim(strip_tags(html_entity_decode($rec->seoKeywords)))), 'META_KEYWORDS');
        }
    }
    
    
    public static function renderSearchResults($menuId, $q)
    {
        //$q = Request::get('q');
        //$menuId = Request::get('menuId', 'int');
        
        
        $query = self::getQuery();
        $query->orderBy('order');
        
        if ($menuId) {
            $rec = self::fetch($menuId);
            $domainId = $rec->domainId;
        } else {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        
        $query->where("#domainId = {$domainId} AND #id != {$menuId}");
        
        $res = array();
        
        do {
            if (!$rec->source) {
                continue;
            }
            $cls = cls::get($rec->source);
            
            if (cls::existsMethod($cls, 'getSearchResults')) {
                $res = $cls->getSearchResults($rec->id, $q);
                if (count($res)) {
                    $html .= "<h2>Резултати в <strong style='color:green'>" . type_Varchar::escape($rec->menu) . '</strong></h2>';
                    $html .= '<ul>';
                    foreach ($res as $o) {
                        $html .= "<li style='font-size:1.2em; margin:5px;' >" . ht::createLink($o->title, $o->url) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
        } while ($rec = $query->fetch());
        
        $res = new ET("<h1>Търсене на \"<strong style='color:green'>" . type_Varchar::escape($q) . "</strong>\"</h1><div style='padding:0px;' class='results'>[#1#]</div>", $html);
        
        plg_Search::highlight($res, $q, 'results');
        
        return  $res;
    }
}
