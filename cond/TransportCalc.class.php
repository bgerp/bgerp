<?php


/**
 * Клас 'cond_TransportCalc' - Интерфейс за класове, които определят цената за транспорт
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_TransportCalc
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'tcost_CostCalcIntf';
    
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Стойността, която ще се върне ако е не може да се намери зона
     */
    const ZONE_FIND_ERROR = -2;
    
    
    /**
     * Стойността, която ще се върне ако има грешка при деление
     */
    const DELIMITER_ERROR = -4;
    
    
    /**
     * Стойността, която ще се върне ако артикула няма тегло
     */
    const EMPTY_WEIGHT_ERROR = -8;
    
    
    /**
     * Стойността, която ще се върне ако не е намерено общо обемно тегло
     */
    const NOT_FOUND_TOTAL_VOLUMIC_WEIGHT = -16;
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param float $weight        - Тегло на товара
     * @param float $volume        - Обем  на товара
     * @param int $deliveryTermId  - Условие на доставка
     * @param array $params        - допълнителни параметри
     *
     * @return float - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume, $deliveryTermId, $params)
    {
        return $this->class->getVolumicWeight($weight, $volume, $deliveryTermId, $params);
    }
    
    
    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int   $deliveryTermId     - условие на доставка
     * @param float $volumicWeight      - единичното обемно тегло
     * @param int   $totalVolumicWeight - Общото обемно тегло
     * @param array $params             - други параметри
     *
     * @return array
     *               ['fee']          - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     *               ['deliveryTime'] - срока на доставка в секунди ако го има
     *               ['explain']      - текстово обяснение на изчислението
     */
    public function getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params)
    {
        return $this->class->getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params);
    }
    
    
    /**
     * Добавя полета за доставка към форма
     *
     * @param core_FieldSet $form
     * @param string|NULL   $userId
     *
     * @return void
     */
    public function addFields(core_FieldSet &$form, $userId = null)
    {
        return $this->class->addFields($form, $userId);
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @return array
     */
    public function getFields()
    {
        return $this->class->getFields();
    }
    
    
    /**
     * Проверява форма
     *
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkForm(core_FieldSet &$form)
    {
        return $this->class->checkForm($form);
    }
    
    
    /**
     * Рендира информацията
     *
     * @param stdClass rec
     *
     * @return core_ET $tpl
     */
    public function renderDeliveryInfo($rec)
    {
        return $this->class->renderDeliveryInfo($rec);
    }
    
    
    /**
     * Добавя промени по изгледа на количката във външната част
     *
     * @param stdClass $termRec
     * @param stdClass $cartRec
     * @param stdClass $cartRow
     * @param core_ET $tpl
     * 
     * @return boolean
     */
    public function addToCartView($termRec, $cartRec, $cartRow, &$tpl)
    {
        return $this->class->addToCartView($termRec, $cartRec, $cartRow, $tpl);
    }
    
    
    /**
     * При упдейт на количката в е-магазина, какво да се  изпълнява
     *
     * @param stdClass $cartRec
     *
     * @return void
     */
    public function onUpdateCartMaster(&$cartRec)
    {
        return $this->class->onUpdateCartMaster($cartRec);
    }
}
