<?php


/**
 * Обработвач на шаблона за документ с обобщено количество
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за документ с обобщено количество
 */
abstract class deals_tpl_DocumentWithTotalQuantity extends doc_TplScript
{
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
            $totalQuantityVerbal = core_Type::getByName("double(smartRound})")->toVerbal($totalQuantity);
            $totalQuantityVerbal = ht::styleNumber($totalQuantityVerbal, $totalQuantity);
            $detail->Master->pushTemplateLg($data->masterData->rec->template);
            $data->totalQuantityData = (object)array('baseMeasureId' => tr(cat_UoM::getShortName($baseMeasureId)), 'totalQuantity' => $totalQuantityVerbal);
            core_Lg::pop();
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
            $columns = countR($data->listFields) - $subtract;
            $tpl1 = new core_ET(tr("|*<tr><td colspan='[#colspan#]' class='rightCol'>|Общо|*:</td><td class='centered'>[#baseMeasureId#]</td><td class='centered'>[#totalQuantity#]</td><td></td><td></td></tr>"));
            $tpl1->replace($columns, 'colspan');
            $tpl1->replace($data->totalQuantityData->baseMeasureId, 'baseMeasureId');
            $tpl1->replace($data->totalQuantityData->totalQuantity, 'totalQuantity');
            $tpl->append($tpl1, 'ROW_AFTER');
        }
    }
}
