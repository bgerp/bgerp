<?php


/**
 * Обработвач на шаблона за фактура с обобщено количество
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за фактура с обобщено количество
 */
class sales_tpl_InvoiceWithTotalQuantity extends deals_tpl_DocumentWithTotalQuantity
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'sales_Invoices';
}
