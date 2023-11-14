<?php


/**
 * Агрегатор на алергените на материалите от рецептата
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
class cat_interface_AllergensParamAggregateImpl extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cat_ParamAggregateIntf';


    /**
     * Заглавие
     */
    public $title = 'Агрегатор на алергените на артикулите от рецепта';


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
        $allergensCombined = array();
        $allergenParamId = cat_Params::fetchIdBySysId('allergens');
        foreach ($materialsArr as $matRec){
            $allergenKeylist = keylist::toArray(cat_Products::getParams($matRec->productId, $allergenParamId));
            $allergensCombined = array_merge($allergensCombined, $allergenKeylist);
        }
        $allergensCombined = array_combine($allergensCombined, $allergensCombined);

        return countR($allergensCombined) ? array($allergenParamId => keylist::fromArray($allergensCombined)) : array();
    }
}