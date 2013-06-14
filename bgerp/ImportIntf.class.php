<?php



/**
 * Интерфейс за импортиране на csv данни.
 * Класовете които го имплементират не трябва да имат description, но трябва
 * след сетъпа да са дефинирани в core_Classes
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_ImportIntf
{
    
	/**
	 * Инпортиране на csv-файл в cat_Products
     * @param array $rows - масив с обработени csv данни, получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     * 		полетата от модела array[{поле_oт_модела}] = {колона_от_csv}
     * @return string $html - съобщение с резултата
	 */
    function import($rows, $fields)
    {
        return $this->class->import($rows, $fields);
    }
    
    
    /**
     * В кой мениджър ще се импортират данните
     * @return core_Class $class - инстанция на мениджъра-дестинация
     */
	function getDestinationManager()
    {
        return $this->class->getDestinationManager();
    }
    
    
    /**
     * Метод връщащ масив от полета с техните заглавия от мениджъра, 
     * които ще приемат стойностти от csv-то
     * @return array $fields - масив от вида [{име_на_полето}] = {заглавие_на_полето}
     */
    function getFields()
    {
    	return $this->class->getFields();
    }
}