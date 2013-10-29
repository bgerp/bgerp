<?php 


/**
 * Подсистема за помощ - Логове
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_Log extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Логове";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Лог";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'help_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,userId,infoId,seeOn';


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
    var $canWrite = 'no_one';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
		$this->FLD('infoId', 'key(mvc=help_Info)', 'caption=За кой клас, hint=За кой клас се отнася информацията');
		$this->FLD('seeOn', 'datetime', 'caption=Видяно на, hint=Кога за първи път е видяно');
		$this->FLD('showCnt', 'int', 'caption=Брой виждания');
    }
    
}