<?php



/**
 * Интерфейс за документи върху които артикули може да им се коригират стойностите
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи върху които артикули може да им се коригират стойностите
 */
class acc_AllowArticlesCostCorrectionDocsIntf
{
    
	
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Списък с артикули върху, на които може да им се коригират стойностите
     * 
     * @param mixed $id               - ид или запис
     * @return array $products        - масив с информация за артикули
     * 			    o productId       - ид на артикул
     * 				o name            - име на артикула
     *  			o quantity        - к-во
     *   			o amount          - сума на артикула
     *   			o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *    			o transportWeight - транспортно тегло на артикула
     *     			o transportVolume - транспортен обем на артикула
     */
    function getCorrectableProducts($id)
    {
    	$this->class->getCorrectableProducts($id);
    }
}