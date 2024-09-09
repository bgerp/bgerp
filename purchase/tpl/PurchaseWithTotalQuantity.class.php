<?php


/**
 * Обработвач на шаблона за покупка с обобщено количество
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за покупка с обобщено количество
 */
class purchase_tpl_PurchaseWithTotalQuantity extends deals_tpl_DocumentWithTotalQuantity
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'purchase_Purchases';
}
