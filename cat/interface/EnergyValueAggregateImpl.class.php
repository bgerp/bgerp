<?php


/**
 * Агрегатор на енергийната стойност на материалите от рецептата
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
class cat_interface_EnergyValueAggregateImpl extends core_Manager
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
        $totalEnergy = 0;
        $energyParamId = cat_Params::fetchIdBySysId('energyKcal');
        foreach ($materialsArr as $matRec){
            $energyVal = cat_Products::getParams($matRec->productId, $energyParamId);
            if(isset($energyVal)){
                $totalEnergy += $energyVal;
            }
        }

        return !empty($totalEnergy) ? array($energyParamId => $totalEnergy) : array();
    }
}