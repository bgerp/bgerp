<?php 


/**
 * Поддържани компоненти от сигналите
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Components extends core_Detail
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Components';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Поддържани компоненти';
    
    
    /**
     * 
     */
    var $singleTitle = 'Компонент';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, plg_RowTools';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'systemId';
    
    
    /**
     * 
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * 
     */
    var $listFields = 'id, name, description';
    
    
    /**
     * 
     */
    var $currentTab = 'Системи';

    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, mandatory');
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory, width=100%');
        $this->FLD('description', 'richtext', "caption=Описание, width=100%");

        $this->setDbUnique('systemId, name');
    }
    
    
	/**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
//    static function on_AfterPrepareListFilter($mvc, $data)
//    {
//        // Добавяме поле във формата за търсене
//        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent');
//        
//        // Добавяме поле за избор на система
//        $data->listFilter->FNC('systemIdFnc', 'key(mvc=support_Systems, select=name, allowEmpty=true)', 'input, caption=Система');
//        
//        // Да са разпрелени хоризонтално
//        $data->listFilter->view = 'horizontal';
//        
//        // Добавяме бутон за филтриране
//        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,clsss=btn-filter');
//        
//        // Показваме само това поле. Иначе и другите полета 
//        // на модела ще се появят
//        $data->listFilter->showFields = 'systemIdFnc, search';
//        
//        // Вземаме systemId
//        $systemId = Request::get('systemIdFnc', 'key(mvc=support_Systems, select=name)');
//    
//        $filter = $data->listFilter->input();
//        
//        // Ако се търси в система
//        if ($systemId) {
//            
//            // Задаваме да е избран по подразбиране
//            $data->listFilter->setDefault('systemIdFnc', $systemId);
//        
//            // Очакваме да е зададено
//            expect($filter->systemIdFnc);
//            
//            // Добавяме във where клаузата
//            $data->query->where("#systemId = '{$filter->systemIdFnc}'");    
//        }
//        
//        // Ако сме добавили текст за търсене
//        if($filter->search) {
//            
//            // Да е в долния регистър
//            $filter->search = mb_strtolower($filter->search);
//            
//            // Добавяме във where клаузата
//            $data->query->where(array("LOWER(#name) LIKE '[#1#]' OR LOWER(#description) LIKE '[#1#]'", "%{$filter->search}%"));
//        }
//    }
}