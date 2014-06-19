<?php 




/**
 * Съхранява данни за автомобилите за проследяване
 *
 *
 * @category  vendors
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_Vehicles extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Vehicles';
    
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
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('make', 'varchar(12)', 'caption=марка');
        $this->FLD('model', 'varchar(12)', 'caption=модел');
        $this->FLD('number', 'varchar(10)', 'caption=рег. номер');
    }
    
    
}
