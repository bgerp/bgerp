<?php


/**
 * Тестова таблица за миграции на данни от доставките
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
class eurozone_PurchasesDataTest extends purchase_PurchasesData
{

    /**
     * Заглавие
     */
    public $title = 'ТЕСТОВИ доставки';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->dbTableName = 'eurozone_purchases_data_test';
        parent::description();
    }
}