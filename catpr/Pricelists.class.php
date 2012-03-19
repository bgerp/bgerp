<?php



/**
 * Ценоразписи за продукти от каталога
 *
 *
 * @category  all
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class catpr_Pricelists extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'catpr_pricelists_Details';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, date, discountId, currencyId, vat';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой може да го промени?
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('date', 'date', 'mandatory,input,caption=Към Дата');
        $this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name,allowEmpty)', 'input,caption=По Отстъпка');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies,select=name,allowEmpty)', 'input,caption=Валута');
        $this->FLD('vat', 'percent', 'input,caption=ДДС');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name)', 'input,caption=Групи');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterSave($mvc, &$id, $rec)
    {
        // Изтриване на (евентуални) стари изчисления
        catpr_pricelists_Details::delete("#pricelistId = {$rec->id}");
        
        // Намираме всички продукти, които са в поне една от заявените групи.
        $productIds = cat_Products::fetchByGroups($rec->groups, 'id');
        
        if (empty($productIds)) {
            // В никоя от заявените групи няма продукти
            return;
        }
        
        $costsQuery = catpr_Costs::getQuery();
        
        // Ограничаваме се само до продукти със зададена себестойност от заявените ценови групи.
        $costsQuery->where('#productId IN (' . implode(',', array_keys($productIds)) . ')');
        $costsQuery->groupBy('productId');
        
        //        $costsQuery->show('productId'); // <- това не работи за сега, трябва поправка в core_Query
        
        $ProductIntf = cls::getInterface('cat_ProductAccRegIntf', 'cat_Products');
        
        while ($cRec = $costsQuery->fetch()) {
            
            $costRec = catpr_Costs::getProductCosts($cRec->productId, $rec->date);
            
            if (count($costRec) == 0) {
                // Продукта няма себестойност към зададената дата - не влиза в ценоразписа.
                continue;
            }
            
            $costRec = reset($costRec);
            
            $price = $ProductIntf->getProductPrice($costRec->productId, $rec->date, $rec->discountId);
            
            if (!isset($price)) {
                // Ако цената на продукта не е дефинирана (най-вероятно няма себестойност), той
                // не влиза в ценоразпис.
                continue;
            }
            
            // Завишаване на цената с зададения процент ДДС
            $price = $price * (1 + $rec->vat);
            
            /*
             * @TODO Конвертиране на $price към валутата $rec->currencyId
             */
            
            catpr_pricelists_Details::save(
                (object)array(
                    'pricelistId' => $rec->id,
                    'priceGroupId' => $costRec->priceGroupId,
                    'productId' => $costRec->productId,
                    'price' => $price,
                    'state' => 'draft',
                )
            );
        }
    }
}