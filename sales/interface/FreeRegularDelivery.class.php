<?php


/**
 * Драйвер за Безплатна доставка с наш транспорт
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_interface_FreeRegularDelivery extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_TransportCalc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Безплатна доставка с наш транспорт';
    
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Роли, които може да го избират в ешопа;
     */
    public $rolesForEshopSelect = 'partner';
    
    
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
        $routeRec = null;
        if($params['routeId']){
            $routeRec = sales_Routes::fetch($params['routeId']);
        }
        
        // Цената е според навлата
        $res = array('fee' => cond_TransportCalc::OTHER_FEE_ERROR, 'explain' => ' NO ROUTE FOUND');
        if(is_object($routeRec)){
            $FeeZones = cls::getInterface('cond_TransportCalc', 'tcost_FeeZones');
            $res = $FeeZones->getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params);
            $diff = strtotime($routeRec->nextVisit) - strtotime(dt::today());
            $res['deliveryTime'] = $diff;
        } 
        
        return $res;
    }
    
    
    /**
     * Кои марршрути са допустими за избор
     * 
     * @param int $locationId  - към коя локация
     * @param int $inDays - в следващите колко дни? null за без ограничение
     * @return string[] $routeOptions - опции от маршрути
     */
    private static function getRouteOptions($locationId, $inDays = null)
    {
        $today = dt::today();
        
        $routeOptions = array();
        $rQuery = sales_Routes::getQuery();
        $rQuery->where("#locationId = '{$locationId}' AND #nextVisit > '{$today}' AND #state != 'rejected'");
        if(isset($inDays)){
            $inDays = dt::addDays($inDays, $today, false);
            $rQuery->where("#nextVisit <= '{$inDays}'");
        }
        
        $rQuery->show('id,nextVisit');
        $rQuery->orderBy('id', "ASC");
        
        while($rRec = $rQuery->fetch()){
            $routeOptions[$rRec->id] = sales_Routes::getSmartTitle($rRec);
        }
        
        return $routeOptions;
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
        $Document = cls::get($document);
        $inDays = ($Document instanceof eshop_Carts) ? 7 : null;
        $locationId = ($Document instanceof eshop_Carts) ? $form->rec->locationId : $form->rec->deliveryLocationId;
      
        if(isset($locationId)){
            $routeOptions = self::getRouteOptions($locationId, $inDays);
            $countRoutes = countR($routeOptions);
            $form->FLD('routeId', "key(mvc=sales_Routes,select=nextVisit)", 'silent,mandatory,caption=Доставка->Доставка на');
            
            if($countRoutes > 1){
                $routeOptions = array('' => '') + $routeOptions;
            } elseif($countRoutes == 1){
                $form->setDefault('routeId', key($routeOptions));
            }
            
            $form->setOptions('routeId', $routeOptions);
        }
        
        if($Document instanceof eshop_Carts){
            $form->setField('deliveryCountry', 'input=hidden');
            $form->setField('deliveryPCode', 'input=hidden');
            $form->setField('deliveryPlace', 'input=hidden');
            $form->setField('deliveryAddress', 'input=hidden');
            
            if(!$countRoutes){
                $infoText = tr('За съжаление, няма планирани маршрути до вашата локация. Ако имате въпроси, моля да се свържете с нас|*!');
                $form->info = new core_ET("<div id='editStatus'><div class='warningMsg'>{$infoText}</div></div>");
            }
        } elseif($Document instanceof sales_Sales){
            $form->setField('deliveryAdress', 'input=hidden');
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
        return array('routeId');
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
        
        if($deliveryData['routeId']){
            $routeName = sales_Routes::getSmartTitle($deliveryData['routeId']);
        } else {
            $routeName = ht::createHint('', 'Маршрутът още не е уточнен', 'error');
        }
        
        $res[] = (object)array('caption' => tr('Доставка на'), 'value' => $routeName);
        
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
        if(empty($deliveryData['routeId'])){
            $error = "Не е избран маршрут|*!";
            
            return false;
        } else {
            $routeState = sales_Routes::fetchField($deliveryData['routeId'], 'state');
            if($routeState != 'active'){
                $error = "Избраният маршрут, вече не е активен|*!";
                
                return false;
            }
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
        $settings = cms_Domains::getSettings();
        
        $tpl->append(tr('Доставката се извършва с наш регулярен транспорт') . "<br>", 'CART_FOOTER');
        
        if($cartRec->haveOnlyServices != 'yes'){
            if(!empty($settings->freeDeliveryByBus)){
                $cartRow->freeDeliveryCurrencyId = $settings->currencyId;
                $deliveryAmount = $settings->freeDeliveryByBus;
                
                if($cartRec->freeDelivery != 'yes'){
                    $string1 = tr('Добавете артикули на обща стойност');
                    $string2 = tr("|за|* <b style='color:green;text-transform:uppercase'>" . tr('безплатна') . "</b> |доставка|*.");
                    $block = new core_ET(tr("|*<!--ET_BEGIN freeDelivery--><div>{$string1} <b style='font-size:1.1em'>[#freeDelivery#]</b> <span class='cCode'>[#freeDeliveryCurrencyId#]</span>, {$string2}</div><!--ET_END freeDelivery-->"));
                    
                    $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
                    $deliveryWithVat  = $cartRec->deliveryNoVat * (1 + cat_Products::getVat($transportId));
                    $delivery = currency_CurrencyRates::convertAmount($cartRec->total - $deliveryWithVat, null, null, $settings->currencyId);
                    
                    $deliveryAmount = round($deliveryAmount - ($delivery), 2);
                } else {
                    $string = tr('Печелите безплатна доставка, защото поръчката ви надвишава');
                    $block = new core_ET(tr("|*<!--ET_BEGIN freeDelivery--><div>{$string} <b style='font-size:1.1em'>[#freeDelivery#]</b> <span class='cCode'>[#freeDeliveryCurrencyId#]</span>.</div><!--ET_END freeDelivery-->"));
                }
                
                $cartRow->freeDelivery = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryAmount);
                $block->append($cartRow->freeDelivery, 'freeDelivery');
                $block->append($cartRow->freeDeliveryCurrencyId, 'freeDeliveryCurrencyId');
            } else {
                $block = new core_ET(tr("|*<div>|Доставката ви е безплатна|*</div>"));
            }
            
            $tpl->append($block, 'CART_FOOTER');
        }
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
        if($cartRec->deliveryData['routeId']){
            
            // Ако цената е над минималната доставката е безплатна
            $settings = cms_Domains::getSettings();
            $freeDelivery = currency_CurrencyRates::convertAmount($settings->freeDeliveryByBus, null, $settings->currencyId);
            
            if(empty($settings->freeDeliveryByBus) || (!empty($settings->freeDeliveryByBus) && round($cartRec->total, 2) >= round($freeDelivery, 2))){
                $cartRec->freeDelivery = 'yes';
            }
        }
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
        if(isset($cu)){
            if(core_Users::isContractor($cu)){
                
                return true;
            }
        }
        
        return false;
    }
}
