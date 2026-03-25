<?php


/**
 * Модел за извадка от данни за покупките
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_PurchasesData extends core_Manager
{
    /**
     * Себестойности към документ
     */
    public $title = 'Извадка от данни за покупките';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'purchase_Wrapper,plg_AlignDecimals2,plg_Sorting';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces ;
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo,debug';
    
    
     /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'containerId,valior=Вальор,productId,quantity,price,discount=Отст.,amount,expenses,state,folderId';
  
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        
        $this->FLD('detailClassId', 'int', 'caption=Детайл клас,mandatory');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory, tdClass=leftCol');
        
        $this->FLD('docClassId', 'int', 'caption=Документ клас,mandatory, tdClass=leftCol');
        $this->FLD('docId', 'int', 'caption=Документ Id,mandatory');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка,closed=Затворено)', 'caption=Статус, input=none');
        
        
        $this->FLD('productId', 'int', 'caption=Артикул,mandatory, tdClass=productCell leftCol wrap');
        
        $this->FLD('storeId', 'int', 'caption=Склад,mandatory');
        $this->FLD('quantity', 'double', 'caption=Количество,mandatory');
        $this->FLD('packagingId', 'int', 'caption=Пакетиране,mandatory');
        
        $this->FLD('price', 'double', 'caption=Цена,mandatory');
        $this->FLD('discount', 'percent', 'caption=Цени->Отстъпка,mandatory');
        $this->FLD('amount', 'double', 'caption=Стойност,mandatory');
        $this->FLD('expenses', 'double', 'caption=Разходи,mandatory');
        
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащане->Валута, input=none');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->курс валута,mandatory');
        
        $this->FLD('dealerId', 'int', 'caption=Дилър,mandatory');
        $this->FLD('createdBy', 'int', 'caption=Създател на документа,mandatory');
        
        $this->FLD('contragentId', 'int', 'caption=Контрагент,tdClass=leftCol');
        $this->FLD('contragentClassId', 'int', 'caption=Контрагент клас');
        
        $this->FLD('containerId', 'int', 'caption=Документ,mandatory');
        $this->FLD('folderId', 'int', 'caption=Папка,tdClass=leftCol');
        $this->FLD('threadId', 'int', 'caption=Нишка,tdClass=leftCol');
        $this->FLD('isFromInventory', 'varchar', 'caption=Инвентаризация,tdClass=leftCol');
        $this->FLD('canStore', 'varchar', 'caption=Складируем,tdClass=leftCol');
        
        $this->setDbIndex('productId,containerId');
        $this->setDbIndex('productId');
        $this->setDbIndex('containerId');
        $this->setDbIndex('folderId');
        $this->setDbIndex('valior');
        $this->setDbUnique('detailClassId,detailRecId');
       
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row    Това ще се покаже
     * @param stdClass $rec    Това е записа в машинно представяне
     * @param array    $fields - полета
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        
        if ($rec->productId) {
          $row->productId = cat_Products::getHyperlink($rec->productId, true);
        }
        
        try {
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        } catch (core_exception_Expect $e) {
            $row->containerId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
        }
        
        if(isset($rec->folderId)){
            $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('documentId', 'varchar', 'caption=Документ или контейнер, silent');
        $data->listFilter->showFields = 'documentId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        $data->query->orderBy('id', 'DESC');
        
        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->documentId)) {
                
                // Търсене и на последващите документи
                if ($document = doc_Containers::getDocumentByHandle($rec->documentId)) {
                    $in = array($document->fetchField('containerId'));
                    if ($document->isInstanceOf('purchase_Purchases')) {
                        $descendants = $document->getDescendants();
                        $descendantArr = array_values(array_map(function ($obj) {
                            
                            return $obj->fetchField('containerId');
                        }, $descendants));
                            $in = array_merge($in, $descendantArr);
                    }
                    
                    $data->query->in('containerId', $in);
                } elseif(type_Int::isInt($rec->documentId)){
                    $data->query->where("#containerId = {$rec->documentId}");
                }
            }
        }
    }


    /**
     * Връща информация за последната покупка на посочения артикул
     *
     * @param int $productId
     * @param datetime $valior
     * @param string $chargeVat
     * @param double $currencyRate
     * @param mixed $currencyId
     * @return string|null
     */
    public static function getLastPurchaseFormInfo($productId, $valior, $chargeVat, $currencyRate, $currencyId)
    {
        $inventoryClassId = store_InventoryNotes::getClassId();
        $pQuery = purchase_PurchasesData::getQuery();
        $pQuery->where("#productId = {$productId} AND #state IN ('active', 'closed') AND #docClassId != {$inventoryClassId}");
        $pQuery->orderBy('#valior,#id', 'DESC');

        if($lastPurchaseRec = $pQuery->fetch()){
            $lastPurchaseDocument = doc_Containers::getDocument($lastPurchaseRec->containerId);
            $price = isset($lastPurchaseRec->discount) ? $lastPurchaseRec->price * (1 - $lastPurchaseRec->discount) : $lastPurchaseRec->price;
            $vatExceptionId = cond_VatExceptions::getFromThreadId($lastPurchaseRec->threadId);
            $vat = cat_Products::getVat($productId, $valior, $vatExceptionId);
            $lastPriceDisplayed = deals_Helper::getDisplayPrice($price, $vat, $currencyRate, $chargeVat);

            $unit = ($chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
            $lastPriceVerbal = core_Type::getByName('double(smartRound)')->toVerbal($lastPriceDisplayed);
            $lastPriceVerbal = currency_Currencies::decorate($lastPriceVerbal, $currencyId);

            return "<div class='formCustomInfo'>" . tr("|Последна покупка|*: <b>{$lastPriceVerbal}</b>, |{$unit}|*") . " [{$lastPurchaseDocument->getLink(0)}]</div>";
        }

        return null;
    }
    /**
     * Преизчисляване на записи
     */
    public function act_Recalc()
    {
        expect(haveRole('debug'));


        $form = cls::get('core_Form');
        $form->title = 'Преизчисляване на записи';
        $form->FLD('from', 'date', 'caption=От дата,mandatory');
        $form->FLD('to', 'date', 'caption=До дата,mandatory');

        $form->setDefault('from', '2026-01-01 00:00:00');
        $form->setDefault('to', '2026-01-01 00:00:00');
        $form->input();

        if ($form->isSubmitted()) {

            //Създаване копие на таблицата
            $Class = cls::get('purchase_PurchasesData');
            $timestamp = date('Ymd_His');
            $Class->copyTable($timestamp);

            // Изважда цонтейнерите на записите от този период
            $query = $this->getQuery();

            $query->where(array(
                "#valior >= '[#1#]' AND #valior <= '[#2#]'",
                $form->rec->from . ' 00:00:00',
                $form->rec->to . ' 23:59:59'
            ));

            $query->where("#isFromInventory = 'false'");

            $purRecs = arr::extractValuesFromArray($query->fetchAll(), 'containerId');

            // Изтриване на записите от този период
            $this->delete(array(
                "#valior >= '[#1#]' AND #valior <= '[#2#]'",
                $form->rec->from . ' 00:00:00',
                $form->rec->to . ' 23:59:59'
            ));
            foreach ($purRecs as $v) {

                $pRec = doc_Containers::fetch($v);

                $mvc = cls::get($pRec->docClass);

                $docRec = $mvc->className::fetch($pRec->docId);

                purchase_plg_ExtractPurchasesData::add($mvc, $docRec);

            }

            followRetUrl();
        }


        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $res = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($res, $form);

        return $res;
    }

    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'Recalc', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на записи,target=_blank');
        }
    }

}
