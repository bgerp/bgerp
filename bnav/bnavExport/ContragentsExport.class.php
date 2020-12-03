<?php


/**
 * Експортиране на контрагенти в БН
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт в БН » Експорт контрагенти
 */
 class bnav_bnavExport_ContragentsExport extends frame2_driver_TableData
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
        $pQuery = purchase_Invoices::getQuery();
        
        
        $sQuery->where("#state != 'rejected' ");
        $pQuery->where("#state != 'rejected' ");
        
        $sQuery->show('contragentClassId, contragentId');
        $pQuery->show('contragentClassId, contragentId');
        
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
        

        $contragents  = array();
        
        while ($sRec = $sQuery->fetch()){
            
            //Масив с контрагенти от фактурите по продажби
            $id = $sRec->contragentClassId.'|'.$sRec->contragentId;
            
            if(!in_array($id, $contragents)){
            
                $contragents[$id]=$id;
            }
           
        }
        
        while ($pRec = $pQuery->fetch()){
            
            //Масив с контрагенти от покупките
            $id = $pRec->contragentClassId.'|'.$pRec->contragentId;
            
            if(!in_array($id, $contragents)){
                
                $contragents[$id]=$id;
            }
            
        }
       
        foreach ($contragents as $val){
            
            list($contragentClassId,$contrgentId) = explode('|', $val);
            
            $contragentClassName = core_Classes::getName($contragentClassId);
            
            $cRec = $contragentClassName::fetch($contrgentId);
            
            $id = $cRec->folderId;
            if($contragentClassName == 'crm_Companies'){
                $eic = $cRec->uicId ? $cRec->uicId :'' ;
            }
            if($contragentClassName == 'crm_Persons'){
                $eic = $cRec->egn ? $cRec->egn :'' ;
            }
            
            $vatNo = $cRec->vatId ?$cRec->vatId:'';
            
            expect($cRec->folderId,"Липсва folderId -> $cRec->name");
//             expect(!is_null($cRec->vatId) || !is_null($cRec->uicId) || !is_null($cRec->egn),
//               "Задължително е за контрагента да има поне един от номерата: БУЛСТАТ или ЕГН или Дан. номер -> $cRec->name ,$contragentClassName ,id($cRec->id)");
            
            
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $cRec->folderId,
                    'name' => $cRec->name,
                    'mol' =>'',
                    'vatId' => $vatNo,
                    'eic' =>$eic,
                    'country' =>drdata_Countries::fetch($cRec->country)->letterCode2,
                    'place' =>$cRec->place,
                    'address' =>$cRec->address,
                    
                    
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
            
            
            
            $fld->FLD('code', 'int', 'caption=Код на контрагента');
            $fld->FLD('name', 'varchar', 'caption=Име на контрагента');
            $fld->FLD('mol', 'varchar', 'caption=МОЛ');
            $fld->FLD('vatId', 'varchar', 'caption=Данъчен номер');
            $fld->FLD('eic', 'varchar', 'caption=Булстат - ЕИК/ЕГН');
            $fld->FLD('country', 'varchar', 'caption=Страна,tdClass=centered');
            $fld->FLD('place', 'varchar', 'caption=Град');
            $fld->FLD('address', 'varchar', 'caption=Адрес');
            
        } else {
            
            $fld->FLD('code', 'int', 'caption=Код на контрагента');
            $fld->FLD('name', 'varchar', 'caption=Име на контрагента');
            $fld->FLD('mol', 'varchar', 'caption=МОЛ');
            $fld->FLD('vatId', 'varchar', 'caption=Данъчен номер');
            $fld->FLD('eic', 'varchar', 'caption=Булстат - ЕИК/ЕГН');
            $fld->FLD('country', 'varchar', 'caption=Страна,tdClass=centered');
            $fld->FLD('place', 'varchar', 'caption=Град');
            $fld->FLD('address', 'varchar', 'caption=Адрес');
            
            //$fld->FLD('full', 'varchar', 'caption=Контрагент');
            
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
        $row->mol = $dRec->mol;
        $row->vatId = $dRec->vatId;
        $row->eic = $dRec->eic;
        $row->country = $dRec->country;
        $row->place = $dRec->place;
        $row->address = $dRec->address;
        
        
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
        $res->mol = $dRec->mol;
        $res->vatId = $dRec->vatId;
        $res->eic = $dRec->eic;
        $res->country = $dRec->country;
        $res->place = $dRec->place;
        $res->address = $dRec->address;
        
    }
    
   
}