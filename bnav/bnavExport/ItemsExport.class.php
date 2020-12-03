<?php


/**
 * Експортиране на артикули в БН
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт в БН » Експорт артикули
 */
 class bnav_bnavExport_ItemsExport extends frame2_driver_TableData
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
        
        $sQuery = sales_InvoiceDetails::getQuery();
        $sQuery->EXT('state', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');
        $sQuery->EXT('date', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');
        
        $pQuery = purchase_InvoiceDetails::getQuery();
        $pQuery->EXT('state', 'purchase_Invoices', 'externalName=state,externalKey=invoiceId');
        $pQuery->EXT('date', 'purchase_Invoices', 'externalName=date,externalKey=invoiceId');
        
        $sQuery->where("#state != 'rejected' ");
        $pQuery->where("#state != 'rejected' ");
        
        $sQuery->show('productId');
        $pQuery->show('productId');
        
        // Ако е посочена начална дата на период
        if ($rec->from) {
            $sQuery->where(array(
                "#date >= '[#1#]'",
                $rec->from . ' 00:00:00'
            ));
            
            $pQuery->where(array(
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
            
            $pQuery->where(array(
                "#date <= '[#1#]'",
                $rec->to . ' 23:59:59'
            ));
        }

        $items  = array();
        
        while ($sRec = $sQuery->fetch()){
            
            //Масив с артикулите от фактурите по продажбите
            $id = $sRec->productId;
            
            if(!in_array($id, $items)){
            
                $items[$id]=$id;
            }
           
        }
        
        while ($pRec = $pQuery->fetch()){
            
            //Масив с артикулите от фактурите по покупките
            $id = $pRec->productId;
            
            if(!in_array($id, $items)){
                
                $items[$id]=$id;
            }
            
        }
       
        foreach ($items as $val){
            
            $iRec = cat_Products::fetch($val);
            
            $id = $val;
            
            $erpCode = $iRec->code ? $iRec->code : 'Art'.$val;
            $code = $iRec->bnavCode ? $iRec->bnavCode : $erpCode;
            
            expect(!is_null($code), "Липсва код на артикула -> {$iRec->name}, id({$val})");
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $code,
                    'name' => $iRec->name,
                    'dim' =>'',
                    'measureId' =>$iRec->measureId,
                    
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
            
            
            
            $fld->FLD('code', 'int', 'caption=Код на стоката');
            $fld->FLD('name', 'varchar', 'caption=Име на стоката');
            $fld->FLD('dim', 'varchar', 'caption=Измервана величина');
            $fld->FLD('measureId', 'varchar', 'caption=Мерна единица');
           
            
        } else {
            
            $fld->FLD('code', 'int', 'caption=Код на стоката');
            $fld->FLD('name', 'varchar', 'caption=Име на стоката');
            $fld->FLD('dim', 'varchar', 'caption=Измервана величина');
            $fld->FLD('measureId', 'varchar', 'caption=Мерна единица');
            
            //$fld->FLD('full', 'varchar', 'caption=Артикул');
            
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
        
        $row = new stdClass();
        
        $row->code = $dRec->code;
        $row->name = $dRec->name;
        $row->dim = $dRec->dim;
        $row->measureId = cat_UoM::getShortName($dRec->measureId);
        
        
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
        $res->code = $dRec->code;
        $res->name = $dRec->name;
        $res->dim = $dRec->dim;
        $res->measureId = cat_UoM::getShortName($dRec->measureId);
        
    }
    
   
}