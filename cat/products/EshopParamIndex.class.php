<?php


/**
 * Индекс на параметрите на артикулите, ползвани в е-магазина
 *
 * Копие на вътрешния индекс само за артикулите от е-артикулите, за да не се заключват взаимно
 * търсенията във вътрешната и външната част. Пише се от cat_products_ParamIndex::reindex()
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_products_EshopParamIndex extends cat_products_ProtoParamIndex
{
    /**
     * Заглавие
     */
    public $title = 'Индекс на параметрите на артикулите в е-магазина';
}
