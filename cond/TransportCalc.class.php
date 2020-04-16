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
     * Стойността, която ще се върне ако в зоната няма тегла
     */
    const EMPTY_WEIGHT_ZONE_FEE = -32;
    
    
    /**
     * Стойността, ако е възникнала друга грешка
     */
    const OTHER_FEE_ERROR = -64;
    
    
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
     * @param mixed $document
     * @param string|NULL   $userId
     *
     * @return void
     */
    public function addFields(core_FieldSet &$form, $document, $userId = null)
    {
        return $this->class->addFields($form, $document, $userId);
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @param mixed $document
     * @return array
     */
    public function getFields($document)
    {
        return $this->class->getFields($document);
    }
    
    
    /**
     * Вербализира допълнителните данни за доставка
     *
     * @param stdClass $termRec        - условие на доставка
     * @param array|null $deliveryData - масив с допълнителни условия за доставка
     * @param mixed $document          - документ
     *
     * @return array $res              - данни готови за показване
     */
    public function getVerbalDeliveryData($termRec, $deliveryData, $document)
    {
        return $this->class->getVerbalDeliveryData($termRec, $deliveryData, $document);
    }
    
    
    /**
     * Проверява данните на доставка преди активация
     *
     * @param mixed $id             - ид на търговско условие
     * @param stdClass $documentRec - запис на документа
     * @param array $deliveryData   - данни за доставка
     * @param mixed $document       - документ
     * @param string|null $error    - грешката ако има такава
     * @return boolean
     */
    public function checkDeliveryDataOnActivation($id, $documentRec, $deliveryData, $document, &$error = null)
    {
        return $this->class->checkDeliveryDataOnActivation($id, $documentRec, $deliveryData, $document, $error);
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
