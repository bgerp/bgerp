<?php


/**
 * Мениджър на отчети относно: Детайлни данни за доставките
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Детайлни данни за доставките
 */
class bgfisc_reports_DetailedPurchasesData extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_DetailedPurchasesData';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,purchase,ceo';


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
        
        $query = purchase_PurchasesData::getQuery();
        
        while ($purDetailRec = $query -> fetch()) {
            $classesArr = array('purchase_Purchases');
            
            $firstDoc = doc_Threads::getFirstDocument($purDetailRec->threadId);
            
            if (!in_array($firstDoc->className, $classesArr)) {
                continue;
            }
            
            $firstDocRec = $firstDoc->className::fetch($firstDoc->that);
            $aaa[] = $firstDocRec->createdOn;
            $cond = $firstDocRec->createdOn >= $rec->from. ' 00:00:00' && $firstDocRec->createdOn <= $rec->to. ' 23:59:59';
            if (!$cond) {
                continue;
            }
            
            $prodRec = cat_Products::fetch($purDetailRec->productId);
            
            $id = $purDetailRec->id;
            
            //ID на записа
            $recId = $firstDocRec->containerId;
            
            //Код на продукта
            $productCode = $prodRec->code;
            
            //Име на продукта
            $productName = $prodRec->name;
            
            //Количество
            $quantity = $purDetailRec->quantity;
            
            //Отстъпка
            $discount = $purDetailRec->discount;
            
            //Единична цена
            $price = $purDetailRec->price * $purDetailRec->currencyRate - $discount;
            
            
            //ДДС - сума
            $vatExceptionId = cond_VatExceptions::getFromThreadId($purDetailRec->threadId);
            $vatSum = $purDetailRec->amount * cat_Products::getVat($purDetailRec->productId, null, $vatExceptionId);
            
            //Обща сума на продажбата - без ДДС
            $amountSum = $purDetailRec->amount;
            
            // добавя в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'recId' => $recId,
                    'code' => $productCode,
                    'productName' => $productName,
                    'quantity' => $quantity,
                    'price' => $price,
                    'amountSum' => $amountSum,
                    'discount' => $discount,
                    'vat' => $vatSum,
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
        
        $fld->FLD('recId', 'int', 'caption=ID запис');
        
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productName', 'varchar', 'caption=Име');
        
        $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество,tdClass=centered');
        
        $fld->FLD('price', 'double(decimals=2)', 'caption=Единична цена,tdClass=centered');
        $fld->FLD('discount', 'double(decimals=2)', 'caption=Отстъпка');
        $fld->FLD('vat', 'double(decimals=2)', 'caption=ДДС,tdClass=centered');
        $fld->FLD('amountSum', 'double(decimals=2)', 'caption=Обща сума,tdClass=centered');
        
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
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        
        if (isset($dRec->productName)) {
            $row->productName = $dRec->productName;
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }
        
        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->discount);
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->amountSum)) {
            $row->amountSum = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountSum);
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
