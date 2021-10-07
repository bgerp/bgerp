<?php


/**
 * Архивиране на движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_OldMovements extends rack_MovementAbstract
{
    /**
     * Заглавие
     */
    public $title = 'История на движенията';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'История на движение';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper, plg_State, plg_Sorting,plg_SelectPeriod,plg_Search,plg_AlignDecimals2,plg_Modified';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';


    /**
     * Кой може да заяви движение
     */
    public $canToggle = 'no_one';


    /**
     * Кой може да приключи движение
     */
    public $canDone = 'no_one';


    /**
     * Полета за листовия изглед
     */
    public $listFields = 'productId,movement=Движение,workerId=Изпълнител,documents,createdOn,createdBy,modifiedOn,modifiedBy';


    /**
     * Кой има право да променя системните данни?
     */
    public $canWrite = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('movementId', 'key(mvc=rack_Movements,select=id)', 'caption=Ид');
        parent::setFields($this);
        $this->setDbUnique('movementId');
    }


    /**
     * След обработка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->title = 'История на движенията на палетите в склад |*<b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
    }


    /**
     * Синхронизиране на записа
     *
     * @param stdClass $rec
     * @return void
     */
    public static function sync($rec)
    {
        $clone = clone $rec;
        $clone->movementId = $rec->id;
        unset($clone->id);
        if($exId = static::fetchField("#movementId = {$clone->movementId}")){
            $clone->id = $exId;
        }

        $me = cls::get(get_called_class());
        $me->save_($clone);
    }
}
