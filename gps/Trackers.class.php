<?php 




/**
 * Съхранява данни за автомобилите за проследяване
 *
 *
 * @category  vendors
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gps_Trackers extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Trackers';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created, gps_Wrapper';    
    
    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields = 'trackerId, data, createdOn';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('data', 'blob', 'caption=gps данни');
    }
    
    
}
