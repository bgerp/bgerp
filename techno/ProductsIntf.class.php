<?php

/**
 * Клас 'techno_ProductsIntf' - Интерфейс за нестандартни арткули
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_ProductsIntf
{
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас нестандартно изделие
     * @return core_Form $form - Формата на мениджъра
     */
    public function getEditForm($data)
    {
        return $this->class->getEditForm($data);
    }
    
    
	/**
     * Връща сериализиран вариант на данните, които представят
     * дадено изделие или услуга
     * 
     * @param stdClass $data - Обект с данни от модела
     * @return blob $serialized - сериализирани данни на обекта
     */
    public function serialize($data)
    {
        return $this->class->serialize($data);
    }
    
    
	/**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * 
     * @param stdClass $data - Обект с данни от модела
     * @param boolean $short - Дали да е кратко представянето 
     * @return text/html - вербално представяне на изделието
     */
    public function getVerbal($data, $short = FALSE)
    {
        return $this->class->getVerbal($data, $short);
    }
    
    
    /**
     * Връща информация за ед цена на продукта, отстъпката и таксите
     * @param stdClass $data - дата от модела
     * @param int $packagingId - ид на опаковка
     * @param double quantity - количество
     * @param datetime $datetime - дата
     * @return stdClass $priceInfo - информация за цената на продукта
     * 				[price]- начална цена
     * 				[discount]  - отстъпка
     * 				[tax]     - нач. такса
     */
    public function getPrice($data, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	return $this->class->getPrice($data, $packagingId, $quantity, $datetime);
    }
    
    
	/**
     * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs($data)
    {
    	return $this->class->getUsedDocs($data);
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $data - сериализирани данни
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($data, $packagingId = NULL)
    {
    	return $this->getProductInfo($data, $packagingId);
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $data - сериализирани данни
     * @param date $date - дата
     */
    public static function getVat($data, $date = NULL)
    {
    	return $this->getVat($data, $date);
    }
}