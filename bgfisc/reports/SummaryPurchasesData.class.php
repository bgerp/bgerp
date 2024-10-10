<?php


/**
 * Мениджър на отчети относно: Обобщени данни за доставките
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Обобщени данни за доставките
 */
class bgfisc_reports_SummaryPurchasesData extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_SummaryPurchasesData';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,sales,ceo';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none');
        
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal),allowEmpty', 'caption=Търговци,after=to');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
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
        
        $query = purchase_Purchases::getQuery();
        
        $stateArr = array('active','closed');
        
        $query->in('state', $stateArr);
        
        if ($rec->from) {
            $query->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        if ($rec->to) {
            $query->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        while ($purRec = $query -> fetch()) {
            $id = $purRec->containerId;
            
            //ID на записа
            $recId = $purRec->containerId;
            
            //Код на оператор
            $userId = $purRec->createdBy;
            
            $userId = str_pad(substr($userId, -4), 4, '0', STR_PAD_LEFT);
            
            //Дата на доставката
            $purDate = dt::mysql2verbal($purRec->createdOn, 'd.m.Y');
            
            //Време на доставката
            $purTime = dt::mysql2verbal($purRec->createdOn, 'H:i:s');
            
            //Доставчик код
            $contragentCode = $purRec->folderId;
            
            //Доставчик име
            $contragentName = doc_Folders::getTitleById($purRec->folderId);
            
            $vatSum = $amountSum = 0;
            
            //Отстъпка
            $discount = $purRec->amountDiscount;
            
            //ДДС - сума
            $vatSum = $purRec->amountVat;
            
            //Сума на доставката - без ДДС
            $amountSum = $purRec->amountDeal - $vatSum + $purRec->amountDiscount;
            
            //Обща сума на доставката с ДДС
            $totalAmount = $purRec->amountDeal;
            
            //Метод на плащане
            $paymentMethodId = $purRec->paymentMethodId;
            
            //Фактури по покупката
            $invQuery = purchase_Invoices::getQuery();
            $invQuery->where("#threadId = {$purRec->threadId} ");
            
            $invoicesArr = array();
            while ($invoice = $invQuery->fetch()) {
                $invoicesArr[] = (object) array(
                    'data' => $invoice->date,
                    'number' => $invoice->number
                );
            }
            
            $counter = count($invoicesArr) == 0 ? 1 :count($invoicesArr);
            
            for ($i = 0; $i < $counter;$i++) {
                if (empty($invoicesArr)) {
                    $invoiceNumber = '';
                    $invoiceDate = '';
                } else {
                    $invoiceNumber = $invoicesArr[$i]->number;
                    $invoiceDate = $invoicesArr[$i]->data;
                    $id .= '|'.$invoicesArr[$i]->number;
                }
                
                
                // добавя в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        
                        'recId' => $recId,
                        'purDate' => $purDate,
                        'purTime' => $purTime,
                        'userId' => $userId,
                        'amountSum' => $amountSum,
                        'totalAmount' => $totalAmount,
                        'discount' => $discount,
                        'vat' => $vatSum,
                        'invoiceNumber' => $invoiceNumber,
                        'invoiceDate' => $invoiceDate,
                        'paymentMethodId' => $paymentMethodId,
                        'contragentCode' => $contragentCode,
                        'contragentName' => $contragentName,
                    
                    );
                }
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
        
        $fld->FLD('recId', 'int', 'caption=ID запис');
        
        $fld->FLD('purDate', 'varchar', 'caption=Доставка->дата,tdClass=centered');
        $fld->FLD('purTime', 'varchar', 'caption=Доставка->време,tdClass=centered');
        
        $fld->FLD('userId', 'varchar', 'caption=Код на оператор,tdClass=centered');
        
        $fld->FLD('contragentCode', 'int', 'caption=Доставчик->код,tdClass=centered');
        $fld->FLD('contragentName', 'varchar', 'caption=Доставчик->име');
        
        $fld->FLD('invoiceNumber', 'int', 'caption=Фактура->Номер,tdClass=centered');
        $fld->FLD('invoiceDate', 'varchar', 'caption=Фактура->Дата,tdClass=centered');
        
        $fld->FLD('amountSum', 'double(smartRound,decimals=2)', 'caption=Доставка->Стойност,tdClass=centered');
        $fld->FLD('discount', 'double(smartRound,decimals=2)', 'caption=Доставка->Отстъпка,tdClass=centered');
        $fld->FLD('vat', 'double(smartRound,decimals=2)', 'caption=Доставка->ДДС,tdClass=centered');
        $fld->FLD('totalAmount', 'double(smartRound,decimals=2)', 'caption=Доставка->Общо,tdClass=centered');
        
        $fld->FLD('paymentMethodId', 'varchar', 'caption=Вид на плащането,tdClass=centered');
        
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
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();
        
        $row = new stdClass();
        
        if (isset($dRec->recId)) {
            $row->recId = $dRec->recId;
        }
        
        if (isset($dRec->purDate)) {
            $row->purDate = $dRec->purDate;
        }
        
        if (isset($dRec->purTime)) {
            $row->purTime = $dRec->purTime;
        }
        
        if (isset($dRec->userId)) {
            $row->userId = $dRec->userId;
        }
        
        if (isset($dRec->contragentCode)) {
            $row->contragentCode = $dRec->contragentCode;
        }
        
        if (isset($dRec->contragentName)) {
            $row->contragentName = $dRec->contragentName;
        }
        
        if (isset($dRec->invoiceNumber)) {
            $row->invoiceNumber = $Int->toVerbal($dRec->invoiceNumber);
        }
        
        if (isset($dRec->invoiceDate)) {
            $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        }
        
        if (isset($dRec->amountSum)) {
            $row->amountSum = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountSum);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->discount);
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountSum + $dRec->vat);
        
        if (isset($dRec->paymentMethodId)) {
            $row->paymentMethodId = cond_PaymentMethods::getTitleById($dRec->paymentMethodId);
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        if ((isset($data->rec->dealers)) && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }
            
            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }
        
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
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
    }
}
