<?php


/**
 * Категории на статиите
 *
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blogm_Categories extends core_Manager
{
    /**
     * Заглавие на страницата
     */
    public $title = 'Категории в блога';
    
    
    /**
     * Зареждане на необходимите плъгини
     */
    public $loadList = 'plg_RowTools2, cms_plg_ContentSharable, blogm_Wrapper, plg_StructureAndOrder';
    
    
    /**
     * Полета за изглед
     */
    public $listFields = 'id, title, description, menuId, sharedMenus';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'cms, ceo, admin, blog';


    /**
     * Към менюта от кой клас да се споделят
     *
     * @see cms_plg_ContentSharable
     */
    public $sharableToContentSourceClass = 'blogm_Articles';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms, blog';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, cms, blog';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('title', 'varchar(60)', 'caption=Заглавие,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Основно,silent,refreshForm,mandatory');
        $this->FLD('sharedMenus', 'keylist(mvc=cms_Content,select=menu, allowEmpty)', 'caption=Меню->Споделяне в,silent,refreshForm');
        $this->FLD('description', 'richtext(bucket=' . blogm_Articles::FILE_BUCKET . ')', 'caption=Описание');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
        
        $this->setDbUnique('title');
        $this->setDbUnique('title,menuId');
    }
    
    
    /**
     * Създаване на линк към статиите, филтрирани спрямо избраната категория
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->title = ht::createLink($row->title, array('blogm_Articles', 'list', 'category' => $rec->id));
    }
    
    
    /**
     * Филтрира заявката за категориите, така че да показва само тези
     * от текущия език
     */
    private static function filterByDomain(core_Query &$query, $domainId = null)
    {
        if (empty($domainId)) {
            $domainId = cms_Domains::getPublicDomain('id');
        }
        $query->where("#domainId = '{$domainId}'");
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        
        $form->rec->domainId = cms_Domains::getCurrent();
        $form->setReadonly('domainId');
    }
    
    
    /**
     * Връща категориите по текущия език
     */
    public static function getCategoriesByDomain($domainId = null, $cMenuId = null, $categoryId = null, $showAll = false)
    {
        $options = array();
        
        // Взимаме заявката към категориите, според избрания език
        $query = static::getQuery();
        self::filterByDomain($query, $domainId);
        if(isset($cMenuId)){
            $query->where("#menuId = {$cMenuId} OR LOCATE('|{$cMenuId}|', #sharedMenus)");
        }

        if(!$showAll){
            if (isset($categoryId)) {
                $fRec = self::fetch($categoryId, 'id,saoParentId');
                $parentGroupsArr = array($fRec->id);
                $sisCond = ($fRec->saoParentId) ? " OR #saoParentId = {$fRec->saoParentId} " : '';

                while ($fRec->saoLevel > 1) {
                    $parentGroupsArr[] = $fRec->id;
                    $fRec = self::fetch($fRec->saoParentId. 'id,saoParentId');
                }

                $parentGroupsList = implode(',', $parentGroupsArr);
                $query->where("#id IN ({$parentGroupsList}) OR #saoParentId IN ({$parentGroupsList}) {$sisCond} OR #saoLevel <= 1");
            } else {
                $query->where('#saoLevel <= 1');
            }
        }

        while ($rec = $query->fetch()) {
            $options[$rec->id] = static::getVerbal($rec, 'title');
        }
        
        return $options;
    }
    
    
    /**
     * Статичен метод за рендиране на меню със всички категории, връща шаблон
     */
    public static function renderCategories_($data)
    {
        // Шаблон, който ще представлява списъка от хиперлинкове към категориите
        $tpl = new ET("");
        
        if (!$data->categories) {
            $data->categories = array();
        }

        $Lg = cls::get('core_Lg');
        $allCaption = $Lg->translate('Всички', false, cms_Content::getLang());
        if(!countR($data->categories)) return $tpl;
        $cat = array('' => $allCaption) + $data->categories;

        // За всяка Категория, създаваме линк и го поставяме в списъка
        foreach ($cat as $id => $title) {
            $saoLevel = static::fetchField($id, 'saoLevel');
            $num = ($saoLevel) ? $saoLevel : 1;

            if ($data->selectedCategories[$id] || (!$id && !countR($data->selectedCategories))) {
                $attr = array('class' => "nav_item sel_page level{$num}");
            } else {
                $attr = array('class' => "nav_item level{$num}");
            }
            
            // Създаваме линк, който ще покаже само статиите от избраната категория
            $title = ht::createLink($title, $id ? array('blogm_Articles', 'browse', 'cMenuId' => $data->menuId, 'category' => $id) : array('blogm_Articles', 'Browse', 'cMenuId' => $data->menuId));
            
            // Див-обвивка
            $title = ht::createElement('div', $attr, $title);
            
            // Създаваме шаблон, после заместваме плейсхолдъра със самия линк
            $tpl->append($title);
            $toggleLink = ht::createLink('', null, null, array('ef_icon' => 'img/menu.png', 'class' => 'toggleLink'));
            $tpl->replace($toggleLink, 'TOGGLE_BTN');
        }

        $showRoot = blogm_Setup::get('SHOW_CATEGORIES_ROOT');
        if($showRoot == 'yes'){
            $tpl->append(tr('Категории'), 'CATEGORY_CAPTION');
        }

        // Връщаме вече рендираният шаблон
        return $tpl;
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        self::filterByDomain($data->query, cms_Domains::getCurrent());
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
            $menuId = cms_Content::getDefaultMenuId('blogm_Articles');
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
     * Помощна ф-я връщаща категориите като линкове във външната част
     * @param mixed $categoryArr
     * @param int $menuId
     * @return string
     */
    public static function getCategoryLinks($categoryArr, $menuId)
    {
        $linkArr = array();
        $categories = keylist::isKeylist($categoryArr) ? keylist::toArray($categoryArr) : arr::make($categoryArr, true);
        foreach ($categories as $categoryId){
            $categoryName = blogm_Categories::getTitleById($categoryId);
            $title = ht::createLink($categoryName, array('blogm_Articles', 'browse', 'cMenuId' => $menuId, 'category' => $categoryId));
            $linkArr[] = ht::createElement('span', array('class' => 'blogArticleCategoryLink'), $title);
        }

        return implode(', ', $linkArr);
    }
}
