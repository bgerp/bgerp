<?php


/**
 * Модел "Взаимодействие на Зони и Налва"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_FeeZones extends core_Master
{


    /**
     * Старо име на класа
     */
    public $oldClassName = "trans_ZoneNames";


    /**
     * Заглавие
     */
    public $title = "Имена на зони";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";


    /**
     * Детайли за зареждане
     */
    public $details = "trans_Fees, trans_Zones";


    /**
     * Единично поле за RowTools
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        //id column
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
    }
}