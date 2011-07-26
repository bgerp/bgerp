<?php


/**
 * Клас 'cams_Positions' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    cams
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cams_Positions extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, cams_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Предефинирани позиции на камери';
    
    
    /**
     * Права
     */
    var $canWrite = 'cams, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'cams, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
        $this->FLD('cameraId', 'key(mvc=cams_Cameras,select=name)', 'caption=Камера, mandatory');
        $this->FLD('pan', 'double', 'caption=Pan');
        $this->FLD('tilt', 'double', 'caption=Tilt');
        $this->FLD('zoom', 'double', 'caption=Zoom');
        $this->FLD('moveTime' , 'int(min=0,max=300)', 'caption=Време');
    }
}