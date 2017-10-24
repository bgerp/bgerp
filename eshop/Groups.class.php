<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Онлайн магазин";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_State2, cms_VerbalIdPlg,plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,menuId,state';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'eshop,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'eshop,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'eshop,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    /**
     * Нов темплейт за показване
     */
    // var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Група, mandatory,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');
        $this->FLD('icon', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Малка');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Голяма');
        $this->FLD('productCnt', 'int', 'input=none');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::getId($mvc->className);
        $domainId = cms_Domains::getCurrent();
        if($menuId = $data->form->rec->menuId) {
            $cond = "(#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}) || (#id = $menuId)";
        } else {
            $cond = "#source = {$classId} AND #state = 'active' AND #domainId = {$domainId}";
        }
        while($rec = $cQuery->fetch($cond)) {
            $opt[$rec->id] = cms_Content::getVerbal($rec, 'menu');
        }
        
        if(count($opt) == 1) {  
            $data->form->setReadOnly('menuId'); 
        }

        $data->form->setOptions('menuId', $opt);
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
        $form->showFields = 'search, menuId';
        
        $form->input('search, menuId', 'silent');

        $form->setOptions('menuId', $opt = cms_Content::getMenuOpt($mvc));

        $form->setField('menuId', 'refreshForm');
        
        if(count($opt) == 0) {
            redirect(array('cms_Content'), FALSE, '|Моля въведете поне една точка от менюто с източник "Онлайн магазин"');
        }

        if(!$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }
        
        $data->query->where(array("#menuId = '[#1#]'", $form->rec->menuId));
        
        $data->query->orderBy('#menuId');
    }
    
    
    /**
     * Изпълнява се след подготовката на вербалните стойности за всеки запис
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if($fields['-list']) {
            $row->name = ht::createLink($row->name, self::getUrl($rec), NULL, 'ef_icon=img/16/monitor.png');
        }
    }
    
    
    /**
     * Показва списъка с всички групи
     */
    function act_ShowAll()
    {
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);

        $data = new stdClass();
        $data->menuId = Request::get('cMenuId', 'int');
        
        if(!$data->menuId) {
            $data->menuId = cms_Content::getDefaultMenuId($this);
        }
        
        $menuId = $data->menuId;
        
        cms_Content::setCurrent($data->menuId);
        
        $layout = $this->getLayout();

        if(($q = Request::get('q')) && $menuId > 0) {  

            $layout->replace(cms_Content::renderSearchResults($menuId, $q), 'PAGE_CONTENT');

            vislog_History::add("Търсене в продуктите: {$q}");
        }  


        if(self::mustShowSideNavigation()) {
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
        if(core_Packs::fetch("#name = 'vislog'")) {
            if($data->menuId) {
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

        $menuId =  Mode::get('cMenuId');

        if(!$menuId) {
            $menuId = cms_Content::getDefaultMenuId('eshop_Groups');
        }
        
        return self::count("#state = 'active' AND #menuId = '$menuId'") >= $conf->ESHOP_MIN_GROUPS_FOR_NAVIGATION;
    }
    
    
    /**
     * Екшън за единичен изглед на групата във витрината
     */
    function act_Show()
    {
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);

        $data = new stdClass();
        
        $data->groupId = Request::get('id', 'int');
        
        if(!$data->groupId) {
            
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
        $url = toUrl(self::getUrl($data->rec, TRUE), 'absolute');
        $layout->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');
        
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);
        
        if(core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add("Група «" . $groupRec->name . "»");
        }
        
        // Премахва зададения временно текущ език
        core_Lg::pop();
        
        return $layout;
    }
    
    
    /**
     * Подготвя данните за показването на страницата със всички групи
     */
    function prepareAllGroups($data)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$data->menuId}");
        
        while($rec = $query->fetch()) {
            $rec->url = self::getUrl($rec);
            $data->recs[] = $rec;
        }
        
        $cRec = cms_Content::fetch($data->menuId);
        
        $data->title = type_Varchar::escape($cRec->url);
    }
    
    
    /**
     * Подготвя данните за показването на една група
     */
    function prepareGroup_($data)
    {
        expect($rec = $data->rec = $this->fetch($data->groupId), $data);
        
        $rec->menuId = $rec->menuId;
        
        $row = $data->row = new stdClass();
        
        $row->name = $this->getVerbal($rec, 'name');
        
        if($rec->image) {
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
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::getId($mvc->className);
        
        while($rec = $cQuery->fetch("#source = {$classId} AND #state = 'active'")) {
            $data->toolbar->addBtn(type_Varchar::escape($rec->menu),
                array('eshop_Groups', 'ShowAll', 'cMenuId' =>  $rec->id, 'PU' => 1));
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderAllGroups_($data)
    {
        $all = new ET("<h1>{$data->title}</h1>");
        
        if(is_array($data->recs)) {
            foreach($data->recs as $rec) {
                $tpl = new ET(getFileContent('eshop/tpl/GroupButton.shtml'));
                
                if($rec->icon) {
                    $img = new thumb_Img($rec->icon, 500, 180, 'fileman');
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
    function renderGroup_($data)
    {
        $groupTpl = getTplFromFile("eshop/tpl/SingleGroupShow.shtml");
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
    static function getLayout()
    {
        Mode::set('wrapper', 'cms_page_External');
        
        if(self::mustShowSideNavigation()) {
            if(Mode::is('screenMode', 'narrow')) {
                $layout = "eshop/tpl/ProductGroupsNarrow.shtml";
            } else {
                $layout =  "eshop/tpl/ProductGroups.shtml";
            }
        } else {
            $layout = "eshop/tpl/AllProducts.shtml";
        }
        
        Mode::set('cmsLayout',  $layout);
        
        return new ET();
    }
    
    
    /**
     * Подготвя данните за навигацията
     */
    function prepareNavigation_($data)
    {
        $query = $this->getQuery();
        
        $query->where("#state = 'active'");
        
        $groupId   = $data->groupId;
        $productId = $data->productId;
        $menuId = $data->menuId;
        
        if($productId) {
            $pRec = eshop_Products::fetch("#id = {$productId} AND #state = 'active'");
            $groupId = $pRec->groupId;
        }
        
        if($groupId) {
            $data->menuId = $menuId = self::fetch($groupId)->menuId;
        }
        
        $query->where("#menuId = {$menuId}");
        
        $l = new stdClass();
        $l->selected = ($groupId == NULL &&  $productId == NULL);
        
        $l->url = $this->getUrlByMenuId($menuId);
        
        if(haveRole('powerUser')) {
            $l->url['PU'] = 1;
        }
        
        $l->title = tr('Всички продукти');;
        $l->level = 1;
        $data->links[] = $l;
        
        $editSbf = sbf("img/16/edit.png", '');
        $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
        
        while($rec = $query->fetch()) {
            $l = new stdClass();
            $l->url = self::getUrl($rec);
            $l->title  = $this->getVerbal($rec, 'name');
            $l->level = 2;
            $l->selected = ($groupId == $rec->id);
            
            if($this->haveRightFor('edit', $rec)) {
                $l->editLink = ht::createLink($editImg, array('eshop_Groups', 'edit', $rec->id, 'ret_url' => TRUE));
            }
            
            $data->links[] = $l;
        }

        $data->searchCtr = 'eshop_Groups';
        $data->searchAct = 'ShowAll';
    }
    
    
    /**
     * Връща каноничното URL на статията за външния изглед
     */
    static function getUrl($rec, $canonical = FALSE)
    {
        $mRec = cms_Content::fetch($rec->menuId);
        
        $lg = $mRec->lang;
        
        $lg{0} = strtoupper($lg{0});
        
        $url = array('A', 'g', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);
        
        return $url;
    }
    
    
    /**
     * Връща кратко URL към продуктова група
     */
    static function getShortUrl($url)
    {
        $vid = urldecode($url['id']);
        $act = strtolower($url['Act']);
        
        if($vid && $act == 'show') {
            $id = cms_VerbalId::fetchId($vid, 'eshop_Groups');
            
            if(!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if(!$id && is_numeric($vid)) {
                $id = $vid;
            }
            
            if($id) {
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
    function getUrlByMenuId($cMenuId)
    {
        $cDefaultMenuId = cms_Content::getDefaultMenuId($this);
        $lang = cms_Domains::getPublicDomain('lang');
        if($cDefaultMenuId == $cMenuId && ($lang == 'bg' || $lang == 'en')) {
            $url = array(ucfirst($lang), 'Products');
        }
        
        if(!$url) {
            $url = array('eshop_Groups', 'ShowAll', 'cMenuId' => $cMenuId);
        }
        
        return $url;
    }
    
    
    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    function getUrlByRec($rec)
    {
        $url = self::getUrl($rec);
        
        return $url;
    }
    

    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    static function getSearchResults($menuId, $q, $maxResults = 15)
    { 
        $res = array();
        $query = self::getQuery();
        $query->where("#menuId = {$menuId} AND #state = 'active'");
        $groups = array();
        while($rec = $query->fetch()) {
            $groups[$rec->id] = $rec->id;
        }
 
        if(!empty($groups)) {
       
            $queryM = eshop_Products::getQuery();
            $queryM->where("#groupId IN (" . implode(',', $groups) . ")");
            $queryM->limit($maxResults);

            $query = clone($queryM);
            plg_Search::applySearch($q, $query, NULL, 5, 64);
            while($r = $query->fetch()) {
                $title = $r->name;
                $url = eshop_Products::getUrl($r);
                $url['q'] = $q;

                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
            
            if(count($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, NULL, 9);
                while($r = $query->fetch()) {
                    $title = $r->name;
                    $url = eshop_Products::getUrl($r);
                    $url['q'] = $q;

                    $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
                }
            }

            if(count($res) < $maxResults) {
                $query = clone($queryM);
                plg_Search::applySearch($q, $query, NULL, 3);
                while($r = $query->fetch()) {
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
    function getWorkshopUrl($menuId)
    {
        $url = array('eshop_Groups', 'list');
        
        return $url;
    }


    /**
     * Титлата за листовия изглед
     * Съдържа и текущия домейн
     */
    static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        
        $data->title .= cms_Domains::getCurrentDomainInTitle();
    }


    /**
     * Връща масив с групите, които оттоварят на посочения или на текущия домейн
     */
    public static function getGroupsByDomain($domainId = NULL)
    {
        if(!$domainId) {
            $domainId = cms_Domains::getPublicDomain('id');
        }

        $query = self::getQuery();
        $query->EXT('domainId', 'cms_Content', 'externalKey=menuId');
        $query->where("#domainId = {$domainId} AND #state = 'active'");
        
        $res = array();
        while($rec = $query->fetch()) {
            $res[$rec->id] = $rec->name;
        }

        return $res;
    }

}