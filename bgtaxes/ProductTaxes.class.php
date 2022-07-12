<?php


/**
 * Такси на опаковки
 *
 * @category  bgerp
 * @package   bgtaxes
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgtaxes_ProductTaxes extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Продуктови такси';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'bgtaxes_Packagings';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_WrapperSettings, plg_State2, plg_Created, plg_Modified, plg_RowTools2, plg_AlignDecimals2';


    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';


    /**
     * Кой може да го добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';


    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Продуктова такса';


    /**
     * Кой може да пише?
     */
    public $canEdit = 'accMaster,ceo';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'accMaster,ceo';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,tax,currencyId,paramId,modifiedOn,modifiedBy';


    /**
     * Работен кеш
     */
    private static $cache = null;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Материал');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=typeExt)', 'caption=Параметър');
        $this->FLD('tax', 'double(min=0)', 'caption=|Такса|* (|за|* 1 |кг|*),unit=за|* 1 |кг|*');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута');

        $this->setDbUnique('name');
        $this->setDbUnique('paramId');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $form->setReadOnly('name');
        $form->setReadOnly('paramId');
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'bgtaxes/data/ProductTaxes.csv';
        $fields = array(
            0 => 'name',
        );

        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;

        return $res;
    }


    /**
     * След импортиране на запис
     *
     * @param ztm_Registers $mvc
     * @param stdClass      $rec
     */
    protected function on_AfterImportRec($mvc, $rec)
    {
        $paramId = cat_Params::force("product_pack_{$rec->id}", $rec->name, 'double', null, 'kg', false, false, 'Материал', "min=0");
        $rec->paramId = $paramId;
        $mvc->save_($rec, 'paramId');
    }


    /**
     * Калкулира продуктовата такса на артикула
     *
     * @param int $productId     - ид на артикул
     * @param date|null $date    - към коя дата
     * @param null|array $params - продуктовите параметри, null ако ще се извличат на момента
     * @return double|int        - колко е продуковата такса
     */
    public static function calcTax($productId, $date = null, $params = null)
    {
        // Ако има подадени продуктови параметри - те, иначе се извличат
        $params = is_array($params) ? $params : cat_Products::getParams($productId);

        // Еднократен кеш на таблицата с таксите
        if(!is_array(static::$cache)){
            $pQuery = bgtaxes_ProductTaxes::getQuery();
            $pQuery->show('paramId,tax,currencyId');
            while($pRec = $pQuery->fetch()){
                static::$cache[$pRec->paramId] = $pRec;
            }
        }
        $paramIds = array_keys(static::$cache);

        // За всеки параметър на такси се сумира стойността му на артикула (ако има)
        $sum = null;
        foreach ($paramIds as $paramId){
            if(isset($params[$paramId])){
                $sum += $params[$paramId] * currency_CurrencyRates::convertAmount(static::$cache[$paramId]->tax, $date, static::$cache[$paramId]->currencyId);
            }
        }

        return $sum;
    }
}