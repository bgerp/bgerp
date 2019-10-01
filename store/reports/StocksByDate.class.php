<?php


/**
 * Мениджър на отчети относно: Движение на стоки за период
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Движение на стоки за период
 */
class store_reports_StocksByDate extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'napodit,ceo';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none');
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
        
        bp();
        $Balance = new acc_ActiveShortBalance(array('from' => $rec->from, 'to' => $rec->to, 'accs' => '321', 'cacheBalance' => false, 'keepUnique' => true));
        $bRecs = $Balance->getBalance('321');
        
        foreach ($bRecs as $item) {
            $id = $item->ent2Id;
            
            $iRec = acc_Items::fetch($item->ent2Id);
            
            //Код на продукта
            list($productCode) = explode(' ', $iRec->num);
            
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
            
            //Стойност в края на периода
            $blAmount = $item->blAmount;
            
            // добавя в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $productCode,
                    'productName' => $productName,
                    
                    'baseQuantity' => $baseQuantity,
                    'baseAmount' => $baseAmount,
                    
                    'debitQuantity' => $debitQuantity,
                    'debitAmount' => $debitAmount,
                    
                    'creditQuantity' => $creditQuantity,
                    'creditAmount' => $creditAmount,
                    
                    'blQuantity' => $blQuantity,
                    'blAmount' => $blAmount,
                    
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
        
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productName', 'varchar', 'caption=Име');
        
        $fld->FLD('baseQuantity', 'double(decimals=2)', 'caption=Начало на периода->Количество,tdClass=centered');
        $fld->FLD('baseAmount', 'double(decimals=2)', 'caption=Начало на периода->Стойност,tdClass=centered');
        
        $fld->FLD('debitQuantity', 'double(decimals=2)', 'caption=Обороти дебит->Количество,tdClass=centered');
        $fld->FLD('debitAmount', 'double(decimals=2)', 'caption=Обороти дебит->Стойност,tdClass=centered');
        
        $fld->FLD('creditQuantity', 'double(decimals=2)', 'caption=Обороти кредит->Количество,tdClass=centered');
        $fld->FLD('creditAmount', 'double(decimals=2)', 'caption=Обороти кредит->Стойност,tdClass=centered');
        
        $fld->FLD('blQuantity', 'double(decimals=2)', 'caption=Край на периода->Количество,tdClass=centered');
        $fld->FLD('blAmount', 'double(decimals=2)', 'caption=Край на периода->Стойност,tdClass=centered');
        
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
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        
        if (isset($dRec->productName)) {
            $row->productName = $dRec->productName;
        }
        
        if (isset($dRec->baseQuantity)) {
            $row->baseQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->baseQuantity);
        }
        
        if (isset($dRec->baseAmount)) {
            $row->baseAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->baseAmount);
        }
        
        
        if (isset($dRec->debitQuantity)) {
            $row->debitQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitQuantity);
        }
        
        if (isset($dRec->debitAmount)) {
            $row->debitAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->debitAmount);
        }
        
        
        if (isset($dRec->creditQuantity)) {
            $row->creditQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->creditQuantity);
        }
        
        if (isset($dRec->creditAmount)) {
            $row->creditAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->creditAmount);
        }
        
        
        if (isset($dRec->blQuantity)) {
            $row->blQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->blQuantity);
        }
        
        if (isset($dRec->creditAmount)) {
            $row->blAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->blAmount);
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
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
            
                                <small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small>
            
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
