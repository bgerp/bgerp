<?php

/**
 * Клас 'tehno_ProductsIntf' - Интерфейс за нестандартни продукти
 *
 *
 * @category  bgerp
 * @package   tehno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tehno_ProductsIntf
{
    /**
     * Връща форма, с която могат да се въвеждат параметри на
     * определен клас не-стандартно изделие
     * 
     * @param stdClass $data - Обект с данни от модела 
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
}