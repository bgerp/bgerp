<?php 


/**
 * Мениджира детайлите на стелажите (rack_RackDetails)
 *
 *
 * @category  bgerp
 * @package   rack
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_RackDetails extends core_Detail
{	
	
    /**
     * Заглавие
     */
    var $title = "Детайли на стелаж";
    

    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Състояние на клетка";

    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, rack_Wrapper';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'rackId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, rackMaster';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, rackMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, rackMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, rackMaster';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('rackId', 'key(mvc=rack_Racks)', 'caption=Стелаж, input=hidden');
        $this->FLD('row', 'varchar(1)', 'caption=Ред');
        $this->FLD('col', 'int', 'caption=Колона');
        $this->FLD('status', 'enum(usable=Използваемо,
                                   unusable=Неизползваемо,
                                   reserved=Резервирано                                     
                                   )', 'caption=Състояние');
    }
}