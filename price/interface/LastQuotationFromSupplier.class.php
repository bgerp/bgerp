<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна оферта от доставчик"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна оферта от доставчик"
 *
 */
class price_interface_LastQuotationFromSupplier extends price_interface_BaseCostPolicy
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
        $res = ($verbal) ? tr('Последна оферта от доставчик') : 'lastQuotationDelivery';

        return $res;
    }

    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @param datetime $datetime
     *
     * @return array
     */
    public function getAffectedProducts($datetime)
    {
        // Участват артикулите в активирани или оттеглени активни покупки, след посочената дата
        $pQuery = purchase_QuotationDetails::getQuery();
        $pQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery->EXT('activatedOn', 'purchase_Quotations', 'externalName=activatedOn,externalKey=quotationId');
        $pQuery->EXT('documentModifiedOn', 'purchase_Quotations', 'externalName=modifiedOn,externalKey=quotationId');
        $pQuery->EXT('state', 'purchase_Quotations', 'externalName=state,externalKey=quotationId');
        $pQuery->where("((#state = 'active' || #state = 'closed') AND #activatedOn >= '{$datetime}') OR (#state = 'rejected' AND #activatedOn IS NOT NULL AND #documentModifiedOn >= '{$datetime}')");
        $pQuery->where("#canStore = 'yes' AND #isPublic = 'yes'");
        $pQuery->show('productId');
        $affected = arr::extractValuesFromArray($pQuery->fetchAll(), 'productId');

        return $affected;
    }


    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts - засегнати артикули
     * @param array $params - параметри
     *
     * @return array
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts, $params = array())
    {
        $now = dt::now();

        // Коя е последната валидна активна оферта от доставчик за посочените артикули
        $quoteQuery = purchase_QuotationDetails::getQuery();
        $quoteQuery->EXT('state', 'purchase_Quotations', 'externalName=state,externalKey=quotationId');
        $quoteQuery->EXT('containerId', 'purchase_Quotations', 'externalName=containerId,externalKey=quotationId');
        $quoteQuery->EXT('date', 'purchase_Quotations', 'externalName=date,externalKey=quotationId');
        $quoteQuery->EXT('validFor', 'purchase_Quotations', 'externalName=validFor,externalKey=quotationId');
        $quoteQuery->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
        $quoteQuery->where("(#expireOn IS NULL AND #date >= '{$now}') OR (#expireOn IS NOT NULL AND #expireOn >= '{$now}')");
        $quoteQuery->where("#state = 'active'");

        if(countR($affectedTargetedProducts)){
            $quoteQuery->in('productId', $affectedTargetedProducts);
        } else {
            $quoteQuery->where("1=2");
        }

        $quoteQuery->orderBy('date=DESC,quotationId=DESC,quantity=ASC');
        $quotationRecs = $quoteQuery->fetchAll();

        $res = array();
        $classId = purchase_Quotations::getClassId();
        foreach ($quotationRecs as $quoteRec) {

            // Връщане на първата оферирана цена с най-голяма дата
            if (!isset($res[$quoteRec->productId])) {
                $price = deals_Helper::getSmartBaseCurrency($quoteRec->price, $quoteRec->date);
                $res[$quoteRec->productId] = (object)array('productId'     => $quoteRec->productId,
                                                           'classId'       => $this->getClassId(),
                                                           'sourceClassId' => $classId,
                                                           'sourceId'      => $quoteRec->quotationId,
                                                           'valior'        => null,
                                                           'quantity'      => $quoteRec->quantity,
                                                           'price'         => round($price, 5));
            };
        }

        return $res;
    }
}