<?php


/**
 * Публично съдържание, подредено в меню
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Content extends core_Master
{
    /**
     * Име под което записваме в сесията текущия език на CMS изгледа
     */
    const CMS_CURRENT_LANG = 'CMS_CURRENT_LANG';
    
    
    /**
     * Заглавие
     */
    public $title = 'CMS менюта';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'CMS меню';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2, plg_RowTools2, plg_Printing, cms_Wrapper, plg_Sorting, plg_Search,cms_DomainPlg, plg_Rejected, doc_FolderPlg';
    
    
    /**
     * Папка се създава само на менютата, чийто източник е документ
     */
    public $autoCreateFolder = 'manual';
    
    
    /**
     * Достъп по подразбиране до папката на менюто
     */
    public $defaultAccess = 'public';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cms/tpl/SingleLayoutContent.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cms_menu.png';
    
    
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
     * Поле за линк към единичния изглед
     */
    public $rowToolsSingleField = 'menu';
    
    
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
        
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн,notNull,mandatory,autoFilter');
        $this->FLD('source', 'class(interface=cms_SourceIntf, allowEmpty, select=title)', 'caption=Източник,mandatory,silent,refreshForm');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,oldFieldName=url');
        $this->FLD('layout', 'html', 'caption=Лейаут,input=none');
        
        $this->FLD('sharedDomains', 'keylist(mvc=cms_Domains, select=titleExt)', 'caption=Споделяне с,autoFilter');

        $this->FLD('menuWallpaper', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Изображение,hint=Примерни размери 1920×650px');
        $this->FLD('sourceSettings', 'blob(serialize,compress)', 'input=none');
        
        $this->setDbUnique('menu,domainId');
    }
    
    
    /**
     * Дали менюто е корица на папка - когато източникът му е документ
     */
    public static function isFolderCover($rec)
    {
        $source = $rec->source ?? null;
        
        return !empty($source) && cls::load($source, true) && cls::haveInterface('doc_DocumentIntf', $source);
    }
    
    
    /**
     * Заглавие на менюто с домейна му
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $title = $rec->menu ?? '';
        if (!empty($rec->domainId)) {
            $title .= ' [' . cms_Domains::getTitleById($rec->domainId, false) . ']';
        }
        
        return $escaped ? type_Varchar::escape($title) : $title;
    }
    
    
    /**
     * Източникът не се сменя, ако в папката на менюто вече има документи
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $folderId = $form->rec->folderId ?? null;
        if (!empty($form->rec->id) && !empty($folderId) && ($data->action ?? null) != 'clone') {
            if (doc_Containers::fetchField("#folderId = {$folderId}", 'id')) {
                $form->setField('source', array('hint' => 'Източникът не може да се смени, защото в папката на менюто има документи'));
                $form->setReadOnly('source');
            }
        }
        
        // Полетата за настройки на източника
        $settings = $form->rec->sourceSettings ?? null;
        foreach (self::getSourceSettingsFields($form->rec->source ?? null)->fields as $name => $field) {
            $form->fields[$name] = $field;
            if (is_array($settings) && array_key_exists($name, $settings)) {
                $form->setDefault($name, $settings[$name]);
            }
        }
    }
    
    
    /**
     * Прибира настройките на източника в sourceSettings
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        $settings = array();
        foreach (self::getSourceSettingsFields($form->rec->source ?? null)->fields as $name => $field) {
            $settings[$name] = $form->rec->{$name} ?? null;
        }
        $form->rec->sourceSettings = countR($settings) ? $settings : null;
    }
    
    
    /**
     * Връща полетата за настройки на менюто, които източникът добавя
     *
     * @param int|string|null $source
     * @return core_FieldSet
     */
    public static function getSourceSettingsFields($source)
    {
        $fieldset = cls::get('core_FieldSet');
        if (!empty($source) && cls::load($source, true) && cls::haveInterface('cms_SourceIntf', $source)) {
            cls::getInterface('cms_SourceIntf', $source)->addContentSettingsFields($fieldset);
        }
        
        return $fieldset;
    }
    
    
    /**
     * Редовете с настройките на източника за сингъла
     *
     * @param stdClass $rec
     * @return string|null
     */
    private static function renderSourceSettings($rec)
    {
        $settings = $rec->sourceSettings ?? null;
        $res = '';
        foreach (self::getSourceSettingsFields($rec->source ?? null)->fields as $name => $field) {
            $value = (is_array($settings) && isset($settings[$name])) ? $settings[$name] : ($field->value ?? null);
            if (!isset($value)) {
                continue;
            }
            $caption = explode('->', $field->caption ?? $name);
            $res .= "<tr><td class='dt'>" . tr(end($caption)) . ':</td><td>' . $field->type->toVerbal($value) . '</td></tr>';
        }
        
        return $res === '' ? null : $res;
    }
    
    
    /**
     * Връща настройка на източника на менюто
     *
     * @param stdClass|null $menuRec
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getSourceSetting($menuRec, $name, $default = null)
    {
        $settings = is_object($menuRec) ? ($menuRec->sourceSettings ?? null) : null;
        
        return is_array($settings) ? ($settings[$name] ?? $default) : $default;
    }
    
    
    /**
     * Форсира папката на менютата, чийто източник е документ
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        if (empty($rec->folderId) && self::isFolderCover($rec)) {
            $mvc->forceCoverAndFolder($rec);
        }
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
    public static function setLang($lang, $force = null)
    {
        if (!isset($force)) {
            $force = (boolean) !haveRole('user');
        }
        
        core_Lg::set($lang, $force);
        cms_Domains::getPublicDomain(null, $lang);
        
        $langArr = arr::make(core_Lg::getLangs());
        if (!empty($langArr[$lang])) {
            core_Lg::push($lang);
        }
    }
    
    
    /**
     * Екшън за избор на език на интерфейса за CMS часта
     */
    public function act_SelectLang()
    {
        $langsArr = cms_Domains::getCmsLangs();
        
        $lang = $langsArr[Request::get('lang')] ?? null;
        
        if ($lang) {
            self::setLang($lang, true);
            
            return new Redirect(array('cms_Content', 'Show', 'lg' => $lang));
        }
        
        $lang = self::getLang();
        
        $res = new ET(getFileContent('cms/themes/default/LangSelect.shtml'));
        $res->prepend("\n<meta name=\"robots\" content=\"noindex\">", 'HEAD');
        
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
     * Връща или първото id от menuId + $sharedMenusIds, което е от текущия домейн, или $menuId
     */
    public static function getMainMenuId($menuId, $sharedMenuIds)
    {
        if (empty($sharedMenuIds)) {
            $res = $menuId;
        } else {
            $domainId = cms_Domains::getPublicDomain('id');
            $ids = str_replace('|', ',', trim($sharedMenuIds, '|'));
            if (self::fetch("#id = {$menuId} && #domainId = {$domainId}")) {
                $res = $menuId;
            } elseif ($rec = self::fetch("#id IN ({$ids}) && #domainId = {$domainId}")) {
                $res = $rec->id;
            } else {
                $res = $menuId;
            }
        }
        
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
        
        if (is_array($data->items ?? null)) {
            foreach ($data->items as $rec) {
                $attr = array();
                if (($cMenuId == $rec->id)) {
                    $attr['class'] = 'selected';
                    $attr['aria-current'] = 'page';
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
        
        $theme = cms_ProtoTheme::getCurrent();
        $tools = new ET();

        // Поставяне на иконка за Вход
        if ($loginLink == false) {
            $title = '';
            $loginContent = $theme->getMenuLoginContent($title);
            $tools->append(ht::createLink(
                $loginContent,
                array('Portal', 'Show'),
                null,
                array('title' => $title, 'class' => Request::get('Ctr') == 'core_Users' ? 'loginIcon selected' : 'loginIcon')
            ));
        }
        
        // Ако имаме действащи менюта на повече от един език, показваме бутон за избор на езика
        $theme->renderMenuLangs($tools, cms_Domains::getCmsLangs());
        $tpl->append($theme->wrapMenuTools($tools));

        return $tpl;
    }
    
    
    /**
     * Връща URL към съдържанието, което отговаря на този запис
     */
    public static function getContentUrl($rec, $absolute = false)
    {
        $rec = self::fetchRec($rec);
        if (!empty($rec->source) && cls::load($rec->source, true) && cls::haveInterface('cms_SourceIntf', $rec->source)) {
            $source = cls::get($rec->source);
            $url = $source->getUrlByMenuId($rec->id);
        } else {
            $url = '';
        }
        
        core_Request::addUrlHash($url);
        
        if ($absolute && is_array($url)) {
            $domain = cms_Domains::fetchField($rec->domainId, 'domain');
            if ($domain != 'localhost' || in_array($_SERVER['REMOTE_ADDR'] ?? null, array('127.0.0.1', '::1'))) {
                $url = core_Url::change(toUrl($url, 'absolute'), null, $domain);
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
        if (strtolower($cUrl['Ctr'] ?? '') == 'cms_content') {
            
            return $cUrl;
        }
        
        if (!($cUrl['Ctr'] ?? null)) {
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
        
        if (is_array($cUrl) && !empty($cUrl['Ctr']) && cls::existsMethod($cUrl['Ctr'], 'getShortUrl')) {
            $man = cls::get($cUrl['Ctr']);
            $cUrl = $man->getShortUrl($cUrl);
        }
        
        return $cUrl;
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if (!empty($rec->source)) {
            if (cls::load($rec->source, true) && cls::haveInterface('cms_SourceIntf', $rec->source)) {
                $Source = cls::getInterface('cms_SourceIntf', $rec->source);
                $workUrl = $Source->getWorkshopUrl($rec->id);
                $row->source = ht::createLink($row->source, $workUrl);
            } else {
                $row->source = "<span class='red'>" . tr('Проблем с показването') . '<span>';
            }
        }
        
        if (isset($fields['-single'])) {
            $row->SOURCE_SETTINGS = self::renderSourceSettings($rec);
        }
        
        if (isset($fields['-list'])) {
            $publicUrl = $mvc->getContentUrl($rec, true);
            if (!empty($publicUrl) && $publicUrl != '#') {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Преглед', $publicUrl, 'alwaysShow,ef_icon=img/16/monitor.png,title=Преглед във външната част');
            }
        }
    }
    
    
    /**
     * Бутон за преглед във външната част
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $publicUrl = $mvc->getContentUrl($data->rec, true);
        if (!empty($publicUrl) && $publicUrl != '#') {
            $data->toolbar->addBtn('Преглед', $publicUrl, null, 'ef_icon=img/16/monitor.png,title=Преглед във външната част');
        }
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
    public static function getLayout_()
    {
        $layoutPath = Mode::get('cmsLayout');
        
        $layout = new ET($layoutPath ? tr('|*' . getFileContent($layoutPath)) : '[#PAGE_CONTENT#]');
        
        return $layout;
    }
    
    
    /**
     * Задава текущото меню
     */
    public static function setCurrent($menuId = null, $externalPage = true)
    {
        if ($menuId && ($rec = cms_Content::fetch($menuId))) {
            Mode::set('cMenuId', $menuId);
            cms_Domains::setPublicDomain($rec->domainId);
            if (haveRole('powerUser')) {
                cms_Domains::selectCurrent($rec->domainId);
            }
        }
        
        $lg = cms_Domains::getPublicDomain('lang');
        
        self::setLang($lg);
        
        if ($externalPage) {
            Mode::set('wrapper', 'cms_page_External');
        }
    }
    
    
    /**
     * Връща менюто по подразбиране за съответния тип източник на съдържание
     *
     * @param mixed $class Името на класа
     *
     * @return int $menuId id-то на менюто
     */
    public static function getDefaultMenuId($class, $domainId = null)
    {
        if (!$domainId) {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        
        $classId = core_Classes::getId($class);
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
    protected static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $data->title .= cms_Domains::getCurrentDomainInTitle($data);
    }


    /**
     * Връща опциите от менюто, които отговарят на текущия домейн и клас
     *
     * @param mixed $class
     * @param null|int $domainId
     * @param null|int $exceptDomainId
     *
     * @return array $res
     */
    public static function getMenuOpt($class, $domainId = null, $exceptDomainId = null)
    {
        $classId = core_Classes::getId($class);

        $res = array();
        $query = self::getQuery();
        $query->where("#source = {$classId} AND #state = 'active'");
        if(isset($domainId)){
            $query->where("#domainId = {$domainId}");
        }

        if(isset($exceptDomainId)){
            $query->where("#domainId != {$exceptDomainId}");
        }

        $query->orderBy('#order');

        while ($rec = $query->fetch()) {
            if(!isset($domainId)){
                $title = cms_Content::getVerbal($rec, 'menu') . ' (' . cms_Content::getVerbal($rec, 'domainId') . ')';
            } else {
                $title = cms_Content::getVerbal($rec, 'menu');
            }

            $res[$rec->id] = $title;
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
        if (empty($rec->order)) {
            $lastOrder = 0;
            $query = self::getQuery();
            $query->orderBy('#order', 'DESC');
            $cd = !empty($rec->domainId) ? $rec->domainId : cms_Domains::getCurrent();
            
            $typeOrder = cls::get('type_Order');
            $lastRec = $query->fetch("#state = 'active' AND #domainId = {$cd}");
            if ($lastRec && $lastRec->order) {
                list($lastOrder, ) = explode('.', $typeOrder->toVerbal_($lastRec->order));
            }
            $rec->order = $typeOrder->fromVerbal($lastOrder + 10);
        }
    }
    
    
    /**
     * Добавя към шаблона каноничното URL
     */
    public static function addCanonicalUrl($url, $tpl)
    {
        $selfUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
        
        if ($url != $selfUrl) {
            $tpl->append("\n<link rel=\"canonical\" href=\"{$url}\">", 'HEAD');
        }
    }
    
    
    /**
     * Подготвя параметрите за SEO оптимизация
     */
    public static function prepareSeo_($rec, $suggestions = array())
    {
        expect(is_object($rec), $rec);

        $rec->seoDescription = $rec->seoDescription ?? null;
        $rec->seoTitle = $rec->seoTitle ?? null;
        $rec->seoKeywords = $rec->seoKeywords ?? null;
        $rec->seoThumb = $rec->seoThumb ?? null;

        // seoTitle
        if (!$rec->seoTitle) {
            $rec->seoTitle = $suggestions['seoTitle'] ?? null;
        }
        if ($rec->seoTitle) {
            $rec->seoTitle = type_Varchar::escape(trim(html_entity_decode(strip_tags($rec->seoTitle))));
        }
        
        // seoDescription
        if (empty($rec->seoDescription) && !empty($suggestions['seoDescription'])) {
            $rec->seoDescription = self::getSeoDescription($suggestions['seoDescription']);
        }
        if (!$rec->seoDescription) {
            $rec->seoDescription = cms_Domains::getPublicDomain('seoDescription');
        }
        if ($rec->seoDescription) {
            $rec->seoDescription = ht::escapeAttr(trim(strip_tags(html_entity_decode($rec->seoDescription))));
        }
        
        // seoKeywords
        if (!$rec->seoKeywords) {
            $rec->seoKeywords = $suggestions['seoKeywords'] ?? null;
        }
        if (!$rec->seoKeywords) {
            $rec->seoKeywords = cms_Domains::getPublicDomain('seoKeywords');
        }
        if ($rec->seoKeywords) {
            $rec->seoKeywords = ht::escapeAttr(trim(strip_tags(html_entity_decode($rec->seoKeywords))));
        }
        
        // seoThumb
        if (!$rec->seoThumb) {
            $rec->seoThumb = $suggestions['seoThumb'] ?? null;
        }
        if (!$rec->seoThumb && !empty($suggestions['seoDescription'])) {
            $rec->seoThumb = cms_Content::getSeoThumb($suggestions['seoDescription']);
        }
        
        Mode::set('SOC_TITLE', $rec->seoTitle ?? null);
        Mode::set('SOC_SUMMARY', $rec->seoDescription ?? null);
    }
    
    
    /**
     * Добавя параметрите за SEO оптимизация
     */
    public static function renderSeo_($content, $rec)
    {
        expect(is_object($rec), $rec);
        
        if (!empty($rec->seoTitle)) {
            $content->prependOnce($rec->seoTitle . ' » ', 'PAGE_TITLE');
        }
        
        // seoDescription
        if (!empty($rec->seoDescription)) {
            $content->replace($rec->seoDescription, 'META_DESCRIPTION');
        }
        
        // seoKeywords
        if (!empty($rec->seoKeywords)) {
            $content->replace($rec->seoKeywords, 'META_KEYWORDS');
        }
    }
    
    
    /**
     * Връща началото на дадения текст, като се опитва точни изречения
     */
    public static function getSeoDescription($text, $minLen = 280, $maxLen = 350)
    {
        // Обръща се текства в хтмл, ако е ричтекст
        $rt = cls::get('type_RichText');
        $text = $rt->toHtml($text);

        // Реплейсват се стиловете и таговете, ако има такива
        $text = preg_replace('/<style(.*?)>(.*?)<\/style>/is', '', $text);
        $text = strip_tags($text);

        $text = preg_replace("/([\p{L}0-9_]{3,16}\\.) /ui", "$1\n", $text);
        $lines = explode("\n", $text);
        
        $res = '';
        foreach ($lines as $l) {
            $res .= ' ' . $l;
            if (mb_strlen($res) >= $minLen) {
                break;
            }
        }
        
        $res = trim($res);
        
        if (mb_strlen($res) > $maxLen) {
            $words = explode(' ', $res);
            $res = '';
            foreach ($words as $w) {
                if (mb_strlen($res) + mb_strlen($w) > $maxLen) {
                    break;
                }
                $res .= ' ' . $w;
            }
        }
        
        $res = preg_replace('/ +/ui', ' ', trim($res));
        
        return $res;
    }
    
    
    /**
     * Връща файла на първата срещната картинка в ричтекста
     */
    public static function getSeoThumb($text)
    {
        $pattern = cms_GalleryRichTextPlg::IMG_PATTERN;
        $matches = null;
        preg_match($pattern, $text, $matches);
        $fileSrc = null;

        if (!empty($matches[1])) {
            $iHnd = $matches[1];
            $iRec = cms_GalleryImages::fetch(array("#title = '[#1#]'", $iHnd));
            $fileSrc = $iRec->src ?? null;
        }
        
        return $fileSrc;
    }
    
    
    /**
     * Рендира резултатите отговарящи на търсенето във външната част
     */
    public static function renderSearchResults($menuId, $q, $oQ = null)
    {
        $query = self::getQuery();
        $query->orderBy('order');
        $rec = null;

        if ($menuId) {
            $rec = self::fetch($menuId);
            $domainId = !empty($rec) ? $rec->domainId : null;
        } else {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        
        $query->where("(#domainId = {$domainId} OR #sharedDomains LIKE '%|{$domainId}|%') AND #id != {$menuId} AND #source IS NOT NULL");
        $query->orderBy('id', 'ASC');
        $html = '';

        $recs = $query->fetchAll();
        if(!empty($rec)){
            $recs = array($rec->id => $rec) + $recs;
        }

        foreach ($recs as $rec) {
            if (!cls::load($rec->source, true)) {
                continue;
            }

            $cls = cls::get($rec->source);
            if (cls::existsMethod($cls, 'getSearchResults')) {
                $res = $cls->getSearchResults($rec->id, $q);

                if (countR($res)) {
                    $domainName = '';
                    if ($rec->domainId != $domainId) {
                        $domainHost = cms_Domains::fetchField($rec->domainId, 'domain');
                        Mode::push('BGERP_CURRENT_DOMAIN', $domainHost);
                        $domainTitle = $domainHost;
                        if ($domainTitle != 'localhost') {
                            $domainName = ' (' . $domainTitle . ')';
                        }
                    }

                    $html .= "<h2><strong style='color:green'>" . type_Varchar::escape(!empty($rec->title) ? $rec->title : $rec->menu) . $domainName . '</strong></h2>';
                    $itemsInTable = $itemsInUl = '';

                    foreach ($res as $o) {
                        if (isset($o->img) && $o->img instanceof thumb_Img) {
                            $img = $o->img->createImg(array('class' => 'eshop-product-image'));
                            $titleLink = ht::createLink($o->title, $o->url, false, array('class' => "searchName"));
                            if (haveRole('debug') && isset($o->rating)) {
                                $titleLink = ht::createHint($titleLink, "Рейтинг|*: {$o->rating}");
                            }
                            $itemsInTable .= "<tr><td class='searchImg'>" . ht::createLink($img, $o->url) . "</td><td class='searchName'>" . $titleLink . '</td></tr>';
                        } else {
                            $titleLink = ht::createLink($o->title, $o->url);
                            if (haveRole('debug') && isset($o->rating)) {
                                $titleLink = ht::createHint($titleLink, "Рейтинг|*: {$o->rating}");
                            }

                            $itemsInUl .= "<li style='font-size:1.2em; margin:5px;'>" . $titleLink . '</li>';
                        }
                    }

                    if (!empty($itemsInUl)) {
                        $html .= "<ul>{$itemsInUl}</ul>";
                    }

                    if (!empty($itemsInTable)) {
                        $html .= "<table class='searchResult'>{$itemsInTable}</table>";
                    }

                    if ($rec->domainId != $domainId) {
                        Mode::pop('BGERP_CURRENT_DOMAIN');
                    }
                }
            }
        }
        
        if ($html) {
            if (!isset($oQ)) {
                $html = new ET('<h3>' . tr('Търсене на') . " \"<strong style='color:green'>" . type_Varchar::escape($q) . "</strong>\"</h3><div style='padding:0px;' class='results'>[#1#]</div>", $html);
            } else {
                $html = new ET('<h3>' . tr('При търсене на') . " \"<strong style='color:green'>" . type_Varchar::escape($oQ) . '</strong>" ' . tr('не бяха открити точни резултати, затова са показани приблизителни') . ":</h3><div style='padding:0px;' class='results'>[#1#]</div>", $html);
            }
            plg_Search::highlight($html, $q, 'results');
        } else {
            if (!isset($oQ)) {
                // Правим опит да подобрим заявката
                if ($nQ = self::reduceSearch($q)) {
                    $html = self::renderSearchResults($menuId, $nQ, $q);
                }
            }
            
            if (empty($html)) {
                $html = new ET('<h1>'. tr('При търсене на') . " \"<strong style='color:green'>" . type_Varchar::escape(!empty($oQ) ? $oQ : $q) . '</strong>" ' . tr('не бяха открити резултати') . '</h1>');
            }
        }
        
        return  $html;
    }
    
    
    /**
     * Редуцира заявка за търсене, като същевременно се опитва да оправи правописа на думите
     */
    public static function reduceSearch($q)
    {
        $domainId = cms_Domains::getPublicDomain('id');
        
        $kArr = self::getAllKeywords($domainId);  
        $q = str::utf2ascii($q);
        $iConvStr = @iconv('UTF-8', 'ASCII//TRANSLIT', $q);
        if (isset($iConvStr)) {
            $q = $iConvStr;
        }
        
        $qArr = plg_Search::parseQuery($q);
        
        $resArr = array();
        $flag = false;
        $lastW = null;

        foreach ($qArr as $j => &$w) {
            $w = (string) $w;
            if ($w === '') {
                continue;
            }
            
            $f = $w[0];
            $len = strlen($w);
            
            // Отрицателните думи не ги обработваме
            if ($w[0] == '-') {
                $resArr[] = $w;
                $lastW = null;
                continue;
            }
           
            // Фразите не ги обработваме
            if ($w[0] == '"') {
                $flag = true;
                $resArr[] = $w . '"';
                $lastW = null;
                continue;
            }
            
            // Точни думи
            if (is_array($kArr[$f][$len] ?? null) && in_array($w, $kArr[$f][$len])) {
                $flag = true;
                $resArr[] = $w;
                $lastW = null;
                continue;
            } 
            
            // Разбити на две думи в заявката
            if (isset($lastW)) {  
                $dw = $lastW . $w;
                $df = $dw[0];
                $dLen = strlen($dw);
                if(is_array($kArr[$df][$dLen] ?? null) && in_array($dw, $kArr[$df][$dLen])) {
                    $flag = true;
                    $resArr[] = $dw;
                    $lastW = null;
                    continue;
                }
            }
            
            // Думи съставени от дума и число
            if(preg_match("/^([a-z]+)([0-9]+)$/", $w, $matches)) {
                $fw = $matches[1];
                $fLen = strlen($fw);
                if(is_array($kArr[$f][$fLen] ?? null) && in_array($fw, $kArr[$f][$fLen])) {
                    $flag = true;
                    $resArr[] = $fw;
                    $resArr[] = $matches[2];
                    $lastW = null;
                    continue;
                }
            }
            
            // Размито търсене
            if ($len > 3) {
                $len = strlen($w);
                $min = max(3, $len - 1);
                $max = $len + 2;
                $bestD = 1;
                $bestW = '';
                
                for ($i = $min; $i <= $max; $i++) {
                    if (is_array($kArr[$f][$i] ?? null)) {
                        foreach ($kArr[$f][$i] as $kw) {
                            $d = levenshtein($w, $kw) / $i;
                            if ($d < 0.20 && $d < $bestD) {
                                $bestD = $d;
                                $bestW = $kw;
                            }
                        }
                    }
                }
                
                if ($bestW) {
                    $resArr[] = $bestW;
                    $flag = true;
                    $lastW = null;
                    continue;
                }
            }
 
            // Нормализиране на думи с добре познати окончания
            if(preg_match("/^([a-z]+)(te|to|ta|at|yat|ska)$/", $w, $matches)) {
                $fw = $matches[1];
                $fLen = strlen($fw);
                if(is_array($kArr[$f][$fLen] ?? null) && in_array($fw, $kArr[$f][$fLen])) {
                    $flag = true;
                    $resArr[] = $fw;
                    $lastW = null;
                    continue;
                }
            }

            $lastW = $w;   
        }
        
        $res = null;
        
        if (countR($resArr) && $flag) {
            $res = implode(' ', $resArr);
        }
        
        return $res;
    }
    
    
    /**
     * Връща масив с подредени всички ключови думи от външната част
     */
    public static function getAllKeywords($domainId)
    {
        if (!($kArr = core_Cache::get('AllCmsKeywords', $domainId))) {
            $query = self::getQuery();
            $query->where("(#domainId = {$domainId} OR #sharedDomains LIKE '%|{$domainId}|%')");
            $kArr = array();
            while ($rec = $query->fetch()) {
                if (!$rec->source || !cls::load($rec->source, true)) {
                    continue;
                }
                
                $cls = cls::get($rec->source);
                
                if (cls::existsMethod($cls, 'getAllSearchKeywords')) {
                    $newWords = $cls::getAllSearchKeywords($rec->id);
                    foreach ($newWords as $w => $bool) {
                        $w = (string) $w;
                        if ($w === '') {
                            continue;
                        }

                        $kArr[$w[0]][strlen($w)][] = $w;
                    }
                }
            }
            
            core_Cache::set('AllCmsKeywords', $domainId, $kArr, 12 * 60, 'eshop_Groups,eshop_Products,blogm_Articles,cms_Articles');
        }
        
        return $kArr;
    }
    
    
    /**
     * Връща съдържанието на sitemap.xml за подадения домейн
     */
    public static function getSitemapXml($dRec)
    {
        $dQuery = cms_Domains::getQuery();
        $dIds = array();
        while ($d = $dQuery->fetch(array("#domain = '[#1#]'", $dRec->domain ?? null))) {
            $dIds[] = $d->id;
        }
        
        $dIds = implode(',', $dIds);
        
        $query = self::getQuery();
        $query->where("#state = 'active' AND #domainId IN ({$dIds})");
        
        $domainHost = $dRec->domain;
        if ($dRec->domain != 'localhost') {
            Mode::push('BGERP_CURRENT_DOMAIN', $domainHost);
        }
        
        $res = '<?xml version="1.0" encoding="UTF-8"?>';
        $res .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
        
        while ($rec = $query->fetch()) {
            $class = cls::getClassName($rec->source);
            if (!$class) {
                continue;
            }
            
            $source = cls::get($rec->source);
            if (!$source) {
                continue;
            }
            if (!cls::existsMethod($source, 'getSitemapEntries')) {
                continue;
            }
            $entries = $source->getSitemapEntries($rec->id);
            
            if (is_array($entries) && countR($entries)) {
                foreach ($entries as $eRec) {
                    $res .= "\n<url>";
                    
                    $res .= "\n<loc>" . str_replace('&', '&amp;', toUrl($eRec->loc, 'absolute')) . '</loc>';
                    $res .= "\n<lastmod>" . $eRec->lastmod . '</lastmod>';

                    if (!empty($eRec->changefreq)) {
                        $res .= "\n<changefreq>" . $eRec->changefreq . '</changefreq>';
                    }

                    if (!empty($eRec->priority)) {
                        $res .= "\n<priority>" . $eRec->priority . '</priority>';
                    }
                    
                    $res .= "\n</url>";
                }
            }
        }
        
        $res .= "\n</urlset>";
        
        if ($dRec->domain != 'localhost') {
            Mode::pop('BGERP_CURRENT_DOMAIN');
        }
        
        return $res;
    }
    
    
    /**
     * Генерира и регистрира sitemap.xml за посочения домейн
     */
    public static function registerSitemap($dRec)
    {
        if (!empty($dRec->sitemap)) {
            // Регистриране на sitemap.xml
            $xml = cms_Content::getSitemapXml($dRec);
            if ($xml) {
                core_Webroot::register($xml, '', $dRec->sitemap, $dRec->id);
            }
        } else {
            // Премахване на публичния
            core_Webroot::remove(cms_Domains::CMS_PUBLIC_SITEMAP_NAME, $dRec->id);
        }
    }
    
    
    /**
     * Обновява sitemap.xml-ите за всички домейни
     */
    public function cron_UpdateSitemap()
    {
        $dQuery = cms_Domains::getQuery();
        
        $used = array();
        
        while ($dRec = $dQuery->fetch()) {
            if (isset($used[$dRec->domain])) {
                continue;
            }
            self::registerSitemap($dRec);
            $used[$dRec->domain] = true;
        }
    }
}
