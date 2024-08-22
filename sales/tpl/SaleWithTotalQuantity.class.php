<?php


/**
 * Обработвач на шаблона за продажба с обобщено количество
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за продажба с обобщено количество
 */
class sales_tpl_SaleWithTotalQuantity extends deals_tpl_DocumentWithTotalQuantity
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'sales_Sales';
}
