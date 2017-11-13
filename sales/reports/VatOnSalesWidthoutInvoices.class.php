<?php



/**
 * Мениджър на отчети за начислено ДДС при продажба без фактура:-по арткули
 *
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » ДДС при продажба без фактура
 */
class sales_reports_VatOnSalesWidthoutInvoices extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, store, sales, admin, purchase';



    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,after=title');
        $fieldset->FLD('totalVat', 'double(decimals=2)', 'caption=ДДС за периода,input=none');

    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */

    protected function prepareRecs($rec, &$data = NULL)
    {

        $recs = array();

        $query = sales_SalesDetails::getQuery();

        $query->EXT('chargeVat', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('makeInvoice', 'sales_Sales', 'externalKey=saleId');
        $query->EXT('state', 'sales_Sales', 'externalKey=saleId');

        $query->where("#state = 'closed'");
        $query->where("#makeInvoice = 'no'");
        $query->where(array("#chargeVat = '[#1#]' OR #chargeVat = '[#2#]'", 'yes', 'separate'));

        $totalVat = 0;

        while ($articul = $query->fetch()){

            $id = $articul->productId;


            $totalVat += $articul->amount;

            if (!array_key_exists($id, $recs)) {

                $recs[$id] =

                    (object)array(

                        'productId' => $articul->productId,
                        'measure' => cat_Products::fetchField($id, 'measureId'),
                        'quantity' => $articul->quantity,
                        'amount' => $articul->amount,
                        'vat' => $articul->amount * 0.2,
                        'price' => $articul->price,

                    );

            } else {

                $obj = &$recs[$id];

                $obj->quantity += $articul->quantity;

                $obj->amount += $articul->amount;

            }

            $recs[$id]->vat = (double)($recs[$id]->amount * 0.2);

            $recs[$id]->price = (double)($recs[$id]->amount / $recs[$id]->quantity);


        }

        $rec->totalVat = $totalVat;

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {

        $fld = cls::get('core_FieldSet');

        if($export === FALSE){

            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
            $fld->FLD('price', 'double', 'caption=Ед.цена,smartCenter');
            $fld->FLD('amount', 'double(decimals=2)', 'caption=Стойност,smartCenter');
            $fld->FLD('vat', 'double', 'caption=ДДС,smartCenter');
        } else {
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
            $fld->FLD('price', 'double', 'caption=Ед.цена,smartCenter');
            $fld->FLD('amount', 'double(decimals=2)', 'caption=Стойност,smartCenter');
            $fld->FLD('vat', 'double', 'caption=ДДС,smartCenter');

        }

        return $fld;

    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');

        $row = new stdClass();

        if(isset($dRec->productId)) {
            $row->productId =  cat_Products::getShortHyperlink($dRec->productId);
        }

        if(isset($dRec->quantity)) {
            $row->quantity =  core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }
        if(isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure,'shortName');
        }

        if (isset($dRec->amount)) {
            $row->amount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amount);
        }

        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }

        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }

        return $row;

    }

}
