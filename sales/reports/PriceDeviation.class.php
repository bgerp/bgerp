<?php



/**
 * Мениджър на отчети за отклонения от цените
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Отклонения от цените
 */
class sales_reports_PriceDeviation extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planing,purchase,sales';

    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'conditionQuantity';

    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'saleId';

    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'typeOfQuantity,additional,storeId,groupId';

    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('selfPriceTolerance', 'double', 'caption=Отклонение от себестойност->Толеранс под себестойност,unit= %,after=title,single=none');
        $fieldset->FLD('sellPriceToleranceDown', 'double', 'caption=Отклонение от продажна цена по политика->Толеранс под цена,unit= %,after=selfPriceTolerance,single=none');
        $fieldset->FLD('sellPriceToleranceUp', 'double', 'caption=Отклонение от продажна цена по политика->Толеранс над цена,unit= %,after=sellPriceToleranceDown,single=none');
        $fieldset->FLD('from', 'date(smartTime)', 'caption=Отчетен период->От,after=sellPriceToleranceUp,mandatory,single=none');
        $fieldset->FLD('to', 'date(smartTime)', 'caption=Отчетен период->До,after=from,mandatory,single=none');
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat)', 'caption=Дилъри,placeholder=Всички,after=to');
        $fieldset->FLD('articleType', 'enum(all=Всички,yes=Стандартни,no=Нестандартни)', "caption=Тип артикули,maxRadio=3,columns=3,removeAndRefreshForm,after=dealers");
    }

    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setDefault('articleType', 'all');
        $form->setDefault('selfPriceTolerance', 1);
        $form->setDefault('sellPriceToleranceDown', 1);
        $form->setDefault('sellPriceToleranceUp', 1);
    }

    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec            
     * @param stdClass $data            
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
    	$sallRecs = $expRecs = $recs = array();
        $dealersId = keylist::toArray($rec->dealers);
        
        // По продажби
        $query = sales_SalesDetails::getQuery();
        
        $query->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
        
        $query->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
        
        $query->EXT('dealerId', 'sales_Sales', 'externalName=dealerId,externalKey=saleId');
        
        $query->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
        
        $query->EXT('containerId', 'sales_Sales', 'externalName=containerId,externalKey=saleId');
        
        $query->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        
        $query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $query->where("#state != 'rejected'");
        
        $query->where(
            array(
                "#valior>= '[#1#]' AND #valior <= '[#2#]'",
                $rec->from,
                $rec->to . ' 23:59:59'
            ));
        
        if ($rec->articleType == 'yes') {
            $query->where("#isPublic = 'yes'");
        }
        
        if ($rec->articleType == 'no') {
            $query->where("#isPublic = 'no'");
        }
        
        while ($saleProducts = $query->fetch()) {
            
            if ($rec->dealers && ! in_array($saleProducts->dealerId, $dealersId))
                continue;
            
            $sallProductId = $saleProducts->productId;
            
            if ($saleProducts->valior) {
                $valior = $saleProducts->valior;
            }
            
            // Себестойност: ако има по политика "себестойност", ако не: от драйвера, ако не: по рецептура
            $selfPrice = cat_Products::getSelfValue($sallProductId, NULL, $saleProducts->quantity, NULL);
            
            $isPublic = $saleProducts->isPublic;
            
            if ($rec->articleType != 'all') {
                
                if ($rec->articleType != $isPublic)
                    continue;
            }
            
            // цена на артикула за клиента по политика или каталог
            $contragentFuturePrice = cls::get('price_ListToCustomers')->getPriceInfo($saleProducts->contragentClassId, 
                $saleProducts->contragentId, $sallProductId, $valior, $saleProducts->quantity);
            
            if ((cls::get('price_ListToCustomers')->getListForCustomer($saleProducts->contragentClassId, 
                $saleProducts->contragentId, $valior)) && ! empty($contragentFuturePrice)) {
                
                $productCatPrice = ($contragentFuturePrice->price);
            } else {
                $productCatPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $sallProductId, NULL, 
                    $valior);
            }
            
            // Ако продажната цена е над продажната цена по политика(отчита се толеранса)
            if (($rec->sellPriceToleranceUp || (! $rec->sellPriceToleranceUp && is_numeric($rec->sellPriceToleranceUp)))) {
                
                $productCatPriceUp = $productCatPrice + ($productCatPrice * $rec->sellPriceToleranceUp) / 100;
                
                if ($productCatPrice && ($saleProducts->price > $productCatPriceUp)) {
                    
                    $productCatPriceUpFlag = TRUE;
                } else {
                    
                    $productCatPriceUpFlag = FALSE;
                }
            } else {
                
                $productCatPriceUp = 'не се търси';
            }
            
            // Ако продажната цена е под продажната цена по политика(отчита се толеранса)
            if (($rec->sellPriceToleranceDown ||
                 (! $rec->sellPriceToleranceDown && is_numeric($rec->sellPriceToleranceDown)))) {
                
                $productCatPriceDown = $productCatPrice - ($productCatPrice * $rec->sellPriceToleranceDown) / 100;
                
                if ($productCatPrice && ((double) $saleProducts->price < $productCatPriceDown)) {
                    
                    $productCatPriceDownFlag = TRUE;
                } else {
                    
                    $productCatPriceDownFlag = FALSE;
                }
            } else {
                
                $productCatPriceDown = 'не се търси';
            }
            
            // Ако продажната цена е под себестойност(отчита се толеранса)
            if (($rec->selfPriceTolerance || (! $rec->selfPriceTolerance && is_numeric($rec->selfPriceTolerance)))) {
                
                $selfPriceDown = $selfPrice - ($selfPrice * $rec->selfPriceTolerance) / 100;
                
                if ($selfPrice && ($saleProducts->price < $selfPriceDown)) {
                    
                    $selfDownFlag = TRUE;
                } else {
                    
                    $selfDownFlag = FALSE;
                }
            } else {
                
                $selfPriceDown = 'не се търси';
            }
            
            if ($selfDownFlag || $productCatPriceDownFlag || $productCatPriceUpFlag) {
                
                $sallRecs[] = (object) array(
                    'saleId' => $saleProducts->saleId,
                    'selfPrice' => $selfPrice,
                    'productId' => $sallProductId,
                    'measure' => cat_Products::fetchField($sallProductId, 'measureId'),
                    'quantity' => $saleProducts->quantity,
                    'containerId' => $saleProducts->containerId,
                    'isPublic' => $isPublic,
                    'selfPriceDown' => $selfPriceDown,
                    'productCatPriceDown' => $productCatPriceDown,
                    'productCatPriceUp' => $productCatPriceUp,
                    'catPrice' => $productCatPrice,
                    'price' => $saleProducts->price
                );
            }
        }
        
        // По екпедиционни нареждания
        
        $expQuery = store_ShipmentOrderDetails::getQuery();
        
        $expQuery->EXT('contragentClassId', 'store_ShipmentOrders', 
            'externalName=contragentClassId,externalKey=shipmentId');
        
        $expQuery->EXT('contragentId', 'store_ShipmentOrders', 'externalName=contragentId,externalKey=shipmentId');
        
        $expQuery->EXT('valior', 'store_ShipmentOrders', 'externalName=valior,externalKey=shipmentId');
        
        $expQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $expQuery->EXT('containerId', 'store_ShipmentOrders', 'externalName=containerId,externalKey=shipmentId');
        
        $expQuery->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');
        
        $expQuery->where("#state != 'rejected'");
        
        $expQuery->where(
            array(
                "#valior>= '[#1#]' AND #valior <= '[#2#]'",
                $rec->from,
                $rec->to . ' 23:59:59'
            ));
        
        if ($rec->articleType == 'yes') {
            $query->where("#isPublic = 'yes'");
        }
        
        if ($rec->articleType == 'no') {
            $query->where("#isPublic = 'no'");
        }
        
        while ($expProducts = $expQuery->fetch()) {
            
            $threadId = store_ShipmentOrders::fetch($expProducts->shipmentId)->threadId;
            
            $saleId = doc_Threads::getFirstDocument($threadId)->that;
            
            $dealerId = sales_Sales::fetch($saleId)->dealerId;
            
            if ($rec->dealers && ! in_array($dealerId, $dealersId))
                continue;
            
            $expProductId = $expProducts->productId;
            
            if ($expProducts->valior) {
                $valior = $expProducts->valior;
            }
            
            // Себестойност: ако има по политика "себестойност", ако не: от драйвера, ако не: по рецептура
            $expSelfPrice = cat_Products::getSelfValue($expProductId, NULL, $expProducts->quantity, NULL);
            
            // цена на артикула за клиента
            $contragentFuturePrice = cls::get('price_ListToCustomers')->getPriceInfo($expProducts->contragentClassId, 
                $expProducts->contragentId, $expProductId, $valior, $expProducts->quantity);
            
            if ((cls::get('price_ListToCustomers')->getListForCustomer($expProducts->contragentClassId, 
                $expProducts->contragentId, $valior)) && ! empty($contragentFuturePrice)) {
                
                $expProductCatPrice = ($contragentFuturePrice->price);
            } else {
                $expProductCatPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $expProductId, 
                    NULL, $valior);
            }
            
            $isPublic = $saleProducts->isPublic;
            
            if ($rec->articleType != 'all') {
                
                if ($rec->articleType != $isPublic)
                    continue;
            }
            
            // Ако продажната цена е над продажната цена по политика(отчита се толеранса)
            if (($rec->sellPriceToleranceUp || (! $rec->sellPriceToleranceUp && is_numeric($rec->sellPriceToleranceUp)))) {
                
                $expProductCatPriceUp = $expProductCatPrice + ($expProductCatPrice * $rec->sellPriceToleranceUp) / 100;
                
                if ($expProductCatPrice && ($expProducts->price > $expProductCatPriceUp)) {
                    
                    $productCatPriceUpFlag = TRUE;
                } else {
                    
                    $productCatPriceUpFlag = FALSE;
                }
            } else {
                
                $expProductCatPriceUp = 'не се търси';
            }
            
            // Ако продажната цена е под продажната цена по политика(отчита се толеранса)
            if (($rec->sellPriceToleranceDown ||
                 (! $rec->sellPriceToleranceDown && is_numeric($rec->sellPriceToleranceDown)))) {
                
                $expProductCatPriceDown = $expProductCatPrice -
                 ($expProductCatPrice * $rec->sellPriceToleranceDown) / 100;
            
            if ($expProductCatPrice && ($expProducts->price < $expProductCatPriceDown)) {
                
                $productCatPriceDownFlag = TRUE;
            } else {
                
                $productCatPriceDownFlag = FALSE;
            }
        } else {
            
            $expProductCatPriceDown = 'не се търси';
        }
        
        // Ако продажната цена е под себестойност(отчита се толеранса)
        if (($rec->selfPriceTolerance || (! $rec->selfPriceTolerance && is_numeric($rec->selfPriceTolerance)))) {
            
            $expSelfPriceDown = $expSelfPrice - ($expSelfPrice * $rec->selfPriceTolerance) / 100;
            
            if ($expSelfPrice && ($expProducts->price < $expSelfPriceDown)) {
                
                $selfDownFlag = TRUE;
            } else {
                
                $selfDownFlag = FALSE;
            }
        } else {
            
            $expSelfPriceDown = 'не се търси';
        }
        
        if ($selfDownFlag || $productCatPriceDownFlag || $productCatPriceUpFlag) {
            
            $expRecs[] = (object) array(
                'saleId' => $saleId,
                'selfPrice' => $expSelfPrice,
                'productId' => $expProductId,
                'measure' => cat_Products::fetchField($expProductId, 'measureId'),
                'quantity' => $expProducts->quantity,
                'containerId' => $expProducts->containerId,
                'isPublic' => $isPublic,
                'selfPriceDown' => $expSelfPriceDown,
                'productCatPriceDown' => $expProductCatPriceDown,
                'productCatPriceUp' => $expProductCatPriceUp,
                'catPrice' => $expProductCatPrice,
                'price' => $expProducts->price
            );
        }
    }
    
    foreach ($expRecs as $k => $v) {
        
        array_push($sallRecs, $v);
    }
    
    foreach ($sallRecs as $k => $v) {
        
        $flag = FALSE;
        
        if (empty($recs)) {
            
            $recs[] = $v;
            
            continue;
        }
        
        foreach ($recs as $rv) {
            
            if (($v->saleId == $rv->saleId) && ($v->productId == $rv->productId)) {
                
                $flag = TRUE;
            }
        }
        
        if (! $flag) {
            
            array_push($recs, $v);
        }
    }
    
    return $recs;
}


/**
 * Връща фийлдсета на таблицата, която ще се рендира
 *
 * @param stdClass $rec
 *            - записа
 * @param boolean $export
 *            - таблицата за експорт ли е
 * @return core_FieldSet - полетата
 */
protected function getTableFieldSet($rec, $export = FALSE)
{
    $fld = cls::get('core_FieldSet');
    
    $fld->FLD('saleId', 'varchar', 'caption=Сделка');
    if($export === TRUE){
    	$fld->FLD('folderId', 'key(mvc=doc_Folders,select=title)', 'caption=Папка');
    	$fld->FLD('code', 'varchar', 'caption=Код');
    }
    $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
    $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
    $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
    $fld->FLD('price', 'double', 'caption=Прод. цена,smartCenter');
    $fld->FLD('selfPrice', 'double', 'caption=Себестойност,smartCenter');
    $fld->FLD('catPrice', 'double', 'caption=Цена по политика,smartCenter');
    $fld->FLD('deviationDownSelf', 'percent', 'caption=Отклонение->Под себестойност,tdClass=centered');
    $fld->FLD('deviationCatPrice', 'percent', 'caption=Отклонение->Спрямо политика,tdClass=centered');
    
    return $fld;
}


/**
 * Връща разлика
 *
 * @param stdClass $dRec
 * @param boolean $verbal
 * @return mixed $deviationCatPrice
 */
private static function getDeviationCatPrice($dRec, $verbal = TRUE)
{
	if (is_numeric($dRec->catPrice) && ($dRec->price != $dRec->catPrice)) {
	
		$marker = (double) (($dRec->price - $dRec->catPrice) / $dRec->catPrice);
		if ($dRec->catPrice != 0) {
			if($verbal === TRUE){
				$deviationCatPrice = core_Type::getByName('percent')->toVerbal($marker);
				$deviationCatPrice = ht::styleIfNegative($deviationCatPrice, $marker);
			} else {
				$deviationCatPrice = $marker;
			}
		}
		if (($dRec->catPrice == 0)) {
			$deviationCatPrice = 'няма политика';
		}
	}
	
	return $deviationCatPrice;
}


/**
 * Връща разлика
 * 
 * @param stdClass $dRec
 * @param boolean $verbal
 * @return mixed $deviationDownSelf
 */
private static function getDeviationDownSelf($dRec, $verbal = TRUE)
{
	if (is_numeric($dRec->selfPrice) && ($dRec->price < $dRec->selfPrice)) {
		$marker = (double) (($dRec->price - $dRec->selfPrice) / $dRec->selfPrice);
		if ($dRec->selfPrice != 0) {
			if($verbal === TRUE){
				$deviationDownSelf = core_Type::getByName('percent')->toVerbal($marker);
				$deviationDownSelf = ht::styleIfNegative($deviationDownSelf, $marker);
			} else {
				$deviationDownSelf = $marker;
			}
		}
		
		if (($dRec->selfPrice == 0)) {
			$deviationDownSelf = 'няма политика';
		}
	}
	
	return $deviationDownSelf;
}


/**
 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
 *
 * @param stdClass $rec - записа
 * @param stdClass $dRec - чистия запис
 * @return stdClass $row - вербалния запис
 */
protected function detailRecToVerbal($rec, &$dRec)
{
    $Int = cls::get('type_Int');
    
    $row = new stdClass();
    
    $marker = '';
    
    if ($dRec->catPrice) {
    	$row->deviationCatPrice = self::getDeviationCatPrice($dRec);
    }
    
    if ($dRec->selfPriceDown) {
    	$row->deviationDownSelf = self::getDeviationDownSelf($dRec);
    }
    
    $Sale = doc_Containers::getDocument(sales_Sales::fetch($dRec->saleId)->containerId);
    $handle = $Sale->getHandle();
    $folder = ((sales_Sales::fetch($dRec->saleId)->folderId));
    $folderLink = doc_Folders::recToVerbal(doc_Folders::fetch($folder))->title;
    $singleUrl = $Sale->getUrlWithAccess($Sale->getInstance(), $Sale->that);
    
    if (isset($dRec->saleId)) {
        $row->saleId = "<div ><span class= 'state-{$state} document-handler' >" . ht::createLink("#{$handle}", 
            $singleUrl, FALSE, "ef_icon={$Sale->singleIcon}") . "</span>" . ' »  ' . "<span class= 'quiet small'>" .
             $folderLink . "</span></div>";
    }
    
    $row->productId = cat_Products::getShortHyperlink($dRec->productId);
    
    if (isset($dRec->quantity)) {
        $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
    }
    
    if (isset($dRec->price)) {
        $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
    }
    
    if (isset($dRec->measure)) {
        $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
    }
    
    if (isset($dRec->selfPrice)) {
        $row->selfPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->selfPrice);
    }
    
    if (isset($dRec->catPrice)) {
        $row->catPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->catPrice);
    }
    
    return $row;
}


/**
 * След подготовка на реда за експорт
 *
 * @param frame2_driver_Proto $Driver
 * @param stdClass $res
 * @param stdClass $rec
 * @param stdClass $dRec
 */
protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
{
	$res->code = cat_Products::getVerbal($dRec->productId, 'code');
	
	$saleRec = sales_Sales::fetch($dRec->saleId, 'folderId');
	$res->saleId = "#" . sales_Sales::getHandle($dRec->saleId);
	$res->folderId = $saleRec->folderId;
	
	if ($dRec->catPrice) {
		$res->deviationCatPrice = self::getDeviationCatPrice($dRec, FALSE);
	}
	
	if ($dRec->selfPriceDown) {
		$res->deviationDownSelf = self::getDeviationDownSelf($dRec, FALSE);
	}
}


/**
 * След рендиране на единичния изглед
 *
 * @param cat_ProductDriver $Driver            
 * @param embed_Manager $Embedder            
 * @param core_ET $tpl            
 * @param stdClass $data            
 */
protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
{
    $fieldTpl = new core_ET(
        tr(
            "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|Отчетен период От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|Отчетен период До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN dealers-->|Групи|*: [#dealers#]<!--ET_END dealers--></div></small>
                                <small><div><!--ET_BEGIN selfPriceTolerance-->|Толеранс под себестойност|*: [#selfPriceTolerance#]<!--ET_END selfPriceTolerance--></div></small>
                                <small><div><!--ET_BEGIN sellPriceTTRUEoleranceDown-->|Толеранс под политика|*: [#sellPriceToleranceDown#]<!--ET_END sellPriceToleranceDown--></div></small>
                                <small><div><!--ET_BEGIN sellPriceToleranceUp-->|Толеранс над политика|*: [#sellPriceToleranceUp#]<!--ET_END sellPriceToleranceUp--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
    
    if (isset($data->rec->from)) {
        $fieldTpl->append($data->rec->from, 'from');
    }
    
    if (isset($data->rec->to)) {
        $fieldTpl->append($data->rec->to, 'to');
    }
    
    if (isset($data->rec->selfPriceTolerance)) {
        $fieldTpl->append(core_Type::getByName('percent')->toVerbal(($data->rec->selfPriceTolerance) / 100), 
            'selfPriceTolerance');
    }
    
    if (isset($data->rec->sellPriceToleranceDown)) {
        $fieldTpl->append(core_Type::getByName('percent')->toVerbal(($data->rec->sellPriceToleranceDown) / 100), 
            'sellPriceToleranceDown');
    }
    
    if (isset($data->rec->sellPriceToleranceUp)) {
        $fieldTpl->append(core_Type::getByName('percent')->toVerbal(($data->rec->sellPriceToleranceUp) / 100), 
            'sellPriceToleranceUp');
    }
    
    $tpl->append($fieldTpl, 'DRIVER_FIELDS');
}

/**
 * Кои полета да са скрити във вътрешното показване
 *
 * @param core_Master $mvc            
 * @param NULL|array $res            
 * @param object $rec            
 * @param object $row            
 */
public static function on_AfterGetHideArrForLetterHead(frame2_driver_Proto $Driver, embed_Manager $Embedd, &$res, $rec, 
    $row)
{
    $res = arr::make($res);
    
    $res['external']['selfPriceTolerance'] = TRUE;
}
}