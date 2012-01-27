<?php



/**
 * Продукти
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Products extends core_Manager
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,store';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, tools=Пулт, name, storeId, quantity, quantityNotOnPallets, quantityOnPallets, makePallets';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'key(mvc=cat_Products, select=name)', 'caption=Име,remember=info');
        $this->FLD('storeId', 'varchar(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'int', 'caption=Количество->Общо');
        $this->FNC('quantityNotOnPallets', 'int', 'caption=Количество->Непалетирано');
        $this->FLD('quantityOnPallets', 'int', 'caption=Количество->На палети');
        $this->FNC('makePallets', 'varchar(255)', 'caption=Палетирай');
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @
     */
    function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->title = "Продукти в СКЛАД № {$selectedStoreId}";
    }
    
    
    /**
     * Извличане записите само от избрания склад
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
    }
    
    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        // storeId
        $selectedStoreId = store_Stores::getCurrent();
        $data->form->setReadOnly('storeId', $selectedStoreId);
        
        $data->form->showFields = 'storeId,name,quantity';
    }
    
    
    /**
     * Изпълнява се след конвертирането на $rec във вербални стойности
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $measureId = cat_Products::fetchField("#id = {$rec->name}", 'measureId');
        $measureShortName = cat_UoM::fetchField("#id = {$measureId}", 'shortName');
        
        if (haveRole('admin,store')) {
            $row->makePallets = Ht::createBtn('Палетирай', array('store_Pallets', 'add', 'productId' => $rec->id));
        }
        
        $row->quantity .= ' ' . $measureShortName;
        $row->quantityOnPallets .= ' ' . $measureShortName;
        $row->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets . ' ' . $measureShortName;
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        $data->listFilter->showFields = 'search';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();
        
        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $condProductId = "#id = '{$recFilter->productIdFilter}'";
            }
            
            // query
            if ($condProductId) $data->query->where($condProductId);
        }
    }
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */

}