<?php


/**
 * Модел за кеширани изчислени транспортни цени
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_TransportValues extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'tcost_Calcs';
    
    
    /**
     * Масив за мапване на стойностите от мениджърите
     */
    private static $map = array(
        'masterMvc' => 'sales_Sales',
        'deliveryTermId' => 'deliveryTermId',
        'contragentClassId' => 'contragentClassId',
        'contragentId' => 'contragentId',
        'productId' => 'productId',
        'packagingId' => 'packagingId',
        'deliveryLocationId' => 'deliveryLocationId',
        'valior' => 'valior',
        'quantity' => 'quantity',
        'price' => 'price',
        'packPrice' => 'packPrice',
        'chargeVat' => 'chargeVat',
        'currencyRate' => 'currencyRate',
        'currencyId' => 'currencyId',
        'countryId' => 'countryId',
        'deliveryData' => 'deliveryData',
    );
    
    
    /**
     * Заглавие
     */
    public $title = 'Изчислен транспорт';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'docId,recId,fee,deliveryTime,explain';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'mandatory,caption=Вид на документа');
        $this->FLD('docId', 'int', 'mandatory,caption=Ид на документа');
        $this->FLD('recId', 'int', 'mandatory,caption=Ид на реда');
        $this->FLD('fee', 'double', 'mandatory,caption=Сума на транспорта');
        $this->FLD('deliveryTime', 'time', 'mandatory,caption=Срок на доставка');
        $this->FLD('explain', 'varchar(265)', 'mandatory,caption=Обяснение');
        
        $this->setDbUnique('docClassId,docId,recId');
        $this->setDbIndex('docClassId,docId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->docId = cls::get($rec->docClassId)->getLink($rec->docId, 0);
    }
    
    
    /**
     * Връща информация за цената на транспорта, към клиент
     *
     * @param int   $deliveryTermId - условие на доставка
     * @param int   $productId      - ид на артикул
     * @param int   $packagingId    - ид на опаковка
     * @param float $quantity       - к-во
     * @param float $totalWeight    - общо тегло
     * @param float $totalVolume    - общ обем
     * @param array $deliveryData   - информация за доставка
     *
     * @return FALSE|array $res       - информация за цената на транспорта или NULL, ако няма
     *                     ['totalFee']  - обща сума на целия транспорт, в основна валута без ДДС
     *                     ['singleFee'] - цената от транспорта за 1-ца от артикула, в основна валута без ДДС
     */
    public static function getTransportCost($deliveryTermId, $productId, $packagingId, $quantity, $totalVolumicWeight, $deliveryData)
    {
        // Има ли в условието на доставка, драйвер за изчисляване на цени?
        $TransportCostDriver = cond_DeliveryTerms::getTransportCalculator($deliveryTermId);
        if (!is_object($TransportCostDriver)) {
            
            return false;
        }
        
        $weight = cat_Products::getTransportWeight($productId, $quantity);
        $volume = cat_Products::getTransportVolume($productId, $quantity);
        $volumicWeight = $TransportCostDriver->getVolumicWeight($weight, $volume, $deliveryTermId, $deliveryData);
        
        $totalVolumicWeight = self::normalizeTotalWeight($totalVolumicWeight, $productId, $TransportCostDriver, $deliveryTermId, $deliveryData);
        $totalFee = $TransportCostDriver->getTransportFee($deliveryTermId, $volumicWeight, $totalVolumicWeight, $deliveryData);
        
        $fee = $totalFee['fee'];
        
        $res = array('totalFee' => $fee);
        
        if ($fee > 0) {
            $res['singleFee'] = $fee / $quantity;
        }
        
        if (isset($totalFee['deliveryTime'])) {
            $res['deliveryTime'] = $totalFee['deliveryTime'];
        }
        
        if (!empty($totalFee['explain'])) {
            $res['explain'] = $totalFee['explain'];
        }
        
        return $res;
    }
    
    
    /**
     * Нормализира общото тегло
     *
     * @param float              $totalWeight
     * @param float              $totalWeight
     * @param cond_TransportCalc $TransportCostDriver
     */
    private static function normalizeTotalWeight($totalWeight, $productId, cond_TransportCalc $TransportCostDriver, $deliveryTermId, $deliveryData)
    {
        // Ако продукта има параметър със сис ид aggregateQuantity, то взема общото влуметрично тегло и го сравнява с $totalWeight
        $aggregateQuantityId = cat_Params::force('aggregateQuantity', 'Обобщено количество', 'double', null, '');
        $aggregateQuantity = cat_Products::getParams($productId, $aggregateQuantityId);
        
        if ($aggregateQuantity > 0) {
            $aggregateWeight = cat_Products::getTransportWeight($productId, $aggregateQuantity);
            $aggregateVolume = cat_Products::getTransportVolume($productId, $aggregateQuantity);
            if ($aggregateWeight && $aggregateVolume) {
                $aggregateWeight = $TransportCostDriver->getVolumicWeight($aggregateWeight, $aggregateVolume, $deliveryTermId, $deliveryData);
            }
            if ($aggregateWeight > $totalWeight) {
                $totalWeight = $aggregateWeight;
            }
        }
        
        return $totalWeight;
    }
    
    
    /**
     * Връща теглото и обема
     *
     * @param int   $productId
     * @param int   $packagingId
     * @param float $quantity
     *
     * @return array $res
     */
    public static function getWeightAndVolume($productId, $packagingId, $quantity)
    {
        $res = array();
        
        // Колко е единичното транспортно тегло на артикула
        $res['weight'] = cat_Products::getTransportWeight($productId, $quantity);
        $res['volume'] = cat_Products::getTransportVolume($productId, $quantity);
        
        // Ако теглото е 0 и няма обем, да не се изчислява транспорт
        if (empty($res['weight']) && isset($res['weight']) && empty($res['volume'])) {
            
            return array();
        }
        
        return $res;
    }
    
    
    /**
     * Връща начисления транспорт към даден документ
     *
     * @param mixed $docClassId - ид на клас на документ
     * @param int   $docId      - ид на документ
     * @param int   $recId      - ид на ред на документ
     *
     * @return stdClass|NULL - записа, или NULL ако няма
     */
    public static function get($docClassId, $docId, $recId)
    {
        $docClassId = cls::get($docClassId)->getClassId();
        $rec = self::fetch("#docClassId = {$docClassId} AND #docId = {$docId} AND #recId = '{$recId}'");
        
        return (is_object($rec)) ? $rec : null;
    }
    
    
    /**
     * Синхронизира сумата на скрития транспорт на един ред на документ
     *
     * @param mixed $docClassId - ид на клас на документ
     * @param int   $docId      - ид на документ
     * @param int   $recId      - ид на ред на документ
     * @param float $fee        - начисления скрит транспорт
     *
     * @return void
     */
    public static function sync($docClass, $docId, $recId, $fee, $deliveryTimeFromFee = null, $explained = null)
    {
        // Клас ид
        $classId = cls::get($docClass)->getClassId();
        
        // Проверка има ли запис за ъпдейт
        $exRec = self::get($classId, $docId, $recId);
        
        // Ако подадената сума е NULL, и има съществуващ запис - трие се
        if (is_null($fee) && is_object($exRec)) {
            self::delete($exRec->id);
        }
        
        // Ако има сума
        if (isset($fee)) {
            
            // И няма съществуващ запис, ще се добавя нов
            if (!$exRec) {
                $exRec = (object) array('docClassId' => $classId, 'docId' => $docId, 'recId' => $recId);
                if (isset($deliveryTimeFromFee)) {
                    $exRec->deliveryTime = $deliveryTimeFromFee;
                }
            }
            
            // Ъпдейт / Добавяне на записа
            $exRec->fee = $fee;
            if (isset($deliveryTimeFromFee)) {
                $exRec->deliveryTime = $deliveryTimeFromFee;
            } else {
                $exRec->deliveryTime = null;
            }
            
            if(!empty($explained)){
                $exRec->explain = $explained;
            }
            
            self::save($exRec);
        }
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
        requireRole('debug');
        
        // Изчистване записите от моделите
        self::truncate();
        
        $this->logWrite('Изтриване на кеша на транспортните суми');
        
        return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
    
    
    /**
     * Помощна ф-я връщаща п. кода и държава от подадени данни
     *
     * @param mixed       $contragentClassId - клас на контрагента
     * @param int         $contragentId      - ид на контрагента
     * @param string|NULL $pCode             - пощенски код
     * @param int|NULL    $countryId         - ид на държава
     * @param int|NULL    $locationId        - ид на локация
     *
     * @return array $res
     *               ['pCode']     - пощенски код
     *               ['countryId'] - ид на държава
     */
    public static function getCodeAndCountryId($contragentClassId, $contragentId, $pCode = null, $countryId = null, $locationId = null)
    {
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);
        
        // Ако има локация, адресните данни са приоритетни от там
        if (isset($locationId)) {
            if (is_numeric($locationId)) {
                $locationRec = crm_Locations::fetch($locationId);
                $locationCountryId = (isset($locationRec->countryId)) ? $locationRec->countryId : $cData->countryId;
                if (isset($locationCountryId) && !empty($locationRec->pCode)) {
                    
                    return array('pCode' => $locationRec->pCode, 'countryId' => $locationCountryId);
                }
                
                if (isset($locationRec->countryId)) {
                    
                    return array('pCode' => null, 'countryId' => $locationRec->countryId);
                }
            } else {
                if ($parsePlace = drdata_Address::parsePlace($locationId)) {
                    
                    return array('pCode' => $parsePlace->pCode, 'countryId' => $parsePlace->countryId);
                }
            }
        }
        
        // Ако има от документа данни, взимат се тях
        $cId = isset($countryId) ? $countryId : $cData->countryId;
        if (isset($cId) && !empty($pCode)) {
            
            return array('pCode' => $pCode, 'countryId' => $cId);
        }
        
        if (isset($countryId)) {
            
            return array('pCode' => null, 'countryId' => $countryId);
        }
        
        // В краен случай се връщат адресните данни на визитката
        return array('pCode' => $cData->pCode, 'countryId' => $cData->countryId);
    }
    
    
    /**
     * Колко е начисления скрит танспорт за документа
     *
     * @param mixed $docClass - клас на документа
     * @param int   $docId    - ид на документа
     *
     * @return float $count  - общо начислени разходи
     */
    public static function calcInDocument($docClass, $docId)
    {
        $count = 0;
        $classId = cls::get($docClass)->getClassId();
        $isQuote = ($classId == sales_Quotations::getClassId());
        
        $query = self::getQuery();
        $query->where("#docClassId = {$classId} AND #docId = {$docId}");
        
        $query->where('#fee > 0');
        while ($rec = $query->fetch()) {
            if ($isQuote === true) {
                $dRec = sales_QuotationsDetails::fetch($rec->recId, 'price,optional');
                if ($dRec->optional == 'yes') {
                    continue;
                }
            }
            
            $count += $rec->fee;
        }
        
        return $count;
    }
    
    
    /**
     * Показване на хинт при изчисление на цена
     *
     * @param string $amountRow    - вербалната сума на реда
     * @param float  $amountFee    - вербалната транспортна такса
     * @param float  $vat          - процент ДДС
     * @param float  $currencyRate - валутен курс
     * @param string $chargeVat    - режим на ДДС
     *
     * @return core_ET|string $amountRow  - сумата на реда с хинт
     */
    public static function getAmountHint($amountRow, $amountFee, $vat, $currencyRate, $chargeVat, $explain = null)
    {
        if (!haveRole('powerUser') || !isset($amountRow)) {
            
            return $amountRow;
        }
        
        if ($amountFee < 0) {
            $hint = '|Скритият транспорт не може да бъде изчислен|*: ';
            if ($amountFee == cond_TransportCalc::ZONE_FIND_ERROR) {
                $hint .= '|липсваща зона|*';
            } elseif ($amountFee == cond_TransportCalc::EMPTY_WEIGHT_ERROR) {
                $hint .= '|няма транспортно тегло|*';
            } else {
                $hint .= "({$amountFee})";
            }
            
            return ht::createHint($amountRow, $hint, 'warning', false);
        } elseif (isset($amountFee)) {
            $amountFee = deals_Helper::getDisplayPrice($amountFee, $vat, $currencyRate, $chargeVat);
            $amountFee = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($amountFee);
            $hint = "Транспорт|*: {$amountFee}";
            
            if (!empty($explain) && haveRole('admin,tcost')){
                $hint .= "<br>" . $explain;
            }
            
            return ht::createHint($amountRow, $hint, 'notice', false);
        }
        
        return $amountRow;
    }
    
    
    /**
     * Сумата на видимия транспорт в документа
     *
     * @param core_Query $query           - заявка
     * @param string     $productFld      - име на полето на артикула
     * @param string     $amountFld       - име на полето на сумата
     * @param string     $packPriceFld    - име на полето на цената на опаковката
     * @param string     $packQuantityFld - име на полето на количеството на опаковката
     * @param string     $discountFld     - име на полето за отстъпката
     *
     * @return float $amount         - сума на видимия транспорт в основна валута без ДДС
     */
    public static function getVisibleTransportCost(core_Query $query, $productFld = 'productId', $amountFld = 'amount', $packPriceFld = 'packPrice', $packQuantityFld = 'packQuantity', $discountFld = 'discount')
    {
        $amount = 0;
        
        // Ще се гледат само отбелязаните артикули, като транспорт
        $transportArr = keylist::toArray(sales_Setup::get('TRANSPORT_PRODUCTS_ID'));
        $query->in($productFld, $transportArr);
        
        // За всеки намерен, сумата му се събира
        while ($dRec = $query->fetch()) {
            
            // Ако няма поле за сума, тя се взима от к-то по цената
            $amountRec = isset($dRec->{$amountFld}) ? $dRec->{$amountFld} : $dRec->{$packPriceFld} * $dRec->{$packQuantityFld};
            if (isset($dRec->{$discountFld})) {
                $amountRec = $amountRec * (1 - $dRec->{$discountFld});
            }
            
            $amount += $amountRec;
        }
        
        // Връщане на намерената сума
        return $amount;
    }
    
    
    /**
     * Връща общото тегло и общия обем на масив с артикули
     * 
     * @param cond_TransportCalc $TransportCalc
     * @param array $products
     * @param int $deliveryTermId
     * @param array $params
     * @param string $productFld
     * @param string $quantityFld
     * @param string $packagingFld
     * @return double|null
     */
    public static function getTotalWeightAndVolume(cond_TransportCalc $TransportCalc, $products, $deliveryTermId, $params = array(), $productFld = 'productId', $quantityFld = 'quantity', $packagingFld = 'packagingId')
    {
        expect($TransportCalc);
        $totalVolumicWeight = null;
        if (!is_array($products)) {
            
            return $totalVolumicWeight;
        }
       
        // За всеки артикул в масива
        foreach ($products as $p1) {
            $weight = cat_Products::getTransportWeight($p1->{$productFld}, $p1->{$quantityFld});
            $volume = cat_Products::getTransportVolume($p1->{$productFld}, $p1->{$quantityFld});
            
            $volumicWeight = $TransportCalc->getVolumicWeight($weight, $volume, $deliveryTermId, $params);
            
            if(is_null($volumicWeight)) {
                
                return cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT;
            }
            
            $totalVolumicWeight += $volumicWeight;
        }
        
        // Връщане на общото тегло
        return $totalVolumicWeight;
    }
    
    
    /**
     * Помощна функция връщаща сумата на реда
     * Използва се след инпута на формата на документите
     *
     * @param int      $deliveryTermId     - ид на условие на доставка
     * @param mixed    $contragentClassId  - клас на контрагента
     * @param int      $contragentId       - ид на контрагента
     * @param int      $productId          - ид на артикула
     * @param int      $packagingId        - ид на опаковка
     * @param float    $quantity           - количество
     * @param int|NULL $deliveryLocationId - ид на локация
     * @param array|NULL $params - параметри
     * @return NULL|array $feeArray        - сумата на транспорта
     */
    public static function getCostArray($deliveryTermId, $contragentClassId, $contragentId, $productId, $packagingId, $quantity, $deliveryLocationId, $countryId = null, $pCode = null, $params = array())
    {
        // Ако може да се изчислява скрит транспорт
        if (!cond_DeliveryTerms::canCalcHiddenCost($deliveryTermId, $productId)) {
            
            return;
        }
        
        // Пощенския код и ид-то на държавата
        $codeAndCountryArr = self::getCodeAndCountryId($contragentClassId, $contragentId, $pCode, $countryId, $deliveryLocationId);
        
        // Опит за изчисляване на транспорт
        $totalWeight = cond_Parameters::getParameter($contragentClassId, $contragentId, 'calcShippingWeight');
        
        $ourCompany = crm_Companies::fetchOurCompany();
        $params = $params + array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode'], 'fromCountry' => $ourCompany->country, 'fromPostalCode' => $ourCompany->pCode);
        $feeArr = self::getTransportCost($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $params);
        
        return $feeArr;
    }
    
    
    /**
     * Връща вербалното показване на транспортните цени
     *
     * @param stdClass $row                   - вербалното представяне на реда
     * @param float    $leftTransportCost     - остатъка за транспорт
     * @param float    $hiddenTransportCost   - скрития транспорт
     * @param float    $expectedTransportCost - очаквания транспорт
     * @param float    $visibleTransportCost  - явния транспорт
     */
    public static function getVerbalTransportCost(&$row, &$leftTransportCost, $hiddenTransportCost, $expectedTransportCost, $visibleTransportCost, $currencyRate)
    {
        $vars = array('hiddenTransportCost', 'expectedTransportCost', 'visibleTransportCost');
        if($expectedTransportCost == cond_TransportCalc::NOT_FOUND_TOTAL_VOLUMIC_WEIGHT){
            $row->expectedTransportCost = ht::createHint("<span class='red'>N/A</span>", 'Транспорта не може да бъде изчислен, защото някои от артикулите нямат обемно тегло|*!', 'error', false);
            unset($vars[1]);
            $expectedTransportCost = 0;
        }
        
        $Double = core_Type::getByName('double(decimals=2)');
        foreach ($vars as $fld) {
            if($currencyRate){
                ${$fld} /= $currencyRate;
            }
            
            $row->{$fld} = $Double->toVerbal(${$fld});
            if (${$fld} == 0) {
                $row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
            }
        }
        
        $leftTransportCost = $expectedTransportCost - $hiddenTransportCost - $visibleTransportCost;
        $row->leftTransportCost = $Double->toVerbal($leftTransportCost);
        $leftTransportCost = round($leftTransportCost, 2);
        $class = ($leftTransportCost > 0) ? 'green' : (($leftTransportCost < 0) ? 'red' : 'quiet');
        
        $row->leftTransportCost = "<span class='{$class}'>{$row->leftTransportCost}</span>";
    }
    
    
    /**
     * Помощен метод дали има грешка при избраното условие на доставка
     *
     * @param int      $deliveryTermId     - условие на доставка
     * @param string   $deliveryAddress    - точно място на доставка
     * @param int      $contragentClassId  - клас на контрагента
     * @param int      $contragentId       - ид на контрагент
     * @param int|NULL $deliveryLocationId - адрес на локация
     * @param array|NULL $params           - допълнителни параметри
     *
     * @return FALSE|string - съобщението за грешка, което ще се показва
     */
    public static function getDeliveryTermError($deliveryTermId, $deliveryAddress, $contragentClassId, $contragentId, $deliveryLocationId, $params = array())
    {
        $deliveryRec = cond_DeliveryTerms::fetchRec($deliveryTermId);
        $properties = type_Set::toArray($deliveryRec->properties);
       
        // Ако няма изчисляване на транспорт не се връща нищо
        $Driver = cond_DeliveryTerms::getTransportCalculator($deliveryTermId);
        if (empty($Driver)) {
            if(array_key_exists('transport', $properties)){
                return "|Не може да се изчисли транспорт|*";
            } else {
                
                return false;
            }
        }
        
        $toPcodeId = $toCountryId = null;
        
        // Извличане на държавата и кода
        $location = !empty($deliveryLocationId) ? $deliveryLocationId : $deliveryAddress;
        $codeAndCountryArr = self::getCodeAndCountryId($contragentClassId, $contragentId, $toPcodeId, $toCountryId, $location);
        $ourCompany = crm_Companies::fetchOurCompany();
        
        // Опит за изчисляване на дъмми транспорт
        $params = $params + array('deliveryCountry' => $codeAndCountryArr['countryId'], 'deliveryPCode' => $codeAndCountryArr['pCode'], 'fromCountry' => $ourCompany->country, 'fromPostalCode' => $ourCompany->pCode);
        $totalFee = $Driver->getTransportFee($deliveryTermId, 1, 1000, $params);
        
        if ($totalFee['fee'] < 0) {
            $toCountryId = core_Type::getByName('key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)')->toVerbal($codeAndCountryArr['countryId']);
            $errAddress = cond_DeliveryTerms::getVerbal($deliveryTermId, 'codeName') . ', ' . $toCountryId . ' ' . $codeAndCountryArr['pCode'];
            
            return "|Не може да се изчисли транспорт за|*: <b>{$errAddress}</b>";
        }
        
        return false;
    }
    
    
    /**
     * Помощна функция за подотовка на цената на транспорта
     *
     * @param stdClass  $rec       - запис
     * @param core_Form $form      - форма
     * @param stdClass  $masterRec - мастър запис
     * @param array     $map       - масив за мапване на полетата
     *
     * @return void
     */
    public static function prepareFee(&$rec, &$form, $masterRec, $map = array())
    {
        $map = array_merge(self::$map, $map);
        
        // Имали вече начислен транспорт
        if ($cRec = self::get($map['masterMvc'], $masterRec->id, $rec->id)) {
            $rec->fee = $cRec->fee;
            $rec->deliveryTimeFromFee = $cRec->deliveryTime;
        }
        
        if ($masterRec->deliveryAdress) {
            if ($parsePlace = drdata_Address::parsePlace($masterRec->deliveryAdress)) {
                $countryId = $parsePlace->countryId;
                $PCode = $parsePlace->pCode;
            }
        }
        
        if (!$countryId) {
            $countryId = !empty($masterRec->{$map['countryId']}) ? $masterRec->{$map['countryId']} : null;
            $PCode = !empty($masterRec->pCode) ? $masterRec->pCode : null;
        }
        
        // Ако драйвера не иска да се начислява цената да не се начислява
        if (isset($rec->{$map['productId']})) {
            $Driver = cat_Products::getDriver($rec->{$map['productId']});
            if(!is_object($Driver)) return;
            
            if (!$Driver->canCalcTransportFee($rec->{$map['productId']})) {
                
                return;
            }
        }
        
        // Колко е очаквания транспорт
        $deliveryData = is_array($masterRec->{$map['deliveryData']}) ? $masterRec->{$map['deliveryData']} : array();
        $feeArr = self::getCostArray($masterRec->{$map['deliveryTermId']}, $masterRec->{$map['contragentClassId']}, $masterRec->{$map['contragentId']}, $rec->{$map['productId']}, $rec->{$map['packagingId']}, $rec->{$map['quantity']}, $masterRec->{$map['deliveryLocationId']}, $countryId, $PCode, $deliveryData);
        
        // Ако има такъв към цената се добавя
        if (is_array($feeArr)) {
            if(!empty($feeArr['explain'])){
                $rec->_transportExplained = $feeArr['explain'];
            }
            
            if (isset($feeArr['deliveryTime'])) {
                $rec->deliveryTimeFromFee = $feeArr['deliveryTime'];
            } else {
                $rec->deliveryTimeFromFee = null;
            }
            
            if ($rec->autoPrice === true) {
                if (isset($feeArr['singleFee'])) {
                    $newFee = $feeArr['totalFee'] / $rec->{$map['quantity']};
                    $newFee = $newFee / $masterRec->{$map['currencyRate']};
                    if ($masterRec->{$map['chargeVat']} == 'yes') {
                        $vat = cat_Products::getVat($rec->productId, $masterRec->{$map['valior']});
                        $newFee = $newFee * (1 + $vat);
                    }
                    
                    if ($masterRec->{$map['chargeVat']} == 'yes') {
                        $newFee = $newFee / (1 + $vat);
                    }
                    
                    $newFee *= $masterRec->{$map['currencyRate']};
                    
                    $feeArr['totalFee'] = $newFee * $rec->{$map['quantity']};
                    $feeArr['singleFee'] = $newFee;
                    
                    if (!is_null($rec->{$map['price']})) {
                        $rec->{$map['price']} += $feeArr['singleFee'];
                    }
                }
            }
            
            $rec->fee = $feeArr['totalFee'];
        }
        
        if ($rec->autoPrice !== true) {
            if (cond_DeliveryTerms::canCalcHiddenCost($masterRec->deliveryTermId, $rec->productId)) {
                if (isset($rec->{$map['price']})) {
                    // Проверка дали цената е допустима спрямо сумата на транспорта
                    $amount = round($rec->{$map['price']} * $rec->{$map['quantity']}, 2);
                    
                    if ($amount < round($rec->fee, 2)) {
                        $fee = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->fee / $masterRec->{$map['currencyRate']});
                        $form->setWarning('packPrice', "Сумата на артикула без ДДС е по-малка от сумата на скрития транспорт|* <b>{$fee}</b> {$masterRec->{$map['currencyId']}}, |без ДДС|*");
                        $vat = cat_Products::getVat($rec->{$map['productId']}, $masterRec->{$map['valior']});
                        $rec->{$map['packPrice']} = deals_Helper::getDisplayPrice($rec->{$map['packPrice']}, $vat, $masterRec->{$map['currencyRate']}, $masterRec->{$map['chargeVat']});
                    }
                }
            }
        }
        
        // Ако има сума ще се синхронизира
        if (isset($rec->fee)) {
            $rec->syncFee = true;
        }
    }
    
    
    /**
     * Рекалкулиране на изчисления транспорт на даден документ
     * 
     * @param mixed $detailClassId
     * @param mixed $detailId
     * @return boolean - рекалкулиран ли е транспорта или не
     */
    public static function recalcTransport($detailClassId, $detailId)
    {
        $Detail = cls::get($detailClassId);
        $detailRec = $Detail->fetchRec($detailId);
        if(!is_object($detailRec)) return false;
        
        $form = $Detail->getForm();
        $clone = clone $Detail->Master->fetch($detailRec->{$Detail->masterKey});
        
        $map = array();
        if($Detail instanceof sales_QuotationsDetails){
            $clone->deliveryPlaceId = (!empty($clone->deliveryPlaceId)) ? crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$clone->contragentClassId}' AND #contragentId = '{$clone->contragentId}'", $clone->deliveryPlaceId), 'id') : null;
            $map = array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId', 'countryId' => 'contragentCountryId');
        }
        
        sales_TransportValues::prepareFee($detailRec, $form, $clone, $map);
        sales_TransportValues::sync($Detail->Master, $detailRec->{$Detail->masterKey}, $detailRec->id, $detailRec->fee, $detailRec->deliveryTimeFromFee, $detailRec->_transportExplained);
    
        return isset($detailRec->fee);
    }
    
    
    /**
     * Преизчисляване на сумата на транспорта в черновите оферти/продажби, в които участва подадения артикул
     * 
     * @param int $productId
     * @return void
     */
    public static function recalcTransportByProductId($productId)
    {
        foreach (array('sales_QuotationsDetails', 'sales_SalesDetails') as $Detail){
            $Detail = cls::get($Detail);
            
            // Намират се всички документи с транспорт на чернова
            $quoteQuery = $Detail->getQuery();
            $quoteQuery->EXT('state', $Detail->Master, "externalName=state,externalKey={$Detail->masterKey}");
            $quoteQuery->EXT('containerId', $Detail->Master, "externalName=containerId,externalKey={$Detail->masterKey}");
            $quoteQuery->where("#state = 'draft' AND #productId = '{$productId}'");
            
            while($detailRec = $quoteQuery->fetch()){
                
                // Прави се опит за преизчисление на транспорта (ако трябва)
                $isRecalced = self::recalcTransport($Detail, $detailRec);
                if($isRecalced === true){
                    $Detail->Master->logWrite('Преизчисляване на сумата за транспорт  на артикул', $detailRec->{$Detail->masterKey});
                    doc_DocumentCache::cacheInvalidation($detailRec->containerId);
                }
            }
        }
    }
}
