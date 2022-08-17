<?php


/**
 * Тип за параметър 'Формула'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Формула
 */
class cond_type_Formula extends cond_type_Text
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('formula', 'text(rows=2, maxOptionsShowCount=20)', 'mandatory,caption=Конкретизиране->Формула,after=order');

        // Налични за достъп са всички параметри
        $pQuery = cat_Params::getQuery();
        $pQuery->where("#state != 'rejected'");
        $pQuery->show('id');
        $paramIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'id');

        // Задаване на параметрите като предложения във формата
        $formulaMap = cat_Params::getFormulaParamMap($paramIds);
        $suggestions = cat_Params::formulaMapToSuggestions($formulaMap);
        $fieldset->setSuggestions('formula', $suggestions);
    }


    /**
     * Връща наличните параметри според домейна
     *
     * @param mixed $domainClass
     * @param int $domainId
     * @return array $params
     */
    private static function getParamsFromDomain($domainClass, $domainId)
    {
        $params = array();
        if(isset($domainClass)){
            $Domain = cls::get($domainClass);
            if($Domain instanceof cat_Products){
                $params = $Domain->getParams($domainId);
            } elseif($Domain instanceof planning_Tasks){

                // Ако е ПО, прави се обединение между нейните и на артикула от заданието параметрите
                $tRec = $Domain->fetch($domainId, 'originId,productId');
                $jobProductId = planning_Jobs::fetchField("#containerId = {$tRec->originId}", 'productId');
                $params = cat_Products::getParams($jobProductId);

                $tQuery = cat_products_Params::getQuery();
                $tQuery->where("#classId = {$Domain->getClassId()} AND #productId = {$domainId}");
                $tQuery->show('paramId,paramValue');
                while($tRec = $tQuery->fetch()){
                    $params[$tRec->paramId] = $tRec->paramValue;
                }
            }
        }

        return $params;
    }


    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $Type = parent::getType($rec, $domainClass, $domainId, $value);

        $params = static::getParamsFromDomain($domainClass, $domainId);
        $formulaMap = cat_Params::getFormulaParamMap($params);
        $suggestions = cat_Params::formulaMapToSuggestions($formulaMap);

        $Type = cls::get($Type, array('params' => array('rows' => 2, ' maxOptionsShowCount' => 20), 'suggestions' => $suggestions));

        return $Type;
    }


    /**
     * Връща дефолтната стойност на параметъра
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return mixed                   - дефолтната стойност (ако има)
     */
    public function getDefaultValue($rec, $domainClass = null, $domainId = null, $value = null)
    {
        return $this->driverRec->formula;
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        $idToNameArr = array();
        $params = static::getParamsFromDomain($domainClass, $domainId);
        $paramMap = cat_Params::getFormulaParamMap($params, $idToNameArr);
        $calced = cat_BomDetails::calcExpr($value, $paramMap);
        $verbal = $calced;
        if(!Mode::is('text', 'plain')){
            $exprDisplay = strtr($value, $idToNameArr);
            if ($calced === cat_BomDetails::CALC_ERROR) {
                $verbal = ht::createHint('', "Не може да се изчисли|*: {$exprDisplay}", 'warning');
            } else {
                $calced = "<span style='color:blue'>{$calced}</span>";
                $verbal = ht::createHint($calced, "Формула|*: {$exprDisplay}");
            }
        }

        return $verbal;
    }
}