<?php


/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Groups extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Онлайн магазин';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Каталог';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cms_SourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, eshop_Wrapper, plg_State2, cms_VerbalIdPlg,plg_Search,plg_StructureAndOrder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Име,menuId=Меню->Основно,sharedMenus=Меню->Споделяне||Shared,productCnt=Продукти,state=Видимост';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'name';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Група';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'eshop,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'eshop,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'eshop,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Основно,silent,refreshForm');
        $this->FLD('sharedMenus', 'keylist(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Споделяне в,silent,refreshForm');
        
        $this->FLD('name', 'varchar(64)', 'caption=Група->Наименование, mandatory,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Група->Описание');
        $this->FLD('showParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Група->Параметри,optionsFunc=cat_Params::getPublic');
        $this->FLD('showPacks', 'keylist(mvc=cat_UoM,select=name)', 'caption=Група->Опаковки/Мерки');
        $this->FLD('order', 'double', 'caption=Подредба,hint=Важи само за менютата, където групата е споделена');
        $this->FLD('perPage', 'int(Min=0)', 'caption=Страниране,unit=продукта на страница');
        $this->FLD('icon', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Малка');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Голяма');
        $this->FLD('productCnt', 'int', 'input=none,single=none');
        
        $this->setDbIndex('menuId');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    protected function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $classId = core_Classes::getId($mvc->className);
        $domainId = cms_Domains::getCurrent();
        if (isset($data->form->rec) && ($menuId = $data->form->rec->menuId)) {
            $cond = "(#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}) || (#id = ${menuId})";
        } else {
            $cond = "#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}";
        }
        
        $cQuery = cms_Content::getQuery();
        $egId = core_Classes::getId('eshop_Groups');
        $cQuery->where("#source = {$egId}");
        
        if ($menuId) {
            $cQuery->where("#id != {$menuId}");
        }
        
        $menuArr = array();
        while ($cRec = $cQuery->fetch()) {
            $menuArr[$cRec->id] = cms_Content::getVerbal($cRec, 'menu') . ' (' . cms_Content::getVerbal($cRec, 'domainId') . ')';
        }
        $data->form->setSuggestions('sharedMenus', $menuArr);
        
        $cQuery = cms_Content::getQuery();
        $opt = array();
        while ($rec = $cQuery->fetch($cond)) {
            $opt[$rec->id] = cms_Content::getVerbal($rec, 'menu');
        }
        
        if (countR($opt) == 1) {
            $data->form->setReadOnly('menuId');
        }
        
        $data->form->setOptions('menuId', $opt);
        $data->form->setField('perPage', 'placeholder=' . eshop_Setup::get('PRODUCTS_PER_PAGE'));
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $form->showFields = 'search, menuId';
        
        $form->input('search, menuId', 'silent');
        
        $form->setOptions('menuId', $opt = cms_Content::getMenuOpt($mvc));
        
        $form->setField('menuId', 'refreshForm');
        
        if (countR($opt) == 0) {
            redirect(array('cms_Content'), false, '|Моля въведете поне една точка от менюто с източник "Онлайн магазин"');
        }
        
        if ($form->rec->menuId && !$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }
        
        if (countR($opt) && !$form->isSubmitted()) {
            $form->rec->menuId = key($opt);
        }
        
        if ($form->rec->menuId) {
            $data->query->where(array("#menuId = '[#1#]' OR #sharedMenus LIKE '%|[#1#]|%'", $form->rec->menuId));
            $data->query->XPR('_isShared', 'enum(no,yes)', "(CASE #menuId WHEN {$form->rec->menuId} THEN 'no' ELSE 'yes' END)");
        }
        
        $data->query->orderBy('#menuId');
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности за всеки запис
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $row->name = $mvc->getHyperlink($rec, true);
            $row->name = $mvc->saoGetTitle($rec, $row->name, '&nbsp;&nbsp;');
            
            if (haveRole('powerUser') && $rec->state != 'closed') {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Преглед', self::getUrl($rec), 'alwaysShow,ef_icon=img/16/monitor.png,title=Преглед във външната част');
            }
            
            if ($rec->_isShared == 'yes') {
                $row->name = ht::createHint($row->name, 'Групата е споделена към менюто', 'notice', false);
                $otherDomainId = cms_Content::fetchField($rec->menuId, 'domainId');
                $otherDomainName = cms_Domains::getTitleById($otherDomainId);
                $row->menuId .= " [<span style='color:green'>{$otherDomainName}</span>]";
            }
            
            $productCnt = eshop_Products::count("#groupId = {$rec->id} OR #sharedInGroups LIKE '%|{$rec->id}|%'");
            $row->productCnt = $mvc->getFieldType('productCnt')->toVerbal($productCnt);
            $row->productCnt = ht::createLinkRef($row->productCnt, array('eshop_Products', 'list', 'groupId' => $rec->id));
        }
        
        foreach (array('showPacks', 'showParams') as $fld) {
            $hint = null;
            $showPacks = eshop_Products::getSettingField(null, $rec->id, $fld, $hint);
            if (countR($showPacks)) {
                $row->{$fld} = $mvc->getFieldType($fld)->toVerbal(keylist::fromArray($showPacks));
                if (!empty($hint)) {
                    $row->{$fld} = ht::createHint($row->{$fld}, $hint, 'notice', false);
                }
            }
        }
        
        $row->perPage = !empty($rec->perPage) ? $row->perPage : ht::createHint(eshop_Setup::get('PRODUCTS_PER_PAGE'), 'Стойност по подразбиране', 'notice', false);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (haveRole('powerUser') && $data->rec->state != 'closed') {
            $data->toolbar->addBtn('Преглед', self::getUrl($data->rec), null, 'ef_icon=img/16/monitor.png,title=Преглед във външната част');
        }
    }
    
    
    /**
     * Показва списъка с всички групи
     */
    public function act_ShowAll()
    {
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $data = new stdClass();
        $data->menuId = Request::get('cMenuId', 'int');
        
        if (!$data->menuId) {
            $data->menuId = cms_Content::getDefaultMenuId($this);
        }
        
        $menuId = $data->menuId;
        
        cms_Content::setCurrent($data->menuId);
        
        $layout = $this->getLayout();
        
        if (($q = Request::get('q')) && $menuId > 0) {
            $layout->replace(cms_Content::renderSearchResults($menuId, $q), 'PAGE_CONTENT');
            
            vislog_History::add("Търсене в продуктите: {$q}");
        }
        
        
        if (self::mustShowSideNavigation()) {
            // Ако имаме поне 4-ри групи продукти
            $this->prepareNavigation($data);
            $this->prepareAllGroups($data);
            $layout->append(cms_Articles::renderNavigation($data), 'NAVIGATION');
            
            $seoRec = new stdClass();
            $cRec = cms_Content::fetch($data->menuId);
            $seoRec->seoTitle = $cRec->title;
            cms_Content::prepareSeo($seoRec);
            $layout->append('<h1>' . type_Varchar::escape($cRec->title) . '</h1>', 'PAGE_CONTENT');
            $layout->append($this->renderAllGroups($data), 'PAGE_CONTENT');
            cms_Content::renderSeo($layout, $seoRec);
        } else {
            eshop_Products::prepareAllProducts($data);
            $layout->append(eshop_Products::renderAllProducts($data), 'PAGE_CONTENT');
        }
        
        // Добавя канонично URL
        $url = toUrl($this->getUrlByMenuId($data->menuId), 'absolute');
        cms_Content::addCanonicalUrl($url, $layout);
        
        // Колко време страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);
        
        // Записваме в посетителския лог
        if (core_Packs::fetch("#name = 'vislog'")) {
            if ($data->menuId) {
                $cRec = cms_Content::fetch($data->menuId);
            }
            vislog_History::add("Всички групи «{$cRec->menu}»");
        }
        
        // Премахва зададения временно текущ език
        core_Lg::pop();
        
        $layout->push('css/no-sass.css', 'CSS');
        
        return $layout;
    }
    
    
    /**
     * Връща дали е необходимо да се показва навигация на групите
     */
    private static function mustShowSideNavigation()
    {
        $conf = core_Packs::getConfig('eshop');
        
        $menuId = Mode::get('cMenuId');
        
        if (!$menuId) {
            $menuId = cms_Content::getDefaultMenuId('eshop_Groups');
        }
        
        return self::count("#state = 'active' AND #menuId = '${menuId}'") >= $conf->ESHOP_MIN_GROUPS_FOR_NAVIGATION;
    }
    
    
    /**
     * Екшън за единичен изглед на групата във витрината
     */
    public function act_Show()
    {
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $data = new stdClass();
        
        $data->groupId = Request::get('id', 'int');
        
        if (!$data->groupId) {
            
            return $this->act_ShowAll();
        }
        expect($groupRec = self::fetch($data->groupId));
        if (!($data->menuId = Request::get('cMenuId', 'int')) || ($groupRec->menuId != $data->menuId && strpos($groupRec->sharedMenus, "|{$data->menuId}|") === false)) {
            $data->menuId = cms_Content::getMainMenuId($groupRec->menuId, $groupRec->sharedMenus);
        }
        
        cms_Content::setCurrent($data->menuId);
        
        $this->prepareGroup($data);
        $this->prepareNavigation($data);
        plg_AlignDecimals2::alignDecimals(cls::get('eshop_Products'), $data->products->recs, $data->products->rows);
        
        $layout = $this->getLayout();
        $layout->append(cms_Articles::renderNavigation($data), 'NAVIGATION');
        $layout->append($this->renderGroup($data), 'PAGE_CONTENT');
        
        // Добавя канонично URL
        $url = toUrl(self::getUrl($data->rec, true), 'absolute');
        $layout->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');
        
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);
        
        if (core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add('Група «' . $groupRec->name . '»');
        }
        
        // Премахва зададения временно текущ език
        core_Lg::pop();
        
        $layout->push('css/no-sass.css', 'CSS');
        
        return $layout;
    }
    
    
    /**
     * Задава подредбата на групите
     */
    public static function setOrder($query, $menuId)
    {
        $query->XPR('orderCalc', 'double', "IF(#menuId = {$menuId}, #saoOrder, IF(#order > 0, #order, #id+1000))");
        $query->orderBy('orderCalc');
    }
    
    
    /**
     * Подготвя данните за показването на страницата със всички групи
     */
    public function prepareAllGroups($data, $groupId = null)
    {
        $query = self::getQuery();
        self::setOrder($query, $data->menuId);
        
        if ($groupId) {
            $query->where("#state = 'active' AND #saoParentId = {$groupId} AND (#menuId = {$data->menuId} OR LOCATE('|{$data->menuId}|', #sharedMenus))");
        } else {
            $query->where("#state = 'active' AND (#menuId = {$data->menuId} OR LOCATE('|{$data->menuId}|', #sharedMenus)) AND #saoLevel <= 1");
        }
        
        while ($rec = $query->fetch()) {
            if ($rec->menuId != $data->menuId) {
                $rec->altMenuId = $data->menuId;
            }
            $rec->url = self::getUrl($rec);
            $data->recs[] = $rec;
        }
    }
    
    
    /**
     * Подготвя данните за показването на една група
     */
    public function prepareGroup_($data)
    {
        expect($rec = $data->rec = $this->fetch($data->groupId), $data);
        
        $rec->menuId = $rec->menuId;
        
        $row = $data->row = new stdClass();
        
        $row->name = $this->getVerbal($rec, 'name');
        
        if ($rec->image) {
            $row->image = fancybox_Fancybox::getImage($rec->image, array(620, 620), array(1200, 1200), $row->name);
        }
        
        $row->description = $this->getVerbal($rec, 'info');
        
        Mode::set('SOC_TITLE', $row->name);
        Mode::set('SOC_SUMMARY', $row->info);
        
        $data->products = new stdClass();
        $data->products->groupId = $data->groupId;
        
        $this->prepareAllGroups($data, $data->groupId);
        
        eshop_Products::prepareGroupList($data->products);
    }
    
    
    /**
     * Добавя бутони за разглеждане във витрината на групите с продукти
     */
    protected function on_AfterPrepareListToolbar($mvc, $data)
    {
        $currentDomainId = cms_Domains::getCurrent();
        $cQuery = cms_Content::getQuery();
        $cQuery->XPR('_domainOrder', 'int', "(CASE #domainId WHEN {$currentDomainId} THEN 1 ELSE 2 END)");
        $cQuery->orderBy('_domainOrder', 'ASC');
        
        $classId = core_Classes::getId($mvc->className);
        while ($rec = $cQuery->fetch("#source = {$classId} AND #state = 'active'")) {
            $menuName = cms_Content::getVerbal($rec, 'menu');
            if ($rec->domainId != $currentDomainId) {
                $domainName = cms_Content::getVerbal($rec, 'domainId');
                $menuName .= " [{$domainName}]";
            }
            
            $data->toolbar->addBtn($menuName, array('eshop_Groups', 'ShowAll', 'cMenuId' => $rec->id, 'PU' => 1), 'ef_icon=img/16/monitor.png,title=Преглед на артикулите в менюто');
        }
    }
    
    
    /**
     * Подготовка на групата
     */
    public function renderAllGroups_($data)
    {
        $all = new ET('');
        
        if (is_array($data->recs)) {
            foreach ($data->recs as $rec) {
                $tpl = new ET(getFileContent('eshop/tpl/GroupButton.shtml'));
                
                if ($rec->icon) {
                    $img = new thumb_Img($rec->icon, 400, 300, 'fileman');
                    $tpl->replace(ht::createLink($img->createImg(), $rec->url), 'img');
                } else {
                    continue;
                }
                $name = ht::createLink($this->getVerbal($rec, 'name'), $rec->url);
                $tpl->replace($name, 'name');
                $all->append($tpl);
            }
        }
        
        return $all;
    }
    
    
    /**
     * Рендиране на групата
     */
    public function renderGroup_($data)
    {
        $groupTpl = getTplFromFile('eshop/tpl/SingleGroupShow.shtml');
        $groupTpl->setRemovableBlocks(array('PRODUCT'));
        $groupTpl->placeArray($data->row);
        
        // Добавяне на подгрупите
        if (is_array($data->recs) && countR($data->recs)) {
            $groupTpl->append("<div class='subgroups clearfix21'>", 'PRODUCTS');
            $groupTpl->append(self::renderAllGroups($data), 'PRODUCTS');
            $groupTpl->append('</div>', 'PRODUCTS');
        }
        
        $rec = $data->rec;
        
        // Подготвяме данните за SEO
        cms_Content::prepareSeo($rec, array('seoTitle' => $rec->name, 'seoDescription' => $rec->info, 'seoThumb' => $rec->image ? $rec->image : $rec->icon));
        
        $groupTpl->append(eshop_Products::renderGroupList($data->products), 'PRODUCTS');
        
        // Рендираме данните за seo
        cms_Content::renderSeo($groupTpl, $rec);
        
        return $groupTpl;
    }
    
    
    /**
     * Връща лейаута за единичния изглед на групата
     */
    public static function getLayout()
    {
        Mode::set('wrapper', 'cms_page_External');
        
        if (self::mustShowSideNavigation()) {
            if (Mode::is('screenMode', 'narrow')) {
                $layout = 'eshop/tpl/ProductGroupsNarrow.shtml';
            } else {
                $layout = 'eshop/tpl/ProductGroups.shtml';
            }
        } else {
            $layout = 'eshop/tpl/AllProducts.shtml';
        }
        
        Mode::set('cmsLayout', $layout);
        
        return new ET();
    }
    
    
    /**
     * Подготвя данните за навигацията
     */
    public function prepareNavigation_($data)
    {
        $query = $this->getQuery();
        self::setOrder($query, $data->menuId);
        
        $query->where("#state = 'active'");
        
        $groupId = $data->groupId;
        $productId = $data->productId;
        $menuId = $data->menuId;
        
        if (empty($data->groupId) && $productId) {
            $pRec = eshop_Products::fetch("#id = {$productId} AND #state = 'active'");
            $groupId = $pRec->groupId;
        } else {
            $groupId = $data->groupId;
        }
        
        if ($groupId) {
            $fRec = self::fetch($groupId);
            
            $parentGroupsArr = array($fRec->id);
            
            $sisCond = '';
            if ($fRec->saoParentId) {
                $sisCond = " OR #saoParentId = {$fRec->saoParentId} ";
            }
            while ($fRec->saoLevel > 1) {
                $parentGroupsArr[] = $fRec->id;
                $fRec = self::fetch($fRec->saoParentId);
            }
            $parentGroupsList = implode(',', $parentGroupsArr);
            $query->where("#id IN ({$parentGroupsList}) OR #saoParentId IN ({$parentGroupsList}) {$sisCond} OR #saoLevel <= 1");
        } else {
            $query->where('#saoLevel <= 1');
        }
        
        $query->where("#menuId = '{$menuId}' OR LOCATE('|{$menuId}|', #sharedMenus)");
        
        $l = new stdClass();
        $l->selected = ($groupId == null && $productId == null);
        
        $l->url = $this->getUrlByMenuId($menuId);
        
        if (haveRole('powerUser')) {
            $l->url['PU'] = 1;
        }
        
        $l->title = tr('Продуктови групи');
        $l->level = 1;
        $data->links[] = $l;
        
        $editSbf = sbf('img/16/edit.png', '');
        $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
        
        while ($rec = $query->fetch()) {
            $l = new stdClass();
            if ($rec->menuId != $menuId) {
                $rec->altMenuId = $menuId;
            }
            $l->url = self::getUrl($rec);
            $l->title = $this->getVerbal($rec, 'name');
            $l->level = $rec->saoLevel + 1;
            $l->selected = ($groupId == $rec->id);
            
            if ($this->haveRightFor('edit', $rec)) {
                $l->editLink = ht::createLink($editImg, array('eshop_Groups', 'edit', $rec->id, 'ret_url' => true));
            }
            
            $data->links[] = $l;
        }
        
        $data->searchCtr = 'eshop_Groups';
        $data->searchAct = 'ShowAll';
    }
    
    
    /**
     * Връща каноничното URL на статията за външния изглед
     */
    public static function getUrl($rec, $canonical = false)
    {
        $mRec = cms_Content::fetch($rec->menuId);
        
        $lg = $mRec->lang;
        
        $lg{0} = strtoupper($lg{0});
        
        $url = array('A', 'g', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : null);
        
        if ($rec->altMenuId) {
            $url['cMenuId'] = $rec->altMenuId;
        }
        
        return $url;
    }
    
    
    /**
     * Връща кратко URL към продуктова група
     */
    public static function getShortUrl($url)
    {
        $vid = urldecode($url['id']);
        $act = strtolower($url['Act']);
        
        if ($vid && $act == 'show') {
            $id = cms_VerbalId::fetchId($vid, 'eshop_Groups');
            
            if (!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if (!$id && is_numeric($vid)) {
                $id = $vid;
            }
            
            if ($id) {
                $url['Ctr'] = 'A';
                $url['Act'] = 'g';
                $url['id'] = $id;
            }
        }
        
        unset($url['PU']);
        
        return $url;
    }
    
    // Интерфейс cms_SourceIntf
    
    
    
    /**
     * Връща URL към себе си
     */
    public function getUrlByMenuId($cMenuId)
    {
        $cDefaultMenuId = cms_Content::getDefaultMenuId($this);
        $lang = cms_Domains::getPublicDomain('lang');
        if ($cDefaultMenuId == $cMenuId && ($lang == 'bg' || $lang == 'en')) {
            $url = array(ucfirst($lang), 'Products');
        }
        
        if (!$url) {
            $url = array('eshop_Groups', 'ShowAll', 'cMenuId' => $cMenuId);
        }
        
        return $url;
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    public function getUrlByRec($rec)
    {
        $url = self::getUrl($rec);
        
        return $url;
    }
    
    
    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    public static function getSearchResults($menuId, $q, $maxResults = 15)
    {
        $res = array();
        $query = self::getQuery();
        
        $query->where("#menuId = {$menuId} AND #state = 'active'");
        
        $groups = array();
        while ($rec = $query->fetch()) {
            $groups[$rec->id] = $rec->id;
        }
        
        if (!empty($groups)) {
            $queryM = eshop_Products::getQuery();
            $queryM->where("#state = 'active'");
            $queryM->where('#groupId IN (' . implode(',', $groups) . ')');
            
            $eshopClassId = eshop_Products::getClassId();
            $queryM->EXT('rating', 'sales_ProductRatings', array('externalName' => 'value', 'onCond' => "#sales_ProductRatings.classId = {$eshopClassId} AND #sales_ProductRatings.objectId = #id", 'join' => 'right'));
            $queryM->orderBy('rating,createdOn', 'DESC');
            $queryM->limit($maxResults);
            
            $recs = array();
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, null, 5, 64);
            $recs += $query->fetchAll();
            
            
            if (countR($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, null, 9);
                $recs += $query->fetchAll();
            }
            
            if (countR($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, null, 3);
                $recs += $query->fetchAll();
            }
            
            foreach ($recs as $r) {
                $title = tr($r->name);
                $url = eshop_Products::getUrl($r);
                $url['q'] = $q;
                
                if(haveRole('debug')){
                    $rating = empty($r->rating) ? tr("n / a") : $r->rating;
                    $title = ht::createHint($title, "Рейтинг:|* {$rating}");
                }
                
                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url, 'img' => eshop_Products::getProductThumb($r, 60, 60));
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавя ключовите думи от обектите в менюто към масива
     */
    public static function getAllSearchKeywords($menuId)
    {
        $kArr = array();
        
        $query = self::getQuery();
        
        $text = '';
        
        $gArr = array();
        while ($rec = $query->fetch("#menuId = {$menuId} AND #state = 'active'")) {
            $gArr[] = $rec->id;
            $text .= ' ' . $rec->name . ' ' . $rec->seoKeywords;
        }
        
        $groups = implode(',', $gArr);
        
        if ($groups) {
            $pQuery = eshop_Products::getQuery();
            while ($pRec = $pQuery->fetch("#state = 'active' AND #groupId IN ({$groups})")) {
                $text .= ' ' . $pRec->searchKeywords;
            }
        }
        
        if ($text) {
            $text = strtolower(str::canonize($text, ' '));
            $wArr = explode(' ', $text);
            foreach ($wArr as $w) {
                if (strlen($w) > 3) {
                    $kArr[$w] = true;
                }
            }
        }
        
        return $kArr;
    }
    
    
    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    public function getWorkshopUrl($menuId)
    {
        $url = array('eshop_Groups', 'list');
        
        return $url;
    }
    
    
    /**
     * Титлата за листовия изглед
     * Съдържа и текущия домейн
     */
    protected static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $data->title .= cms_Domains::getCurrentDomainInTitle();
    }
    
    
    /**
     * Връща масив с групите, които оттоварят на посочения или на текущия домейн
     */
    public static function getGroupsByDomain($domainId = null)
    {
        if (!$domainId) {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        
        $query = self::getQuery();
        $query->EXT('domainId', 'cms_Content', 'externalKey=menuId');
        $query->where("#domainId = {$domainId} AND #state = 'active'");
        
        $res = array();
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec->name;
        }
        
        return $res;
    }
    
    
    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = null)
    {
        return $rec->saoLevel <= 3;
    }
    
    
    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $menuId = Request::get('menuId', 'int');
        if (!$menuId || strpos($rec->sharedMenus, "|{$menuId}|") === false) {
            $menuId = (int) $rec->menuId;
        }
        if (!$menuId) {
            $menuId = cms_Content::getDefaultMenuId('eshop_Groups');
        }
        if (!$menuId) {
            
            return $res;
        }
        $query->where("#menuId = {$menuId}");
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }
        
        return $res;
    }
    
    
    /**
     * Връща групите в избрания домейн
     *
     * @param int|NULL $domainId
     *
     * @return array $groups
     */
    public static function getByDomain($domainId = null)
    {
        $groups = array();
        $domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
        
        // Намиране на опциите, които са вързани към артикули от подадения домейн
        $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;
        $contentQuery = cms_Content::getQuery();
        $contentQuery->show('id');
        $contentQuery->where("#domainId = {$domainId}");
        $contents = arr::extractValuesFromArray($contentQuery->fetchAll(), 'id');
        if (!countR($contents)) {
            
            return $groups;
        }
        
        $groupQuery = eshop_Groups::getQuery();
        $groupQuery->show('id,saoLevel');
        $groupQuery->in('menuId', $contents);
        $me = cls::get(get_called_class());
        while ($rec = $groupQuery->fetch()) {
            $groups[$rec->id] = $me->saoGetTitle($rec, eshop_Groups::getTitleById($rec->id, false));
        }
        
        return $groups;
    }
    
    
    /**
     * Връща връща масив със обекти, съдържащи връзки към публичните страници, генерирани от този обект
     */
    public function getSitemapEntries($menuId)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$menuId}");
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            $resObj = new stdClass();
            $resObj->loc = $this->getUrl($rec, true);
            
            $modifiedOn = $rec->modifiedOn ? $rec->modifiedOn : $rec->createdOn;
            
            $resObj->lastmod = date('c', dt::mysql2timestamp($modifiedOn));
            $resObj->priority = 1;
            $res[] = $resObj;
            
            $Products = cls::get('eshop_Products');
            $pQuery = eshop_Products::getQuery();
            $pQuery->where("#state = 'active' AND #groupId = {$rec->id}");
            while ($pRec = $pQuery->fetch()) {
                $resObj = new stdClass();
                $resObj->loc = $Products->getUrl($pRec, true);
                $modifiedOn = $pRec->modifiedOn ? $pRec->modifiedOn : $pRec->createdOn;
                $resObj->lastmod = date('c', dt::mysql2timestamp($modifiedOn));
                $resObj->priority = 0.9;
                $res[] = $resObj;
            }
        }
        
        return $res;
    }
}
