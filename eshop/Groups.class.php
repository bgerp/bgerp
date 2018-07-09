<?php


/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_State2, cms_VerbalIdPlg,plg_Search,plg_StructureAndOrder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,menuId,state';
    
    
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
     * Кой може да чете
     */
    public $canRead = 'eshop,ceo';
    
    
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
     * Кой може да го види?
     */
    public $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Нов темплейт за показване
     */
    // var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню,silent,refreshForm');
        $this->FLD('name', 'varchar(64)', 'caption=Група, mandatory,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');
        $this->FLD('showParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Параметри,optionsFunc=cat_Params::getPublic');
        $this->FLD('icon', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Малка');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Голяма');
        $this->FLD('productCnt', 'int', 'input=none');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    public function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::getId($mvc->className);
        $domainId = cms_Domains::getCurrent();
        if ($menuId = $data->form->rec->menuId) {
            $cond = "(#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}) || (#id = ${menuId})";
        } else {
            $cond = "#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}";
        }
        while ($rec = $cQuery->fetch($cond)) {
            $opt[$rec->id] = cms_Content::getVerbal($rec, 'menu');
        }
        
        if (count($opt) == 1) {
            $data->form->setReadOnly('menuId');
        }
        
        $data->form->setOptions('menuId', $opt);
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
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
        
        if (count($opt) == 0) {
            redirect(array('cms_Content'), false, '|Моля въведете поне една точка от менюто с източник "Онлайн магазин"');
        }
        
        if (!$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }
        
        $data->query->where(array("#menuId = '[#1#]'", $form->rec->menuId));
        
        $data->query->orderBy('#menuId');
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности за всеки запис
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            $row->name = ht::createLink($row->name, self::getUrl($rec), null, 'ef_icon=img/16/monitor.png');
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
            $layout->append($this->renderAllGroups($data), 'PAGE_CONTENT');
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
        cms_Content::setCurrent($groupRec->menuId);
        
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
        
        return $layout;
    }
    
    
    /**
     * Подготвя данните за показването на страницата със всички групи
     */
    public function prepareAllGroups($data)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$data->menuId}");
        
        while ($rec = $query->fetch()) {
            $rec->url = self::getUrl($rec);
            $data->recs[] = $rec;
        }
        
        $cRec = cms_Content::fetch($data->menuId);
        
        $data->title = type_Varchar::escape($cRec->url);
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
        
        eshop_Products::prepareGroupList($data->products);
    }
    
    
    /**
     * Добавя бутони за разглеждане във витрината на групите с продукти
     */
    public function on_AfterPrepareListToolbar($mvc, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::getId($mvc->className);
        
        while ($rec = $cQuery->fetch("#source = {$classId} AND #state = 'active'")) {
            $data->toolbar->addBtn(
                type_Varchar::escape($rec->menu),
                array('eshop_Groups', 'ShowAll', 'cMenuId' => $rec->id, 'PU' => 1)
            );
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function renderAllGroups_($data)
    {
        $all = new ET("<h1>{$data->title}</h1>");
        
        if (is_array($data->recs)) {
            foreach ($data->recs as $rec) {
                $tpl = new ET(getFileContent('eshop/tpl/GroupButton.shtml'));
                
                if ($rec->icon) {
                    $img = new thumb_Img($rec->icon, 400, 300, 'fileman');
                    $tpl->replace(ht::createLink($img->createImg(), $rec->url), 'img');
                }
                $name = ht::createLink($this->getVerbal($rec, 'name'), $rec->url);
                $tpl->replace($name, 'name');
                $all->append($tpl);
            }
        }
        
        $rec = new stdClass();
        $rec->seoTitle = tr('Всички продукти');
        
        cms_Content::setSeo($all, $rec);
        
        return $all;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function renderGroup_($data)
    {
        $groupTpl = getTplFromFile('eshop/tpl/SingleGroupShow.shtml');
        $groupTpl->setRemovableBlocks(array('PRODUCT'));
        $groupTpl->placeArray($data->row);
        $groupTpl->append(eshop_Products::renderGroupList($data->products), 'PRODUCTS');
        
        setIfNot($data->rec->seoTitle, $data->rec->name);
        
        cms_Content::setSeo($groupTpl, $data->rec);
        
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
        
        $query->where("#state = 'active'");
        
        $groupId = $data->groupId;
        $productId = $data->productId;
        $menuId = $data->menuId;
        
        if ($productId) {
            $pRec = eshop_Products::fetch("#id = {$productId} AND #state = 'active'");
            $groupId = $pRec->groupId;
        }
        
        if ($groupId) {
            $data->menuId = $menuId = self::fetch($groupId)->menuId;
        }
        
        $query->where("#menuId = '{$menuId}'");
        
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
            $l->url = self::getUrl($rec);
            $l->title = $this->getVerbal($rec, 'name');
            $l->level = 2;
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
            $queryM->where('#groupId IN (' . implode(',', $groups) . ')');
            $queryM->limit($maxResults);
            
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, null, 5, 64);
            while ($r = $query->fetch()) {
                $title = $r->name;
                $url = eshop_Products::getUrl($r);
                $url['q'] = $q;
                
                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
            
            if (count($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, null, 9);
                while ($r = $query->fetch()) {
                    $title = $r->name;
                    $url = eshop_Products::getUrl($r);
                    $url['q'] = $q;
                    
                    $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
                }
            }
            
            if (count($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, null, 3);
                while ($r = $query->fetch()) {
                    $title = $r->name;
                    $url = eshop_Products::getUrl($r);
                    $url['q'] = $q;
                    
                    $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
                }
            }
        }
        
        return $res;
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
    public static function on_AfterPrepareListTitle($mvc, $res, $data)
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
        return false;
    }
    
    
    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $menuId = Request::get('menuId', 'int');
        if (!$menuId) {
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
        if (!count($contents)) {
            
            return $groups;
        }
        
        $groupQuery = eshop_Groups::getQuery();
        $groupQuery->show('id');
        $groupQuery->in('menuId', $contents);
        while ($rec = $groupQuery->fetch()) {
            $groups[$rec->id] = eshop_Groups::getTitleById($rec->id, false);
        }
        
        return $groups;
    }
}
