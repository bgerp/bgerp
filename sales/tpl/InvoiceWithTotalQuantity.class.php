<?php


/**
 * Обработвач на шаблона за фактура с обобщено количество
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за фактура с обобщено количество
 */
class sales_tpl_InvoiceWithTotalQuantity extends doc_TplScript
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'sales_Invoices';


    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    public function modifyDetailData(core_Mvc $detail, &$data)
    {
        $count = countR($data->recs);
        if ($count <= 1) return;

        $pQuery = cat_Products::getQuery();
        $pQuery->in('id', arr::extractValuesFromArray($data->recs, 'productId'));
        $pQuery->EXT('baseUnitId', 'cat_UoM', 'externalName=baseUnitId,externalKey=measureId');
        $pQuery->EXT('baseUnitRatio', 'cat_UoM', 'externalName=baseUnitRatio,externalKey=measureId');
        $pQuery->show('id,baseUnitRatio,baseUnitId,measureId');
        $pArr = $baseMeasures = array();
        while($pRec = $pQuery->fetch()){
            $baseUnitId = empty($pRec->baseUnitId) ? $pRec->measureId : $pRec->baseUnitId;
            $pArr[$pRec->id] = empty($pRec->baseUnitRatio) ? 1 : $pRec->baseUnitRatio;
            $baseMeasures[$baseUnitId] = $baseUnitId;
        }
        if(countR($baseMeasures) > 1) return;

        $baseMeasureId = key($baseMeasures);
        $totalQuantity = 0;
        foreach ($data->recs as $rec){
            if($data->masterData->rec->type == 'dc_note' && $rec->changedQuantity !== true) continue;
            $ratio = $pArr[$rec->productId];
            $totalQuantity += $ratio * $rec->quantity * $rec->quantityInPack;
        }

        if($totalQuantity){
            $round = cat_UoM::fetchField($baseMeasureId, 'round');
            $totalQuantityVerbal = core_Type::getByName("double(decimals={$round})")->toVerbal($totalQuantity);
            $totalQuantityVerbal = ht::styleNumber($totalQuantityVerbal, $totalQuantity);
            $data->totalQuantityData = (object)array('baseMeasureId' => tr(cat_UoM::getShortName($baseMeasureId)), 'totalQuantity' => $totalQuantityVerbal);
        }
    }


    /**
     * След рендиране на лист таблицата
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function afterRenderListTable(core_Mvc $detail, &$tpl, &$data)
    {
        if(is_object($data->totalQuantityData)){
            $subtract = (isset($data->listFields['discount'])) ? 5 : 4;
            $columns = countR($data->listFields) - $diff;
            $tpl1 = new core_ET(tr("|*<tr><td colspan='[#colspan#]' class='rightCol'>|Общо|*:</td><td class='centered'>[#baseMeasureId#]</td><td class='centered'>[#totalQuantity#]</td><td></td><td></td></tr>"));
            $tpl1->replace($columns, 'colspan');
            $tpl1->replace($data->totalQuantityData->baseMeasureId, 'baseMeasureId');
            $tpl1->replace($data->totalQuantityData->totalQuantity, 'totalQuantity');
            $tpl->append($tpl1, 'ROW_AFTER');
        }
    }
}
