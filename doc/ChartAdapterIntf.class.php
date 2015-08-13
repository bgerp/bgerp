<?php


/**
 *
 *
 * @category  bgerp
 * @package   docschartadapter
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ChartAdapterIntf
{

    /**
     * Подготвя диаграмата
     * @param array $data - данните, които ще се използват за изчертаване
     * @param string $chartType - тип на диаграмата: pie, bar, line
     */
    public function  prepare($data, $chartType)
    {
        $this->class->prepare($data, $chartType);
    }
	
}