<?php
/**
 * Мениджър на отчети за дефицит на складове
 *
 * @category  bgerp
 * @package   store
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Склад » Дефицит на складове
 */
class store_reports_DeficitInStores extends frame2_driver_TableData
 {
 	
 	
	const NUMBER_OF_ITEMS_TO_ADD = 50;
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo,manager,store,planing,purchase';
	
	/**
	 * Брой записи на страница
	 *
	 * @var int
	 */
	protected $listItemsPerPage = 30;
	
// 	/**
// 	 * Полета от таблицата за скриване, ако са празни
// 	 *
// 	 * @var int
// 	 */
// 	protected $filterEmptyListFields;
	
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
	protected $groupByField;
	
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
		$fieldset->FLD ( 'typeOfQuantity', 'enum(FALSE=Налично,TRUE=Разполагаемо)', 'caption=Количество за показване,maxRadio=2,columns=2,after=title,single=none' );
		$fieldset->FLD ( 'additional', 'table(columns=code|name,captions=Код на атикула|Наименование,widths=8em|20em)', "caption=Артикули||Additional,autohide,advanced,after=storeId,single=none" );
		$fieldset->FLD ( 'storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=typeOfQuantity' );
		$fieldset->FLD ( 'groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група продукти,after=storeId,silent,single=none,removeAndRefreshForm' );
		$fieldset->FLD ('horizon', 'time', 'caption=Хоризонт,after=groupId');
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
		
		$form->setDefault ( 'typeOfQuantity', 'TRUE' );
	}
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver        	
	 * @param embed_Manager $Embedder        	
	 * @param core_Form $form        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form) {
		$details = (json_decode ( $form->rec->additional ));
		
		if ($form->isSubmitted ()) {
			
			$details = (json_decode ( $form->rec->additional ));
			
			if (is_array ( $details->code )) {
				
				foreach ( $details->code as $v ) {
					
					$v = trim ( $v );
					
					if (! $v) {
						$form->setError ( 'additional', 'Не попълнен код на артикул' );
					} else {
						
						if (! cat_Products::getByCode ( $v )) {
							
							$form->setError ( 'additional', 'Не съществуващ артикул с код: ' . $v );
						}
					}
				}
				

				
				$grDetails = ( array ) $details;
				
				foreach ( $grDetails ['name'] as $k => $detail ) {
					
					if (! $detail && $grDetails ['code'] [$k]) {
						
						$prId = cat_Products::getByCode ( $grDetails ['code'] [$k] );
						
						if ($prId->productId) {
							
							$prName = cat_Products::getTitleById ( $prId->productId, $escaped = TRUE );
							
							$grDetails ['name'] [$k] = $prName;
						}
					}
				}
				
				$jDetails = json_encode ( self::removeRpeadValues ( $grDetails ) );
				
				$form->rec->additional = $jDetails;
			}
		} else {
			
			$rec = $form->rec;
			
			// Добавя цяла група артикули
			if ($form->cmd == 'refresh' && $rec->groupId) {
			
				
				$rQuery = cat_Products::getQuery ();
				
				$details = ( array ) $details;
				
				$rQuery->where ( "#groups Like'%|{$rec->groupId}|%'" );
				
				while ( $grProduct = $rQuery->fetch () ) {
					
					$grDetails ['code'] [] = $grProduct->code;
					
					$grDetails ['name'] [] = cat_Products::getTitleById ( $grProduct->id );
					
					$grDetails ['minQuantity'] [] = $grProduct->minQuantity;
					
					$grDetails ['maxQuantity'] [] = $grProduct->maxQuantity;
				}
				
				// Премахва артикули ако вече са добавени
				if (is_array ( $grDetails ['code'] )) {
					foreach ( $grDetails ['code'] as $k => $v ) {
						
						if ($details ['code'] && in_array ( $v, $details ['code'] )) {
							
							unset ( $grDetails ['code'] [$k] );
							unset ( $grDetails ['name'] [$k] );
							unset ( $grDetails ['minQuantity'] [$k] );
							unset ( $grDetails ['maxQuantity'] [$k] );
						}
					}
				}
				
				//Премахване на нестандартнитв артикули
				
				if (is_array($grDetails['name'])) {
				
					foreach ($grDetails['name'] as $k=>$v){
						 
						if ($grDetails['code'][$k]){
				
				
							$isPublic = (cat_Products::fetch(cat_Products::getByCode($grDetails['code'][$k])->productId)->isPublic);
						}
				
						if (!$grDetails['code'][$k] || $isPublic == 'no'){
				
							unset($grDetails['code'][$k]);
							unset($grDetails['name'][$k]);
							unset($grDetails['minQuantity'][$k]);
							unset($grDetails['maxQuantity'][$k]);
				
						}
				
					}
				
				}
				
				// Ограничава броя на артикулите за добавяне
				$count = 0;
				$countUnset = 0;
				if (is_array ( $grDetails ['code'] )) {
					foreach ( $grDetails ['code'] as $k => $v ) {
						
						$count ++;
						
						if ($count > self::NUMBER_OF_ITEMS_TO_ADD) {
							
							unset ( $grDetails ['code'] [$k] );
							unset ( $grDetails ['name'] [$k] );
							unset ( $grDetails ['minQuantity'] [$k] );
							unset ( $grDetails ['maxQuantity'] [$k] );
							$countUnset ++;
							continue;
						}
						
						$details ['code'] [] = $grDetails ['code'] [$k];
						$details ['name'] [] = $grDetails ['name'] [$k];
						$details ['minQuantity'] [] = $grDetails ['minQuantity'] [$k];
						$details ['maxQuantity'] [] = $grDetails ['maxQuantity'] [$k];
					}
					
					if ($countUnset > 0) {
						$groupName = cat_Products::getTitleById ( $rec->groupId );
						$maxArt = self::NUMBER_OF_ITEMS_TO_ADD;
						
						$form->setWarning ( 'groupId', "$countUnset артикула от група $groupName няма да  бъдат добавени.
                                                     Максимален брой артикули за еднократно добавяне - $maxArt.  
                                                     Може да добавите още артикули от групата при следваща редакция." );
					}
				}
				
				$jDetails = json_encode ( $details );
				
				$form->rec->additional = $jDetails;
			}
		}
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
		
		$shipmentProducts = array();
		
		$productsForJobs = array();
		
		$receiptProducts = array();
		
		$tempProducts = array ();
	
		$jobsQuery = planning_Jobs::getQuery();
	
		$shipDetQuery = store_ShipmentOrderDetails::getQuery();
	
		$shipDetQuery->EXT('deliveryTime', 'store_ShipmentOrders', 'externalName=deliveryTime,externalKey=shipmentId');
		
		$shipDetQuery->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');
		
		$receipQuery = store_ReceiptDetails::getQuery();
		
		$receipQuery->EXT('deliveryTime', 'store_Receipts', 'externalName=deliveryTime,externalKey=receiptId');
		
		$receipQuery->EXT('state', 'store_Receipts', 'externalName=state,externalKey=receiptId');
	
		$jobsQuery->where ( "#state = 'active' OR #state = 'wakeup'" );
		
		$shipDetQuery->where ( "#state = 'pending'" );
		
		$receipQuery->where ( " #state = 'pending'" );
			
		
		
		if(!empty($rec->horizon)){
			
			$horizon = dt::addSecs($rec->horizon, dt::today(), FALSE);
			
			$jobsQuery->where("(#deliveryDate IS NOT NULL AND #deliveryDate <= '{$horizon} 23:59:59') OR #deliveryDate IS NULL");
			
			$shipDetQuery->where("(#deliveryTime IS NOT NULL AND #deliveryTime <= '{$horizon} 23:59:59') OR #deliveryTime IS NULL");
			
			$receipQuery->where("(#deliveryTime IS NOT NULL AND #deliveryTime <= '{$horizon} 23:59:59') OR #deliveryTime IS NULL");
		}
		
		
		/*
		 * Масив с артикули по складови разписки за доставка
		 */
		while ($receiptArt = $receipQuery->fetch()){
		
		$recArr[]=$receiptArt;
			if (! array_key_exists ( $receiptArt->productId, $receiptProducts)) {
					
				$receiptProducts [$receiptArt->productId] =
					
															( object ) array (
													
																	'productId' => $receiptArt->productId,
													
																	'quantity' => $receiptArt->quantity,
															);
					
			} else {
					
				$obj = &$receiptProducts [$receiptArt->productId];
		
				$obj->quantity += $receiptArt->quantity;
			}
				
		}
		
		
		/*
		 * Масив с артикули по експедиционни нареждания
		 */
			while ($shipmentDet = $shipDetQuery->fetch()){
			
					if (! array_key_exists ( $shipmentDet->productId, $shipmentProducts)) {
							
						$shipmentProducts [$shipmentDet->productId] =
							
																	( object ) array (
																
																			'productId' => $shipmentDet->productId,
																
																			'quantity' => $shipmentDet->quantity,
																	);
					
					} else {
							
						$obj = &$shipmentProducts [$shipmentDet->productId];
						
						$obj->quantity += $shipmentDet->quantity;
					}
					
			}
	
	
		/*
		 * Масив с артикули по задания за производство	
		 */
 		while ( $jobses = $jobsQuery->fetch()) {
 			
			$jobsProdId = $jobses->productId ;
			
			if (! array_key_exists ( $jobsProdId, $productsForJobs )) {
			
				$productsForJobs [$jobsProdId] =
			
												( object ) array (
										
														'productId' => $jobsProdId,
														
														'quantity' => $jobses->quantity,
												);
				
			} else {
			
				$obj = &$productsForJobs [$jobses->productId];
		
				$obj->quantity += $jobses->quantity;
			}

		}
		
		
		//Извлича материалите и количествата им по филтрираните задания за производство
		
		if (is_array($productsForJobs)){
			
			foreach ($productsForJobs as $v){
							
				$lastActivBomm = cat_Products::getLastActiveBom ( $v->productId );
					
					if ($lastActivBomm){	
						
						$bommMaterials = cat_Boms::getBomMaterials($lastActivBomm->id,$lastActivBomm->quantity);
					
					}
				
				//Масив артикули и количество необходими за изпълнение на заданията //
				if (is_array($bommMaterials)){
					
					foreach ($bommMaterials as $material){
						
						if (! array_key_exists ( $jobsProdId, $bommsMaterials )) {
								
							$bommsMaterials [$material->productId] =
								
																	( object ) array (
																
																			'productId' => $material->productId,
																
																			'quantity' => $material->quantity,
																	);
						
						} else {
								
							$obj = &$bommsMaterials [$material->productId];
						
							$obj->quantity += $material->quantity;
							}
							
						}
					}
				}
				
			
			}

		/*
		 * От продуктите по експедиционни нареждания
		 *  изваждаме продуктите за  които има задание за производство ????
		 */
		
		if (is_array($shipmentProducts)){
			foreach ($shipmentProducts as $k =>$v){
			
				if (is_array($productsForJobs)){
					foreach ($productsForJobs as $key =>$jobv){
						
						if ($key == $k){
							
							unset ($shipmentProducts[$k]);
						}
					}
				}
			}
		}
		
		
		/*
		 * Ако има повтарящи се артикули в материалите за призводство
		 * и експедиционните нареждания се обединяват количествата
		 */
		
		if (is_array($bommMaterials)){
			foreach ($bommsMaterials as $k => $v){
		
				if (is_array($shipmentProducts)){
					foreach ($shipmentProducts as $skey => $sv){
						
						if ($k == $skey){
							
							$bommsMaterials[$k]->quantity= $v->quantity+$sv->quantity;
							
							unset($shipmentProducts[$skey]);
						}
						
					}
				}
			
			}
		}
		
		
		/*
		 * Масив с всички необходими материали
		 */
		$neseseryMaterials = $bommsMaterials + $shipmentProducts;
		
		
		/*
		 * Необходимите количества се коригират с вече заявените за доставка
		 */
		if (is_array($neseseryMaterials)){
			foreach ($neseseryMaterials as $k => $v){
			
				if (is_array($receiptProducts)){
					foreach ($receiptProducts as $skey => $sv){
				
						if ($k == $skey){
							
							if ($v->quantity-$sv->quantity >0){
								
							$neseseryMaterials[$k]->quantity= $v->quantity-$sv->quantity;
							
							}
				
						}
					}
			
				}
			}
		}
		
		$products = (json_decode ( $rec->additional, false ));
		
		
		/*
		 * Премахваме повтарящи се артикули
		 */
		if (is_array ( $products->code )) {
			
			foreach ( $products->code as $k => $v ) {
				
				if (in_array ( $v, $tempProducts ))
					continue;
				
				$tempProducts [$k] = $v;
			}
			
			$products->code = $tempProducts;
			
			foreach ( $products->code as $key => $code ) {
				
				if (! isset ( $products->code [$key] )) {
					
					$code = 0;
				}
				
				$productId = cat_Products::getByCode ( $code )->productId;
			
				$query = store_Products::getQuery ();
				
				$query->where ( "#productId = $productId" );
				
				if (isset ( $rec->storeId )) {
					
					$query->where ( "#storeId = $rec->storeId" );
				}
				
				while ( $recProduct = $query->fetch () ) {
					
					$id = $recProduct->productId;
					
					if ($rec->typeOfQuantity == 'FALSE') {
						$typeOfQuantity = FALSE;
					} else {
						$typeOfQuantity = TRUE;
					}
					
					$quantity = store_Products::getQuantity ( $id, $recProduct->storeId, $typeOfQuantity );

					if (cat_Products::fetch($id)->canManifacture == 'yes')continue;
							
					if (! array_key_exists ( $id, $recs )) {
						
						$recs [$id] = 

						( object ) array (
								
								'measure' => cat_Products::fetchField ( $id, 'measureId' ),
								'productId' => $productId,
								'storeId' => $rec->storeId,
								'quantity' => $quantity,					
								'code' => $products->code [$key],
								'neseseryQuantity'=>'',
								'shipmentQuantity'=>$shipmentProducts[$id]->quantity,
								'jobsQuantity'=>$bommsMaterials[$id]->quantity,
								'receiptQuantity' =>$receiptProducts[$id]->quantity
						);
					} else {
						
						$obj = &$recs [$id];
						
						$obj->quantity += $recProduct->quantity;
					}
				} // цикъл за добавяне
			}
			
		}
	
		foreach ( $recs as $k => $v ) {
			
			foreach ($neseseryMaterials as $kmat => $vmat){
				
				if ($vmat->productId == $v->productId){
										
					$recs[$kmat]->neseseryQuantity = $vmat->quantity;
					
					
							
				}

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
			
			$fld->FLD ( 'productId', 'varchar', 'caption=Артикул' );
			$fld->FLD ( 'measure', 'varchar', 'caption=Мярка,tdClass=centered' );
			$fld->FLD ( 'quantity', 'double(smartRound,decimals=2)', 'caption=Количество->Разполагаемо,smartCenter' );
			$fld->FLD ( 'receiptQuantity', 'double', 'caption=Количество->За получаване,smartCenter' );
			$fld->FLD ( 'shipmentQuantity', 'double', 'caption=Количество->Необходимо->За експедиция,smartCenter' );
			$fld->FLD ( 'jobsQuantity', 'double', 'caption=Количество->Необходимо->За производство,smartCenter' );
			$fld->FLD ( 'deliveryQuatity', 'double', 'caption=Количество->За доставка,smartCenter' );
			
		} else {
			$fld->FLD ( 'productId', 'varchar', 'caption=Артикул' );
			$fld->FLD ( 'measure', 'varchar', 'caption=Мярка,tdClass=centered' );
			$fld->FLD ( 'quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter' );
			$fld->FLD ( 'neseseryQuantity', 'double', 'caption=Необходимо->количество,smartCenter' );
			$fld->FLD ( 'deliveryQuatity', 'double', 'caption=Количество->за доставка,smartCenter' );

		}
		
		return $fld;
	}
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec- записа        	
	 * @param stdClass $dRec- чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec) {
		
		
	    $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
		$row = new stdClass ();
		
		if (isset ( $dRec->productId )) {
			$row->productId = cat_Products::getShortHyperlink ( $dRec->productId );
		}
		
		if (isset ( $dRec->quantity  )) {
			$row->quantity = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->quantity );
		}
		
		if (isset ( $dRec->receiptQuantity  )) {
			$row->receiptQuantity = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->receiptQuantity );
		}
		
		if (isset ( $dRec->jobsQuantity  )) {
			$row->jobsQuantity = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->jobsQuantity );
		}
		
		if (isset ( $dRec->shipmentQuantity  )) {
			$row->shipmentQuantity = core_Type::getByName ( 'double(decimals=2)' )->toVerbal ( $dRec->shipmentQuantity );
		}
		
		if (isset ( $dRec->storeId )) {
			$row->storeId = store_Stores::getShortHyperlink ( $dRec->storeId );
		} else {
			$row->storeId = 'Общо';
		}
		
		if (isset ( $dRec->measure )) {
			$row->measure = cat_UoM::fetchField ( $dRec->measure, 'shortName' );
		}
		
		if (isset ( $dRec->neseseryQuantity )) {
 			$row->neseseryQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->neseseryQuantity);
		}
		
		if ($dRec->quantity <0){
			
			$dRec->quantity = 0;
			
			$deliveryQuantity = $dRec->neseseryQuantity - $dRec->quantity;
		}
		
		if ($deliveryQuantity > 0){
			$row->deliveryQuatity = core_Type::getByName('double(decimals=2)')->toVerbal($deliveryQuantity);
		}
		
		if ($deliveryQuantity <= 0){
			$row->deliveryQuatity ='не';
		}
		
		if ((isset ( $dRec->conditionQuantity ) && ((isset ( $dRec->minQuantity )) || (isset ( $dRec->maxQuantity ))))) {
			$row->conditionQuantity = "<span style='color: $dRec->conditionColor'>{$dRec->conditionQuantity}</span>";
		}
		
		return $row;
	}
	
	/**
	 * Изчиства повтарящи се стойности във формата
	 *
	 * @param $arr
	 * @return array
	 */
	static function removeRpeadValues($arr) {
		
		$tempArr = ( array ) $arr;
		
		$tempProducts = array ();
		if (is_array ( $tempArr ['code'] )) {
			
			foreach ( $tempArr ['code'] as $k => $v ) {
				
				if (in_array ( $v, $tempProducts )) {
					
					unset ( $tempArr ['minQuantity'] [$k] );
					unset ( $tempArr ['maxQuantity'] [$k] );
					unset ( $tempArr ['name'] [$k] );
					unset ( $tempArr ['code'] [$k] );
					continue;
				}
				
				$tempProducts [$k] = $v;
			}
		}
		
		$groupNamerr = $tempArr;
		
		return $arr;
	}
}