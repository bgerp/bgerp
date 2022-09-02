<?php


/**
 * Мениджър на отчети за стоки на склад
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Складови наличности
 */
class store_reports_ProductsInStock extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat, store, acc';


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
    protected $sortableListFields = 'amount,code,productName';


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'amount';


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
    protected $changeableFields = 'type,date,storeId,selfPrices,group,products,availability,orderBy';



    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('type', 'enum(short=Кратка, long=Разширена)', 'notNull,caption=Тип на справката,after=title,removeAndRefreshForm,silent,single=none');

        $fieldset->FLD('date', 'date', 'caption=Към дата,after=type,single=none');

        $fieldset->FLD('storeId', 'keylist(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=date,single=none');

        $fieldset->FLD('selfPrices', 'enum(balance=По баланс, manager=Мениджърска)', 'notNull,caption=Филтри->Вид цени,after=storeId,single=none');

        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Филтри->Група артикули,placeholder=Всички,after=selfPrices,single=none');
        $fieldset->FLD('products', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Филтри->Артикули,placeholder=Всички,after=group,single=none,class=w100');
        $fieldset->FLD('availability', 'enum(Всички=Всички, Налични=Налични,Отрицателни=Отрицателни)', 'notNull,caption=Филтри->Наличност,maxRadio=3,columns=3,after=products,single=none');

        $fieldset->FLD('orderBy', 'enum(productName=Артикул,code=Код,amount=Стойност)', 'caption=Филтри->Подреди по,maxRadio=3,columns=3,after=availability,silent');

        $fieldset->FLD('seeByGroups', 'set(yes = )', 'caption=Филтри->"Общо" по групи,after=orderBy,input=none,single=none');

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

        $form->setDefault('selfPrices', 'balance');
        $form->setDefault('availability', 'Всички');
        $form->setDefault('seeByGroups', '');
        $form->setDefault('orderBy', 'name');
        $form->setDefault('type', 'short');

        if ($rec->type == 'long') {
            $today = dt::today();
            $rec->date = $today;
            $form->setReadOnly('date');
        }
        if ($rec->type == 'short') {
            $form->setField('seeByGroups', 'input');
        }

        $sQuery = store_Stores::getQuery();

        $sQuery->where("#state != 'rejected'");

        $sQuery->show('id, name,state');

        while ($sRec = $sQuery->fetch()) {
            if (!is_null($sRec->name)) {
                $suggestions[$sRec->id] = $sRec->name;
            }
        }

        asort($suggestions);

        $form->setSuggestions('storeId', $suggestions);
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

        $storeItemIdArr = array();

        if ($rec->storeId) {
            $storeItemIdArr = array();

            foreach (keylist::toArray($rec->storeId) as $storeId) {
                array_push($storeItemIdArr, acc_Items::fetchItem('store_Stores', $storeId)->id);
            }
        }

        $productItemId = $rec->products ? acc_Items::fetchItem('cat_Products', $rec->products)->id : null;

        $Balance = new acc_ActiveShortBalance(array('from' => $date, 'to' => $date, 'accs' => '321', 'item1' => $storeItemIdArr, 'item2' => $productItemId, 'cacheBalance' => false, 'keepUnique' => true));
        $bRecs = $Balance->getBalance('321');

        foreach ($bRecs as $item) {

            if (($rec->storeId && !in_array($item->ent1Id, $storeItemIdArr)) ||
                ($rec->products && $item->ent2Id != $productItemId)
            ) continue;

            $blQuantity = 0;
            $iRec = acc_Items::fetch($item->ent2Id);

            $prodClass = core_Classes::fetch($iRec->classId)->name;

            $prodRec = $prodClass::fetch($iRec->objectId);

            $id = $iRec->objectId;

            //Филтър по групи артикули
            if (isset($rec->group)) {
                $checkGdroupsArr = keylist::toArray($rec->group);
                if (!keylist::isIn($checkGdroupsArr, $prodRec->groups)) {
                    continue;
                }
            }

            //Код на продукта
            $productCode = cat_Products::getVerbal($prodRec->id,'code');

            //Код на основна мярка
            $productMeasureId = $prodRec->measureId;

            //Продукт ID
            $productId = $iRec->objectId;

            //Име на продукта
            $productName = $iRec->title;


            //Количество в началото на периода
            $baseQuantity = $item->baseQuantity;

            //Стойност в началото на периода
            $baseAmount = $item->baseAmount;

            //Дебит оборот количество
            $debitQuantity = $item->debitQuantity;

            //Дебит оборот стойност
            $debitAmount = $item->debitAmount;

            //Кредит оборот количество
            $creditQuantity = $item->creditQuantity;

            //Кредит оборот стойност
            $creditAmount = $item->creditAmount;

            //Количество в края на периода
            $blQuantity = $item->blQuantity;

            if (($rec->availability == 'Налични') && $blQuantity < 0.0001) {
                continue;
            }
            if ($rec->availability == 'Отрицателни' && $blQuantity > -0.0001) {
                continue;
            }

            //Стойност в края на периода
            $blAmount = $item->blAmount;

            // добавя в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'productId' => $productId,
                    'code' => $productCode,
                    'productName' => $productName,
                    'prodGroups' => $prodRec->groups,
                    'measureId' => $productMeasureId,
                    'groupOne' => '',

                    'selfPrice' => '',
                    'amount' => '',

                    'baseQuantity' => $baseQuantity,
                    'baseAmount' => $baseAmount,

                    'debitQuantity' => $debitQuantity,
                    'debitAmount' => $debitAmount,

                    'creditQuantity' => $creditQuantity,
                    'creditAmount' => $creditAmount,

                    'blQuantity' => $blQuantity,
                    'blAmount' => $blAmount,

                    'reservedQuantity' => 0,
                    'expectedQuantity' => 0,
                    'freeQuantity' => 0,

                );
            } else {
                $obj = &$recs[$id];

                $obj->baseQuantity += $baseQuantity;
                $obj->baseAmount += $baseAmount;

                $obj->debitQuantity += $debitQuantity;
                $obj->debitAmount += $debitAmount;

                $obj->creditQuantity += $creditQuantity;
                $obj->creditAmount += $creditAmount;

                $obj->blQuantity += $blQuantity;
                $obj->blAmount += $blAmount;
            }
        }

        //Ако е избран разширен вариант на справката добавяме резервираните и очакваните количества
        if ($rec->type == 'long') {

            //Извличане на всички артикули със запазени количества
            $prodQuery = store_Products::getQuery();
            if ($rec->products) {
                $prodQuery->where("#productId = $rec->products");
            }

            $prodQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

            //Филтър по групи артикули
            if (isset($rec->group)) {
                $prodQuery->likeKeylist('groups', $rec->group);
            }

            //Филтър по склад
            if (isset($rec->storeId)) {
                $storeArr = keylist::toArray($rec->storeId);
                $prodQuery->in('storeId', $storeArr);
            }

            $prodQuery->where("#reservedQuantity IS NOT NULL OR #expectedQuantity IS NOT NULL");
            $reQuantitiesArr = array();
            while ($prodRERec = $prodQuery->fetch()) {

                if (!array_key_exists($prodRERec->productId, $reQuantitiesArr)) {
                    $reQuantitiesArr[$prodRERec->productId] = (object)array('reservedQuantity' => $prodRERec->reservedQuantity,
                        'expectedQuantity' => $prodRERec->expectedQuantity,
                        'freeQuantity' => $prodRERec->quantity - $prodRERec->reservedQuantity + $prodRERec->expectedQuantity,

                    );
                }else{
                    $obj = &$reQuantitiesArr[$prodRERec->productId];

                    $obj->reservedQuantity += $prodRERec->reservedQuantity;
                    $obj->expectedQuantity += $prodRERec->expectedQuantity;
                    $obj->freeQuantity += $prodRERec->quantity - $prodRERec->reservedQuantity + $prodRERec->expectedQuantity;

                }
            }

            //Добавяне на резервираните количества
            foreach ($reQuantitiesArr as $key => $val) {
                if ($recs[$key]) {
                    $recs[$key]->reservedQuantity = $val->reservedQuantity;
                    $recs[$key]->expectedQuantity = $val->expectedQuantity;
                    $recs[$key]->freeQuantity = $val->freeQuantity;
                } else {

                    $prodToFillRec = cat_Products::fetch($key);


                    $productRECode = cat_Products::getVerbal($prodToFillRec->id,'code');

                    if (!array_key_exists($key, $recs)) {
                        $recs[$key] = (object)array(

                            'productId' => $key,
                            'code' => $productRECode,
                            'productName' => $prodToFillRec->name,


                            'reservedQuantity' => $val->reservedQuantity,
                            'expectedQuantity' => $val->expectedQuantity,
                            'freeQuantity' => $val->freeQuantity,


                        );
                    }else{
                        $obj = &$recs[$key];

                        $obj->reservedQuantity += $val->reservedQuantity;
                        $obj->expectedQuantity += $val->expectedQuantity;
                        $obj->freeQuantity += $val->freeQuantity;
                    }
                }
            }
        }

        foreach ($recs as $key => $val) {

            //Себестойност на артикула
            if ($rec->selfPrice == 'manager') {
                $val->selfPrice = cat_Products::getPrimeCost($key, null, $val->blQuantity, $date);
            } else {
                $val->selfPrice = $val->blQuantity ? $val->blAmount / $val->blQuantity : null;
            }

            if ($val->blQuantity > 0) {
                $val->amount = $val->selfPrice * $val->blQuantity;
            } else {
                $val->amount = $val->blAmount;
            }

        }

        if (!is_null($recs) && $rec->orderBy) {
            $order = ($rec->orderBy == 'amount') ? 'DESC' : 'ASC';
            arr::sortObjects($recs, $rec->orderBy, $order);
        }

        $rec->totalProducts = (countR($recs));


        //Разпределение по групи
        if ($rec->seeByGroups && $rec->type == 'short') {

            $sumByGroup = $quantityByMeasureGroup = array();

            foreach ($recs as $key => $val) {

                $prodGroupsArr = (!empty(keylist::toArray($val->prodGroups))) ? keylist::toArray($val->prodGroups) : array('nn' => 'nn');

                foreach ($prodGroupsArr as $gr) {

                    $cln = clone $val;

                    if (!array_key_exists($gr, $sumByGroup)) {

                        //филтър по групи
                        if(isset($rec->group)){
                            if(!in_array($gr,keylist::toArray($rec->group)))continue;
                        }

                        $sumByGroup[$gr] = (object)array(
                            'amount' => $cln->amount,
                        );
                    }else{
                        $obj = &$sumByGroup[$gr];
                        $obj->amount += $cln->amount;
                    }

                    $mgrkey = $gr.'|'.$cln->measureId;

                    if (!array_key_exists($mgrkey, $quantityByMeasureGroup)) {
                        $quantityByMeasureGroup[$mgrkey] = (object)array(

                            'quantity' => $cln->blQuantity,
                            'measureId' => $cln->measureId,
                            'gr' => $gr,

                        );
                    }else{
                        $obj = &$quantityByMeasureGroup[$mgrkey];

                        $obj->quantity += $cln->blQuantity;
                    }

                    $id = $key . '|' . $gr;
                    if (is_numeric($gr)){
                        $grName = cat_Groups::getVerbal($gr, 'name');
                    }else{
                        $grName = 'яяя';
                    }

                    $cln->groupOne = $gr;
                    $cln->groupName = $grName;
                    $recs[$id] = $cln;
                    $recs[$id]->groupOne = $gr;

                }
                unset($recs[$key]);
            }

            $sumByGroup['quantities'] = $quantityByMeasureGroup;

            $this->groupByField = 'groupOne';

            if (!is_null($recs)) {
                arr::sortObjects($recs, 'groupName', 'asc','stri');

            }

            $rec->sumByGroup = $sumByGroup;

            $this->summaryListFields = '';
            $this->summaryRowCaption = '';
            $this->sortableListFields = '';

        }


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

        $fld->FLD('quantyti', 'double(smartRound,decimals=2)', 'caption=Налично');
        $fld->FLD('selfPrice', 'double(smartRound,decimals=2)', 'caption=Себестойност');
        $fld->FLD('amount', 'double(smartRound,decimals=2)', 'caption=Стойност');

        //Ако е избран разширен вариант на справката
        if ($rec->type == 'long') {
            $fld->FLD('reservedQuantity', 'double(smartRound,decimals=2)', 'caption=Запазено');
            $fld->FLD('expectedQuantity', 'double(smartRound,decimals=2)', 'caption=Очаквано');
            $fld->FLD('freeQuantity', 'double(smartRound,decimals=2)', 'caption=Разполагаемо');
        }

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

        $row = new stdClass();


        if (is_numeric($dRec->groupOne)){

            $row->groupOne = cat_Groups::getVerbal($dRec->groupOne, 'name').' :: стойност: '.$Double->toVerbal($rec->sumByGroup[$dRec->groupOne]->amount).' '.acc_Periods::getBaseCurrencyCode($rec->date).
                             ';  количества: ';
	    $bm = 0;
            foreach ($rec->sumByGroup['quantities'] as $val){
                if($val->gr == $dRec->groupOne) {
		    if($bm > 0) {
			    $row->groupOne .= ' + ';
		    }
                    $row->groupOne .= $Double->toVerbal($val->quantity).' '.cat_UoM::fetchField($val->measureId,'shortName');
		    $bm = $bm + 1;
                }
            }
        }else{
            $row->groupOne = 'Без група';
        }


        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }

        if (isset($dRec->productName)) {
            $row->productName = cat_Products::getLinkToSingle($dRec->productId, 'name');
        }

        $row->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');


        if (isset($dRec->blQuantity)) {
            $row->quantyti = $Double->toVerbal($dRec->blQuantity);
            $row->quantyti = ht::styleNumber($row->quantyti, $dRec->blQuantity);
        }

        if (isset($dRec->selfPrice)) {
            $row->selfPrice = $Double->toVerbal($dRec->selfPrice);
            $row->selfPrice = ht::styleNumber($row->selfPrice, $dRec->selfPrice);
        }

        $row->amount = $Double->toVerbal($dRec->amount);
        $row->amount = ht::styleNumber($row->amount, $dRec->amount);

        //Ако е избран разширен вариант на справката
        if ($rec->type == 'long') {

            $row->reservedQuantity = $Double->toVerbal($dRec->reservedQuantity);
            $row->reservedQuantity = ht::styleNumber($row->reservedQuantity, $dRec->reservedQuantity);

            $row->expectedQuantity = $Double->toVerbal($dRec->expectedQuantity);
            $row->expectedQuantity = ht::styleNumber($row->expectedQuantity, $dRec->expectedQuantity);

            $row->freeQuantity = $Double->toVerbal($dRec->freeQuantity);
            $row->freeQuantity = ht::styleNumber($row->freeQuantity, $dRec->freeQuantity);

        }

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


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN date--><div>|Към дата|*: [#date#]</div><!--ET_END date-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN products--><div>|Артикул|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN availability--><div>|Наличност|*: [#availability#]</div><!--ET_END availability-->
                                        <!--ET_BEGIN totalProducts--><div>|Брой артикули|*: [#totalProducts#]</div><!--ET_END totalProducts-->
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
            $fieldTpl->append('<b>' . cat_Products::getTitleById($data->rec->products) . '</b>', 'products');
        }

        if ((isset($data->rec->availability))) {
            $fieldTpl->append('<b>' . ($data->rec->availability) . '</b>', 'availability');
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
