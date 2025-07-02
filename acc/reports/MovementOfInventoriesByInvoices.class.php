<?php


/**
 * Мениджър на отчети за стоки на склад
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Движение на материални ценности по фактури
 */
class acc_reports_MovementOfInventoriesByInvoices extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug, acc';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('products', 'fileman_FileType(bucket=reports)', 'caption=Файл с Артикули,placeholder=Избери,after=source,removeAndRefreshForm,silent,single=none,class=w100,input');

        // Период на справката
        $fieldset->FLD('from', 'date',
            'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date',
            'caption=До,after=from,single=none,mandatory');

//        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=date,single=none');
//
//        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Филтри->Група артикули,placeholder=Всички,after=selfPrices,single=none');

//        $fieldset->FLD('products', 'keylist(mvc=cat_Products,select=name)', 'caption=Филтри->Артикули,placeholder=Всички,after=group,single=none,class=w100');

        $fieldset->FLD('orderBy', 'enum(productName=Артикул,code=Код,amount=Стойност)', 'caption=Филтри->Подреди по,maxRadio=4,columns=4,after=availability,silent');

 //       $fieldset->FLD('seeByGroups', 'enum(no=Без разбивка,checked=Само за избраните,subGroups=Включи подгрупите)', 'notNull,caption=Филтри->"Общо" по групи,after=orderBy, single=none');

        $fieldset->FNC('totalProducts', 'int', 'input=none,single=none');
        $fieldset->FNC('sumByGroup', 'blob', 'input=none,single=none');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {

            if (isset($form->rec->workingPdogresOn) && $form->rec->workingPdogresOn == 'included' && ($form->rec->type == 'long')) {
                $form->setError('type', 'Незавършено производство може да се включи само при избран вариант "Кратка".');
            }

        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('seeByGroups', 'no');
        $form->setDefault('orderBy', 'name');


        $sQuery = store_Stores::getQuery();

        $sQuery->where("#state != 'rejected'");

        $sQuery->show('id, name,state');

        $suggestions = array();
        while ($sRec = $sQuery->fetch()) {
            if (!is_null($sRec->name)) {
                $suggestions[$sRec->id] = $sRec->name;
            }
        }

        asort($suggestions);

        $form->setSuggestions('storeId', $suggestions);

        $suggestions1 = array();

        $suggestions1 = cat_Products::getProducts(null, null, null, 'canStore', null, null, false, null);

        $form->setSuggestions('products', $suggestions1);
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)

    {

        $date = (is_null($rec->date)) ? dt::today() : $rec->date;

        $recs = array();


        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $fld->FLD('groupOne', 'varchar', 'caption=Група,tdClass=centered nowrap');
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered nowrap');
        $fld->FLD('productName', 'varchar', 'caption=Артикул');
        $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');

        //Начални количества и стойност
        $fld->FLD('startQuantity', 'double(smartRound,decimals=2)', 'caption=Начало на периода->Количество');
        $fld->FLD('startAmount', 'double(smartRound,decimals=2)', 'caption=Начало на периода->Стойност');

        //Доставени количества и стойност
        $fld->FLD('inQuantity', 'double(smartRound,decimals=2)', 'caption=Доставено->Количество');
        $fld->FLD('inAmount', 'double(smartRound,decimals=2)', 'caption=Доставено->Стойност');

        //Продадено количества и стойност
        $fld->FLD('outQuantity', 'double(smartRound,decimals=2)', 'caption=Продадено->Количество');
        $fld->FLD('outAmount', 'double(smartRound,decimals=2)', 'caption=Продадено->Стойност');

        //Крайно количества и стойност
        $fld->FLD('endQuantity', 'double(smartRound,decimals=2)', 'caption=Крайно->Количество');
        $fld->FLD('endAmount', 'double(smartRound,decimals=2)', 'caption=Крайно->Стойност');


        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Enum = cls::get('type_Enum', array('options' => array('yes' => 'Включено')));

        $row = new stdClass();


        if (is_numeric($dRec->groupOne)) {

            $row->groupOne = cat_Groups::getVerbal($dRec->groupOne, 'name') . ' :: стойност: ' . $Double->toVerbal($rec->sumByGroup[$dRec->groupOne]->amount) . ' ' . acc_Periods::getBaseCurrencyCode($rec->date) .
                ';  количества: ';
            $bm = 0;
            foreach ($rec->sumByGroup['quantities'] as $val) {
                if ($val->gr == $dRec->groupOne) {
                    if ($bm > 0) {
                        $row->groupOne .= ' + ';
                    }
                    $row->groupOne .= $Double->toVerbal($val->quantity) . ' ' . cat_UoM::fetchField($val->measureId, 'shortName');
                    $bm = $bm + 1;
                }
            }
        } else {
            $row->groupOne = 'Без група';
        }


        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }

        if (isset($dRec->productName)) {
            $row->productName = cat_Products::getLinkToSingle($dRec->productId, 'name');
        }

        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');


        $row->startQuantity = $Double->toVerbal($dRec->startQuantity);
        $row->startQuantity = ht::styleNumber($row->startQuantity, $dRec->startQuantity);

        $row->startAmount = $Double->toVerbal($dRec->startAmount);
        $row->startAmount = ht::styleNumber($row->startAmount, $dRec->startAmount);

        $row->inQuantity = $Double->toVerbal($dRec->inQuantity);
        $row->inQuantity = ht::styleNumber($row->inQuantity, $dRec->inQuantity);

        $row->inAmount = $Double->toVerbal($dRec->inAmount);
        $row->inAmount = ht::styleNumber($row->inAmount, $dRec->inAmount);

        $row->outQuantity = $Double->toVerbal($dRec->outQuantity);
        $row->outQuantity = ht::styleNumber($row->outQuantity, $dRec->outQuantity);

        $row->outAmount = $Double->toVerbal($dRec->outAmount);
        $row->outAmount = ht::styleNumber($row->outAmount, $dRec->outAmount);

        $row->endQuantity = $Double->toVerbal($dRec->endQuantity);
        $row->endQuantity = ht::styleNumber($row->endQuantity, $dRec->endQuantity);

        $row->endAmount = $Double->toVerbal($dRec->endAmount);
        $row->endAmount = ht::styleNumber($row->endAmount, $dRec->endAmount);


        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Enum = cls::get('type_Enum', array('options' => array('included' => 'Включено', 'off' => 'Изключено', 'only' => 'Само')));
        $Set = cls::get('type_Set', array('options' => array('available' => 'Положителна', 'neg' => 'Отрицателна', 'zero' => 'Ненулева')));


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN date--><div>|Към дата|*: [#date#]</div><!--ET_END date-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN products--><div>|Артикули|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN availability--><div>|Наличност|*: [#availability#]</div><!--ET_END availability-->
                                        <!--ET_BEGIN totalProducts--><div>|Брой артикули|*: [#totalProducts#]</div><!--ET_END totalProducts-->
                                        <!--ET_BEGIN workingPdogresOn--><div>|Незавършено производство|*: [#workingPdogresOn#]</div><!--ET_END workingPdogresOn-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        $date = (is_null($data->rec->date)) ? dt::today() : $data->rec->date;

        $fieldTpl->append('<b>' . $Date->toVerbal($date) . '</b>', 'date');


        if (isset($data->rec->group)) {
            $marker = 0;
            $groupVerb = '';
            foreach (type_Keylist::toArray($data->rec->group) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::getTitleById($group));

                if ((countR((type_Keylist::toArray($data->rec->group))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'group');
        }


        if (isset($data->rec->storeId)) {
            $marker = 0;
            $storeIdVerb = '';
            foreach (type_Keylist::toArray($data->rec->storeId) as $store) {
                $marker++;

                $storeIdVerb .= (store_Stores::getTitleById($store));

                if ((countR(type_Keylist::toArray($data->rec->storeId))) - $marker != 0) {
                    $storeIdVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $storeIdVerb . '</b>', 'storeId');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'storeId');
        }

        if ((isset($data->rec->products))) {
            foreach (keylist::toArray($data->rec->products) as $val) {

                $fieldTpl->append('<b>' . cat_Products::getTitleById($val) . ', ' . '</b>', 'products');
            }

        }

        if ((isset($data->rec->workingPdogresOn))) {

            $fieldTpl->append('<b>' . $Enum->toVerbal($data->rec->workingPdogresOn) . '</b>', 'workingPdogresOn');

        } else {
            $fieldTpl->append('<b>' . 'Не е включено' . '</b>', 'workingPdogresOn');
        }

        if ((isset($data->rec->availability))) {
            $fieldTpl->append('<b>' . $Set->toVerbal($data->rec->availability) . '</b>', 'availability');
        }

        if ((isset($data->rec->totalProducts))) {
            $fieldTpl->append('<b>' . ($data->rec->totalProducts) . '</b>', 'totalProducts');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $res->quantyti = $dRec->blQuantity;

        $res->measure = cat_UoM::fetch(cat_Products::fetch($dRec->productId)->measureId)->shortName;
    }

}
