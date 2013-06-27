<?php 


/**
 * Модул за записване на вътрешните номера
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_InternalNum extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Вътрешни номера';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, callcenter_DataWrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_saveAndNew';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'userId, number';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('userId', 'user(rolesForAll=user)', 'caption=Потребител');
        $this->FLD('number', 'varchar', 'caption=Номер');
        
        $this->setDbUnique('userId, number');
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->input('search', 'silent');
    }
}
