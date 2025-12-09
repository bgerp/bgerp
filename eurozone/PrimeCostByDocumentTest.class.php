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
class eurozone_PrimeCostByDocumentTest extends sales_PrimeCostByDocument
{

    /**
     * Заглавие
     */
    public $title = 'ТЕСТОВИ делти на себестойностите';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->dbTableName = 'eurozone_prime_cost_by_document_test';
        parent::description();
    }
}