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
class sales_reports_PriceDeviation extends frame2_driver_TableData {
	
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
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields;
	
	/**
	 * Полета за хеширане на таговете
	 *
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField;
	
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
	public function addFields(core_Fieldset &$fieldset) {
		$fieldset->FLD ( 'selfPriceTolerance', 'double', 'caption=Отклонение от себестойност->Толеранс под себестойност,unit= %,after=title,single=none' );
		$fieldset->FLD ( 'sellPriceToleranceDown', 'double', 'caption=Отклонение от продажна цена по политика->Толеранс под цена,unit= %,after=selfPriceTolerance,single=none' );
		$fieldset->FLD ( 'sellPriceToleranceUp', 'double', 'caption=Отклонение от продажна цена по политика->Толеранс над цена,unit= %,after=sellPriceToleranceDown,single=none' );
		$fieldset->FLD ( 'from', 'date(smartTime)', 'caption=Отчетен период->От,after=sellPriceToleranceUp,single=none1,mandatory' );
		$fieldset->FLD ( 'to', 'date(smartTime)', 'caption=Отчетен период->До,after=from,single=none1,mandatory' );
		$fieldset->FLD ( 'dealers', 'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat)', 'caption=Дилъри,placeholder=Всички,after=to' );
		$fieldset->FLD ( 'articleType', 'enum(all=Всички,yes=Стандартни,no=Нестандартни)', "caption=Тип артикули,maxRadio=3,columns=3,removeAndRefreshForm,after=dealers" );
	}
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver
	 *        	$Driver
	 * @param embed_Manager $Embedder        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data) {
		$form = $data->form;
		$rec = $form->rec;
		
		$form->setDefault ( 'articleType', 'all' );
	}
	
	/**
	 * Кои записи ще се показват в таблицата
	 *
	 * @param stdClass $rec        	
	 * @param stdClass $data        	
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL) {
		$recs = array ();
		$expRecs = array ();
		$sallRecs = array ();
		
		$dealersId = keylist::toArray ( $rec->dealers );
		
		// По продажби
		$query = sales_SalesDetails::getQuery ();
		
		$query->EXT ( 'contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId' );
		
		$query->EXT ( 'contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId' );
		
		$query->EXT ( 'dealerId', 'sales_Sales', 'externalName=dealerId,externalKey=saleId' );
		
		$query->EXT ( 'valior', 'sales_Sales', 'externalName=valior,externalKey=saleId' );
		
		$query->EXT ( 'containerId', 'sales_Sales', 'externalName=containerId,externalKey=saleId' );
		
		$query->EXT ( 'isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId' );
		
		$query->where ( array (
				"#valior>= '[#1#]' AND #valior <= '[#2#]'",
				$rec->from,
				$rec->to . ' 23:59:59' 
		) );
		
		if ($rec->articleType == 'yes') {
			$query->where ( "#isPublic = 'yes'" );
		}
		
		if ($rec->articleType == 'no') {
			$query->where ( "#isPublic = 'no'" );
		}
		
		while ( $saleProducts = $query->fetch () ) {
			
			if ($rec->dealers && ! in_array ( $saleProducts->dealerId, $dealersId ))
				continue;
			
			$sallProductId = $saleProducts->productId;
			
			if ($saleProducts->valior) {
				$valior = $saleProducts->valior;
			}
			
			// Себестойност: ако има по политика "себестойност", ако не: от драйвера, ако не: по рецептура
			$selfPrice = cat_Products::getSelfValue ( $sallProductId );
			
			$isPublic = $saleProducts->isPublic;
			
			// цена на артикула за клиента по политика или каталог
			$contragentFuturePrice = cls::get ( 'price_ListToCustomers' )->getPriceInfo ( $saleProducts->contragentClassId, $saleProducts->contragentId, $sallProductId, $valior, $saleProducts->quantity );
			
			if ((cls::get ( 'price_ListToCustomers' )->getListForCustomer ( $saleProducts->contragentClassId, $saleProducts->contragentId, $valior )) && ! empty ( $contragentFuturePrice )) {
				
				$productCatPrice = ($contragentFuturePrice->price);
			} else {
				$productCatPrice = price_ListRules::getPrice ( price_ListRules::PRICE_LIST_CATALOG, $sallProductId, NULL, $valior );
			}
			
			// Ако продажната цена е над продажната цена по политика(отчита се толеранса)
			if (($rec->sellPriceToleranceUp || (! $rec->sellPriceToleranceUp && is_numeric ( $rec->sellPriceToleranceUp )))) {
				
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
			if (($rec->sellPriceToleranceDown || (! $rec->sellPriceToleranceDown && is_numeric ( $rec->sellPriceToleranceDown )))) {
				
				$productCatPriceDown = $productCatPrice - ($productCatPrice * $rec->sellPriceToleranceDown) / 100;
				
				if ($productCatPrice && ($saleProducts->price < $productCatPriceDown)) {
					
					$productCatPriceDownFlag = TRUE;
				} else {
					
					$productCatPriceDownFlag = FALSE;
				}
			} else {
				
				$productCatPriceDown = 'не се търси';
			}
			
			// Ако продажната цена е под себестойност(отчита се толеранса)
			if (($rec->selfPriceTolerance || (! $rec->selfPriceTolerance && is_numeric ( $rec->selfPriceTolerance )))) {
				
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
				
				$sallRecs [] = ( object ) array (
						'saleId' => $saleProducts->saleId,
						'productId' => $sallProductId,
						'measure' => cat_Products::fetchField ( $sallProductId, 'measureId' ),
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
		
		$expQuery = store_ShipmentOrderDetails::getQuery ();
		
		$expQuery->EXT ( 'contragentClassId', 'store_ShipmentOrders', 'externalName=contragentClassId,externalKey=shipmentId' );
		
		$expQuery->EXT ( 'contragentId', 'store_ShipmentOrders', 'externalName=contragentId,externalKey=shipmentId' );
		
		$expQuery->EXT ( 'valior', 'store_ShipmentOrders', 'externalName=valior,externalKey=shipmentId' );
		
		$expQuery->EXT ( 'isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId' );
		
		$expQuery->EXT ( 'containerId', 'store_ShipmentOrders', 'externalName=containerId,externalKey=shipmentId' );
		
		$expQuery->where ( array (
				"#valior>= '[#1#]' AND #valior <= '[#2#]'",
				$rec->from,
				$rec->to . ' 23:59:59' 
		) );
		
		if ($rec->articleType == 'yes') {
			$query->where ( "#isPublic = 'yes'" );
		}
		
		if ($rec->articleType == 'no') {
			$query->where ( "#isPublic = 'no'" );
		}
		
		while ( $expProducts = $expQuery->fetch () ) {
			
			$threadId = store_ShipmentOrders::fetch ( $expProducts->shipmentId )->threadId;
			
			$saleId = doc_Threads::getFirstDocument ( $threadId )->that;
			
			$dealerId = sales_Sales::fetch ( $saleId )->dealerId;
			
			if ($rec->dealers && ! in_array ( $dealerId, $dealersId ))
				continue;
			
			$expProductId = $expProducts->productId;
			
			if ($expProducts->valior) {
				$valior = $expProducts->valior;
			}
			
			// Себестойност: ако има по политика "себестойност", ако не: от драйвера, ако не: по рецептура
			$expSelfPrice = cat_Products::getSelfValue ( $expProductId );
			
			// цена на артикула за клиента
			$contragentFuturePrice = cls::get ( 'price_ListToCustomers' )->getPriceInfo ( $expProducts->contragentClassId, $expProducts->contragentId, $expProductId, $valior, $expProducts->quantity );
			
			if ((cls::get ( 'price_ListToCustomers' )->getListForCustomer ( $expProducts->contragentClassId, $expProducts->contragentId, $valior )) && ! empty ( $contragentFuturePrice )) {
				
				$expProductCatPrice = ($contragentFuturePrice->price);
			} else {
				$expProductCatPrice = price_ListRules::getPrice ( price_ListRules::PRICE_LIST_CATALOG, $expProductId, NULL, $valior );
			}
			
			$isPublic = $saleProducts->isPublic;
			
			// Ако продажната цена е над продажната цена по политика(отчита се толеранса)
			if (($rec->sellPriceToleranceUp || (! $rec->sellPriceToleranceUp && is_numeric ( $rec->sellPriceToleranceUp )))) {
				
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
			if (($rec->sellPriceToleranceDown || (! $rec->sellPriceToleranceDown && is_numeric ( $rec->sellPriceToleranceDown )))) {
				
				$expProductCatPriceDown = $expProductCatPrice - ($expProductCatPrice * $rec->sellPriceToleranceDown) / 100;
				
				if ($expProductCatPrice && ($expProducts->price < $expProductCatPriceDown)) {
					
					$productCatPriceDownFlag = TRUE;
				} else {
					
					$productCatPriceDownFlag = FALSE;
				}
			} else {
				
				$expProductCatPriceDown = 'не се търси';
			}
			
			// Ако продажната цена е под себестойност(отчита се толеранса)
			if (($rec->selfPriceTolerance || (! $rec->selfPriceTolerance && is_numeric ( $rec->selfPriceTolerance )))) {
				
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
				
				$expRecs [] = ( object ) array (
						'saleId' => $saleId,
						'productId' => $expProductId,
						'measure' => cat_Products::fetchField ( $expProductId, 'measureId' ),
						'quantity' => $expProducts->quantity,
						'containerId' => $expProducts->containerId,
						'isPublic' => $isPublic,
						'selfPriceDown' => $expSelfPriceDown,
						'productCatPriceDown' => $expProductCatPriceDown,
						'productCatPriceUp' => $expProductCatPriceUp,
						'catPrice' => $expProductCatPrice,
						'price' => $expProducts->price 
				)
				;
			}
		}
		
		foreach ( $expRecs as $k => $v ) {
			
			array_push ( $sallRecs, $v );
		}
		
		foreach ( $sallRecs as $k => $v ) {
			
			$flag = FALSE;
			
			if (empty ( $recs )) {
				
				$recs [] = $v;
				
				continue;
			}
			
			foreach ( $recs as $rv ) {
				
				if (($v->saleId == $rv->saleId) && ($v->productId == $rv->productId)) {
					
					$flag = TRUE;
				}
			}
			
			if (! $flag) {
				
				array_push ( $recs, $v );
			}
		}
		
		return $recs;
	}
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec
	 *        	- записа
	 * @param boolean $export
	 *        	- таблицата за експорт ли е
	 * @return core_FieldSet - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE) {
		$fld = cls::get ( 'core_FieldSet' );
		
		if ($export === FALSE) {
			
			$fld->FLD ( 'saleId', 'varchar', 'caption=Сделка' );
			$fld->FLD ( 'productId', 'varchar', 'caption=Артикул' );
			$fld->FLD ( 'deviationDownSelf', 'varchar', 'caption=Отклонение->Под себестойност,tdClass=centered' );
			$fld->FLD ( 'deviationDownCatPrice', 'varchar', 'caption=Отклонение->Под политика,tdClass=centered' );
			$fld->FLD ( 'deviationUpCatPrice', 'varchar', 'caption=Отклонение->Над политика,tdClass=centered' );
			$fld->FLD ( 'measure', 'varchar', 'caption=Мярка,tdClass=centered' );
			$fld->FLD ( 'quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter' );
			$fld->FLD ( 'price', 'double', 'caption=Прод. цена,smartCenter' );
			$fld->FLD ( 'selfPrice', 'double', 'caption=Себестойност,smartCenter' );
			$fld->FLD ( 'catPrice', 'double', 'caption=Цена по политика,smartCenter' );
		} else {
		}
		
		return $fld;
	}
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec
	 *        	- записа
	 * @param stdClass $dRec
	 *        	- чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec) {
		
		// bp($dRec);
		$Int = cls::get ( 'type_Int' );
		
		$row = new stdClass ();
		
		if ($dRec->selfPrice > $dRec->price) {
			$marker = ( double ) (($dRec->price - $dRec->selfPrice) / $dRec->price);
		}
		// if ($dRec->price > $dRec->catPrice) {
		// if ($dRec->catPrice > 0) {
		// $marker = - 1 * ( double ) (($dRec->catPrice - $dRec->price) / $dRec->catPrice);
		// }
		// }
		// if ($dRec->saleId == 15 && $dRec->productId == 12)bp((is_numeric($dRec->productCatPriceDown) && ($dRec->price < $dRec->productCatPriceDown )));
		
		if ($dRec->productCatPriceDown) {
			
			if (is_numeric ( $dRec->productCatPriceDown ) && ($dRec->price < $dRec->productCatPriceDown)) {
				
				$marker = ( double ) (($dRec->price - $dRec->productCatPriceDown) / $dRec->price);
				if ($dRec->productCatPriceDown != 0) {
					$row->deviationDownCatPrice = core_Type::getByName ( 'percent' )->toVerbal ( $marker );
					$row->deviationDownCatPrice = ht::styleIfNegative ( $row->deviationDownCatPrice, $marker );
				}
				if (($dRec->productCatPriceDown == 0)) {
					$row->deviationDownCatPrice = 'няма политика';
				}
			}
		}
		
		if ($dRec->productCatPriceUp) {
				
			if (is_numeric ( $dRec->productCatPriceUp ) && ($dRec->price > $dRec->productCatPriceUp)) {
		
				$marker = ( double ) (($dRec->price - $dRec->productCatPriceUp) / $dRec->price);
				if ($dRec->productCatPriceUp != 0) {
					$row->deviationUpCatPrice = core_Type::getByName ( 'percent' )->toVerbal ( $marker );
					$row->deviationUpCatPrice = ht::styleIfNegative ( $row->deviationUpCatPrice, $marker );
				}
				if (($dRec->productCatPriceUp == 0)) {
					$row->deviationUpCatPrice = 'няма политика';
				}
			}
		}
		
		if ($dRec->selfPriceDown) {
		
			if (is_numeric ( $dRec->selfPriceDown ) && ($dRec->price > $dRec->selfPriceDown)) {
		
				$marker = ( double ) (($dRec->price - $dRec->selfPriceDown) / $dRec->price);
				if ($dRec->selfPriceDown != 0) {
					$row->deviationDownSelf = core_Type::getByName ( 'percent' )->toVerbal ( $marker );
					$row->deviationDownSelf = ht::styleIfNegative ( $row->deviationDownSelf, $marker );
				}
				if (($dRec->deviationDownSelf == 0)) {
					$row->deviationDownCatPrice = 'няма политика';
				}
			}
		}
		
		
		
		$row->deviation = core_Type::getByName ( 'percent' )->toVerbal ( $marker );
		$row->deviation = ht::styleIfNegative ( $row->deviation, $marker );
		
		if (isset ( $dRec->saleId )) {
			$row->saleId = sales_Sales::getLinkToSingle ( $dRec->saleId );
		}
		
		if (isset ( $dRec->productId )) {
			$row->productId = cat_Products::getShortHyperlink ( $dRec->productId );
		}
		
		if (isset ( $dRec->quantity )) {
			$row->quantity = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->quantity );
		}
		
		if (isset ( $dRec->price )) {
			$row->price = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->price );
		}
		
		if (isset ( $dRec->measure )) {
			$row->measure = cat_UoM::fetchField ( $dRec->measure, 'shortName' );
		}
		
		if (isset ( $dRec->selfPrice )) {
			$row->selfPrice = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->selfPrice );
		}
		
		if (isset ( $dRec->catPrice )) {
			$row->catPrice = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->catPrice );
		}
		return $row;
	}
}