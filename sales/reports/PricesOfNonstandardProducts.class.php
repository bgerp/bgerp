<?php


/**
 * Мениджър на отчети за цени на нестандартни артикули
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Цени на нестандартни артикули
 */
class sales_reports_PricesOfNonstandardProducts extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, admin';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck ;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField ;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields ;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        //$fieldset->FLD('products', 'varchar', 'caption=Артикули и количества,placeholder=Избери,after=title,removeAndRefreshForm,single=none,class=w100');
        $fieldset->FLD('products', 'text', 'caption=Артикули и количества,placeholder=Избери,after=title,removeAndRefreshForm,single=none,class=w100');

        $fieldset->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,silent,after=products');


    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

      //  $csv = '../bgerp/sales/reports/ProductsAndQuantities.csv';

       // $form->setDefault('products', $csv);

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
        $rec = $form->rec;



        if ($form->isSubmitted()) {




        }


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
        $recs  = array();



       // $prodArr = file($rec->products);

        $prodArr =explode('#',$rec->products);

        //Ако няма артикули в папката на контрагента връща празен масив
      if (!$prodArr) return $recs;


        foreach ($prodArr as $product){
            if ($product == '')continue;

        $productArr = explode(',',$product);
        $code =  ltrim($productArr[0], '#');


            //Артикул
            $productId = cat_Products::getByCode($code)->productId;

            $prodRec = cat_Products::fetch($productId);

            $quantity =$productArr[1];


            //Намиране на цената за съответното количество
            $contragent = doc_Folders::fetch($rec->folderId);

            $date = dt::today();

            $Policy = cls::get('price_ListToCustomers');

            $listId = price_ListToCustomers::getListForCustomer($contragent->coverClass,$contragent->coverId,$date);

            $currencyCode  = currency_Currencies::getCodeById($rec->currencyId);

            $currencyRate = currency_CurrencyRates::getRate(dt::today(),$currencyCode,null);

            $policyInfo = $Policy->getPriceInfo($contragent->coverClass, $contragent->coverId, $productId, null, $quantity,null, $currencyRate,null, $listId);

            $defoltTransport = sales_TransportValues::calcDefaultTransportToClient($productId,$quantity,$contragent->coverClass,$contragent->coverId);

            $price = $policyInfo->price + $defoltTransport['singleFee'];

            $id = $productId.'|'.$quantity;

          // добавяме в масива
          if (!array_key_exists($id, $recs)) {
              $recs[$id] = (object)array(

                  'id' => $id,
                  'product' => $productId,
                  'prodName' => $prodRec->name,
                  'measureId' => $prodRec->measureId,
                  'quantity'=> $quantity,
                  'price'=> $price,
              );
          }

        }
//bp($recs);
        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {
            $fld->FLD('product', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,tdClass=productCell leftCol wrap');
            $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
            $fld->FLD('price', 'double(decimals=2)', 'caption=Цена,smartCenter');
        }else{
            $fld->FLD('product', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,tdClass=productCell leftCol wrap');
            $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
            $fld->FLD('price', 'double(decimals=2)', 'caption=Цени->Продажна,smartCenter');
        }


        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        $row = new stdClass();

        $row->product = cat_Products::getShortHyperlink($dRec->product);

        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }

        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }

        if (isset($dRec->measureId)) {
            $row->measureId = cat_UoM::fetchField($dRec->measureId, 'shortName');
        }

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
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

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                       <!--ET_BEGIN folderId--><div>|Контрагент|*: [#folderId#]</div><!--ET_END folderId-->
                                       <!--ET_BEGIN currencyId--><div>|Валута|*: [#currencyId#]</div><!--ET_END currencyId-->                                       
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));



        if (isset($data->rec->currencyId)) {
            $fieldTpl->append('<b>' . currency_Currencies::fetch($data->rec->currencyId)->code . '</b>', 'currencyId');
        }

        if (isset($data->rec->folderId)) {
            $fieldTpl->append('<b>' . doc_Folders::fetch($data->rec->folderId)->title . '</b>', 'folderId');
        }


        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * Промяна на стойностите min и max
     *
     */
    public function act_EditQuantity()
    {

        expect($recId = Request::get('recId', 'int'));
        expect($productId = Request::get('productId', 'int'));
        expect($id = Request::get('rowId'));

        $rec = frame2_Reports::fetch($recId);

        $details = $rec->prodQuantities;


if ($details){

    $quantity = $details[$id]['quantity'];
    $price = $details[$id]['price'];

}else{
    $quantity = 0;
    $price = 0;
}

        $form = cls::get('core_Form');

        $form->title = "Редактиране на  |* ' " . ' ' . cat_Products::getHyperlink($productId) . "' ||*";

        $volOldquantity = $quantity;
        $volOldprice = $price;

        $form->FLD('volNewQuantity', 'double', 'caption=Въведи количество,input,silent');

        $form->FLD('volNewPrice', 'double', 'caption=Въведи max,input=none,silent');

        $form->setDefault('volNewQuantity', $volOldquantity);
        $form->setDefault('volNewPrice', $volOldprice);

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            //Намиране на цената за съответното количество
            $contragent = doc_Folders::fetch($rec->folderId);

            $date = dt::today();

            $Policy = cls::get('price_ListToCustomers');

            $listId = price_ListToCustomers::getListForCustomer($contragent->coverClass,$contragent->coverId,$date);

            $policyInfo = $Policy->getPriceInfo($contragent->coverClass, $contragent->coverId, $productId, null, $form->rec->volNewQuantity,null, 1,null, $listId);
            $defoltTransport = sales_TransportValues::calcDefaultTransportToClient($productId,$form->rec->volNewQuantity,$contragent->coverClass,$contragent->coverId);

            $volNewPrice = $policyInfo->price + $defoltTransport['singleFee'];

            $form->rec->volNewPrice = $volNewPrice;

            $idNew = $productId.'|'.$form->rec->volNewQuantity;


            $details[$idNew]['quantity'] = $mRec->volNewQuantity;
            $details[$idNew]['price'] = $mRec->$volNewPrice;

            $rec->prodQuantities = $details;

            $oldRowRec = $rec->data->recs[$id];

            $oldRowRec->id = $idNew;
            $oldRowRec->quantity = $form->rec->volNewQuantity;
            $oldRowRec->price = $form->rec->volNewPrice;



            $rec->data->recs[$idNew] = $oldRowRec;



            if (array_key_exists($id,$details)){
                unset($details[$id]);
            }

            unset($rec->data->recs[$id]);



            frame2_Reports::save($rec);


            frame2_Reports::refresh($rec);

            return new Redirect(getRetUrl());
        }

        return $form->renderHtml();


    }

    /**
     * Кои полета да са скрити във вътрешното показване
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    public static function on_AfterGetHideArrForLetterHead(frame2_driver_Proto $Driver, embed_Manager $Embedd, &$res, $rec, $row)
    {
        $res = arr::make($res);

      //  $res['external']['selfPriceTolerance'] = true;
    }
}
