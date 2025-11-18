<?php


/**
 * Тестова таблица за миграции на артикулните сб-сти
 *
 *
 * @category  bgerp
 * @package   eurozone
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eurozone_ProductCostsTest extends price_ProductCosts
{

    /**
     * Заглавие
     */
    public $title = 'ТЕСТОВИ цени на артикулите';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->dbTableName = 'eurozone_product_costs_test';
        parent::description();
    }
}