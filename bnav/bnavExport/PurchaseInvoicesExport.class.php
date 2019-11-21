<?php


/**
 * Експортиране на фактури по покупки в БН
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт в БН » Експорт фактури покупки
 */
class bnav_bnavExport_PurchaseInvoicesExport extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,admin,debug';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
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
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
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
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
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
        $recs = array();
        
        $pQuery = purchase_Invoices::getQuery();
        
        
        $pQuery->where("#state != 'rejected' ");
        
        // Ако е посочена начална дата на период
        if ($rec->from) {
            $pQuery->where(array(
                "#date >= '[#1#]'",
                $rec->from . ' 00:00:00'
            ));
        }
        
        //Крайна дата / 'към дата'
        if ($rec->from) {
            $pQuery->where(array(
                "#date <= '[#1#]'",
                $rec->to . ' 23:59:59'
            ));
        }
        
        
        $invoices = array();
        
        while ($pRec = $pQuery->fetch()) {
            
            //Масив с фактури от продажбите
            $id = $pRec->id;
            
            //Име на контрагента
            $contragentName = $pRec->contragentName;
            
            //VAT номер на контрагента
            $contragentVatNo = $pRec->contragentVatNo;
            
            //Национален номер на контрагента
            $contragentNo = $pRec->uicNo;
            
            //Тип на плащането
            $paymentType = $pRec->paymentType;
            
            //Банкова сметка
            $bankAccount = $pRec->accountId;
            
            // Запис в масива
            if (!array_key_exists($id, $invoices)) {
                $invoices[$id] = (object) array(
                    
                    'type' => $pRec->type,
                    'number' => $pRec->number,
                    'date' => $pRec->date,
                    'contragentVatNo' => $contragentVatNo,
                    'contragentNo' => $contragentNo,
                    'contragentName' => $contragentName,
                    'paymentType' => $paymentType,
                    'accountId' => $bankAccount,
                    'accItem' => '',
                    'currencyId' => $pRec->currencyId,
                    'rate' => $pRec->rate,
                    'dealValue' => $pRec->dealValue,
                    'vatRate' => $pRec->vatRate
                    
                );
            }
        }
        
        $invArr = array_keys($invoices);
        
        $dQuery = purchase_InvoiceDetails::getQuery();
        $dQuery->in('invoiceId', $invArr);
        
        
        while ($dRec = $dQuery->fetch()) {
            $id = $dRec->id;
            
            $prodRec = cat_Products::fetch($dRec->productId);
            $erpCode = $prodRec->code ? $prodRec->code : 'Art'.$prodRec->id;
            $prodCode = $prodRec->bnavCode ? $prodRec->bnavCode : $erpCode;
            $measure = cat_UoM::getShortName($prodRec->measureId);
            
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    'invoice' => $invoices[$dRec->invoiceId],
                    'prodCode' => $prodCode,
                    'quantity' => $dRec->quantity,
                    'price' => $dRec->price,
                    'vatAmount' => '',
                    'measure' => $measure,
                    'vat' => cat_Products::getVat($prodRec->id) * 100,
                
                
                );
            }
        }
        
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
            
            $fld->FLD('type', 'varchar', 'caption=Тип на документа');
            $fld->FLD('number', 'varchar', 'caption=Номер на документа,tdClass=centered');
            $fld->FLD('date', 'date', 'caption=Дата');
            $fld->FLD('contragentName', 'varchar', 'caption=Доставчик->Име');
            $fld->FLD('contragentVatNo', 'varchar', 'caption=Доставчик->VAT Код');
            $fld->FLD('contragentNo', 'varchar', 'caption=Доставчик->Нац. Код');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('rate', 'double', 'caption=Курс на валутата');
            $fld->FLD('dealValue', 'double', 'caption=Обща стойност->без ДДС');
            $fld->FLD('prodCode', 'varchar', 'caption=Код на стоката');
            $fld->FLD('quantity', 'double', 'caption=Количество');
            $fld->FLD('price', 'double', 'caption=Ед цена');
            $fld->FLD('measure', 'varchar', 'caption=Мерна единица,tdClass=centered');
            $fld->FLD('vat', 'double', 'caption=% ДДС');
            $fld->FLD('paymentType', 'varchar', 'caption=Плащане');
            $fld->FLD('bankAccount', 'varchar', 'caption=Сметка');
        } else {
            $fld->FLD('type', 'varchar', 'caption=Док Тип');
            $fld->FLD('number', 'varchar', 'caption=Номер,tdClass=centered');
            $fld->FLD('date', 'date', 'caption=Дата');
            $fld->FLD('contragentName', 'varchar', 'caption=Име');
            $fld->FLD('contragentVatNo', 'varchar', 'caption=VAT');
            $fld->FLD('contragentNo', 'varchar', 'caption=Код');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('rate', 'double', 'caption=Курс');
            $fld->FLD('dealValue', 'double', 'caption=без ДДС');
            $fld->FLD('prodCode', 'varchar', 'caption=Код прод.');
            $fld->FLD('quantity', 'double', 'caption=Кол');
            $fld->FLD('price', 'double', 'caption=Цена');
            $fld->FLD('measure', 'varchar', 'caption=Мерна ед.,tdClass=centered');
            $fld->FLD('vat', 'double', 'caption=ДДС ставка');
            $fld->FLD('paymentType', 'varchar', 'caption=Плащане');
            $fld->FLD('bankAccount', 'varchar', 'caption=Сметка');
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
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');
        
        $row = new stdClass();
        
        $row->type = $dRec->invoice->type;
        $row->number = $dRec->invoice->number;
        $row->date = $Date->toVerbal($dRec->invoice->date);
        $row->contragentName = $dRec->invoice->contragentName;
        $row->contragentVatNo = $dRec->invoice->contragentVatNo;
        $row->contragentNo = $dRec->invoice->contragentNo;
        $row->accItem = $dRec->invoice->accItem;
        $row->currencyId = $dRec->invoice->currencyId;
        $row->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->invoice->rate);
        $row->dealValue = $Double->toVerbal($dRec->invoice->dealValue);
        $row->prodCode = $dRec->prodCode;
        $row->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
        $row->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
        $row->measure = $dRec->measure;
        $row->vat = $dRec->vat;
        $row->paymentType = $dRec->invoice->paymentType;
        $row->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
        
        return $row;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');
        
        $row = new stdClass();
        
        $res->type = $dRec->invoice->type;
        $res->number = $dRec->invoice->number;
        $res->date = $Date->toVerbal($dRec->invoice->date);
        $res->contragentName = $dRec->invoice->contragentName;
        $res->contragentVatNo = $dRec->invoice->contragentVatNo;
        $res->contragentNo = $dRec->invoice->contragentNo;
        $res->accItem = $dRec->invoice->accItem;
        $res->currencyId = $dRec->invoice->currencyId;
        $res->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->invoice->rate);
        $res->dealValue = $Double->toVerbal($dRec->invoice->dealValue);
        $res->prodCode = $dRec->prodCode;
        $res->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
        $res->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
        $res->measure = $dRec->measure;
        $res->vat = $dRec->vat;
        $row->paymentType = $dRec->invoice->paymentType;
        $row->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
    }
}
