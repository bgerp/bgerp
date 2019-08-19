<?php


/**
 * Помощен детайл подготвящ и обединяващ заедно детайлите за изпозлванията на артикула в документа
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_products_Usage extends core_Manager
{
    /**
     * Колко да са на страница заданията
     */
    public $listJobsPerPage = 10;
    
    
    /**
     * Колко да са на страница другите документи
     */
    public $listOtherDocumentsPerPage = 10;
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
    public function prepareUsage($data)
    {
        $masterRec = $data->masterData->rec;
        $data->isPublic = ($masterRec->isPublic == 'yes');
        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        
        $data->jobData = clone $data;
        $data->jobData->Jobs = cls::get('planning_Jobs');
        $this->prepareJobs($data->jobData);
        
        // Промяна на таба взависимост дали артикула е стандартен или не
        if ($data->isPublic === true) {
            if ($data->jobData->notManifacturable === true && !count($data->jobData->rows)) {
                
                return;
            }
            if (!haveRole('ceo,planning,job')) {
                
                return;
            }
            $data->Tab = 'top';
            $data->TabCaption = 'Задания';
            if (!$prepareTab || $prepareTab != 'Usage') {
                
                return;
            }
        } else {
            $data->Tab = 'top';
            $data->TabCaption = 'Документи';
            $data->Order = 2;
            if ($prepareTab && $prepareTab != 'Usage') {
                
                return;
            }
        }
        
        // Ако артикула е нестандартен тогава се показват и документите, в които се използват
        if ($data->isPublic === false) {
            $data->saleData = clone $data;
            $this->prepareDocuments('sales_Sales', 'sales_SalesDetails', $data->saleData);
            
            $data->purData = clone $data;
            $this->prepareDocuments('purchase_Purchases', 'purchase_PurchasesDetails', $data->purData);
            
            $data->quoteData = clone $data;
            $this->prepareDocuments('sales_Quotations', 'sales_QuotationsDetails', $data->quoteData);
        }
    }
    
    
    /**
     * Рендира ценовата информация за артикула
     */
    public function renderUsage($data)
    {
        $tpl = new core_ET('');
        $tpl->append($this->renderJobs($data->jobData));
        
        if ($data->isPublic === false) {
            $renderEvenIfEmpty = ($data->jobData->notManifacturable === true) ? true : false;
            $tpl->append($this->renderDocuments($data->saleData, $renderEvenIfEmpty));
            $tpl->append($this->renderDocuments($data->purData));
            $tpl->append($this->renderDocuments($data->quoteData));
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на използвания в документ
     *
     * @param mixed    $Document       - Документ
     * @param mixed    $DocumentDetail - Детайл на документ
     * @param stdClass $data           - Данни
     */
    private function prepareDocuments($Document, $DocumentDetail, &$data)
    {
        $data->Document = cls::get($Document);
        $Detail = cls::get($DocumentDetail);
        
        $data->recs = $data->rows = array();
        
        // Извличане на документите в чиито детайл се среща
        $dQuery = $Detail->getQuery();
        $dQuery->EXT('state', $Document, "externalName=state,externalKey={$Detail->masterKey}");
        $dQuery->where("#productId = {$data->masterId} AND #state != 'rejected'");
        $dQuery->groupBy($Detail->masterKey);
        $dQuery->show($Detail->masterKey);
        
        $ids = arr::extractValuesFromArray($dQuery->fetchAll(), $Detail->masterKey);
        
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listOtherDocumentsPerPage));
        $data->Pager->setPageVar('cat_Products', $data->masterId, $Document);
        
        // Ограничаване на заявката
        $query = $data->Document->getQuery();
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'closed' THEN 2  WHEN 'pending' THEN '3' ELSE 4 END)");
        $query->orderBy('#orderByState=ASC');
        
        if (count($ids)) {
            $query->in('id', $ids);
        } else {
            $query->where('1=2');
        }
        
        $data->Pager->setLimit($query);
        $fields = $data->Document->selectFields();
        $fields['-list'] = true;
        
        // Вербализиране на записите
        while ($dRec = $query->fetch()) {
            $data->recs[$dRec->id] = $dRec;
            $row = $data->Document->recToVerbal($dRec, $fields);
            $row->title = $data->Document->getHyperlink($dRec->id, true);
            $row->created = "{$row->createdOn} " . tr('от||by') . " {$row->createdBy}";
            $data->rows[$dRec->id] = $row;
        }
    }
    
    
    /**
     * Рендира таблицата с документите
     *
     * @param stdClass $data
     * @param bool     $evenIfEmpty
     *
     * @return void|core_ET
     */
    private function renderDocuments($data, $evenIfEmpty = false)
    {
        if (!count($data->rows) && $evenIfEmpty === false) {
            
            return;
        }
        
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->replace("style='margin-top:10px'", 'STYLE');
        $title = tr($data->Document->title);
        $tpl->append($title, 'title');
        
        $data->listFields = arr::make("title={$data->Document->singleTitle},folderId=Папка,created=Създадено");
        $dateArr = ($data->Document instanceof sales_Quotations) ? array('date' => 'Дата') : array('valior' => 'Вальор');
        arr::placeInAssocArray($data->listFields, $dateArr, null, 'title');
        
        $data->Document->invoke('BeforeRenderListTable', array($tpl, &$data));
        $table = cls::get('core_TableView', array('mvc' => $data->Document));
        $details = $table->get($data->rows, $data->listFields);
        
        $tpl->append($details, 'content');
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml(), 'content');
        }
        
        $tpl->removePlaces();
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на заданията към артикул
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl - шаблон на детайла
     */
    private function renderJobs($data)
    {
        if ($data->hide === true) {
            
            return;
        }
        
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $title = tr('Задания за производство');
        $tpl->append($title, 'title');
        
        if (isset($data->addUrl)) {
            $addBtn = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,title=Добавяне на ново задание за производство');
            $tpl->append($addBtn, 'title');
        }
        
        $listFields = arr::make('title=Задание,dueDate=Падеж,saleId=Продажба,packQuantity=Планирано,quantityProduced=Заскладено,packagingId=Мярка');
        $listFields = core_TableView::filterEmptyColumns($data->rows, $listFields, 'saleId');
        $data->listFields = $listFields;
        
        $data->Jobs->invoke('BeforeRenderListTable', array($tpl, &$data));
        $table = cls::get('core_TableView', array('mvc' => $data->jobData->Jobs));
        $details = $table->get($data->rows, $data->listFields);
        
        // Ако артикула не е производим, показваме в детайла
        if ($data->notManifacturable === true) {
            $tpl->append(" <span class='red small'>(" . tr('Артикулът не е производим') . ')</span>', 'title');
            $tpl->append('state-rejected', 'TAB_STATE');
        }
        
        $tpl->append($details, 'content');
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml(), 'content');
        }
        
        $tpl->removePlaces();
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на заданията за артикула
     *
     * @param stdClass $data
     */
    private function prepareJobs(&$data)
    {
        $masterRec = $data->masterData->rec;
        $data->rows = $data->recs = array();
        
        $fields = $data->Jobs->selectFields();
        $fields['__isDetail'] = $fields['-list'] = true;
        
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listJobsPerPage));
        $data->Pager->setPageVar('cat_Products', $data->masterId, 'planning_Jobs');
        
        // Намираме неоттеглените задания
        $query = $data->Jobs->getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->where("#state != 'rejected'");
        $query->orderBy('id,state', 'DESC');
        $data->Pager->setLimit($query);
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $data->Jobs->recToVerbal($rec, $fields);
        }
        
        if ($masterRec->canManifacture != 'yes') {
            $data->notManifacturable = true;
        }
        
        if (!haveRole('ceo,planning,job') || ($data->notManifacturable === true && !count($data->rows)) || $masterRec->state == 'template' || $masterRec->brState == 'template') {
            $data->hide = true;
            
            return;
        }
        
        // Проверяваме можем ли да добавяме нови задания
        if ($data->Jobs->haveRightFor('add', (object) array('productId' => $data->masterId))) {
            $data->addUrl = array('planning_Jobs', 'add', 'threadId' => $masterRec->threadId, 'productId' => $data->masterId, 'foreignId' => $masterRec->containerId, 'ret_url' => true);
        }
    }
}
