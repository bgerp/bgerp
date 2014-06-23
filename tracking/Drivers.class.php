<?php 




/**
 * Връзка между шофьори и автомобили
 *
 *
 * @category  bgerp
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_Drivers extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Шофьори';
    
    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created, tracking_Wrapper';
    
    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields = 'trackerId, make, model, number';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('vehicleId', 'int()', 'caption=Автомобил');
        $this->FLD('userId', 'int()', 'caption=Шофьор');
    }
    
    
}