<?php


/**
 * Плъгин за показване на кода в бизнес документите
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_ShowCodes extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->showCodeColumn, false);
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->showReffCode, false);
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $data->showReffCode = $mvc->showReffCode;
        $data->showCodeColumn = $mvc->showCodeColumn;
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        if (!countR($data->recs)) return;

        $masterRec = $data->masterData->rec;
        if ($data->showReffCode === true) {
            $firstDocument = doc_Threads::getFirstDocument($masterRec->threadId);
            if ($firstDocument) {
                $listSysId = ($firstDocument->isInstanceOf('sales_Sales')) ? 'salesList' : 'purchaseList';
            } else {
                $listSysId = ($mvc instanceof sales_SalesDetails) ? 'salesList' : 'purchaseList';
            }
            
            $listId = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId);
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            
            // Показване на вашия реф, ако има
            if (isset($listId)) {
                $row->reff = cat_Listings::getReffByProductId($listId, $rec->productId, $rec->packagingId);
            }
            
            $row->code = cat_Products::getVerbal($rec->{$mvc->productFld}, 'code');
        }

        if($mvc->Master->detailOrderByField){
            $sortRequest = Request::get('Sort');

            // Ако все пак се сортира по артикула от стрелките да се игнорира зададеното сортиране
            if(!empty($sortRequest) && in_array($sortRequest, array("{$mvc->productFld}|up", "{$mvc->productFld}|down"))) return;

            $detailOrderBy = $data->masterData->rec->{$mvc->Master->detailOrderByField};
            if($detailOrderBy == 'code'){
                arr::sortObjects($data->rows, 'code', 'ASC', 'natural');
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if ($data->showCodeColumn === true) {
            arr::placeInAssocArray($data->listFields, array('code' => 'Код'), $mvc->productFld);
            $data->listTableMvc->FNC('code', 'varchar', 'tdClass=small-field morePadding nowrap');
        }
        
        if ($data->showReffCode === true) {
            $before = ($mvc->showCodeColumn === true) ? 'code' : 'productId';
            arr::placeInAssocArray($data->listFields, array('reff' => 'Ваш №'), $before);
            $data->listTableMvc->FNC('reff', 'varchar', 'tdClass=small-field morePadding nowrap');
        }
    }


    /**
     * Метод по подразбиране за извличане на детайлите в правилната подредба за бутоните напред/назад
     *
     * @param core_Detail $DetailMvc
     * @param array $res
     * @param int $detailId
     * @return void
     */
    public static function on_BeforeGetPrevAndNextDetailQuery($DetailMvc, &$res, $detailId)
    {
        // Извличане на записа и мастъра
        $masterId = $DetailMvc->fetchField($detailId, $DetailMvc->masterKey);
        $masterRec = $DetailMvc->Master->fetch($masterId);
        $dQuery = $DetailMvc->getQuery();
        $dQuery->where("#{$DetailMvc->masterKey} = {$masterId}");

        $res = array();

        // Ако в мастъра има посочено поле за сортиране на детайла
        if(isset($DetailMvc->Master->detailOrderByField)) {
            if($masterRec->{$DetailMvc->Master->detailOrderByField} == 'code'){
                if(isset($DetailMvc->productFieldName)){
                    $dRecs = array();

                    // Извличат се кодовете на артикулите, за да може да се сортира по тях
                    $cloneQuery = clone $dQuery;
                    $cloneQuery->EXT('code', 'cat_Products', "externalName=code,externalKey={$DetailMvc->productFieldName}");
                    $cloneQuery->XPR('codeCalc', 'varchar', "COALESCE(#code, CONCAT('Art', #{$DetailMvc->productFieldName}))");
                    while($dRec = $cloneQuery->fetch()){
                        $dRecs[] = array('id' => $dRec->id, 'code' => $dRec->codeCalc);
                    }
                    arr::sortObjects($dRecs, 'code', 'ASC', 'natural');
                    foreach ($dRecs as $dRec1){
                        $res[] = $dRec1['id'];
                    }
                }
            }

            // Иначе ще си се сортират по реда на създаване
            if(!countR($res)){
                $dQuery->orderBy('id', 'ASC');
                $res = arr::extractValuesFromArray($dQuery->fetchAll(), 'id');
            }
        }
    }
}
