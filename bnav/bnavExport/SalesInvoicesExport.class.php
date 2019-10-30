<?php


/**
 * Експортиране на фактури по продажби в БН
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт в БН » Експорт фактури продажби
 */
class bnav_bnavExport_SalesInvoicesExport extends frame2_driver_TableData
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
        
        $sQuery = sales_Invoices::getQuery();
        
        
        $sQuery->where("#state != 'draft' ");
        
        // Ако е посочена начална дата на период
        if ($rec->from) {
            $sQuery->where(array(
                "#date >= '[#1#]'",
                $rec->from . ' 00:00:00'
            ));
        }
        
        //Крайна дата / 'към дата'
        if ($rec->from) {
            $sQuery->where(array(
                "#date <= '[#1#]'",
                $rec->to . ' 23:59:59'
            ));
        }
        
        
        $invoices = array();
        
        while ($sRec = $sQuery->fetch()) {
            
            //Масив с фактури от продажбите
            $id = $sRec->id;
            
            //Състояние
            $state = $sRec->state;
            
            //Код на контрагента, така както е експортиран в БН. В случая folderId  на контрагента
            $contragentClassName = core_Classes::getName($sRec->contragentClassId);
            $contragentCode = $contragentClassName::fetch($sRec->contragentId)->folderId;
            
            //Име на контрагента
            $contragentName = $sRec->contragentName;
            
            //VAT номер на контрагента
            $contragentVatNo = $sRec->contragentVatNo;
            
            //Национален номер на контрагента
            $contragentNo = $sRec->uicNo;
            
            //Тип на плащането
            $paymentType = $sRec->paymentType;
            
            //Банкова сметка
            $bankAccount = $sRec->accountId;
            
            if ($sRec->type != 'invoice'){
               
                $detRecs = sales_InvoiceDetails::getQuery()->fetchAll("#invoiceId = $sRec->id");//if ($sRec->number == 406)bp($sRec,$detRecs);
               
                $mvc = cls::get('sales_InvoiceDetails'); //if ($sRec->number == 406)bp($detRecs,$mvc->Master->getInvoiceDetailedInfo($sRec->originId));
                sales_InvoiceDetails::modifyDcDetails($detRecs, $sRec, $mvc);
                if ($sRec->number == 406) bp($detRecs);
               
                if (!$detRecs && !array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'type' => $sRec->type,
                        'number' => $sRec->number,
                        'date' => $sRec->date,
                        'contragentVatNo' => $contragentVatNo,
                        'contragentNo' => $contragentNo,
                        'contragentName' => $contragentName,
                        'paymentType' => $paymentType,
                        'accountId' => $bankAccount,
                        'accItem' => '',
                        'currencyId' => $sRec->currencyId,
                        'rate' => $sRec->rate,
                        'dealValue' => $sRec->dealValue,
                        'state' => $state,
                        
                    );
                }
                
                
            }
            
            // Запис в масива
            if (!array_key_exists($id, $invoices)) {
                $invoices[$id] = (object) array(
                    
                    'type' => $sRec->type,
                    'number' => $sRec->number,
                    'date' => $sRec->date,
                    'contragentVatNo' => $contragentVatNo,
                    'contragentNo' => $contragentNo,
                    'contragentName' => $contragentName,
                    'paymentType' => $paymentType,
                    'accountId' => $bankAccount,
                    'accItem' => '',
                    'currencyId' => $sRec->currencyId,
                    'rate' => $sRec->rate,
                    'dealValue' => $sRec->dealValue,
                    'state' => $state,
                
                );
            }
        }
        
        $invArr = array_keys($invoices);
        
        $dQuery = sales_InvoiceDetails::getQuery();
        $dQuery->in('invoiceId', $invArr);
        
        
        while ($dRec = $dQuery->fetch()) {
            $id = $dRec->id;
            
            $pRec = cat_Products::fetch($dRec->productId);
            $erpCode = $pRec->code ? $pRec->code : 'Art'.$pRec->id;
            $prodCode = $pRec->bnavCode ? $pRec->bnavCode : $erpCode;
            $measure = cat_UoM::getShortName($pRec->measureId);
            $detAmount = $dRec->amount;
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    'invoice' => $invoices[$dRec->invoiceId],
                    'number' => $invoices[$dRec->invoiceId]->number,
                    'prodCode' => $prodCode,
                    'quantity' => $dRec->quantity,
                    'price' => $dRec->price,
                    'detAmount' => $detAmount,
                    'vatAmount' => '',
                    'measure' => $measure,
                    'vat' => cat_Products::getVat($pRec->id)*100,
                    'accText' => '',
                
                );
            }
        }
        
        arr::sortObjects($recs, 'number', 'ASC');
     //  bp($recs);
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
            $fld->FLD('state', 'varchar', 'caption=Статус');
            $fld->FLD('contragentName', 'varchar', 'caption=Доставчик->Име');
            $fld->FLD('contragentVatNo', 'varchar', 'caption=Доставчик->VAT Код');
            $fld->FLD('contragentNo', 'varchar', 'caption=Доставчик->Нац. Код');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('rate', 'double', 'caption=Курс на валутата');
            $fld->FLD('dealValue', 'double', 'caption=Обща стойност->без ДДС');
            $fld->FLD('accItem', 'int', 'caption=Сч. с-ка');
            $fld->FLD('prodCode', 'varchar', 'caption=Код на стоката');
            $fld->FLD('quantity', 'double', 'caption=Количество');
            $fld->FLD('price', 'double', 'caption=Ед цена');
            $fld->FLD('detAmount', 'double', 'caption=Ст. на реда');
            $fld->FLD('measure', 'varchar', 'caption=Мерна единица,tdClass=centered');
            $fld->FLD('vat', 'double', 'caption=% ДДС');
            $fld->FLD('paymentType', 'varchar', 'caption=Плащане');
            $fld->FLD('bankAccount', 'varchar', 'caption=Сметка');
        } else {
            $fld->FLD('type', 'varchar', 'caption=Док Тип');
            $fld->FLD('number', 'varchar', 'caption=Номер,tdClass=centered');
            $fld->FLD('date', 'date', 'caption=Дата');
            $fld->FLD('state', 'varchar', 'caption=Статус');
            $fld->FLD('contragentName', 'varchar', 'caption=Име');
            $fld->FLD('contragentVatNo', 'varchar', 'caption=VAT');
            $fld->FLD('contragentNo', 'varchar', 'caption=Код');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('rate', 'double', 'caption=Курс');
            $fld->FLD('dealValue', 'double', 'caption=без ДДС');
            $fld->FLD('accItem', 'int', 'caption=Сч. с-ка');
            $fld->FLD('prodCode', 'varchar', 'caption=Код прод.');
            $fld->FLD('quantity', 'double', 'caption=Кол');
            $fld->FLD('price', 'double', 'caption=Цена');
            $fld->FLD('detAmount', 'double', 'caption=Ст. на реда');
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
        //bp($dRec);
        $row = new stdClass();
        if ($dRec->invoice){
        $row->type = $dRec->invoice->type;
        $row->number = $dRec->invoice->number;
        $row->date = $Date->toVerbal($dRec->invoice->date);
        $row->state = $dRec->invoice->state;
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
        $row->detAmount = $Double->toVerbal($dRec->detAmount);
        $row->measure = $dRec->measure;
        $row->vat = $dRec->vat;
        $row->paymentType = $dRec->invoice->paymentType;
        $row->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
        }else{
            $row->type = $dRec->type;
            $row->number = $dRec->number;
            $row->date = $Date->toVerbal($dRec->date);
            $row->state = $dRec->state;
            $row->contragentName = $dRec->contragentName;
            $row->contragentVatNo = $dRec->contragentVatNo;
            $row->contragentNo = $dRec->contragentNo;
            $row->accItem = $dRec->accItem;
            $row->currencyId = $dRec->currencyId;
            $row->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->rate);
            $row->dealValue = $Double->toVerbal($dRec->dealValue);
            $row->prodCode = $dRec->prodCode;
            $row->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
            $row->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
            $row->detAmount = $Double->toVerbal($dRec->detAmount);
            $row->measure = $dRec->measure;
            $row->vat = $dRec->vat;
            $row->paymentType = $dRec->paymentType;
            $row->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
        }
        
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
        $row->state = $dRec->invoice->state;
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
        $res->detAmount = $Double->toVerbal($dRec->detAmount);
        $res->measure = $dRec->measure;
        $res->vat = $dRec->vat;
        $res->paymentType = $dRec->paymentType;
        $res->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
    }
}
