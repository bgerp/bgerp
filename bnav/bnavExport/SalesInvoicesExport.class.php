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
        
        
        $sQuery->where("#state != 'rejected' ");
        
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
        
        
        $invoices  = array();
        
        while ($sRec = $sQuery->fetch()){
            
            //Масив с фактури от продажбите
            $id = $sRec->id;
            
            //Код на контрагента, така както е експортиран в БН. В случая folderId  на контрагента
            $contragentClassName = core_Classes::getName($sRec->contragentClassId);
            $contragentCode = $contragentClassName::fetch($sRec->contragentId)->folderId;
            
            
            
            
            
            // Запис в масива
            if (!array_key_exists($id, $invoices)) {
                $invoices[$id] = (object) array(
                    
                    'type' => $sRec->type,
                    'number' => $sRec->number,
                    'date' =>$sRec->date,
                    'contragentCode' => $contragentCode,
                    'accItem' =>'',
                    'currencyId' =>$sRec->currencyId,
                    'rate' =>$sRec->rate,
                    'dealValue' =>$sRec->dealValue,
                    
                );
            }
            
           
        }
    
        $invArr = array_keys($invoices);
        
        $dQuery = sales_InvoiceDetails::getQuery();
        $dQuery->in('invoiceId' , $invArr);
        
        
        while ($dRec = $dQuery->fetch()){
            
            $id = $dRec->id;
            
            $pRec = cat_Products::fetch($dRec->productId);
            $erpCode = $pRec->code ? $pRec->code : 'Art'.$pRec->id;
            $prodCode = $pRec->bnavCode ? $pRec->bnavCode : $erpCode;
            $measure = cat_UoM::getShortName($pRec->measureId);
          
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    'invoice'=>$invoices[$dRec->invoiceId],
                    'prodCode' => $prodCode,
                    'quantity' => $dRec->quantity,
                    'price' =>$dRec->price,
                    'vatAmount' => '',
                    'measure' =>$measure,
                    'vat' =>cat_Products::getVat($pRec->id)*100,
                    'accText' =>'',
                    
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
            $fld->FLD('date', 'varchar', 'caption=Дата');
            $fld->FLD('contragentCode', 'varchar', 'caption=Код на доставчика');
           // $fld->FLD('accItem', 'varchar', 'caption=Счетоводна сметка');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('rate', 'double', 'caption=Курс на валутата');
            $fld->FLD('dealValue', 'double', 'caption=Обща стойност->без ДДС');
            $fld->FLD('prodCode', 'varchar', 'caption=Код на стоката');
            $fld->FLD('quantity', 'double', 'caption=Количество');
            $fld->FLD('price', 'double', 'caption=Ед цена');
            $fld->FLD('measure', 'varchar', 'caption=Мерна единица,tdClass=centered');
            $fld->FLD('vat', 'varchar', 'caption=% ДДС');
            
        } else {
            
            $fld->FLD('full', 'varchar', 'caption= ');
            
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
        $row->contragentCode = $dRec->invoice->contragentCode;
        $row->accItem = $dRec->invoice->accItem;
        $row->currencyId = $dRec->invoice->currencyId;
        $row->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->invoice->rate);
        $row->dealValue = $Double->toVerbal($dRec->invoice->dealValue);
        $row->prodCode = $dRec->prodCode;
        $row->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
        $row->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
        $row->measure = $dRec->measure;
        $row->vat = $dRec->vat;
        
        
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
        $Date = cls::get('type_Date');
        
        $res->full = $dRec->invoice->type.','.
                     $dRec->invoice->number.','.
                     $dRec->invoice->date.','.
                     $dRec->invoice->contragentCode.','.
                     $dRec->invoice->accItem.','.
                     $dRec->invoice->currencyId.','.
                     $dRec->invoice->rate.','.
                     $dRec->invoice->dealValue.','.
                     $dRec->prodCode.','.
                     $dRec->quantity.','.
                     $dRec->price.','.
                     $dRec->vatAmount.','.
                     $dRec->measure.','.
                     $dRec->vat.','.
                     $dRec->accText
                     ;
            
    }
    
//     /**
//      * Определя вида сделка за IBS
//      *
//      * @param  stdClass $rec - запис
//      * @return int
//      */
//     private function getDealType($rec)
//     {
//         $number = ($rec->contragentVatNo) ? $rec->contragentVatNo : $rec->uicNo;
        
//         if ($rec->contragentCountryId == $this->countryId || empty($rec->contragentCountryId)) {
//             // Ако е фирма от БГ сделката е 21
//             $vidSdelka = $this->confCache->FSD_DEAL_TYPE_BG;
//         } elseif (drdata_Vats::isHaveVatPrefix($number)) {
//             // Не е от БГ но е VAT - Евросъюз
//             $vidSdelka = $this->confCache->FSD_DEAL_TYPE_EU; // 23
//             // Обаче, ако експедиционното /packaging list/ е с адрес за достaвка в страна извън ЕС
//             // => $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU;
            
//             // Ако има експедиционно със същия containerId,
//             // взимаме данните за доставка и проверяваме дали това ни е случая
//             $shOrder = store_ShipmentOrders::fetch("#fromContainerId = {$rec->containerId}");
//             if ($shOrder->country) {
//                 $groupsArr = drdata_CountryGroups::getGroupsArr($shOrder->country);
//                 foreach ($groupsArr as $group) {
//                     if ('Чужбина извън ЕС' == $group->name) {
//                         $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU; // 22
//                     }
//                 }
//             }
//         } else {
//             // Извън Евросъюза
            
//             $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU; // 22
//             // Но ако е начислено ДДС вида сделка става 21 - по заявка на Даниела /нерегистрирани по ДДС извън БГ/
//             if ($rec->vatRate != 'no' && $rec->vatRate != 'exempt') {
//                 $vidSdelka = $this->confCache->FSD_DEAL_TYPE_BG;
//             }
//         }
        
//         return ($vidSdelka);
 //   }
    
    
    
}