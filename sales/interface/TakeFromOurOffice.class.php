<?php


/**
 * Драйвер за транспорт "Безплатна доставка до офис"
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_interface_TakeFromOurOffice extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_TransportCalc';


    /**
     * Заглавие
     */
    public $title = 'Безплатна доставка до офис';


    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;


    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int   $deliveryTermId           - условие на доставка
     * @param float $volumicWeight            - единичното обемно тегло
     * @param int   $totalVolumicWeight       - общото обемно тегло
     * @param array $params                   - други параметри
     * @param null|string $toBaseCurrencyDate - към основната валута за коя дата
     * @return array
     *               ['fee']          - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     *               ['deliveryTime'] - срока на доставка в секунди ако го има
     *               ['explain']      - текстово обяснение на изчислението
     */
    public function getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $params, $toBaseCurrencyDate = null)
    {
        return array('fee' => 0);
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

        // Локациите на нашата фирма
        $ourLocations = crm_Locations::getContragentOptions('crm_Companies', crm_Setup::BGERP_OWN_COMPANY_ID);
        if($Document instanceof eshop_Carts){
            $settings = cms_Domains::getSettings();
            if(!empty($settings->takingFromOffice)){
                $selected = keylist::toArray($settings->takingFromOffice);
                $ourLocations = array_intersect_key($ourLocations, $selected);
            }

            foreach ($ourLocations as $locationId => $locationName){
                $locationRec = crm_Locations::fetch($locationId);
                $ourLocations[$locationId] = !empty($locationRec->eshopName) ? $locationRec->eshopName : $locationRec->title;
            }
        }

        $form->FLD('ourLocationId', "int", 'silent,mandatory,caption=Доставка->Офис');
        $form->setOptions('ourLocationId', array('' => '') + $ourLocations);

        if ($Document instanceof eshop_Carts) {
            unset($form->rec->deliveryCountry, $form->rec->deliveryPCode, $form->rec->deliveryPlace, $form->rec->deliveryAddress);
            $form->setField('deliveryCountry', 'input=hidden');
            $form->setField('deliveryPCode', 'input=hidden');
            $form->setField('deliveryPlace', 'input=hidden');
            $form->setField('deliveryAddress', 'input=hidden');

            if (isset($locationId)) {
                if (!countR($ourLocations)) {
                    $infoText = tr('За съжаление, няма офиси на фирмата, от които да вземете пратката|*!');
                    $form->info = new core_ET("<div id='editStatus'><div class='warningMsg'>{$infoText}</div></div>");
                }
            } else {
                $infoText = tr('Моля изберете офис за получаване|*!');
                $form->info = new core_ET("<div id='editStatus'><div class='warningMsg'>{$infoText}</div></div>");
            }
        } elseif ($Document instanceof sales_Sales) {
            $form->setField('deliveryAdress', 'input=hidden');
        } elseif ($Document instanceof sales_Quotations) {
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
        return array('ourLocationId');
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

        $officeName = $deliveryFrom = null;
        if($deliveryData['ourLocationId']){
            $locationRec = crm_Locations::fetch($deliveryData['ourLocationId']);
            $officeName = !empty($locationRec->eshopName) ? $locationRec->eshopName : $locationRec->title;

            if(!empty($locationRec->regularDelivery)){

                // Изчисляване на следващото регулярно посещение
                $days = type_Set::toArray($locationRec->regularDelivery);
                $now = new DateTime();
                $dates = array_map(function ($day) use ($now) {
                    $d = clone $now;
                    $d->modify('next ' . trim($day));
                    return $d;
                }, $days);

                usort($dates, function ($a, $b) {
                    return $a <=> $b;
                });

                $tomorrow = (clone $now)->modify('+1 day')->format('Y-m-d');
                $currentHour = (int)$now->format('H');
                $nextDate = $dates[0];

                // ако е утре и сме след 16:00 → взимаме следващата
                if ($nextDate->format('Y-m-d') === $tomorrow && $currentHour >= 16) {
                    $nextDate = $dates[1] ?? $nextDate->modify('+7 days');
                }

                $nextVisit = $nextDate->format('Y-m-d');
                $deliveryFrom = dt::mysql2verbal($nextVisit, 'd M');
            }
        } else {
            if(!Mode::is('text', 'plain')){
                $officeName = ht::createHint('', 'Офисът не е уточнен', 'error');
            }
        }

        $res['ourLocationId'] = (object)array('caption' => tr('Офис'), 'value' => $officeName);
        if($deliveryFrom){
            $res['deliveryFrom'] = (object)array('caption' => tr('Получаване'), 'value' => $deliveryFrom);
        }

        return $res;
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
        $block = new core_ET(tr("|*<div>|Безплатна доставка при вземане от офис|*</div>"));
        $tpl->append($block, 'CART_FOOTER');
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
        return true;
    }


    /**
     * При ъпдейт на количката в е-магазина, какво да се  изпълнява
     *
     * @param stdClass $cartRec
     *
     * @return void
     */
    public function onUpdateCartMaster(&$cartRec)
    {
        if($cartRec->deliveryData['ourLocationId']){
            $cartRec->freeDelivery = 'yes';
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
        return true;
    }


    /**
     * Колко е най-голямото време за доставка
     *
     * @param int $deliveryTermId - ид на условие на доставка
     * @param array $params       - параметри за доставка
     * @return int                - най-голямото време за доставка в секунди
     */
    public function getMaxDeliveryTime($deliveryTermId, $params)
    {
        return null;
    }


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
        if($volume * 22 < $weight) {
            $m = 1000;
        }

        return max($weight, $volume * $m);
    }
}
