<?php



/**
 * Мениджър на състоянията на пратките
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     състояние на пратките
 */
class transport_Shipment extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Състояние на пратките';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    transport_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,transport';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,transport';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,transport';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,transport';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,transport';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, number, state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('number','key(mvc=transport_Requests, select=number)', 'caption=Номер на заявката');
    	$this->FLD('state','enum(1=Производство,
    							 2=Склад,
    							 3=На път,
    							 4=Доставена)', 'caption=Състояние');
    }
    
}