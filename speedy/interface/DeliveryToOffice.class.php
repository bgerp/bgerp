<?php


/**
 * Драйвер за доставка до Офис на speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_interface_DeliveryToOffice extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_TransportCalc';
    
    
    /**
     * Заглавие
     */
    public $title = 'До офис Спиди';
    
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param float $weight         - Тегло на товара
     * @param float $volume         - Обем  на товара
     * @param int   $deliveryTermId - Условие на доставка
     * @param array $params         - допълнителни параметри
     *
     * @return float - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume, $deliveryTermId, $params)
    {
        $m = 1;
        if($volume * 33 < $weight) {
            $m = 1000;
        }

        return max($weight, $volume * $m);
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
        //@todo Да се направи да работи с API-то
        $officeId = $params['officeId'];
        $params['deliveryCountry'] = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        
        // Временно работи с навлата
        $FeeZones = cls::getInterface('cond_TransportCalc', tcost_FeeZones::getClassId());
        $res = $FeeZones->getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params);
        
        return $res;
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
        $form->FLD('officeId', "key(mvc=speedy_Offices,select=name)", 'silent,mandatory,caption=Доставка->Офис');
        
        $options = array('' => '') + speedy_Offices::getAvailable();
        $form->setOptions('officeId', $options);
        unset($form->rec->deliveryCountry, $form->rec->deliveryPCode, $form->rec->deliveryPlace, $form->rec->deliveryAddress);
       
        $form->setField('deliveryCountry', 'input=hidden');
        $form->setField('deliveryPCode', 'input=hidden');
        $form->setField('deliveryPlace', 'input=hidden');
        $form->setField('deliveryAddress', 'input=hidden');
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @return array
     */
    public function getFields()
    {
        return array('officeId');
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
        $officeId = $rec->deliveryData['officeId'];
        $officeName = speedy_Offices::getVerbal($officeId, 'extName');
        
        $tpl = new core_ET(tr("|*<tr><td class='aright'>|Офис|*:</td><td> <span class='richtext-holder'> [#officeId#]</span></td></tr>"));
        $tpl->append($officeName, 'officeId');
        
        return $tpl;
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
        //$bgName = drdata_Countries::getCountryName('BG', core_Lg::getCurrent());
        
        //$block = new core_ET(tr("|*<div>|Безплатна доставка на територията на|* <b>{$bgName}</b>|*</div>"));
        //$tpl->append($block, 'CART_FOOTER');
        
        return false;
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
        
    }
}
