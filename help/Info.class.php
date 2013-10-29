<?php 


/**
 * Подсистема за помощ - Информация
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_Info extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Помощни информационни текстове";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Помощен информационен текст";
        
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'help_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,class,text,createdOn,createdBy';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'debug, help';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('class', 'varchar', 'caption=Име на класа');
		$this->FLD('text', 'richtext', 'caption=Помощна информацията, hint=Текст на информацията за помощ');
    }

}
