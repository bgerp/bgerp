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
    public $listFields = 'containerId,valior=Вальор,productId,quantity,price,amount,expenses,state,folderId';
  
    
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
        $this->FLD('discount', 'double', 'caption=Цени->Отстъпка,mandatory');
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
}
