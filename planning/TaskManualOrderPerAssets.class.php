<?php


/**
 * Модел за ограничения на ПО
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_TaskManualOrderPerAssets extends core_Master
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Ръчни подредби на ПО по оборудване';


    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Ръчна подредба на ПО по оборудване';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, plg_GroupByField, plg_Created';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да го променя?
     */
    public $canDelete = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $listFields = 'assetId,data,createdOn,createdBy';


    /**
     * По-кое поле да се групират листовите данни
     */
    public $groupByField = 'assetId';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('order', 'int', 'caption=Подредба');

        $this->setDbUnique('assetId');
    }
}