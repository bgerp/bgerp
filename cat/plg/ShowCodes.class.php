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
                arr::sortObjects($data->rows, 'code', 'ASC', 'str');
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
}
