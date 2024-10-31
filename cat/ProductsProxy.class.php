<?php


/**
 * Прокси клас 'cat_ProductsProxy' - За артикулите в каталога
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_ProductsProxy extends core_Manager
{


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->forceProxy('cat_Products');
    }
}