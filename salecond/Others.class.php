<?php



/**
 * Клас 'salecond_Others' - Други условия на доставка
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Others extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper';
    

    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'salecond';
    
    
    /**
     * Заглавие
     */
    var $title = 'Други условия на продажба';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Други условия на продажба";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число, varchar=Текст, color=Цвят, date=Дата)', 'caption=Тип');
        
        $this->setDbUnique('name');
    }
}