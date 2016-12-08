<?php



/**
 * Клас 'cams_Positions' -
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class cams_Positions extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, cams_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Предефинирани позиции на камери';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'ceo,cams, admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,cams, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cams';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cams';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
        $this->FLD('cameraId', 'key(mvc=cams_Cameras,select=title)', 'caption=Камера, mandatory');
        $this->FLD('pan', 'double', 'caption=Pan');
        $this->FLD('tilt', 'double', 'caption=Tilt');
        $this->FLD('zoom', 'double', 'caption=Zoom');
        $this->FLD('moveTime' , 'int(min=0,max=300)', 'caption=Време');
    }
}