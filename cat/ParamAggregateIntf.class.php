<?php


/**
 * Интерфейс за агрегатор на продуктовите параметри на база рецептата му
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_ParamAggregateIntf
{

    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;


    /**
     * Връща масив със стойностите на агрегираните параметри
     *
     * @param int $productId      - ид на артикул
     * @param array $materialsArr - масив с материалите за производство на артикула
     *              ['productId']      - ид на материала
     *              ['packagingId']    - опаковка/мярка
     *              ['quantity']       - количество в основна мярка
     *              ['quantityInPack'] - к-во в опаковка/мярка
     * @return array
     *          <ид_параметър> = <стойност_на_параметър>
     */
    public function getAggregatedParams($productId, $materialsArr)
    {
        return $this->class->getAggregatedParams($productId, $materialsArr);
    }
}