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
    var $loadList = 'support_Wrapper, plg_RowTools, plg_Sorting';
    
    
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
}