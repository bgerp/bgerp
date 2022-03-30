<?php


/**
 * Помощен детайл подготвящ и обединяващ заедно детайлите на артикулите свързани
 * с ценовата информация на артикулите
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_products_PriceDetails extends core_Manager
{
    /**
     * Кои мениджъри ще се зареждат
     */
    public $loadList = 'VatGroups=cat_products_VatGroups';
    
    
    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    public $canSeeprices = 'ceo,priceDealer';
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
    public function preparePrices($data)
    {
        $data->TabCaption = 'Цени';
        $data->Tab = 'top';
        $data->Order = 5;
        
        $Param = core_Request::get($data->masterData->tabTopParam, 'varchar');
        $isPublic = ($data->masterData->rec->isPublic == 'yes') ? true : false;
        
        if (!(($isPublic === true && (empty($Param) || $Param == 'Prices')) || ($isPublic === false && $Param == 'Prices'))) {
            $data->hide = true;
            
            return;
        }
        
        $listsData = clone $data;
        $vatData = clone $data;
        
        $this->preparePriceInfo($listsData);
        $data->listsData = $listsData;
        
        if (haveRole($this->canSeeprices) && $data->masterData->rec->state != 'template') {
            $this->VatGroups->prepareVatGroups($vatData);
            $data->vatData = $vatData;
        }
    }
    
    
    /**
     * Рендира ценовата информация за артикула
     */
    public function renderPrices($data)
    {
        if ($data->hide === true) return;
        
        $tpl = getTplFromFile('cat/tpl/PriceDetails.shtml');
        $tpl->append($this->renderPriceInfo($data->listsData), 'PriceList');
        
        if (isset($data->vatData)) {
            $tpl->append($this->VatGroups->renderVatGroups($data->vatData), 'VatGroups');
        }

        return $tpl;
    }
    
    
    /**
     * Подготвя подробната ценова информация
     */
    private function preparePriceInfo($data)
    {
        $btns = '';
        $minPriceValidFrom = $validFrom = dt::now();
        $hideIcons = false;
        if (Mode::isReadOnly()) {
            $hideIcons = true;
        }
        
        // Може да се добавя нова себестойност, ако продукта е в група и може да се променя
        $primeCostListId = price_ListRules::PRICE_LIST_COST;
        
        if (price_ListRules::haveRightFor('add', (object) array('productId' => $data->masterId, 'listId' => $primeCostListId))) {
            $data->addPriceUrl = array('price_ListRules', 'add', 'type' => 'value', 'listId' => $primeCostListId, 'productId' => $data->masterId, 'priority' => 1, 'ret_url' => true);
        }
        
        $now = dt::now();
        
        $primeCostRows = array();
        
        $rec = price_ProductCosts::fetch("#productId = {$data->masterId}");
        if (!$rec) {
            $rec = new stdClass();
        }
        
        $primeCostIsFromTemplate = $catalogCostIsFromTemplate = false;
        $primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $data->masterId, null, $now, $validFrom);
        if(is_null($primeCost) && isset($data->masterData->rec->proto)){
            $primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $data->masterData->rec->proto, null, $now, $validFrom);
            $primeCostIsFromTemplate = true;
        }

        $primeCostDate = (isset($primeCost)) ? $validFrom : null;
        
        $catalogListId = cat_Setup::get('DEFAULT_PRICELIST');
        $catalogCost = price_ListRules::getPrice($catalogListId, $data->masterId, null, $now, $validFrom);

        if(is_null($catalogCost) && isset($data->masterData->rec->proto)){
            $catalogCost = price_ListRules::getPrice($catalogListId, $data->masterData->rec->proto, null, $now, $validFrom);
            $catalogCostIsFromTemplate = true;
        }
        
        if ($catalogCost == 0 && !isset($rec->primeCost)) {
            $catalogCost = null;
        }
        if (isset($catalogCost)) {
            $catalogCostDate = $validFrom;
        }

        $futurePrimeCostDate = null;
        $lQuery = price_ListRules::getQuery();
        $lQuery->where("#listId = {$primeCostListId} AND #type = 'value' AND #productId = {$data->masterId} AND #validFrom > '{$now}'");
        $lQuery->orderBy('validFrom', 'ASC');
        $lQuery->limit(1);
        if ($lRec = $lQuery->fetch()) {
            $vat = cat_Products::getVat($data->masterId, $now);
            $futurePrimeCost = price_ListRules::normalizePrice($lRec, $vat, $now);
            $futurePrimeCostDate = $lRec->validFrom;
        }
        
        $DateTime = cls::get('type_DateTime', array('params' => array('format' => 'smartTime')));
        
        // Само за публичните показваме правилото за обновяване
        if ($data->masterData->rec->isPublic == 'yes') {
            if($data->masterData->rec->canStore == 'yes' && ($data->masterData->rec->canBuy == 'yes' || $data->masterData->rec->canManifacture == 'yes')){

                // Ако има правило за обновяване към конкретния артикул
                $data->updateData = clone $data;
                cls::get('price_Updates')->prepareDetail($data->updateData);
            }
        }
        
        if (haveRole('priceDealer,ceo')) {
            if (price_ListRules::haveRightFor('add', (object) array('productId' => $data->masterId, 'listId' => $primeCostListId))) {
                $newCost = null;
                if (isset($uRec->costValue)) {
                    $newCost = $uRec->costValue;
                }
                if ($newCost != $rec->primeCost) {
                    $data->addPriceUrl['price'] = $newCost;
                }
                
                if ($hideIcons === false) {
                    $btns .= "<div style='text-align:left'>" . ht::createLink('Нова себестойност', $data->addPriceUrl, false, 'title=Добавяне на нова мениджърска себестойност') . '</div>';
                }
                
                if (isset($uRec)) {
                    if (price_Updates::haveRightFor('saveprimecost', $uRec)) {
                        if ($hideIcons === false) {
                            $url = array('price_Updates', 'saveprimecost', $uRec->id, 'ret_url' => true);
                            if($uRec->_fromCategory){
                                $url['productId'] = $data->masterId;
                            }
                            $btns .= "<div style='text-align:left'>" . ht::createLink('Обновяване', $url, false, 'title=Обновяване на себестойността според зададеното правило'). '</div>';
                        }
                    }
                }
                
                if (price_Lists::haveRightFor('single', $primeCostListId) && isset($primeCost)) {
                    if ($hideIcons === false) {
                        $threadId = price_Lists::fetchField($primeCostListId, 'threadId');
                        $btns .= "<div style='text-align:left'>" . ht::createLink('Хронология', array('doc_Containers', 'list', 'threadId' => $threadId, 'product' => $data->masterId), false, 'title=Хронология на себестойността на артикула'). '</div>';
                    }
                }
            }
            
            if ($btns || isset($primeCost)) {
                $type = tr('|Политика "Себестойност"|*');
                $threadId = price_Lists::fetchField(price_ListRules::PRICE_LIST_COST, 'threadId');
                
                if (doc_Threads::haveRightFor('single', $threadId)) {
                    $type = ht::createLink($type, array('doc_Containers', 'list', 'threadId' => $threadId, 'product' => $data->masterId, 'ret_url' => true));
                }
                
                $verbPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($primeCost);
                if($primeCostIsFromTemplate === true && isset($verbPrice)){
                    $verbPrice = ht::createHint($verbPrice, 'Себестойността е зададена за шаблонния артикул|*!', 'notice', false, 'height=14px,width=14px', 'style=color:blue');
                }
                
                $priceRow = (is_null($primeCost)) ? $verbPrice : '<b>' . $verbPrice . "</b>";
                $primeCostRows[] = (object) array('type' => $type,
                                                  'updatedOn' => $DateTime->toVerbal($primeCostDate),
                                                  'price' => $priceRow,
                                                  'buttons' => $btns,
                                                  'ROW_ATTR' => array('class' => 'state-active'));
            }
            
            if (isset($futurePrimeCost)) {
                $verbPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($futurePrimeCost);
                $primeCostRows[] = (object) array('type' => tr('|Бъдеща|* |себестойност|*'),
                                                  'updatedOn' => $DateTime->toVerbal($futurePrimeCostDate),
                                                  'price' => '<b>' . $verbPrice . "</b>",
                                                  'ROW_ATTR' => array('class' => 'state-draft'));
            }
        }
        
        if (haveRole('price,ceo')) {
            $cQuery = price_ProductCosts::getQuery();
            $cQuery->where("#productId = {$data->masterId}");
            while ($cRec = $cQuery->fetch()) {
                if(empty($cRec->price)){
                    continue;
                }
                
                $cRow = price_ProductCosts::recToVerbal($cRec);
                $cRow->price = "<b>{$cRow->price}</b>";
                
                $cRow->type = $cRow->classId;
                if(price_ProductCosts::haveRightFor('edit', $cRec)){
                    $cRow->type .= ht::createLink('', array('price_ProductCosts', 'edit', $cRec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit-icon.png,title=Редактиране на цената');
                }
                
                if (isset($cRow->sourceId)) {
                    $cRow->buttons = "<div style='text-align:left'>" . $cRow->sourceId . '</div>';
                }
                $primeCostRows[] = $cRow;
            }
        }

        $catalogPriceCanBeAdded = price_ListRules::haveRightFor('add', (object) array('productId' => $data->masterId, 'listId' => price_ListRules::PRICE_LIST_CATALOG));
        if (isset($catalogCost) || $catalogPriceCanBeAdded) {
            $type = tr('Политика "Каталог"');
            $threadId = price_Lists::fetchField($catalogListId, 'threadId');
            
            if (doc_Threads::haveRightFor('single', $threadId)) {
                $type = ht::createLink($type, array('doc_Containers', 'list', 'threadId' => $threadId, 'product' => $data->masterId, 'ret_url' => true));
            }
            
            // Ако каталожната цена е от прототипа, показва се тази информация
            $verbPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($catalogCost);
            if($catalogCostIsFromTemplate === true){
                $verbPrice = ht::createHint($verbPrice, 'Цената по каталог е зададена за шаблонния артикул|*!', 'notice', false, 'height=14px,width=14px', 'style=color:blue');
            }

            $buttons = '';
            if ($catalogPriceCanBeAdded) {
                $addCatalogPriceUrl = array('price_ListRules', 'add', 'type' => 'value', 'listId' => price_ListRules::PRICE_LIST_CATALOG, 'productId' => $data->masterId, 'priority' => 1, 'ret_url' => true);

                $buttons .= "<div style='text-align:left'>" . ht::createLink('Нова каталожна цена', $addCatalogPriceUrl, false, 'title=Добавяне на нова цена в каталога') . '</div>';
            }

            $primeCostRows[] = (object) array('type' => $type,
                                              'updatedOn' => $DateTime->toVerbal($catalogCostDate),
                                              'price' => '<b>' . $verbPrice . "</b>",
                                              'buttons' => $buttons,
                                              'ROW_ATTR' => array('class' => 'state-active'));
        }

        // Ако има цена по политиката за минимални цени да се показва и тя
        if($minListId = sales_Setup::get('MIN_PRICE_POLICY')){
            if($minCost = price_ListRules::getPrice($minListId, $data->masterId, null, $now, $minPriceValidFrom)){
                $type = tr('Политика|* "' . price_Lists::getTitleById($minListId) . '"');
                $threadId = price_Lists::fetchField($minListId, 'threadId');
                if (doc_Threads::haveRightFor('single', $threadId)) {
                    $type = ht::createLink($type, array('doc_Containers', 'list', 'threadId' => $threadId, 'product' => $data->masterId, 'ret_url' => true));
                }

                $verbPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($minCost);
                $primeCostRows[] = (object) array('type' => $type,
                                                  'updatedOn' => $DateTime->toVerbal($minPriceValidFrom),
                                                  'price' => '<b>' . $verbPrice . "</b>",
                                                  'ROW_ATTR' => array('class' => 'state-active'));
            }
        }

        $data->primeCostRows = $primeCostRows;
    }
    
    
    /**
     * Рендира подготвената ценова информация
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    private function renderPriceInfo($data)
    {
        $tpl = getTplFromFile('cat/tpl/PrimeCostValues.shtml');
        $fieldSet = cls::get('core_FieldSet');
        $fieldSet->FLD('price', 'double');
        $fieldSet->FLD('buttons', 'varchar', 'smartCenter');
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        
        // Рендираме информацията за себестойностите
        $table = cls::get('core_TableView', array('mvc' => $fieldSet));
        
        $fields = arr::make("price=Стойност|* <small>({$baseCurrencyCode})</small>,type=Вид,updatedOn=В сила от||Valid from,buttons=Действия / Документ");
        $primeCostTpl = $table->get($data->primeCostRows, $fields);
        $primeCostTpl->prepend(tr('|*<div>|Цени без ДДС|*:</div>'));

        if (isset($data->updateData)) {
            $tpl->append(cls::get('price_Updates')->renderDetail($data->updateData), 'updateInfo');
        }
        
        $tpl->append($primeCostTpl, 'primeCosts');
        
        return $tpl;
    }
}
