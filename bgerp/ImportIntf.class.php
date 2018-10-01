<?php


/**
 * Интерфейс за импортиране на csv данни. Класовете които го имплементират
 * трябва след сетъпа да са дефинирани в core_Classes
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за импортиране на csv данни
 */
class bgerp_ImportIntf
{
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param array $rows   - масив с обработени csv данни, получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     *                      полетата от модела array[{поле_от_модела}] = {колона_от_csv}
     *
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields)
    {
        return $this->class->import($rows, $fields);
    }
    
    
    /**
     * Метод връщащ масив от полета с техните заглавия от мениджъра,
     * които ще приемат стойностти от csv-то
     *
     * @return array $fields - масив от вида [{име_на_полето}] = {заглавие_на_полето}
     */
    public function getFields()
    {
        return $this->class->getFields();
    }
    
    
    /**
     * Дали драйвъра може да се прикрепи към даден мениджър
     * Мениджърите към които може да се прикачва се дефинират в $applyOnlyTo
     *
     * @param core_Mvc - мениджър за който се проверява
     *
     * @return bool TRUE/FALSE - може ли да се прикепи или не
     */
    public function isApplicable($className)
    {
        return $this->class->isApplicable($className);
    }
}
