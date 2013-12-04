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
    var $title = "Групи в е-магазина";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';


    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, eshop_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,image,menuId,state';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name';
    
    
    /**
     * Дали да се превежда, транслитерира singleField полето
     * 
     * translate - Превежда
     * transliterate - Транслитерира
     */
    var $langSingleField = 'translate';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
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
    var $canRead = 'powerUser';
    
    
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
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'eshop,cat';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'eshop,ceo';


    /**
     * Нов темплейт за показване
     */
   // var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');
        $this->FLD('icon', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Малка');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Голяма');
        $this->FLD('productCnt', 'int', 'input=none');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню');
    }


    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::fetchIdByName($mvc->className);
        while($rec = $cQuery->fetch("#source = {$classId} AND state = 'active'")) {
            $opt[$rec->id] = type_Varchar::escape($rec->menu);
        }

        $data->form->setOptions('menuId', $opt);
    }


    /**
     * Показва списъка с всички групи
     */
    function act_ShowAll()
    {
        $menuId = Request::get('cMenuId', 'int');

        $data = new stdClass();

        $this->prepareNavigation($data, $menuId);   
        $this->prepareAllGroups($data, $menuId);  
        $layout = $this->getLayout();
 
        $layout->replace(cms_Articles::renderNavigation($data), 'NAVIGATION');
        $layout->replace($this->renderAllGroups($data), 'PAGE_CONTENT');

        return $layout;
    }


    function act_Show()
    {
        $groupId = Request::get('id', 'int');
        $data = new stdClass();

        $this->prepareNavigation($data, NULL, $groupId);
        expect($rec = $this->fetch($groupId));
        Mode::set('cMenuId', $rec->menuId);
        
        $groupTpl = new ET(getFileContent("eshop/tpl/SingleGroupShow.shtml"));
        
        $row = new stdClass();

        $row->title = type_Varchar::escape($rec->name);
        if($rec->image) {
            $row->image = fancybox_Fancybox::getImage($rec->image, array(620, 620), array(1200, 1200), $row->title); 
        }
        $rt = cls::get('type_RichText');
        $row->description = $rt->toVerbal($rec->info); 
        $groupTpl->placeArray($row);

        $layout = $this->getLayout();
        $layout->replace($groupTpl, 'PAGE_CONTENT');
        $layout->replace(cms_Articles::renderNavigation($data), 'NAVIGATION');
        
        return $layout;
    }


    /**
     *
     */
    function prepareAllGroups($data, $menuId)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$menuId}");  
        
        while($rec = $query->fetch()) {
            $rec->url = array('eshop_Groups', 'show', $rec->id);
            $data->recs[] = $rec;
        }

        $cRec = cms_Content::fetch($menuId);

        $data->title = type_Varchar::escape($cRec->url);
    }


    function on_AfterPrepareListToolbar($mvc, $data)
    {   
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::fetchIdByName($mvc->className);
        while($rec = $cQuery->fetch("#source = {$classId} AND state = 'active'")) {
            $data->toolbar->addBtn( type_Varchar::escape($rec->menu), 
                array('eshop_Groups', 'ShowAll', 'cMenuId' =>  $rec->id));
        }
    }

    /**
     *
     */
    function renderAllGroups($data)
    {   
        $all = new ET("<h1>{$data->title}</h1>");
        
        if(is_array($data->recs)) {
            foreach($data->recs as $rec) {
                $tpl = new ET(getFileContent('eshop/tpl/GroupButton.shtml'));
                if($rec->icon) {
                    $img = new img_Thumb($rec->icon, 280, 100, 'fileman');
                    $tpl->replace(ht::createLink($img->createImg(), $rec->url), 'IMG');
                }
                $title = ht::createLink(type_Varchar::escape($rec->name), $rec->url);
                $tpl->replace($title, 'TITLE');
                $all->append($tpl);
            }
        }

        return $all;
    }


    function getLayout()
    {
        Mode::set('wrapper', 'cms_Page');
        
        $conf = core_Packs::getConfig('cms');
		$ThemeClass = cls::get($conf->CMS_THEME);
        
		if(Mode::is('screenMode', 'narrow')) {
            $layout = new ET(getFileContent($ThemeClass->getNarrowArticleLayout()));
        } else {
            $layout = new ET(getFileContent($ThemeClass->getArticleLayout()));
        }

        return $layout;
    }
    

    /**
     * Подготвя данните за навигацията
     */
    function prepareNavigation(&$navData, $menuId, $groupId = NULL, $productId = NULL)
    {
        $query = $this->getQuery(); 
        
        $query->where("#state = 'active'");
        
        if($groupId) {
            $menuId = self::fetch($groupId)->menuId;
        }

        $query->where("#menuId = {$menuId}");
 
        $l = new stdClass();
        $l->selected = ($groupId == NULL &&  $productId == NULL);
        $l->url = array('eshop_Groups', 'ShowAll', 'cMenuId' => $menuId);
        $l->title = tr('Всички продукти');;
        $l->level = 1;
        $navData->links[] = $l;

        if($productId) {
            $pRec = eshop_Products::fetch("#id = {$productId} AND #state = 'active'");
        }
 
        while($rec = $query->fetch()) {
            $l = new stdClass();
            $l->url = array('eshop_Groups', 'Show', $rec->id);
            $l->title = type_Varchar::escape($rec->name);
            $l->level = 2;
            $l->selected = ($groupId == $rec->id);
            
            if($this->haveRightFor('edit', $rec)) {
                $editSbf = sbf("img/16/edit.png", '');
                $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
            }

            $l->editLink = ht::createLink($editImg, array('eshop_Groups', 'edit', $rec->id));
            
            $navData->links[] = $l;

            if($l->selected || ($pRec->groupId == $rec->id)) {
                $pQuery = eshop_Products::getQuery();
                while($pRec = $pQuery->fetch("#state = 'active' AND #groupId = {$rec->id}")) {
                    $p = new stdClass();
                    $p->url = array('eshop_Products', 'Show', $pRec->id);
                    $p->title = type_Varchar::escape($pRec->name);
                    $p->level = 3;
                    $p->selected = ($productId == $pRec->id);
                    $navData->links[] = $p;
                }
            }
        }
    }


    // Интерфейс
     

    /**
     * Връща URL към себе си  
     */
    function getContentUrl($cMenuId)
    {
        return array('eshop_Groups', 'ShowAll', 'cMenuId' => $cMenuId);
    }


    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    function getWorkshopUrl($menuId)
    {
        $url = array('eshop_Groups', 'list');

        return $url;
    }

    
}