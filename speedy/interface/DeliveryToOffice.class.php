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
        $FeeZones = cls::getInterface('cond_TransportCalc', 'tcost_FeeZones');
        $params['deliveryCountry'] = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        
        return $FeeZones->getVolumicWeight($weight, $volume, $deliveryTermId, $params);
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
        $FeeZones = cls::getInterface('cond_TransportCalc', 'tcost_FeeZones');
        $res = $FeeZones->getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params);
        
        return $res;
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
        $form->FLD('officeId', "key(mvc=speedy_Offices,select=name)", 'silent,mandatory,caption=Доставка->Офис');
        $options = array('' => '') + speedy_Offices::getAvailable();
        $form->setOptions('officeId', $options);
        
        $Document = cls::get($document);
        if($Document instanceof eshop_Carts){
            unset($form->rec->deliveryCountry, $form->rec->deliveryPCode, $form->rec->deliveryPlace, $form->rec->deliveryAddress);
            $form->setField('deliveryCountry', 'input=hidden');
            $form->setField('deliveryPCode', 'input=hidden');
            $form->setField('deliveryPlace', 'input=hidden');
            $form->setField('deliveryAddress', 'input=hidden');
            $form->setField('locationId', 'input=none');
            unset($form->rec->locationId);
        } elseif($Document instanceof sales_Sales){
            $form->setField('deliveryLocationId', 'input=hidden');
            $form->setField('deliveryAdress', 'input=hidden');
            $form->setField('deliveryCalcTransport', 'input=none');
            unset($form->rec->deliveryCalcTransport);
        } elseif($Document instanceof sales_Quotations){
            $form->setField('deliveryAdress', 'input=hidden');
            $form->setField('deliveryPlaceId', 'input=hidden');
        }
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @param mixed $document
     * @return array
     */
    public function getFields($document)
    {
        return array('officeId');
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
        $res = array();
        
        if($deliveryData['officeId']){
            $officeRec = speedy_Offices::fetch($deliveryData['officeId']);
            $officeName = speedy_Offices::getVerbal($officeRec, 'extName');
            
            $officeLocationUrlTpl = new core_ET(speedy_Setup::get('OFFICE_LOCATOR_URL'));
            $officeLocationUrlTpl->replace($officeRec->num, 'NUM');
            
            if($officeRec->state != 'closed'){
                $officeName = ht::createLinkRef($officeName, $officeLocationUrlTpl->getContent(), false, 'target=_blank');
            } else {
                $officeName = ht::createHint($officeName, 'Офисът, вече не е актуален', 'warning');
            }
        } else {
            $officeName = ht::createHint('', 'Офисът не е уточнен', 'error');
        }
        
        $res[] = (object)array('caption' => tr('Офис'), 'value' => $officeName);
        
        return $res;
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
        if(empty($deliveryData['officeId'])){
            $error = "Не е избран офис на speedy!";
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Добавя промени по изгледа на количката във външната част
     *
     * @param stdClass $termRec
     * @param stdClass $cartRec
     * @param stdClass $cartRow
     * @param core_ET $tpl
     *
     * @return void
     */
    public function addToCartView($termRec, $cartRec, $cartRow, &$tpl)
    {
        $FeeZones = cls::getInterface('cond_TransportCalc', 'tcost_FeeZones');
        $FeeZones->addToCartView($termRec, $cartRec, $cartRow, $tpl);
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
        $FeeZones = cls::getInterface('cond_TransportCalc', 'tcost_FeeZones');
        $FeeZones->onUpdateCartMaster($cartRec);
    }
    
    
    /**
     * Може ли да се избира условието в онлайн магазина
     *
     * @param int|stdClass $cartRec
     * @param int|null $cu
     *
     * @return boolean
     */
    public function canSelectInEshop(&$rec, $cu = null)
    {
        return true;
    }
}
