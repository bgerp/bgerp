<?php



/**
 * Мениджър на заявките за товарене
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за товарене
 */
class transport_Requests extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Заявки за товарене';
    
    
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
    var $listFields = 'tools=Пулт, number, contract, from, to, fromDate, toDate';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('number','varchar', 'caption=Номер на заявката, mandatory');
    	$this->FLD('contract','varchar', 'caption=По договор');
    	$this->FLD('from','keylist(mvc=crm_Locations, select=title)', 'caption=Локация->От');
    	$this->FLD('to','varchar', 'caption=Локация->До');
    	$this->FLD('fromDate','datetime', 'caption=Дата->Товарене');
    	$this->FLD('toDate','datetime', 'caption=Дата->Доставка');
    }

}
