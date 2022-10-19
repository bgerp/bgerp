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
        $data->listTableMvc->FNC('_amountBom', 'double(smartRound)');
        arr::placeInAssocArray($data->listFields, '_amountBom=Рецепта', 'amount');
        $date = isset($data->masterData->rec->valior) ? $data->masterData->rec->valior : dt::today();

        // За всеки запис
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];

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
                        $hintPrice = $data->listTableMvc->getFieldType('_amountBom')->toVerbal(core_Math::roundNumber($cachedRec->price * $rec->quantityInPack));
                        $hint = "ед. сб-ст|*: {$hintPrice}. ";
                        $row->_amountBom = $data->listTableMvc->getFieldType('_amountBom')->toVerbal(core_Math::roundNumber($cachedRec->amount));
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