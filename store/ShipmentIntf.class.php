<?php

/**
 * Клас 'store_ShipmentIntf' - Интерфейс за извличане на данни за експедиция
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за извличане на данни за експедиция
 */
class store_ShipmentIntf
{
    /**
     * Данни за експедиционно нареждане
     * 
     * @param int|stdClass $id ключ (int) или запис (stdClass) на модел 
     * @return stdClass Обект със следните полета:
     *
     *   o contragentClassId  - class, клас на мениджъра на контрагента
     *   o contragentId       - key(mvc=contragentClassId): на кого се доставя
     *   o termId             - key(mvc=salecond_DeliveryTerms): условие на доставка 
     *   o locationId         - key(mvc=crm_Locations): обект, където да бъде доставено
     *   o deliveryTime       - datetime: до кога трябва да бъде доставено 
     *   o storeId            - key(mvc=store_Stores): наш склад, от където се експедира стоката 
     *     
     */
    public function getShipmentInfo($id)
    {
        return $this->class->getShipmentInfo($id);
    }


    /**
     * Списък от продукти-детайли на експедиционно нареждане
     * 
     * @param int|stdClass $id
     * @return array масив от данни на продукти за експедиция. Всеки елемент на масива е обект
     *               със следните полета:
     *
     *   o policyId       - class(interface=price_PolicyIntf): ценова политика, по която е 
     *                           определена цената (price) и отстъпката (discount)
     *   o productId      - key(mvc=ProductManager): Продукт
     *   o uomId          - key(mvc=cat_UoM): Мярка
     *   o packagingId    - key(mvc=cat_Packagings): Опаковка (ако има)
     *   o quantity       - float: количество в основна мярка
     *   o quantityDelivered       - float: експедирано количество в основна мярка
     *   o quantityInPack - float: количество (в осн. мярка) в опаковката, зададена от 'packagingId'; 
     *                             Ако 'packagingId' е празно, се приема ст-ст единица.
     *   o price          - float: цена за единица продукт в основна мярка
     *   o discount       - percent: Отстъпка; приема се, че тази отстъпка вече е приложена в/у 
     *                               цената
     * 
     */
    public function getShipmentProducts($id)
    {
        return $this->class->getShipmentProducts($id);
    }
}