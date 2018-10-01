<?php


/**
 * Драйвер за безплатна доставка до България
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_interface_FreeShipping extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_TransportCalc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Безплатна доставка до България';
    
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param float $weight - Тегло на товара
     * @param float $volume - Обем  на товара
     * @param float|null $coefficient - коефициент за отношение, null за глобалната константа
     *
     * @return float - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume, $coefficient = null)
    {
    }
    
    
    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int   $deliveryTermId - условие на доставка
     * @param float $singleWeight   - тегло
     * @param float $singleVolume   - обем
     * @param int   $totalWeight    - Общо тегло на товара
     * @param int   $totalVolume    - Общ обем на товара
     * @param array $params         - Други параметри
     *
     * @return array
     *               ['fee']          - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     *               ['deliveryTime'] - срока на доставка в секунди ако го има
     *               ['explain']      - текстово обяснение на изчислението
     */
    public function getTransportFee($deliveryTermId, $singleWeight, $singleVolume, $totalWeight, $totalVolume, $params)
    {
        return array('fee' => 0);
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
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        
        $form->rec->deliveryCountry = $bgId;
        $form->setReadOnly('deliveryCountry');
        $form->setField('deliveryCountry', 'mandatory');
        $form->setField('deliveryPCode', 'mandatory');
        $form->setField('deliveryPlace', 'mandatory');
        $form->setField('deliveryAddress', 'mandatory');
        
        $form->setDefault('invoiceCountry', $bgId);
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @return array
     */
    public function getFields()
    {
        return array();
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
        return new core_ET('');
    }
}
