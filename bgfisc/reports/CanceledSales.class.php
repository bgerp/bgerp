<?php


/**
 * Мениджър на отчети относно: Анулирани продажби
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Анулирани продажби
 */
class bgfisc_reports_CanceledSales extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_CanceledSales';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,sales,ceo';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


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
        
        $sQuery = bgfisc_Register::getQuery();
        
        if ($rec->from) {
            $sQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        
        if ($rec->to) {
            $sQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        $regRecArr = $sQuery->fetchAll();
        
        //Анулирани продажби
        foreach (array('pos_Receipts','sales_Sales','sales_Services') as $cls) {
            $canceledSales = array();
            
            $cancelRecQuery = $cls::getQuery();
            
            if ($rec->from) {
                $cancelRecQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
            }
            
            
            if ($rec->to) {
                $cancelRecQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
            }
            
            $cancelRecQuery->where("#state = 'rejected'");
            
            $canceledSales = arr::extractValuesFromArray($cancelRecQuery->fetchAll(), 'id'); //Масив със анулирани продажби
            
            foreach ($regRecArr as $regRec) {
                
                
                //Ако продажбата НЕ Е АНУЛИРАНА НЕ влиза в отчета
                if (!in_array($regRec->objectId, $canceledSales)) {
                    continue;
                }
                
                //Уникален номер на продажбата
                $urn = $regRec->urn;
                
                //Системен номер на продажбата
                $sysNumber = $regRec->number;
                
                $RegClass = cls::get($regRec->classId);
                
                $className = $RegClass->className;
                
                
                $canceledRec = $className::fetch("#id = {$regRec->objectId}");
                
                $detCls = $RegClass->mainDetail;
                
                $canceledDet = $detCls::getQuery();
                
                $masterKey = $canceledDet->mvc->masterKey;
                
                $canceledDet->where(array("#{$masterKey} = [#1#]",$regRec->objectId));
                
                $vatSum = $amountSum = 0;
                
                while ($detail = $canceledDet->fetch()) {
                    
                    if ($detail->action && strpos($detail->action, 'sale') === false) {
                        continue;
                    }
                    
                    //Ключ за $recs
                    $id = $regRec->number.'|'.$detail->productId; 
                    
                    //Код на стоката/услугата
                    if (!is_null(cat_Products::fetchField($detail->productId, 'code'))) {
                        $productCode = cat_Products::fetchField($detail->productId, 'code');
                    } else {
                        $productCode = 'Art'.$detail->productId;
                    }
                    
                    //Наименование на стоката/услугата
                    $name = cat_Products::fetchField($detail->productId, 'name');
                    
                    //количество
                    $quantity = $detail->quantity;
                    
                    //Единична цена
                    $price = $detail->price;
                    
                    //Отстъпка
                    $discount = $detail->amount * $detail->discount;
                    
                    //ДДС ставка
                    $vatRate = cat_Products::getVat($detail->productId)*100;
                    
                    
                    //ДДС - сума
                    $vatSum = ($detail->amount - $discount) * ($vatRate/100);
                    
                    //Обща сума
                    $amountSum = ($detail->amount - $discount) + $vatSum;
                    
                    //Дата на анулиране на продажбата
                    $revertDate = dt::mysql2verbal($canceledRec->modifiedOn, 'd.m.Y');
                    
                    //Време на анулиране на продажбата
                    $revertTime = dt::mysql2verbal($canceledRec->modifiedOn, 'H:i:s');
                    
                    //Дата на откриване на продажбата
                    $openDate = dt::mysql2verbal($regRec->createdOn, 'd.m.Y');
                    
                    //Време на откриване на продажбата
                    $openTime = dt::mysql2verbal($regRec->createdOn, 'H:i:s');
                    
                    //Код на оператор, регистрирал плащането
                    $userId = $canceledRec->createdBy;
                    
                    
                    // добавяме в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            'urn' => $urn,
                            'sysNumber' => $sysNumber,
                            'productCode' => $productCode,       //Код на стоката/услугата
                            'name' => $name,                     //Наименование на стоката/услугата
                            'quantity' => $quantity,             //Количество
                            'price' => $price,                   //Единична цена
                            'discount' => $discount,             //Отстъпка
                            'vatRate' => $vatRate,               //ДДС - ставка
                            'vat' => $vatSum,                    //ДДС - сума
                            'totalAmount' => $amountSum,         //Обща сума
                            'saleCloseDate' => $openDate,        //Дата на приключване на продажбата
                            'saleCloseTime' => $openTime,        //Време на приключване на продажбата
                            'revertDate' => $revertDate,         //Дата на сторниране на продажбата
                            'revertTime' => $revertTime,         //Време на сторниране на продажбата
                            'cashRegNum' => $cashRegNum,         //Индивидуален номер на ФУ регистрирал сторнирането
                            'userId' => $userId,                 //Код на оператор, регистрирал сторнирането
                        
                        );
                    }else {
                        $obj = &$recs[$id];
                        $obj->quantity += $quantity;
                        $obj->discount += $discount;
                        $obj->vat += $vatSum;
                        $obj->totalAmount += $amountSum;
                    }
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
        
        $fld->FLD('urn', 'varchar', 'caption=УНП');
        $fld->FLD('sysNumber', 'varchar', 'caption=Номер');
        
        $fld->FLD('productCode', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('name', 'varchar', 'caption=Име');
        
        $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
        $fld->FLD('price', 'double(decimals=2)', 'caption=Ед.цена');
        
        $fld->FLD('discount', 'double(decimals=2)', 'caption=Отстъпка');
        
        $fld->FLD('vatRate', 'double(decimals=2)', 'caption=ДДС->Ставка');
        $fld->FLD('vat', 'double(decimals=2)', 'caption=ДДС->сума');
        
        $fld->FLD('totalAmount', 'double(decimals=2)', 'caption=Сума');
        
        $fld->FLD('saleCloseDate', 'varchar', 'caption=Откриване->дата,tdClass=centered');
        $fld->FLD('saleCloseTime', 'varchar', 'caption=Откриване->време,tdClass=centered');
        
        $fld->FLD('revertDate', 'varchar', 'caption=Анулиране->дата,tdClass=centered');
        $fld->FLD('revertTime', 'varchar', 'caption=Анулиране->време,tdClass=centered');
        
        $fld->FLD('userId', 'varchar', 'caption=Оператор');
        
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
        
        $row = new stdClass();
        
        if (isset($dRec->urn)) {
            $row->urn = $dRec->urn;
        }
        
        if (isset($dRec->sysNumber)) {
            $row->sysNumber = $dRec->sysNumber;
        }
        
        if (isset($dRec->productCode)) {
            $row->productCode = $dRec->productCode;
        }
        
        if (isset($dRec->name)) {
            $row->name = $dRec->name;
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
            $row->quantity = ht::styleNumber($row->quantity, $dRec->quantity);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = $dRec->discount != 0 ?core_Type::getByName('double(decimals=2)')->toVerbal($dRec->discount):'';
        }
        
        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }
        
        if (isset($dRec->vatRate)) {
            $row->vatRate = $dRec->vatRate;
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->totalAmount)) {
            $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmount);
            $row->totalAmount = ht::styleNumber($row->totalAmount, $dRec->totalAmount);
        }
        
        if (isset($dRec->saleCloseDate)) {
            $row->saleCloseDate = $dRec->saleCloseDate;
        }
        
        if (isset($dRec->saleCloseTime)) {
            $row->saleCloseTime = $dRec->saleCloseTime;
        }
        
        if (isset($dRec->revertDate)) {
            $row->revertDate = $dRec->revertDate;
        }
        
        if (isset($dRec->revertTime)) {
            $row->revertTime = $dRec->revertTime;
        }
        
        if (isset($dRec->cashRegNum)) {
            $row->cashRegNum = $dRec->cashRegNum;
        }
        
        if (isset($dRec->userId)) {
            $row->userId = $dRec->userId;
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
