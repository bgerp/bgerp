<?php



/**
 * Мениджър на отчети за продукти по групи
 *
 *
 *
 * @category  extrapack
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Движения на материали
 */
class acc_reports_MovementArtRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, rep_acc,rep_cat';

    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('from', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=От,mandatory,after=title');
    	$fieldset->FLD('to', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=До,after=from');
	    $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=to,single=none');
	}
      

	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		$periods = acc_Periods::getCalcedPeriods(TRUE);
		$form->setOptions('from', array('' => '') + $periods);
		$form->setOptions('to', array('' => '') + $periods);
		
		$lastPeriod = acc_Periods::fetchByDate(dt::addMonths(-1, dt::now()));
		$form->setDefault('from', $lastPeriod->id);
	}
	
	
	/**
	 * След изпращане на формата
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_Form $form
	 */
	protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
	{
		$rec = &$form->rec;
		
		if($form->isSubmitted()){
			
			// Проверка имали избрани вложени групи
			if(cat_Groups::checkForNestedGroups($rec->group)){
				$form->setError('group', 'Избрани са вложени групи');
			}
			
			// Размяна, ако периодите са объркани
			if(isset($rec->from) && isset($rec->to)) {
				$from = acc_Periods::fetch($rec->from);
				$to = acc_Periods::fetch($rec->to);
			
				if ($from->start > $to->start) {
					$rec->from = $to->id;
					$rec->to = $from->id;
				}
			}
			
			if(empty($rec->to)){
				$currentPeriod = acc_Periods::fetchByDate(dt::today());
				$rec->to = $currentPeriod->id;
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
	protected function prepareRecs($rec, &$data = NULL)
	{
		$recs = array();
		$itemAll = array();
		
	    // Обръщаме се към продуктите и търсим всички складируеми и неоттеглени продукти
		$query = cat_Products::getQuery();
		$query->where("#state = 'active' OR #state = 'closed'");
		$query->where("#canStore = 'yes'");
		$query->show('id,measureId,code,groups');
		
		if (isset($rec->group)) {
		    $query->likeKeylist("groups", $rec->group);
		}

		$productArr = $query->fetchAll();
		
		$maxTimeLimit = 1.2 * count($productArr);
		$maxTimeLimit = max(array($maxTimeLimit, 300));
		
		// задаваме лимит пропорционален на бр. извадени продукти
		core_App::setTimeLimit($maxTimeLimit);
		
	    // id-to на класа на продуктите
	    $productClassId = cat_Products::getClassId();
	    
	    // Извличат се всички пера
	    $iQuery = acc_Items::getQuery();
	    $iQuery->where("#classId = {$productClassId}");
	    $iQuery->in('objectId', array_keys($productArr));
	    $iQuery->show('id,objectId');
	    while($iRec = $iQuery->fetch()){
	        $itemAll[$iRec->objectId] = $iRec->id;
	    }

		$productItemsFlip = array_flip($itemAll);
		$productItems = $itemAll;
		
    	// Начално количество
    	$baseQuantities = array();
    	
    	// Намира се баланса на началния период
    	$periodRec = acc_Periods::fetch($rec->from);
    	$balanceId = acc_Balances::fetchField("#periodId = {$periodRec->id}", 'id');
    	
    	// Извличат се само записите за сметка 321 с участието на перата на артикулите
    	$bQuery = acc_BalanceDetails::getQuery();
    	$bQuery->show('ent2Id,baseQuantity');
    	acc_BalanceDetails::filterQuery($bQuery, $balanceId, '321', $itemAll);
    	
    	// От баланса извлизаме всички начални количества във всички складове, групирани по артикули
    	while($bRec = $bQuery->fetch()){
    		$productId = $productItemsFlip[$bRec->ent2Id];
    		if(!array_key_exists($productId, $baseQuantities)){
    			$baseQuantities[$productId] = $bRec->baseQuantity;
    		} else {
    			$baseQuantities[$productId] += $bRec->baseQuantity;
    		}
    	}

    	// Извличане на записите от журнала по желанието сметки
    	$jQuery = acc_JournalDetails::getQuery();
    	$from = acc_Periods::fetchField($rec->from, 'start');
    	$to = acc_Periods::fetchField($rec->to, 'end');
    	acc_JournalDetails::filterQuery($jQuery, $from, $to, '321,401,61101,61102,701');
    	$jRecs = $jQuery->fetchAll();
    	
    	$recs = array(); 
    	
    	// за всеки един продукт, се изчисляват търсените количествата
    	foreach ($productArr as $productRec){
    		if($itemId = $productItems[$productRec->id]){ 
    			$baseQuantity = (isset($baseQuantities[$productRec->id])) ? $baseQuantities[$productRec->id] : 0;
    			$obj = (object)array('baseQuantity' => $baseQuantity, 'delivered' => 0, 'converted' => 0, 'sold' => 0, 'blQuantity' => 0);
    			$obj->code = (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}";
    			$obj->measureId = $productRec->measureId; 
    			$obj->productId = $productRec->id;
    			$obj->groups = $productRec->groups;
    			
    			// Доставено: Влязло в склада от доставчици
    			if($delRes = acc_Balances::getBlQuantities($jRecs, '321', 'debit', '401', array(NULL, $itemId, NULL))){ 
    				$obj->delivered = $delRes[$itemId]->quantity;
    			}
    			
    			// Доставено влязло в склада от инвентаризация
    			if($delRes1 = acc_Balances::getBlQuantities($jRecs, '321', 'debit', '799', array(NULL, $itemId, NULL))){
    				$obj->delivered += $delRes1[$itemId]->quantity;
    			}
    			
    			// Вложено детайлно
    			if($convRes = acc_Balances::getBlQuantities($jRecs, '61101', 'debit', '321', array($itemId, NULL, NULL))){
    				$obj->converted = $convRes[$itemId]->quantity;
    			}
    			
    			// Вложено бездетайлно
    			if($convRes1 = acc_Balances::getBlQuantities($jRecs, '321', 'credit', '61102', array(NULL, $itemId, NULL))){
    				$obj->converted += $convRes1[$itemId]->quantity;
    			}
    			
    			// Вложено от инвентаризация
    			if($convRes2 = acc_Balances::getBlQuantities($jRecs, '321', 'credit', '699', array(NULL, $itemId, NULL))){
    				$obj->converted += $convRes2[$itemId]->quantity;
    			}
    			
    			// Приспадане на вложеното с върнатото от производството детайлно
    			if($delRes2 = acc_Balances::getBlQuantities($jRecs, '321', 'debit', '61101', array(NULL, $itemId, NULL))){
    			    $obj->converted -= $delRes2[$itemId]->quantity;
    			}
    			
    			// Приспадане на вложеното с върнатото от производството бездетайлно
    			if($convRes3 = acc_Balances::getBlQuantities($jRecs, '321', 'debit', '61102', array(NULL, $itemId, NULL))){
    				$obj->converted -= $convRes3[$itemId]->quantity;
    			}
    			
    			// Продадено
    			if($soldRes = acc_Balances::getBlQuantities($jRecs, '701', 'debit', '321', array(NULL, NULL, $itemId))){
    				$obj->sold = $soldRes[$itemId]->quantity;
    			}
    			
    			// Крайно количество
    			$obj->blQuantity = $baseQuantity;
    			if($blRes = acc_Balances::getBlQuantities($jRecs, '321', NULL, NULL, array(NULL, $itemId, NULL))){
    				$obj->blQuantity += $blRes[$itemId]->quantity;
    			}
 
    			$recs[$productRec->id] = $obj;
    		}
    	}
    	
    	$data->groupByField = 'groupId';
    	$recs = $this->groupRecs($recs, $rec->group, $data);
    	
		return $recs;
	}
	
	
	/**
	 * Групиране по продуктови групи
	 * 
	 * @param array $recs
	 * @param string $group
	 * @param stdClass $data
	 * @return array 
	 */
	private function groupRecs($recs, $group, $data)
	{
		$ordered = array();
		
		$groups = keylist::toArray($group);
		if(!count($groups)){
			$groups = array('total' => 'Общо');
		} else {
			cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(&$groups));
		}
		 
		$data->totals = array();
		
		// За всеки маркер
		foreach ($groups as $grId => $groupName){
			
			// Отделяме тези записи, които съдържат текущия маркер
			$res = array_filter($recs, function (&$e) use ($grId, $groupName, &$data) {
				if(keylist::isIn($grId, $e->groups) || $grId === 'total'){
					$e->groupId = $grId;
					$data->totals[$e->groupId]['baseQuantity'] += $e->baseQuantity;
					$data->totals[$e->groupId]['blQuantity'] += $e->blQuantity;
					$data->totals[$e->groupId]['delivered'] += $e->delivered;
					$data->totals[$e->groupId]['converted'] += $e->converted;
					$data->totals[$e->groupId]['sold'] += $e->sold;
					return TRUE;
				}
				return FALSE;
			});
			
			if(count($res)){
				arr::natOrder($res, 'code');
				$ordered += $res;
			}
		}
		
		return $ordered;
	}
	
	
	/**
	 * Подготовка на реда за групиране
	 *
	 * @param int $columnsCount   - брой колони
	 * @param string $groupValue  - невербалното име на групата
	 * @param string $groupVerbal - вербалното име на групата
	 * @param stdClass $data      - датата
	 * @return string             - съдържанието на групиращия ред
	 */
	protected function getGroupedTr($columnsCount, $groupValue, $groupVerbal, &$data)
	{
		$baseQuantity = $blQuantity = $delivered = $converted = $sold = '';
		foreach (array('baseQuantity', 'blQuantity', 'delivered', 'converted', 'sold') as $totalFld){
			${$totalFld} = core_Type::getByName('double(decimals=2)')->toVerbal($data->totals[$groupValue][$totalFld]);
			if($data->totals[$groupValue][$totalFld] < 0){
				${$totalFld} = "<span class='red'>{${$totalFld}}</span>";
			}
		}
		
		$groupVerbal = "<td style='padding-top:9px;padding-left:5px;' colspan='3'><b>" . $groupVerbal . "</b></td><td style='text-align:right'><b>{$baseQuantity}</b></td><td style='text-align:right'><b>{$delivered}</b></td><td style='text-align:right'><b>{$converted}</b></td><td style='text-align:right'><b>{$sold}</b></td><td style='text-align:right'><b>{$blQuantity}</b></td>";
	
		return $groupVerbal;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec   - записа
	 * @param boolean $export - таблицата за експорт ли е
	 * @return core_FieldSet  - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
	
		if($export === FALSE){
    		$fld->FLD('code', 'varchar','caption=Код');
    		$fld->FLD('name', 'varchar', 'caption=Артикул');
    		$fld->FLD('measureId', 'varchar', 'caption=Мярка');
    		$fld->FLD('baseQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Начално');
    		$fld->FLD('delivered', 'double(smartRound,decimals=2)', 'caption=Количество->Доставено');
    		$fld->FLD('converted', 'double(smartRound,decimals=2)', 'caption=Количество->Вложено');
    		$fld->FLD('sold', 'double(smartRound,decimals=2)', 'caption=Количество->Продадено');
    		$fld->FLD('blQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Крайно');

		} else { 
			$fld->FLD('code', 'varchar','caption=Код');
    		$fld->FLD('name', 'varchar', 'caption=Артикул');
    		$fld->FLD('measureId', 'varchar', 'smartCenter,caption=Мярка');
    		$fld->FLD('baseQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Начално');
    		$fld->FLD('delivered', 'double(smartRound,decimals=2)', 'caption=Количество->Доставено');
    		$fld->FLD('converted', 'double(smartRound,decimals=2)', 'caption=Количество->Вложено');
    		$fld->FLD('sold', 'double(smartRound,decimals=2)', 'caption=Количество->Продадено');
    		$fld->FLD('blQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Крайно');
		}
	
		return $fld;
	}
	
	
    /**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
	{
		$row = new stdClass();
		
		$isPlain = Mode::is('text', 'plain');
		$Int = cls::get('type_Int');
		$Date = cls::get('type_Date');
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$groArr  = array();
		
		$row->code = $dRec->code;
		$row->name = cat_Products::getVerbal($dRec->productId, 'name');
		
		if(!Mode::is('text', 'plain')){
			$link = cat_Products::getSingleUrlArray($dRec->productId);
			$row->name = ht::createLinkRef($row->name, $link);
		}
		
		$row->measureId = cat_UoM::getShortName($dRec->measureId);
		$row->groupId = ($dRec->groupId !== 'total') ? cat_Groups::getVerbal($dRec->groupId, 'name') : tr('Общо');
		
		foreach(array('baseQuantity', 'delivered', 'converted', 'sold', 'blQuantity') as $fld) {
		    $row->{$fld} = $Double->toVerbal($dRec->{$fld});
		    if($dRec->{$fld} < 0){
		        $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
		    } elseif($dRec->{$fld} == 0){
		    	$row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
		    }
		}
		
		return $row;
	}
    
    
    /**
	 * След вербализирането на данните
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $row
	 * @param stdClass $rec
	 * @param array $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        // Показване на избраните групи
        if(!empty($rec->group)){
        	$groupLinks = cat_Groups::getLinks($rec->group);
        	$row->group = implode(' ', $groupLinks);
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи|*: [#group#]<!--ET_END group--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));

        if(isset($data->rec->from)){
            $fieldTpl->append($data->row->from, 'from');
        }
        
        if(isset($data->rec->to)){
            $fieldTpl->append($data->row->to, 'to');
        }

        if(isset($data->rec->group)){
            $fieldTpl->append($data->row->group, 'group');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     * @return boolean $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
    	return FALSE;
    }
}
