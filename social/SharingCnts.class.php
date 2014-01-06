<?php 


/**
 * Споделяне в социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_SharingCnts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Броене на споделянията";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Брой споделяния";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'social_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,networkId,url,cnt';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms, social, admin, ceo';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms, social, admin, ceo';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('networkId', 'key(mvc=social_Sharings)', 'caption=Услуга, input=none');
		$this->FLD('url', 'varchar(128)', 'caption=URL, input=none, hint=URL за споделяне');
		$this->FLD('cnt', 'int', 'caption=Споделяния, input=none,notNull');
    }

    static function addHit($networkId, $url)
    { 
        // Взимаме записите от модела, който брои споделянията
	    $rec = self::fetch(array("#networkId = '{$networkId}' AND #url = '[#1#]'", $url));
	               	 
	    // Ако нямаме записи, създаваме записа
	    if(!$rec){
	        $rec = new stdClass();
	        $rec->networkId = $networkId;
	        $rec->url = $url;
	    }
	               	 	
        // Уваеличаваме брояча и записваме
        $rec->cnt++;
        self::save($rec);

    }
}
