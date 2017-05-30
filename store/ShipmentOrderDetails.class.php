<?php
/**
 * Клас 'store_ShipmentOrderDetails'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrderDetails extends deals_DeliveryDocumentDetail
{
	
	
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на ЕН';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, doc_plg_HidePrices,
                        plg_AlignDecimals2, plg_Sorting, doc_plg_TplManagerDetail, LastPricePolicy=sales_SalesLastPricePolicy,
                        ReversePolicy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo,store,sales,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'info=@Колети, productId, packagingId, packQuantity, packPrice, discount, amount, weight, volume,quantityInPack';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'info,discount,reff';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
    	parent::setDocumentFields($this);
    	
        $this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=short,value=short,after=notes');
        $this->FLD('transUnit', 'varchar', 'caption=Логистични единици->Вид,autohide');
        $this->FLD('info', "text(rows=2)", 'caption=Логистични единици->Номера,after=transUnit,autohide', array('hint' => 'Напишете номерата на колетите, в които се съдържа този продукт, разделени със запетая'));
    }


    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canBuy' : 'canSell';
    	$property .= ',canStore';
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property);
    	
    	return $products;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    { 
    	$rec = &$form->rec;

        if(!$form->isSubmitted()) {
            if($mvc->masterKey && $rec->{$mvc->masterKey}) {
    	        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            }

            if(isset($rec->productId) && isset($masterRec)){
                $masterStore = $masterRec->storeId;
                $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
                $form->info = $storeInfo->formInfo;
            }
            
            if($masterRec->template) {
                $tplRec = doc_TplManager::fetch($masterRec->template);
            }

            $form->setSuggestions('transUnit', $tplRec->lang == 'bg' ? ',Палети,Кашона' : ',Pallets,Carton boxes');
            $form->setField('transUnit', array('placeholder' => $tplRec->lang == 'bg' ? 'Палети' : 'Pallets'));
    	}

    	parent::inputDocForm($mvc, $form);
    	
    	if ($form->isSubmitted() && !$form->gotErrors()) {
            
            if($rec->info){
                $all = self::getLUs($rec->info);
                if(is_string($all)) {
                    $form->setError('info', $all);
                }
            } else {
            	$rec->info = NULL;
            }
        }
    }
    




    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	
    	if(!count($data->recs)) return;
    	
    	$storeId = $data->masterData->rec->storeId;
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    		
    		if(strlen($warning) && $data->masterData->rec->state == 'draft'){
    			$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    		}
    		 
    		if($rec->price < cat_Products::getSelfValue($rec->productId, NULL, $rec->quantity)){
    			if(!core_Users::haveRole('partner')){
    				$row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', FALSE);
    			}
    		}
    	}
    }
    

    /**
     * Парсира текст, въведен от потребителя в масив с номера на логистични единици
     * Връща FALSE, ако текста е некоректно форматиран
     */
    public static function getLUs($infoLU)
    {   
        $res = array();

        $str = str_replace(array(",", '№'), array("\n", ''), $infoLU);
        $arr = explode("\n", $str);

        foreach($arr as $item) {
            $item = trim($item);

            if(empty($item)) continue;

            if(strpos($item, '-')) {
                list($from, $to) = explode('-', $item);
                $from = trim($from);
                $to   = trim($to);
                if(!ctype_digit($from) || !ctype_digit($to) || !($from < $to)) {
                    return "Непарсируем диапазон на колети|* \"". $item . '"';
                }
                for($i = (int) $from; $i <= $to; $i++) {
                    if(isset($res[$i])) {
                        return "Повторение на колет|* №". $i;
                    }
                    $res[$i] = $i;
                }
            } elseif(!ctype_digit($item)) {

                return "Непарсируем номер на колет|* \"". $item . '"';
            } else {
                if(isset($res[$item])) {
                    return "Повторение на колет|* №". $item;
                }
                $item = (int) $item;
                $res[$item] = $item;
            }
        }
        
        if(trim($infoLU) && !count($res)) {
            return "Грешка при парсиране на номерата на колетите";
        }

        asort($res);

        return $res;
    }


    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	core_Lg::push($data->masterData->rec->tplLang);
    	
    	$date = ($data->masterData->rec->state == 'draft') ? NULL : $data->masterData->rec->modifiedOn;
    	if(count($data->rows)) {
            $totalLU = array();
    		foreach ($data->rows as $i => &$row) {
    			$rec = &$data->recs[$i];
    	 
                $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $data->masterData->rec->tplLang);
                deals_Helper::addAdditionalNotesToProductRow($row->productId, $rec->productId, $data->masterMvc, $data->masterId);
                
                if($rec->notes){
    				deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    			}

                $unit = $rec->transUnit ? $mvc->getVerbal($rec, 'transUnit') : 'Палет';
                $unitTr = tr($unit);
                
                // Показване на разпределението
                if(mb_strtolower($unitTr) == 'палет' || mb_strtolower($unitTr) == 'палети' ||  mb_strtolower($unitTr) == 'палетa') {
                    $bigPackName = array('палет', 'палета');
                    $unit = 'палет';
                } elseif(mb_strtolower($unitTr) == 'pallet' || mb_strtolower($unitTr) == 'pallets') {
                    $bigPackName = array('pallet', 'pallets');
                    $unit = 'палет';
                } elseif(mb_strtolower($unitTr) == 'кашон' || mb_strtolower($unitTr) == 'кашони' ||  mb_strtolower($unitTr) == 'кашона') {
                    $bigPackName = array('кашон', 'кашона');
                    $unit = 'палет';
                } elseif(mb_strtolower($unitTr) == 'carton' || mb_strtolower($unitTr) == 'cartons') {
                    $bigPackName = array('carton', 'cartons');
                    $unit = 'carton';
                } else {
                    $bigPackName = $unitTr;
                }

                if($row->info) {
                    $numbers = self::getLUs($rec->info);
                    if(!is_array($numbers)) {
                        $row->info = $numbers;
                    } elseif($bigPackCnt = count($numbers)) {
                        $row->info = "<small>" .  $unitTr . ': №' . implode(', №', $numbers) . "</small>";
                        $haveTransInfo = TRUE;
                        if(!isset($totalLU[$unit])) {
                            $totalLU[$unit] = $numbers;
                        } else {
                            $totalLU[$unit] += $numbers;
                        }
                    }
                }

                $bigPackInQty = cat_products_Packagings::getQuantityInPack($rec->productId, $unit);
                
                if($bigPackInQty) {
                   
                    $medPackRec = cat_products_Packagings::getLowerPack($rec->productId, $bigPackInQty);

                    if($medPackRec) {
                        $medPackInQty = $medPackRec->quantity;
                        $medPackName = tr(cat_UoM::fetchField($medPackRec->packagingId, 'name'));
                        
                        if(mb_strtolower($medPackName) == 'кашон' || mb_strtolower($medPackName) == 'кашони' ||  mb_strtolower($medPackName) == 'палетa') {
                            $medPackName = array('кашон', 'кашона');
                        } elseif(mb_strtolower($medPackName) == 'box' || mb_strtolower($medPackName) == 'boxes') {
                            $medPackName = array('box', 'boxes');
                        }  
                    }

                    if($row->info) {
                        $row->info .= "\n<br>";
                    }
                    
                    $uom = tr(cat_UoM::fetchField(cat_Products::fetch($rec->productId)->measureId, 'shortName'));
                
                    $row->info .= self::getDistribution($bigPackName, $bigPackInQty, $bigPackCnt, $medPackName, $medPackInQty, $rec->quantity, $uom); 
                    
                    $haveTransInfo = TRUE;
                }
    		}
    	}
        
        if(count($totalLU)) {
            $allNum = array();  
            foreach($totalLU as $lu => $luArr) {  
                if(strlen($luInfo)) $luInfo .= ';';
                $luInfo .=  ' <strong>' . count($luArr) . '</strong>&nbsp;' . mb_strtolower($lu);
                foreach($luArr as $i) {
                    if(isset($allNum[$i])) {
                        $err = "Логистичната единица|* №{$i} |не може едновремено да бъде|* " . $allNum[$i] . " |и|* " . $lu;
                    }
                    $allNum[$i] = $lu;
                }
            }

            if(count($allNum)) {
                $max = max(array_keys($allNum));
                $missing = array();
                for($i = 1; $i <= $max; $i++) {
                    if(!isset($allNum[$i])) {
                        $missing[] = $i;
                    }
                }

                if(count($missing)) {
                    $err2 = "Липсва информация за логистични единици|* №" . implode(", №", $missing);
                }
            }
 
            $data->masterData->row->logisticInfo =  $luInfo;

            if($err) {
                $data->masterData->row->logisticInfo = ht::createHint($data->masterData->row->logisticInfo, $err, 'error');
            }
            if($err2) {
                $data->masterData->row->logisticInfo = ht::createHint($data->masterData->row->logisticInfo, $err2, 'error');
            }

        }

        if(!$haveTransInfo) {
            unset($data->listFields['info']);
        }
    	
    	core_Lg::pop();
    }
    

    /**
     * Разпределение по опаковки (автоматично генериране)
     */
    public static function getDistribution($bigPackName, $bigPackInQty, $bigPackCnt, $medPackName, $medPackInQty, $totalQuantity, $uom)
    {
        if($totalQuantity == 0) return '';

        expect($totalQuantity > 0, $totalQuantity);
        expect($bigPackInQty > 0, $bigPackInQty);

        $bigPackCntCalc = (int) ($totalQuantity / $bigPackInQty);
        
        // Колко остават за последния палет
        $restCnt = $totalQuantity - $bigPackCntCalc * $bigPackInQty;
        
        if($restCnt > 0) {
            if($bigPackCnt > 0 && $bigPackCnt == $bigPackCntCalc) {
                $restCnt += $bigPackInQty;
                $bigPackCntCalc--;
            } elseif($bigPackCnt > 0 && ($bigPackCnt - 1) != $bigPackCntCalc) {
 
                return FALSE;
            }
        } elseif($bigPackCnt > 0 && $bigPackCnt != $bigPackCntCalc) {
  
            return FALSE;
        }
        
        if($bigPackCntCalc > 0) {
            // Генерирамe първи ред
            $fRow = "{$bigPackCntCalc} " . self::getPlural($bigPackName, $bigPackCntCalc);
            
            if($medPackInQty) {
                $medPackCnt = round(($bigPackInQty * $bigPackCntCalc) / $medPackInQty, 3);

                $fRow .= " / {$medPackCnt} " . self::getPlural($medPackName, $medPackCnt) . " x {$medPackInQty} {$uom}";
            } else {
                $fRow .= " x {$bigPackInQty} {uom}";
            }

            $fRow .= " = " . round($bigPackInQty * $bigPackCntCalc, 3) . " {$uom}";
        }
        
        // Генериране на втори ред
        if($restCnt > 0) {
            $sRow = "1 " . self::getPlural($bigPackName, 1);
            if($medPackInQty) {
                $medPackCnt = (int) ($restCnt / $medPackInQty);
                $medRest = $restCnt - $medPackCnt * $medPackInQty;
                $sRow .= " / ";
                if($medPackCnt > 0) {
                    $sRow .= "{$medPackCnt} " . self::getPlural($medPackName, $medPackCnt) . " x {$medPackInQty} {$uom}";
                    if($medRest > 0) {
                        $sRow .= ' + ';
                    }
                }

                if($medRest>0) {
                    $sRow .= "1 " . self::getPlural($medPackName, 1) . " x {$medRest} {$uom}";
                }

            }

            $sRow .= " = " . $restCnt . " {$uom}";
        }
        
        return ($fRow ? "<small>{$fRow}</small>\n<br>" : '') . "<small>{$sRow}</small>";
    }


    /**
     * Връща множествено чсило, ако имаме повече от един елемент в $name и $cnt е зададено
     */
    public static function getPlural($name, $cnt)
    {
        if(is_array($name)) {

            return $name[min($cnt-1, count($name)-1)];
        }

        return $name;
    }

    
    /**
     * Преди запис на продукт
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	$rec->weight = cat_Products::getWeight($rec->productId, $rec->packagingId, $rec->quantity);
    	$rec->volume = cat_Products::getVolume($rec->productId, $rec->packagingId, $rec->quantity);
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	$masterRec = store_ShipmentOrders::fetch($rec->shipmentId, 'isReverse,storeId');
    	if($masterRec->isReverse == 'yes'){
    		$res->operation['out'] = $masterRec->storeId;
    		unset($res->operation['in']);
    	}
    }
}
