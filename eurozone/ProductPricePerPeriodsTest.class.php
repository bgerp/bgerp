<?php


/**
 * Тестова таблица за миграции на данни от делтите
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
class eurozone_ProductPricePerPeriodsTest extends acc_ProductPricePerPeriods
{

    /**
     * Заглавие
     */
    public $title = 'ТЕСТОВИ складови себестойности';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->dbTableName = 'eurozone_product_price_per_periods';
        parent::description();
    }
}
