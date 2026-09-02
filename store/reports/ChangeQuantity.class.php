<?php


/**
 * Драйвер на отчет за Промяна по разполагаемо количество
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
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
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var string
     */
    protected $sortableListFields = 'kod,productId,measure,quantity,reservedQuantity,freeQuantity,changeQuantity';
    
    
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
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,placeholderType=all,after=title,single=none');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholderType=all,after=group,single=none');
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
        
        if (!empty($rec->id)) {
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
        $rec->id = $rec->id ?? 0;
        $rec->group = $rec->group ?? null;
        $rec->storeId = $rec->storeId ?? null;
        
        // Наличностите на артикулите по складове, с данните им от артикулната карта
        $query = store_Products::getQuery();
        $query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $query->EXT('productCode', 'cat_Products', 'externalName=code,externalKey=productId');
        $query->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        
        // Филтрирането по склад е в заявката
        if (isset($rec->storeId)) {
            $query->where("#storeId = {$rec->storeId}");
        } else {
            $query->where('#storeId IS NOT NULL');
        }
        
        if (isset($rec->group)) {
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $query, $rec->group, 'productId');
        }
        
        $query->show('id,productId,quantity,reservedQuantity,expectedQuantity,groups,productCode,measureId');
        
        if (!isset(self::$versionData[$rec->id])) {
            self::$versionData[$rec->id] = $this->getVersionBeforeData($rec);
        }
        
        // Разполагаемите количества от предишната версия, индексирани по артикул
        $oldFreeQuantities = array();
        $oldData = self::$versionData[$rec->id];
        if (is_array($oldData)) {
            foreach ($oldData as $oData) {
                if (empty($oData->productId)) {
                    continue;
                }
                $oldFreeQuantities[$oData->productId] = $oData->freeQuantity ?? 0;
            }
        }
        
        // Сумиране на количествата на артикула от всички складове
        while ($recMaterial = $query->fetch()) {
            $id = $recMaterial->productId ?? null;
            if (!$id) {
                continue;
            }
            
            $recMaterial->quantity = $recMaterial->quantity ?? 0;
            $recMaterial->reservedQuantity = $recMaterial->reservedQuantity ?? 0;
            $recMaterial->expectedQuantity = $recMaterial->expectedQuantity ?? 0;
            
            // добавяме в масива събитието
            if (!array_key_exists($id, $recs)) {
                $recs[$id] =
                    (object) array(
                        
                        'kod' => (!empty($recMaterial->productCode)) ? $recMaterial->productCode : "Art{$id}",
                        'measure' => $recMaterial->measureId,
                        'productId' => $id,
                        'quantity' => $recMaterial->quantity,
                        'group' => $recMaterial->groups,
                        'reservedQuantity' => $recMaterial->reservedQuantity,
                        'changeQuantity' => '',
                        'expectedQuantity' => $recMaterial->expectedQuantity
                    );
            } else {
                $obj = &$recs[$id];
                $obj->quantity += $recMaterial->quantity;
                $obj->expectedQuantity += $recMaterial->expectedQuantity;
                $obj->reservedQuantity += $recMaterial->reservedQuantity;
            }
        }
        
        foreach ($recs as $idProd => $productRec) {
            $productRec->freeQuantity = $productRec->quantity - $productRec->reservedQuantity + $productRec->expectedQuantity;
            
            // Промяната спрямо предишната версия, ако артикула е бил и в нея
            if (array_key_exists($idProd, $oldFreeQuantities)) {
                $productRec->changeQuantity = $productRec->freeQuantity - $oldFreeQuantities[$idProd];
            }
        }
        
        usort($recs, function ($a, $b) {
            return ($a->changeQuantity ?? 0) <=> ($b->changeQuantity ?? 0);
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
        
        $fld->FLD('kod', 'varchar', 'caption=Код,tdClass=nowrap');
        $fld->FLD('productId', 'varchar', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=nowrap');
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
        $singleUrl = cat_Products::getSingleUrlArray($dRec->productId);
        $row->productId = ht::createLinkRef(cat_Products::getVerbal($dRec->productId, 'name'), $singleUrl);
        $row->measure = cat_UoM::getShortName($dRec->measure);
        
        foreach (array('quantity', 'reservedQuantity', 'expectedQuantity', 'freeQuantity', 'changeQuantity') as $fld) {
            $value = $dRec->{$fld} ?? 0;
            $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($value);
            $row->{$fld} = ht::styleNumber($row->{$fld}, $value);
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
        
        if (!empty($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->group)) {
            $fieldTpl->append($data->row->group, 'group');
        }
        
        if (isset($data->rec->storeId)) {
            $fieldTpl->append($data->row->storeId, 'storeId');
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

            $versionRec = $query->fetch();
            $versionBeforeId = $versionRec->versionBefore ?? null;
        } else {
            $versionBeforeId = frame2_ReportVersions::fetchField($selectedVersionId, 'versionBefore');
        }

        $versionBeforeData = array();
        if (isset($versionBeforeId)) {
            $oldRec = frame2_ReportVersions::fetchField($versionBeforeId, 'oldRec');
            $versionBeforeData = is_array($oldRec->data->recs ?? null) ? $oldRec->data->recs : array();
        }
        
        return $versionBeforeData;
    }
}
