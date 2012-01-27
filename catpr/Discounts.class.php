<?php



/**
 * Пакет от отстъпки по ценови групи към дата
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Отстъпки
 */
class catpr_Discounts extends core_Master
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Пакети отстъпки по класове клиенти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_Sorting';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'catpr_discounts_Details';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,catpr';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'document';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'input,caption=Наименование');
    }
    
    
    /**
     * @param core_Manager $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'edit' && !$mvc->haveRightFor('delete', $rec, $userId)) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        $paramsModel = 'catpr_Pricegroups';
        $paramsKey = 'priceGroupId';
        $detailsModel = 'catpr_discounts_Details';
        $detailsValue = 'discount';
        
        /* @var $detailsMgr core_Detail */
        $detailsMgr = &cls::get($detailsModel);
        
        /* @var $paramsMgr core_Manager */
        $paramsMgr = &cls::get($paramsModel);
        
        /* @var $paramsQuery core_Query */
        $paramsQuery = $paramsMgr->getQuery();
        
        expect(is_a($detailsMgr, 'core_Detail'));
        
        $valueType = $detailsMgr->getField($detailsValue)->type;
        
        while ($paramRec = $paramsQuery->fetch()) {
            $id = $val = NULL;
            
            if ($form->rec->id) {
                $detailRec = $detailsMgr->fetch("#{$detailsMgr->masterKey} = {$form->rec->id} AND #{$paramsKey} = {$paramRec->id}");
                $id = $detailRec->id;
                $val = $detailRec->discount;
            }
            $form->FLD("value_{$paramRec->id}", $valueType, "input,caption=Отстъпки->{$paramRec->name},value={$val}");
            $form->FLD("id_{$paramRec->id}", "key(mvc={$detailsMgr->className})", "input=hidden,value={$id}");
        }
        
        if ($form->rec->id) {
            $form->title = 'Редактиране на пакет |*"' . $form->rec->name . '"';
        } else {
            $form->title = 'Нов пакет отстъпки';
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
        /* @var $priceGroupQuery core_Query */
        $priceGroupQuery = catpr_Pricegroups::getQuery();
        
        while ($priceGroupRec = $priceGroupQuery->fetch()) {
            $detailRec = (object)array(
                'id' => $rec->{"id_{$priceGroupRec->id}"},
                'discountId' => $rec->id,
                'priceGroupId' => $priceGroupRec->id,
                'discount' => $rec->{"value_{$priceGroupRec->id}"}
            );
            
            catpr_discounts_Details::save($detailRec);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterDelete($mvc)
    {
    
    }
    
    
    /**
     * Процента в пакет отстъпки, дадена за ценова група продукти към дата
     *
     * @param int $id ИД на пакета отстъпки - key(mvc=catpr_Discounts)
     * @param int $priceGroupId ИД на ценова група продукти key(mvc=catpr_Pricegroups)
     * @param string $date
     * @return double число между 0 и 1, определящо отстъпката при зададените условия.
     */
    static function getDiscount($id, $priceGroupId)
    {
        $discount = catpr_discounts_Details::fetchField("#discountId = {$id} AND #priceGroupId = {$priceGroupId}", 'discount');
        $discount = (double)$discount;
        
        return $discount;
    }
}