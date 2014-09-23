<?php

/**
 * Клас 'techno_ProductsIntf' - Интерфейс за нестандартни артикули
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за нестандартни артикули
 */
class techno_ProductsIntf
{
    
	
	/**
	 * Класа имплементиращ интерфейса
	 */
	public $class;
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - ид на продукт
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public function getProductInfo($id, $packagingId = NULL)
    {
    	return $this->class->getProductInfo($id, $packagingId);
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на продукт
     * @param date $date - дата
     */
    public function getVat($id, $date = NULL)
    {
    	return $this->class->getVat($id, $date);
    }
    
    
	/**
     * Подготвя данните на продукта за показване
     * @param int $id - ид на продукт
     * @return stdClass - обработените данни
     */
    public function prepareData($id)
    {
        return $this->class->prepareData($id);
    }
    
    
	/**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param stdClass $data - данните на артикула
     * @return text/html - вербално представяне на изделието
     */
    public function renderShortView($data)
    {
        return $this->class->renderShortView($data);
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	return $this->class->getParam($id, $sysId);
    }
    
    
    /**
     * Връща изгледа на драйвера за показване в Задание за производство
     * @param int $id - ид на продукт
     * @return core_ET - изгледа
     */
    public function renderJobView($id, $data)
    {
    	return $this->class->renderShortView($id, $data);
    }
    
    
    /**
     * Връща масив с допълнителни параметри, специфични за технолога. Те ще се
     * използват в заданията
     * @return array[]   - масив от обекти от вида
     * 		rec->name - име на параметъра
     * 		rec->type - тип в системата
     */
    public function getAdditionalParams()
    {
    	return $this->class->getAdditionalParams();
    }
    
    
	/**
     * Връща масив с допълнителни параметри, специфични за технолога. Те ще се
     * използват в запитванията
     * @return array[]   - масив от обекти от вида
     * 		rec->caption - име на параметъра
     * 		rec->type - тип в системата
     */
    public function getInquiryParams()
    {
    	return $this->class->getInquiryParams();
    }
    
    
    /**
     * Рендира допълнителните параметри
     * @param array[]  - масив от обекти от вида
     * 		rec->name  - име на параметъра
     * 		rec->type  - тип в системата
     * 		rec->value - стойност
     * $paramInfo[$name]->type
     */
	public function renderAdditionalParams($id, $data)
    {
    	return $this->class->renderAdditionalParams($id, $data);
    }
    
    
	/**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
	public function getWeight($productId, $packagingId = NULL)
    {
    	return $this->class->getWeight($productId, $packagingId);
    }
    
    
    /**
     * Връща обема на еденица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
	public function getVolume($productId, $packagingId = NULL)
    {
    	return $this->class->getVolume($productId, $packagingId);
    }
    
    
     /**
      * Добавя към формата на запитването, допълнителни полета
      */
     public function fillInquiryForm($form)
     {
     	return $this->class->fillInquiryForm($form);
     }
     
     
     /**
      * Връща основната мярка, специфична за технолога
      */
     public function getDriverUom($params)
     {
     	return $this->class->getDriverUom($params);
     }
     
     
     /**
      * Връща прикачените файлове
      */
     public function getAttachedFiles($rec)
     {
     	return $this->class->getAttachedFiles($rec);
     }
     
     
     /**
      * Връща заглавието на драйвъра
      */
     public function getProductTitle($data)
     {
     	return $this->class->getProductTitle($data);
     }
}