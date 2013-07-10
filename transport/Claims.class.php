<?php



/**
 * Мениджър на претенциите за рекламация
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
class transport_Claims extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Претенции за рекламация';
    
    
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
    var $listFields = 'tools=Пулт, number, title';
    
    
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
    	$this->FLD('title','varchar', 'caption=Заглавие');
    	$this->FLD('description','richtext(bucket=Transport)', 'caption=Описание');
    }

   
}