<?php 


/**
 * Следене в социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_Followers extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Следени";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Следене";
        
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'social_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, social';
            
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo, social';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('title', 'varchar(32)', 'caption=Услуга');
		$this->FLD('url', 'varchar(128)', 'caption=URL за последване');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('followersCnt', 'int', 'caption=Брой последователи');
    }
    
}