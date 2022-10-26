<?php


/**
 * Обработвач на шаблона за ЕН с цени по активна рецепта
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за ЕН с цени по активна рецепта
 */
class store_iface_ShipmentWithBomPriceTplHandler extends doc_TplScript
{

    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'store_ShipmentOrders';


    /**
     * Помощна ф-я кешираща цената по рецепта за реда на ЕН-то
     *
     * @param core_Detail $detail
     * @param stdClass $rec
     * @param date $date
     * @param boolean $doCache
     * @return mixed|object
     * @throws core_exception_Expect
     */
    private static function getCachedCostObject($detail, $rec, $date, $doCache = false)
    {
        $cachedRec = doc_TplManagerHandlerCache::get($detail, $rec->id, 'bomcost');

        if(!isset($cachedRec)){
            $cachedRec = (object)array();
            if($activeBomRec = cat_Products::getLastActiveBom($rec->productId)) {
                $bomPrice = cat_Boms::getBomPrice($activeBomRec->id, $rec->quantity, 0, 0, $date, price_ListRules::PRICE_LIST_COST);
                $cachedRec->bomId = $activeBomRec->id;
                $cachedRec->price = $bomPrice;
                $cachedRec->amount = null;

                if(isset($bomPrice)){
                    $cachedRec->amount = $bomPrice * $rec->quantity;
                }

                if(!$doCache){
                    $cachedRec->_isLive = true;
                } else {
                    doc_TplManagerHandlerCache::set($detail, $rec->id, 'bomcost', $cachedRec);
                }
            }
        }

        return $cachedRec;
    }


    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    public function modifyDetailData(core_Mvc $detail, &$data)
    {
        // Ако няма редове или не е във вътрешен режим
        if (!countR($data->rows)) return;
        if(Mode::is('printing') || (Mode::is('text', 'xhtml') && !Mode::is('docView'))) return;

        $date = isset($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();

        // За всеки запис
        foreach ($data->rows as $id => &$row) {
            $Double = core_Type::getByName('double(smartRound)');
            $rec = $data->recs[$id];

            $rec->_amountBase = currency_CurrencyRates::convertAmount($rec->price * $rec->quantity, $date, null, $baseCurrencyId);
            $row->_amountBase = $Double->toVerbal(core_Math::roundNumber($rec->_amountBase));
            if(isset($rec->discount)){
                $rec->_amountBase *= 1 - $rec->discount;
            }

            // Ако е производим
            $canManifacture = cat_Products::fetchField($rec->productId, 'canManifacture');
            if($canManifacture == 'yes'){

                // Търси се от кеша информацията
                $cachedRec = self::getCachedCostObject($detail, $rec, $date);

                if(!isset($cachedRec->bomId)){
                    $row->_amountBom = ht::createHint("<span class='quiet'>N/A</span>", 'Артикулът няма активна рецепта|*!', 'notice', false);
                } else {
                    $rec->_amountBom = $cachedRec->amount;
                    $hint = null;
                    if(isset($cachedRec->price)){
                        $hintPrice = $Double->toVerbal(core_Math::roundNumber($cachedRec->price * $rec->quantityInPack));
                        $hint = "ед. сб-ст|*: {$hintPrice}. ";
                        $row->_amountBom = $Double->toVerbal(core_Math::roundNumber($cachedRec->amount));
                    } else {
                        $row->_amountBom = "<span class='red'>???</span>";
                    }

                    if($cachedRec->_isLive){
                        $row->_amountBom = "<span style='color:blue'>{$row->_amountBom}</span>";
                        $hint .= 'Ще се запише при активиране|*!';
                    }

                    if($hint){
                        $hint = trim($hint,'. ');
                        $row->_amountBom = ht::createHint($row->_amountBom, $hint, 'notice', false);
                    }

                    // Линк към рецептата, ако има такава
                    $singleUrl = cat_Boms::getSingleUrlArray($cachedRec->bomId);
                    if(countR($singleUrl)){
                        $row->_amountBom = ht::createLinkRef($row->_amountBom, $singleUrl);
                    }
                }
            }
        }

    }


    /**
     * Преди рендиране на шаблона на детайла
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function beforeRenderListTable(core_Mvc $detail, &$tpl, &$data)
    {
        // Ако няма редове или не е във вътрешен режим
        if (!countR($data->rows)) return;
        if(Mode::is('printing') || (Mode::is('text', 'xhtml') && !Mode::is('docView'))) return;

        // Добавяне на колонката за цена по рецепта
        $date = isset($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($date);
        $data->listTableMvc->FNC('_amountBom', 'double(smartRound)');
        if( array_key_exists('amount', $data->listFields)){
            arr::placeInAssocArray($data->listFields, array('_amountBom' => "Рецепта||Bom|* <span class='quiet small'>({$baseCurrencyId})</span>"), 'amount');
        } else {
            arr::placeInAssocArray($data->listFields, array('_amountBom' => "Рецепта||Bom|* <span class='quiet small'>({$baseCurrencyId})</span>"), null, 'packQuantity');
        }

        if($data->masterData->rec->currencyId != $baseCurrencyId){
            arr::placeInAssocArray($data->listFields, array('_amountBase' => "Сума||Amount|* <span class='quiet small'>({$baseCurrencyId})</span>"), null, '_amountBom');
        }
    }


    /**
     * Метод който подава данните на мастъра за обработка на скрипта
     *
     * @param core_Mvc $mvc  - мастър на документа
     * @param stdClass $data - данни
     *
     * @return void
     */
    public function modifyMasterData(core_Mvc $mvc, &$data)
    {
        // Ако няма редове или не е във вътрешен режим
        if (!countR($data->store_ShipmentOrderDetails->recs)) return;
        if(Mode::is('printing') || (Mode::is('text', 'xhtml') && !Mode::is('docView'))) return;

        $amountBoms = arr::sumValuesArray($data->store_ShipmentOrderDetails->recs, '_amountBom');
        $amountBase = arr::sumValuesArray($data->store_ShipmentOrderDetails->recs, '_amountBase');

        $date = isset($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($date);
        $data->row->totalAmountBom = core_Type::getByName('double(decimals=2)')->toVerbal($amountBoms);
        $data->row->totalAmountBase = core_Type::getByName('double(decimals=2)')->toVerbal($amountBase);
        $data->row->bomCurrencyId = $data->row->baseCurrencyId = $baseCurrencyId;
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    public function afterActivation($mvc, &$rec)
    {
        // След активиране - кешира се цената по рецепта
        $date = isset($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
        while($dRec = $dQuery->fetch()){
            self::getCachedCostObject($Detail, $dRec, $date, true);
        }
    }
}