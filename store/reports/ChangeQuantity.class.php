<?php


/**
 * Драйвер на отчет за Промяна по разполагаемо количество
 *
 *
 * @category  extrapack
 * @package   store
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Промяна по разполагаемо количество
 */
class store_reports_ChangeQuantity extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, store';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'docId';
    
    
    /**
     * Кеш на предишните версии
     */
    private static $versionData = array();
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=title,single=none');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=group');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        
        if ($rec->id) {
            $form->setReadOnly('storeId');
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
        $products = array();
        
        // Обръщаме се към трудовите договори
        $query = store_Products::getQuery();
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        if (isset($rec->group)) {
            $query->likeKeylist('groupMat', $rec->group);
        }
        
        if (!isset(self::$versionData[$rec->id])) {
            self::$versionData[$rec->id] = $this->getVersionBeforeData($rec);
        }
        $oldData = self::$versionData[$rec->id];
        
        $num = 1;
        
        // за всеки един индикатор
        while ($recMaterial = $query->fetch()) {
            if (!is_null($rec->storeId) && ($rec->storeId != $recMaterial->storeId)) {
                continue;
            }
            
            $id = $recMaterial->productId;
            
            if ($recMaterial->reservedQuantity == null) {
                $recMaterial->reservedQuantity = 0;
            }
            
            // добавяме в масива събитието
            if (!array_key_exists($id, $recs)) {
                $recs[$id] =
                    (object) array(
                        
                        'kod' => cat_Products::fetchField($recMaterial->productId, 'code'),
                        'measure' => cat_Products::getProductInfo($recMaterial->productId)->productRec->measureId,
                        'productId' => $recMaterial->productId,
                        'quantity' => $recMaterial->quantity,
                        'group' => cat_Products::fetchField($recMaterial->productId, 'groups'),
                        'reservedQuantity' => $recMaterial->reservedQuantity,
                        'changeQuantity' => ''
                    );
            } else {
                $obj = &$recs[$id];
                $obj->quantity += $recMaterial->quantity;
                $obj->reservedQuantity += $recMaterial->reservedQuantity;
            }
        }
        
        foreach ($recs as $idProd => $products) { 
            
            $products->freeQuantity = $products->quantity - $products->reservedQuantity;
            if (is_array($oldData) && count($oldData)) {
                foreach ($oldData as $oData) {
                    if ($oData->productId == $idProd) {
                        $products->changeQuantity = $products->freeQuantity - $oData->freeQuantity;
                    }
                }
            }
        }
        
        usort($recs, function ($a, $b) {
            
            return ($a->changeQuantity > $b->changeQuantity) ? 1 : -1;
        });
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('kod', 'varchar', 'caption=Код');
        $fld->FLD('productId', 'varchar', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка');
        $fld->FLD('quantity', 'double(smartRound)', 'caption=Наличност');
        $fld->FLD('reservedQuantity', 'double', 'caption=Запазено');
        $fld->FLD('freeQuantity', 'double', 'caption=Разполагаемо');
        $fld->FLD('changeQuantity', 'double', 'caption=Промяна');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();
        $row->kod = (!empty($dRec->kod)) ? core_Type::getByName('varchar')->toVerbal($dRec->kod) : "Art{$dRec->productId}";
        $row->productId = cat_Products::getShortHyperlink($dRec->productId);
        $row->measure = cat_UoM::getShortName($dRec->measure);
        
        foreach (array('quantity', 'reservedQuantity', 'freeQuantity', 'changeQuantity') as $fld) {
            $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
            $row->{$fld} = ht::styleNumber($row->{$fld}, $dRec->{$fld});
        }
        
        return $row;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->kod = (!empty($dRec->kod)) ? $dRec->kod : "Art{$dRec->productId}";
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if (!empty($rec->group)) {
            $row->group = implode(' ', cat_Groups::getLinks($rec->group));
        }
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN group-->|Групи|*: [#group#]<!--ET_END group--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->group)) {
            $fieldTpl->append($data->row->group, 'group');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * Връща данните от предишната версия
     *
     * @param stdClass $rec - записа на отчета
     *
     * @return array $versionBeforeData - данните от предишната версия
     */
    private function getVersionBeforeData($rec)
    {
        $selectedVersionId = frame2_Reports::getSelectedVersionId($rec->id);
        
        // Ако няма избрана версия това е последната за справката
        if (!$selectedVersionId) {
            $query = frame2_ReportVersions::getQuery();
            $query->where("#reportId = {$rec->id}");
            $query->orderBy('id', 'DESC');
            $query->show('versionBefore');
            
            $versionBeforeId = $query->fetch()->versionBefore;
        } else {
            $versionBeforeId = frame2_ReportVersions::fetchField($selectedVersionId, 'versionBefore');
        }
        
        $versionBeforeData = (isset($versionBeforeId)) ? frame2_ReportVersions::fetchField($versionBeforeId, 'oldRec')->data->recs : array();
        
        return $versionBeforeData;
    }
}
