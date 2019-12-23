<?php


/**
 * Категории на статиите
 *
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    public $loadList = 'plg_RowTools2, blogm_Wrapper';
    
    
    /**
     * Полета за изглед
     */
    public $listFields = 'id, title, description';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'cms, ceo, admin, blog';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'cms, ceo, admin, blog';
    
    
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
        $this->FLD('description', 'richtext(bucket=' . blogm_Articles::FILE_BUCKET . ')', 'caption=Описание');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Създаване на линк към статиите, филтрирани спрямо избраната категория
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
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
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        
        $form->rec->domainId = cms_Domains::getCurrent();
        $form->setReadonly('domainId');
    }
    
    
    /**
     * Връща категориите по текущия език
     */
    public static function getCategoriesByDomain($domainId = null)
    {
        $options = array();
        
        // Взимаме заявката към категориите, според избрания език
        $query = static::getQuery();
        self::filterByDomain($query, $domainId);
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
        $tpl = new ET();
        
        if (!$data->categories) {
            $data->categories = array();
        }
        
        $Lg = cls::get('core_Lg');
        $allCaption = $Lg->translate('Всички', false, cms_Content::getLang());
        $cat = array('' => $allCaption) + $data->categories;
        
        // За всяка Категория, създаваме линк и го поставяме в списъка
        foreach ($cat as $id => $title) {
            if ($data->selectedCategories[$id] || (!$id && !countR($data->selectedCategories))) {
                $attr = array('class' => 'nav_item sel_page level2');
            } else {
                $attr = array('class' => 'nav_item level2');
            }
            
            // Създаваме линк, който ще покаже само статиите от избраната категория
            $title = ht::createLink($title, $id ? array('blogm_Articles', 'browse', 'category' => $id) : array('blogm_Articles'));
            
            // Див-обвивка
            $title = ht::createElement('div', $attr, $title);
            
            // Създаваме шаблон, после заместваме плейсхолдъра със самия линк
            $tpl->append($title);
            $toggleLink = ht::createLink('', null, null, array('ef_icon' => 'img/menu.png', 'class' => 'toggleLink'));
            $tpl->replace($toggleLink, 'TOGGLE_BTN');
        }
        
        
        // Връщаме вече рендираният шаблон
        return $tpl;
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        self::filterByDomain($data->query, cms_Domains::getCurrent());
    }
}
