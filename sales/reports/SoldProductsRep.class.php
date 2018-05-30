<?php



/**
 * Мениджър на отчети за продадени артикули продукти по групи и търговци
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Продадени артикули
 */
class sales_reports_SoldProductsRep extends frame2_driver_TableData 
{
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, sales';
	
	/**
	 * Полета за хеширане на таговете
	 *
	 * @see uiext_Labels
	 * @var string
	 */
	protected $hashField = '$recIndic';
	
	/**
	 * Кое поле от $data->recs да се следи, ако има нов във новата версия
	 *
	 * @var string
	 */
	protected $newFieldToCheck = 'docId';
	
	/**
	 * По-кое поле да се групират листовите данни
	 */
	protected $groupByField = 'group';
	
	/**
	 * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
	 */
	protected $changeableFields = 'from,to,compare,group,dealers,contragent,articleType';
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset        	
	 */
	public function addFields(core_Fieldset &$fieldset) 
	{

		$fieldset->FLD ( 'from', 'date(smartTime)', 'caption=От,after=title,single=none,mandatory' );
		$fieldset->FLD ( 'to', 'date(smartTime)', 'caption=До,after=from,single=none,mandatory' );
		$fieldset->FLD ( 'compare', 'enum(no=Без, previous=Предходен, year=Миналогодишен)', 'caption=Сравнение,after=to,single=none' );
		$fieldset->FLD ( 'group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=compare,single=none' );
		$fieldset->FLD ( 'articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', "caption=Тип артикули,maxRadio=3,columns=3,removeAndRefreshForm,after=group" );
		$fieldset->FLD ( 'dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal,allowEmpty)', 'caption=Търговци,single=none,after=to' );
		$fieldset->FLD ( 'contragent', 'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,single=none,after=dealers' );
	}
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver        	
	 * @param embed_Manager $Embedder        	
	 * @param core_Form $form        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form) 
	{

		if ($form->isSubmitted ()) {
			
			if (! ($form->rec->dealers)) {
				$form->setError ( 'dealers', 'Нямате избран дилър' );
			}
			
			// Проверка на периоди
			if (isset ( $form->rec->from ) && isset ( $form->rec->to ) && ($form->rec->from > $form->rec->to)) {
				$form->setError ( 'from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.' );
			}
		}
	}
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver        	
	 * @param embed_Manager $Embedder        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data) 
	{

		$form = &$data->form;
		
		$form->setDefault ( 'articleType', 'all' );
		
		$form->setDefault ( 'compare', 'no' );
		
	}
	
	
	// Action for test //
	public static function act_testtt()
	{
	    requireRole('powerUser');
	    
	    $rec = unserialize(file_get_contents('debug.txt'));
	    
	    self::prepareRecs($rec);
	    
	    bp($rec); // $rec->count - брой документи //
	}
	
	////////////////////////////////////////////////////////////////
	
	/**
	 * Кои записи ще се показват в таблицата
	 *
	 * @param stdClass $rec        	
	 * @param stdClass $data        	
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL) 
	{
	    
	    file_put_contents('debug.txt',serialize($rec));

		$recs = array ();
		
	    $query = sales_PrimeCostByDocument::getQuery ();
		
		$query->EXT ( 'groupMat', 'cat_Products', 'externalName=groups,externalKey=productId' );
		
		$query->EXT ( 'isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId' );
		
		$query->EXT ( 'code', 'cat_Products', 'externalName=code,externalKey=productId' );
		
		$query->EXT ( 'docState', 'doc_Containers', 'externalName=state,externalKey=containerId' );
		
		if(isset($rec->compare) == 'no') {
		    
		    $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
		}
		
		// Last период
		
		if (isset($rec->compare) == 'previous') {
		    
		    $daysInPeriod = dt::daysBetween($rec->to, $rec->from) + 1;
		    $fromPreviuos = dt::addDays(- $daysInPeriod, $rec->from);
		    $toPreviuos = dt::addDays(- $daysInPeriod, $rec->to);
		    
		    $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
		}
		
		 //LastYear период
		
		if (isset($rec->compare) == 'year') {
		    
		    $fromLastYear = dt::addDays(- 365, $rec->from);
		    $toLastYear = dt::addDays(- 365, $rec->to);
		    
		    $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
		    
		}
		
			$query->where( "#docState != 'rejected'" );
		
		
		if (isset ( $rec->dealers )) {
			
			if ((min ( array_keys ( keylist::toArray ( $rec->dealers ) ) ) >= 1)) {
				
				$dealers = keylist::toArray ( $rec->dealers );
				
				$query->whereArr ( "dealerId", $dealers, TRUE );
			}
		}
		
		if ($rec->contragent) {
			
			$query->EXT ( 'coverId', 'doc_Folders', 'externalName=coverId,externalKey=folderDocId' );
			
			$contragentId = doc_Folders::fetch ( $rec->contragent )->coverId;
			
			$query->where ( "#coverId = {$contragentId}" );
		}
		
		
		
		if (isset ( $rec->group )) {
			$query->likeKeylist ( "groupMat", $rec->group );
		}
		
		if ($rec->articleType != 'all') {
			
			$query->where ( "#isPublic = '{$rec->articleType}'" );
		}
		
		
 		// Масив бързи продажби //
		
 		$sQuery = sales_Sales::getQuery ();
 
		if(isset($rec->compare) == 'no') {
		    
		    $sQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
		}
		
		// Last период
		
		if (isset($rec->compare) == 'previous') {
		    
		    $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
		}
		
		//LastYear период
		
		if (isset($rec->compare) == 'year') {
		    
		    $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
		    
		}
		
		$sQuery->like ( 'contoActions', 'ship', FALSE );
		
		$sQuery->EXT ( 'detailId', 'sales_SalesDetails', 'externalName=id,remoteKey=saleId' );
		
		while ( $sale = $sQuery->fetch () ) {
			
		    $salesWithShipArr [$sale->detailId] = $sale->detailId;
		    
		}
		
		$rec->count = $query->count();
		
		$timeLimit = $query->count() * 0.05;
		
		if ($timeLimit >= 30) {
		    core_App::setTimeLimit($timeLimit);
		}
		
		$num = 1;
		$quantity = 0;
		$flag = FALSE;
		
		while ( $recPrimes = $query->fetch () ) {
		    
		    $DetClass = cls::get ( $recPrime->detailClassId );

    	    if ($DetClass instanceof sales_SalesDetails){
    	        
    	        if (is_array($salesWithShipArr)){
	        
    	            if (in_array($recPrime->detailRecId, $salesWithShipArr))continue;
    	        
    	        }
    	    }
			$id = $recPrime->productId;
			
 			if ($rec->compare == 'previous') {
			
 			    if($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos){

			        if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
			        
			            $quantityPrevious = (- 1) * $recPrime->quantity;
			    
    			    } else {
    			        
    			        $quantityPrevious = $recPrime->quantity;
    			   
    			    }
    			    
     			}
 			}
			
			if ($rec->compare == 'year') {   
			    
    			if($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear){
      			    
    			    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
    			        
    			        $quantityLastYear = (- 1) * $recPrime->quantity;
    			        
        			    } else {
        			        
        			        $quantityLastYear = $recPrime->quantity;
    			        
    			    }
    			    
    			}
		
	        }
		
	        if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to){
	            

	            if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
    				
    			    $quantity = (- 1) * $recPrime->quantity;
    				
    			    $primeCost = (- 1) * $recPrime->sellCost * $recPrime->quantity;
    			
    			} else {
    				
    			    $quantity = $recPrime->quantity;
    				
    			    $primeCost = $recPrime->sellCost * $recPrime->quantity;
    			
    			}
	        }
		
	 
			// добавяме в масива събитието
			if (! array_key_exists ( $id, $recs )) {
				
				$recs [$id] = ( object ) array (
						
						'kod' => $recPrime->code ? $recPrime->code : "Art{$recPrime->productId}",
						'measure' => cat_Products::getProductInfo ( $recPrime->productId )->productRec->measureId,
						'productId' => $recPrime->productId,
						'quantity' => $quantity,
						'quantityPrevious' => $quantityPrevious,
						'quantityLastYear' => $quantityLastYear,
						'primeCost' => $primeCost,
						'group' => cat_Products::fetchField($recPrime->productId, 'groups')
						 
				);
			} else {
				$obj = &$recs [$id];
				$obj->quantity += $quantity;
				$obj->quantityPrevious += $quantityPrevious;
				$obj->quantityLastYear += $quantityLastYear;
				$obj->primeCost += $primeCost;
			}
			
			$quantity = $quantityPrevious = $quantityLastYear = 0;
			
		}
	
	//bp($rec->group,$recs);
	     $recs = $this->groupRecs($recs, $rec->group);
		
		
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
	protected function getTableFieldSet($rec, $export = FALSE) 
	{

		$fld = cls::get ( 'core_FieldSet' );
		
		$fld->FLD ( 'kod', 'varchar', 'caption=Код' );
		$fld->FLD ( 'productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул' );
		$fld->FLD ( 'measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered' );
		$fld->FLD ( 'quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Продадено' );
		if ($rec->compare != 'no'){
		
		    $fld->FLD ( 'quantityCompare', 'double(smartRound,decimals=2)', 'smartCenter,caption=Количество->Сравнение' );
		}
		$fld->FLD ( 'primeCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност' );
		if ($export === TRUE) {
			$fld->FLD ( 'group', 'keylist(mvc=cat_groups,select=name)', 'caption=Група' );
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
	protected function detailRecToVerbal($rec, &$dRec) 
	{

		$Int = cls::get ( 'type_Int' );
		$Date = cls::get ( 'type_Date' );
		$Double = cls::get ( 'type_Double' );
		$Double->params ['decimals'] = 2;
		$groArr = array ();
		$row = new stdClass ();
		
		if (isset ( $dRec->kod )) {
			$row->kod = $dRec->kod;
		}
		
		$row->productId = cat_Products::getLinkToSingle_ ( $dRec->productId, 'name' );
		
		if (isset ( $dRec->measure )) {
			$row->measure = cat_UoM::fetchField ( $dRec->measure, 'shortName' );
		}
		
		foreach ( array (
				'quantity',
				'primeCost',
		) as $fld ) {
			$row->{$fld} = $Double->toVerbal ( $dRec->{$fld} );
			if ($dRec->{$fld} < 0) {
			    $row->{$fld} = "<span class='red'>{$dRec->{$fld}}</span>";
			}
		}
		
		if (isset ( $dRec->group )) {
			// и збраната позиция
			$rGroup = keylist::toArray ( $dRec->group );
			foreach ( $rGroup as &$g ) {
				$gro = cat_Groups::getVerbal ( $g, 'name' );
			}
			
			$row->group = $gro;
		}
		
		
		if ($rec->compare == 'previous'){
		    if ($dRec->quantity - $dRec->quantityPrevious > 0){
		    
		        $color = 'green';$marker = '+';
		    }elseif ($dRec->quantity - $dRec->quantityPrevious < 0){
		        
		        $color = 'red';$marker = '';
		    }else {
		        $color = 'black';$marker = '';
		    }
		    $row->quantityCompare = "<span class= {$color}>"."{$marker}" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity - $dRec->quantityPrevious) . "</span>";
		}
		
		if ($rec->compare == 'year'){
		    
		    if ($dRec->quantity - $dRec->quantityLastYear > 0){
		        
		        $color = 'green';$marker = '+';
		    }elseif ($dRec->quantity - $dRec->quantityLastYear < 0){
		        
		        $color = 'red';$marker = '';
		    }else {
		        $color = 'black';$marker = '';
		    }
		    
		    $row->quantityCompare = "<span class= {$color}>"."{$marker}" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity - $dRec->quantityLastYear) . "</span>";
		}
		
		return $row;
		
		
	}
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param frame2_driver_Proto $Driver        	
	 * @param embed_Manager $Embedder        	
	 * @param core_ET $tpl        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array()) 
	{

		$groArr = array ();
		$artArr = array ();
		
		$Date = cls::get ( 'type_Date' );
		
		$row->from = $Date->toVerbal ( $rec->from );
		
		$row->to = $Date->toVerbal ( $rec->to );

		if (isset ( $rec->group )) {
			// избраната позиция
			$groups = keylist::toArray ( $rec->group );
			foreach ( $groups as &$g ) {
				$gro = cat_Groups::getVerbal ( $g, 'name' );
				array_push ( $groArr, $gro );
			}
			
			$row->group = implode ( ', ', $groArr );
		}
		
		if (isset ( $rec->article )) {
			// избраната позиция
			$arts = keylist::toArray ( $rec->article );
			foreach ( $arts as &$ar ) {
				$art = cat_Products::fetchField ( "#id = '{$ar}'", 'name' );
				array_push ( $artArr, $art );
			}
			
			$row->art = implode ( ', ', $artArr );
		}
		
		$arrCompare = array (
				'no' => 'Без сравнение',
				'previous' => 'С предходен период',
				'year' => 'С миналогодишен период' 
		);
		$row->compare = $arrCompare [$rec->compare];
	}
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver        	
	 * @param embed_Manager $Embedder        	
	 * @param core_ET $tpl        	
	 * @param stdClass $data        	
	 */
	protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data) {
		$fieldTpl = new core_ET ( tr ( "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
			                 	<small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small>
			                	<small><div><!--ET_BEGIN contragent-->|Контрагент|*: [#contragent#]<!--ET_END contragent--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи продукти|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN art-->|Артикули|*: [#art#]<!--ET_END art--></div></small>
                                <small><div><!--ET_BEGIN compare-->|Сравнение|*: [#compare#]<!--ET_END compare--></div></small>
                                </fieldset><!--ET_END BLOCK-->" ) );
		
		if (isset ( $data->rec->from )) {
			$fieldTpl->append ( "<b>".$data->row->from."</b>", 'from' );
		}
		
		if (isset ( $data->rec->to )) {
			$fieldTpl->append ( "<b>".$data->row->to."</b>", 'to' );
		}
		
		if ((isset ( $data->rec->dealers)) && ((min ( array_keys ( keylist::toArray ( $data->rec->dealers ) ) ) >= 1))) {
				
			foreach ( type_Keylist::toArray ( $data->rec->dealers ) as $dealer ) {
		
				$dealersVerb .= (core_Users::getTitleById ( $dealer ) . ', ');
			}
				
			$fieldTpl->append ( "<b>".trim ( $dealersVerb, ',  ' )."</b>", 'dealers' );
		} else {
			$fieldTpl->append ("<b>".'Всички'."</b>", 'dealers' );
		}
		
		if (isset ( $data->rec->contragent)) {
		
				$contragentName = (doc_Folders::getTitleById ( $data->rec->contragent ));
			
		
			$fieldTpl->append ( "<b>".$contragentName."</b>", 'contragent' );
		} else {
			$fieldTpl->append ("<b>".'Всички'."</b>", 'contragent' );
		}
		
		if (isset ( $data->rec->group )) {
			$fieldTpl->append ( "<b>".$data->row->group."</b>", 'group' );
		}
		
		if (isset ( $data->rec->article )) {
			$fieldTpl->append ( $data->row->art, 'art' );
		}
		
		if (isset ( $data->rec->compare )) {
			$fieldTpl->append ( "<b>".$data->row->compare."</b>", 'compare' );
		}
		
		$tpl->append ( $fieldTpl, 'DRIVER_FIELDS' );
	}
	
	    /**
	     * Групиране по продуктови групи
	     *
	     * @param array $recs
	     * @param string $group
	     * @param stdClass $data
	     * @return array
	     */
	    private function groupRecs($recs, $group)
	    {
	        $ordered = array();
	
	        $groups = keylist::toArray($group);
	        if (! count($groups)) {
	            return $recs;
	        } else {
	            cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(
	                &$groups
	            ));
	        }
	       // bp($groups);
	        // За всеки маркер
	        foreach ($groups as $grId => $groupName) {
	
	            // Отделяме тези записи, които съдържат текущия маркер
	            $res = array_filter($recs,
	                function (&$e) use($grId, $groupName) {
	                    if (keylist::isIn($grId, $e->group)) {
	                        $e->group = $grId;
	                        return TRUE;
	                    }
	                    return FALSE;
	                });
	
	            if (count($res)) {
	                arr::natOrder($res, 'kod');
	                $ordered += $res;
	            }
	        }
	
	        return $ordered;
	    }
	
	
	
}