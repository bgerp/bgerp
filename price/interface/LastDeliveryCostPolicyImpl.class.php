<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна доставка(+ разходи)"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна доставка(+ разходи)"
 *
 */
class price_interface_LastDeliveryCostPolicyImpl extends price_interface_BaseCostPolicy
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';
    
    
    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Последна доставка(+ разходи)') : 'lastDelivery';
        
        return $res;
    }
    
    
    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts
     *
     * @return $res
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['accPrice']      - счетоводна цена
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts)
    {
        $res = array();
        
        // Намираме всички покупки с доставка
        $allPurchases = $this->getPurchasesWithProducts($affectedTargetedProducts, true, false);
        $classId = purchase_Purchases::getClassId();
        
        // Тук ще кешираме доставените артикули във всяка
        $purchaseProducts = array();
       
        // За всяка
        foreach ($allPurchases as $purRec) {
            
            // Ако няма цена за артикула, взимаме първата срещната, така винаги на артикула
            // ще му съответства последната доставна цена, другите записи ще се пропуснат
            if (!isset($res[$purRec->productId])) {
                
                // Ако няма кеширана информация за доставеното по сделката кешираме го
                if (!isset($purchaseProducts[$purRec->requestId])) {
                    
                    // Намираме всички записи от журнала по покупката
                    $entries = purchase_transaction_Purchase::getEntries($purRec->requestId);
                    
                    // Към тях търсим всички документи от вида "Корекция на стойности", които са
                    // в нишката на покупката и са по друга сделка. Понеже в тяхната контировка не участва
                    // перото на текущата сделка, и 'purchase_transaction_Purchase::getEntries' не може
                    // да им вземе записите, затова ги добавяме ръчно
                    $aExpensesQuery = acc_ValueCorrections::getQuery();
                    $aExpensesQuery->where("#threadId = {$purRec->threadId} AND #state = 'active' AND #correspondingDealOriginId != {$purRec->containerId}");
                    $aExpensesQuery->show('id');
                    
                    // За всеки документ "Корекция на стойности" в нишката
                    while ($aRec = $aExpensesQuery->fetch()) {
                        
                        // Намираме записите от журнала
                        $jRec = acc_Journal::fetchByDoc('acc_ValueCorrections', $aRec->id);
                        $dQuery = acc_JournalDetails::getQuery();
                        $dQuery->where("#journalId = {$jRec->id}");
                        $expensesEntries = $dQuery->fetchAll();
                        
                        // Добавяме записите на корекцията към записите на сделката
                        // Така ще коригираме себестойностите и с техните данни
                        $entries = $expensesEntries + $entries;
                    }
                    
                    // Намираме и кешираме всичко доставено по сделката с приспаднати корекции на сумите
                    // от документите от вида "Корекция на стойност". В обикновените записи имаше приложени
                    // само корекциите от документа когато той е към същата сделка. Когато е към друга не се вземаха
                    // затова трябваше да се добавят ръчно към записите
                    $purchaseProducts[$purRec->requestId] = purchase_transaction_Purchase::getShippedProducts($entries, $purRec->requestId, '321,302,601,602,60010,60020,60201', false, false);
                    
                    // Добавяне и на разпределените разходи, ако има
                    foreach ($purchaseProducts[$purRec->requestId] as $o1) {
                        $itemId = acc_Items::fetchItem('cat_Products', $o1->productId)->id;
                        $amount = acc_Balances::getBlAmounts($entries, '321', 'debit', '60201', array(null, $itemId, null))->amount;
                        $val = (empty($o1->quantity)) ? 0 : ($amount / $o1->quantity);
                        $o1->price += $val;
                    }
                }
                
                // Намираме какво е експедирано по сделката
                $shippedProducts = $purchaseProducts[$purRec->requestId];
                
                // Взимаме цената на продукта по тази сделка
                $price = $shippedProducts[$purRec->productId]->price;
                if (isset($price)) {
                    $price = round($price, 5);
                    
                    $res[$purRec->productId] = (object) array('classId'       => $this->getClassId(), 
                                                              'productId'     => $purRec->productId, 
                                                              'sourceClassId' => $classId, 
                                                              'accPrice'      => null,
                                                              'sourceId'      => $purRec->requestId, 
                                                              'quantity'      => $purRec->quantity, 
                                                              'price'         => $price);
                }
            }
        }
        
        // Връщаме намерените последни цени
        return $res;
    }
}



