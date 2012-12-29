<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   issue
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class issue_Components extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Компоненти';
    
    
    /**
     * 
     */
    var $singleTitle = 'Компонент';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, issue';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, issue';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, issue';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, issue';
    
    
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
    var $loadList = 'issue_Wrapper';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('systemId', 'key(mvc=issue_Systems, select=name)', 'caption=Система, mandatory');
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
        $this->FLD('description', 'text', "caption=Описание");
    }
    
    
	/**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,clsss=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'systemId, search';
        
        $data->listFilter->setDefault('systemId', issue_Systems::getCurrentIssueSystemId());
        
        $filter = $data->listFilter->input();
        
        expect($filter->systemId);
        
        $data->query->where("#systemId = '{$filter->systemId}'");
        
        if($filter->search) {
            $filter->search = mb_strtolower($filter->search);
            $data->query->where(array("LOWER(#name) LIKE '[#1#]' OR LOWER(#description) LIKE '[#1#]'", "%{$filter->search}%"));
        }
    }
}