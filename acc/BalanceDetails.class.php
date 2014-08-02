<?php



/**
 * Мениджър на записите в баланс
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceDetails extends core_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_Wrapper, Accounts=acc_Accounts, Lists=acc_Lists, plg_StyleNumbers, plg_AlignDecimals,plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "accountNum, accountId, baseQuantity, baseAmount, 
                        debitQuantity, debitAmount,    creditQuantity, creditAmount, 
                        blQuantity, blAmount";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'balanceId';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * Временен акумулатор при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $balance;
    
    
    /**
     * Временен акумолатор за извлечената история за перата
     * 
     * @var array
     */
    private $history;
    
    
    /**
     * Брой записи от историята на страница
     */
    var $listHistoryItemsPerPage = 30;
    
    
    /**
     *
     * Стратегии на сметките - използва се при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $strategies;
    
    
    /**
     * Кой има достъп до хронологичната справка
     */
    var $canHistory = 'powerUser';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('balanceId', 'key(mvc=acc_Balances)', 'caption=Баланс');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption=Сметка->име,column=none');
        $this->EXT('accountNum', 'acc_Accounts', 'externalName=num,externalKey=accountId', 'caption=Сметка->№');
        $this->FLD('ent1Id', 'key(mvc=acc_Items,title=titleLink)', 'caption=Сметка->перо 1');
        $this->FLD('ent2Id', 'key(mvc=acc_Items,title=titleLink)', 'caption=Сметка->перо 2');
        $this->FLD('ent3Id', 'key(mvc=acc_Items,title=titleLink)', 'caption=Сметка->перо 3');
        $this->FLD('baseQuantity', 'double', 'caption=База->Количество,tdClass=ballance-field');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума,tdClass=ballance-field');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->Количество,tdClass=ballance-field');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Дебит->Сума,tdClass=ballance-field');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->Количество,tdClass=ballance-field');
        $this->FLD('creditAmount', 'double(decimals=2)', 'caption=Кредит->Сума,tdClass=ballance-field');
        $this->FLD('blQuantity', 'double', 'caption=Салдо->Количество,tdClass=ballance-field');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Салдо->Сума,tdClass=ballance-field');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($mvc->isDetailed()) {
            // Детайлизиран баланс на конкретна аналитична сметка
            $mvc->prepareDetailedBalance($data);
        } else {
            // Обобщен баланс на синтетичните сметки
            $mvc->prepareOverviewBalance($data);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        if ($mvc->isDetailed() && $groupingForm = $mvc->getGroupingForm($data->masterId)) {
            $data->groupingForm = $groupingForm;
        	if($data->groupingForm->isSubmitted()){
        		$mvc->doGrouping($data, (array)$data->groupingForm->rec);
        	}
        }
        
        // При детайлна справка, и потребителя няма роли acc, ceo скриваме
        // записите до които няма достъп
        if($mvc->isDetailed() && !haveRole('ceo,acc')){
        	$recs = &$data->recs;
        	$rows = &$data->rows;
        	
        	if(empty($rows)) return;
        	
        	foreach ($rows as $id => $row){
        		
        		// Ако потребителя не може да вижда записа него показваме
        		if(!$mvc->canReadRecord($recs[$id])){
        			unset($rows[$id]);
        		}
        	}
        }
        
        $mvc->prepareSummary($data);
    }
    
    
    /**
     * Подготвя обобщаващите данни
     */
    private function prepareSummary(&$data)
    {
    	if(!count($data->recs)) return;
    	if(!$this->isDetailed()) return;
    	
    	$recs = $data->recs;
    	
    	$arr = array('debitAmount', 'creditAmount', 'baseAmount', 'blAmount');
    	$debitQuantity = $debitAmount = $creditAmount = $baseQuantity = $baseAmount = $blAmount =  0;
    	foreach ($recs as $rec){
    		foreach ($arr as $param){
    			${$param} += $rec->{$param};
    		}
    	}
    	
    	$data->summary = new stdClass();
    	foreach ($arr as $param){
    		$data->summary->$param = $this->getFieldType($param)->toVerbal(${$param});
    		if(${$param} < 0){
    			$data->summary->$param = "<span style='color:red'>{$data->summary->$param}</span>";
    		}
    	}
    }
    
    
    /**
     * Дали потребителя може да вижда детайл от баланса, може ако има достъп
     * до всички негови пера
     * 
     * @param stdClass $rec - запис от модела
     * @return boolean 
     */
    private function canReadRecord($rec)
    {
    	foreach (range(1,3) as $i){
        	$ent = $rec->{"ent{$i}Id"};
        	if(empty($ent)) continue;
        	
        	$itemRec = acc_Items::fetch($ent);
        	
    		if($itemRec->classId){
    			$AccRegMan = cls::get($itemRec->classId);
    			
    			if($AccRegMan->haveRightFor('single', $itemRec->objectId)){
    				return TRUE;
    			}
    		}
        }
        
        return FALSE;
    }
    
    
    /**
     * Групира записите на баланс по зададен признак. Данните се групират в тримерен масив
     * с индекси избраното свойство за позицията или ако няма съответното перо
     *
     * @param stdClass $data
     */
    private function doGrouping(&$data, $by)
    {
        
        // Ако няма записи не правим нищо
    	if(!count($data->recs)) return;
        
    	$show = $groupedBy = array();
		$Varchar = cls::get('type_Varchar');
    	
    	// Намираме избраните свойства/пера
    	foreach (range(1, 3) as $i){
        	if($by["grouping{$i}"]){
        		$show[$i] = $by["grouping{$i}"];
        	}
        	if($by["feat{$i}"]){
        		$groupedBy[$i] = $by["feat{$i}"];
        		$data->listFields["ent{$i}Id"] = $Varchar->toVerbal($groupedBy[$i]);
        	}
        }
       
        // Ако няма филтриране или групиране не правим нищо
        if(!count($show) && !count($groupedBy)) return;
        
        // Ако има избрано филтриране, махаме тези записи които нямат съответното перо
        if(count($show)){
        	foreach ($data->recs as $id => $rec){
        		foreach (range(1, 3) as $i){
        			if(isset($show[$i]) && $rec->{"ent{$i}Id"} != $show[$i]){
        				unset($data->recs[$id]);
        				break;
        			}
        		}
        	}
        }
        
        // Извличаме всички записани свойства за показваните пера
        $queryFeat = acc_Features::getQuery();
    	$queryFeat->in('itemId', static::$cache);
    	$featuresArr = $queryFeat->fetchAll();
    	
    	// Групираме записите спрямо стойностите на избраните свойства
        $groupedRecs = $groupedIdx = array();
        foreach ($data->recs as $rec) {
            $f = array(1 => NULL, 2 => NULL, 3 => NULL);
           
            // За всяко групиране
            foreach ($groupedBy as $i => $feature) {
            	$itemId = $rec->{"ent{$i}Id"};
            	
            	// Намираме стойността съответстваща на избраното свойство
            	$result = array_filter($featuresArr, function($val) use ($itemId, $feature) {
    													if($val->feature == $feature && $val->itemId == $itemId){
									    					return $val;
									    				}});
	            // Изпозлваме за индекс на групиране
				$f[$i] = $result[key($result)]->value;
	            if(empty($f[$i])){
	            	
	            	// Ако няма стойност влиза в други
	            	$f[$i] = 'others';
	            }
            }
            
            // Индекса за съответната позиция е избраното свойство ако има иначе перото
            foreach($f as $i => &$fi){
            	if(empty($fi)){
            		$ent = $rec->{"ent{$i}Id"};
            		$fi = ($ent) ? "entryId-" . $rec->{"ent{$i}Id"} : NULL;
            	}
            }
            
            // Записваме в масив с индекс стойностите на груприането за всяко перо
            $r = &$groupedIdx[$f[1]][$f[2]][$f[3]];
            
            // Ако някой от индексите е не е перо
            if (!isset($r)) {
            	foreach (range(1, 3) as $i){
            		if(strpos($f[$i], 'entryId-') === false){
            			$r["grouping{$i}"] = $f[$i];
            		}
            	}
            	
                $groupedRecs[] = &$r;
            }
            
            // За всички позиции за които няма стойства показваме перата им
            foreach (range(1, 3) as $i){
            	if(!isset($r["grouping{$i}"])){
            		$r["ent{$i}Id"] = $rec->{"ent{$i}Id"};
            	}
            }
            
            // Групиране на данните
            $r['accountNum'] 	  = $rec->accountNum;
            $r['balanceId']       = $rec->balanceId;
            
            // Събираме числовите данни
            foreach (array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
            	
            	// Ако е NULL полето да не се добавя
            	if (!is_null($rec->$fld)) {
            		$r[$fld] += $rec->$fld;
            	}
            }
        }
       
        // Ако има филтриране по свойства, не показваме бутона за хронология
        if(count($groupedBy)){
        	unset($data->listFields['history']);
        }
       
        $data->recs = $groupedRecs;
        
        // Конвертираме групираните записи към вербални стойности
        $data->rows = array();
        foreach ($data->recs as &$rec) {
        	$rec = (object)$rec;
            $data->rows[] = $this->recToVerbal($rec, $data->listFields);
        }
        
        // Задействаме събитие в plg_StyleNumbers да за да се оцветят отрицателните числа
        plg_StyleNumbers::on_AfterPrepareListRows($this, $data);
    } 
    
    
    /**
     * Подготовка за обобщен баланс на синтетичните сметки
     *  
     * @param StdClass $data
     */
    private function prepareOverviewBalance($data)
    {
        $data->query->where('#ent1Id IS NULL AND #ent2Id IS NULL AND #ent3Id IS NULL');
        $data->query->orderBy('#accountNum', 'ASC');
        
        $data->listFields = array(
            'accountNum' => 'Сметка->№',
            'accountId' => 'Сметка->Име',
            'debitAmount' => 'Обороти->Дебит',
            'creditAmount' => 'Обороти->Кредит',
            'baseAmount' => 'Салдо->Начално',
            'blAmount' => 'Салдо->Крайно',
        );
    }
    
    
    /**
     * Подготовка за детайлизиран баланс на конкретна аналитична сметка,
     * евентуално групиран по зададени признаци
     *
     * @param StdClass $data
     */
    private function prepareDetailedBalance($data)
    { 
        // Кода по-надолу има смисъл само за детайлизиран баланс, очаква да има фиксирана
        // сметка.
        expect($this->Master->accountRec);
        
        $data->query->where("#accountId = {$this->Master->accountRec->id}");
        $data->query->where('#ent1Id IS NOT NULL OR #ent2Id IS NOT NULL OR #ent3Id IS NOT NULL');
       
        $groupingForm = $this->getGroupingForm($data->masterId);
        
        // Извличаме записите за номенклатурите, по които е разбита сметката
        $listRecs = array();
        $registers = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
            }
        }
        
        $data->listFields = array();
        $data->listFields['history'] = ' ';
        
        /**
         * Указва дали редом с паричните стойности да се покажат и колони с количества.
         *
         * Количествата има смисъл да се виждат само за сметки, на които поне една от
         * аналитичностите е измерима.
         *
         * @var boolean true - показват се и количества, false - не се показват
         */
        $bShowQuantities = FALSE;
        
        foreach ($listRecs as $i=>$listRec) {
            $bShowQuantities = $bShowQuantities || ($listRec->isDimensional == 'yes');
            
            
                $data->listFields["ent{$i}Id"] = "|*" . acc_Lists::getVerbal($listRec, 'name');
                
                if (!$flag) {
                    // Не можем да използваме следните редове повече от веднъж, това е проблем
                    $flag = TRUE;
                    $data->query->EXT("ent{$i}Num", 'acc_Items', "externalName=num,externalKey=ent{$i}Id");
                    $data->query->orderBy("#ent{$i}Num", 'ASC');
                }
        }
        
        if ($bShowQuantities) {
            $data->listFields += array(
                'baseQuantity' => 'Начално салдо->ДК->К-во',
                'baseAmount' => 'Начално салдо->ДК->Сума',
                'debitQuantity' => 'Обороти->Дебит->К-во',
                'debitAmount' => 'Обороти->Дебит->Сума',
                'creditQuantity' => 'Обороти->Кредит->К-во',
                'creditAmount' => 'Обороти->Кредит->Сума',
                'blQuantity' => 'Крайно салдо->ДК->К-во',
                'blAmount' => 'Крайно салдо->ДК->Сума',
            );
        } else {
            $data->listFields += array(
                'baseAmount' => 'Салдо->Начално',
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'blAmount' => 'Салдо->Крайно',
            );
        }
    }
    
    
    /**
     * Лека промяна в детайл-layout-а: лентата с инструменти е над основната таблица, вместо под нея.
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderDetailLayout($mvc, &$res, $data)
    {
        $res = new ET("
        	[#ListToolbar#]</div>
        	[#ListSummary#]
        	<div class='clearfix21'></div>
            [#ListTable#]
        ");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnPrint');
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($mvc->isDetailed()) {
            if ($data->groupingForm) {
            	if(!$tpl){
            		$tpl = new ET("");
            	}
		    	$tpl->push(('acc/js/balance.js'), 'JS');
		    	jquery_Jquery::run($tpl, "chosenrefresh();");
    	
                $tpl->append($data->groupingForm->renderHtml(), 'ListToolbar');
            }
        }
    }
    
    
    /**
     * Създаване и подготовка на формата за групиране.
     * 
     * Формата предлага двойка полета за всяка аналитичност, от първото може да се избира перо от
     * номенклатурата за филтриране а от второто свойство на перото
     *
     * @param int $balanceId ИД на баланса, в контекста на който се случва това
     */
    private function getGroupingForm($balanceId)
    {
        expect($this->Master->accountRec);
        
    	static $form;
        
        if (isset($form)) {
            return $form;
        }
        
    	foreach (range(1, 3) as $i) {
        	if ($groupId = $this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($groupId);
            }
        }
        
    	if(empty($listRecs)) return;
        
    	$form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->class = 'simpleForm';
        $form->fieldsLayout = getTplFromFile("acc/tpl/BalanceFilterFormFields.shtml");
        $form->FNC("accId", 'int', 'silent,input=hidden');
        $form->input("accId", true);
        $showFields = '';
        foreach ($listRecs as $i => $listRec) {
        	$this->setGroupingForField($i, $listRec, $form);
        }
        $form->showFields = trim($form->showFields, ',');
        
        $form->input(null, true);
        
        if($form->isSubmitted()){
        	foreach (range(1,3) as $i){
        		if($form->rec->{"grouping{$i}"} && $form->rec->{"feat{$i}"}){
        			$form->setError("grouping{$i},feat{$i}", "Не може да са избрани едновременно перо и свойтво за една позиция");
        		}
        	}
        }
        
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,style=margin-top:6px;');
        
        return $form;
    }
    
    
    /**
     * Подготвя полетата за филтриране
     */
    private function setGroupingForField($i, $listRec, &$form)
    {
    	$form->formAttr['id'] = 'groupForm';
    	$options = acc_Items::makeArray4Select(NULL, "#lists LIKE '%|{$listRec->id}|%' AND #state = 'active'");
        static::$cache = array_merge(static::$cache, array_keys($options));
    	
    	$features = acc_Features::getFeatureOptions(array_keys($options));
        $features = array('' => '') + $features;
    	$options = $options;
    	
    	$listName = acc_Lists::getVerbal($listRec, 'name');
    	$form->fieldsLayout->replace($listName, "caption{$i}");
    	$form->FNC("grouping{$i}", 'key(mvc=acc_Items,allowEmpty)', "silent,caption={$listName},width=330px,input,class=balance-grouping");//, array('attr' => array('onchange' => "document.forms['groupForm'].elements['feat{$i}'].value ='';")));
        $form->FNC("feat{$i}", 'varchar', "silent,caption={$listName}->Свойства,width=330px,input,class=balance-feat");//, array('attr' => array('onchange' => "document.forms['groupForm'].elements['grouping{$i}'].value ='';")));
        $form->setOptions("grouping{$i}", $options);
        $form->setOptions("feat{$i}", $features);
        $form->showFields .= "grouping{$i},";
        if(count($features) > 1){
        	 $form->showFields .= "feat{$i}," ;
        }
    }
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $masterRec = $mvc->Master->fetch($rec->balanceId);
    	
        // Бутон за детайлизиран преглед на историята
        $histImg = ht::createElement('img', array('src' => sbf('img/16/clock_history.png', '')));
        
        if($rec->accountId){
        	$row->accountId = acc_Balances::getAccountLink($rec->accountId, $masterRec, FALSE, TRUE);
        }
        
        $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->accountNum);
        
        if(!$mvc->isDetailed()) return;
        
        $url = array('acc_BalanceDetails', 'History', 'fromDate' => $masterRec->fromDate, 'toDate' => $masterRec->toDate, 'accNum' => $rec->accountNum, 'ent1Id' => $rec->ent1Id, 'ent2Id' => $rec->ent2Id, 'ent3Id' => $rec->ent3Id);
        $row->history = ht::createLink($histImg, $url, NULL, 'title=Подробен преглед');
        $row->history = "<span style='margin:0 4px'>{$row->history}</span>";
        
        $groupingRec = $mvc->getGroupingForm($rec->balanceId)->rec;
        
        foreach (range(1, 3) as $i) {
        	if(isset($rec->{"grouping{$i}"})){
        		$row->{"ent{$i}Id"} = $rec->{"grouping{$i}"};
        		if($row->{"ent{$i}Id"} == 'others'){
        			$row->{"ent{$i}Id"} = "<i>" . tr('Други') . "</i>";
        		}
        	}
        }
    }
    
    
    /**
     * Дали разглеждаме детайлизираната справка
     */
    private function isDetailed()
    {
        return !empty($this->Master->accountRec);
    }
    

    /**
     * Записва баланса в таблицата
     */
    function saveBalance($balanceId)
    {
		if(count($this->balance)) {
			foreach ($this->balance as $accId => $l0) {
				foreach ($l0 as $ent1 => $l1) {
					foreach ($l1 as $ent2 => $l2) {
						foreach ($l2 as $ent3 => $rec) {
							$rec['balanceId'] = $balanceId;
							$this->save((object)$rec);
						}
					}
				}
			}
		}
        
        unset($this->balance, $this->strategies);
    }
    
    
    /**
     * Зарежда в сингълтона баланса с посоченото id
     */
    function loadBalance($balanceId, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
        $query = $this->getQuery();
        
        static::filterQuery($query, $balanceId, $accs, $itemsAll, $items1, $items2, $items3);
        $query->where('#blQuantity != 0 OR #blAmount != 0');
        
        while ($rec = $query->fetch()) { 
            $accId = $rec->accountId;
            $ent1Id = !empty($rec->ent1Id) ? $rec->ent1Id : null;
            $ent2Id = !empty($rec->ent2Id) ? $rec->ent2Id : null;
            $ent3Id = !empty($rec->ent3Id) ? $rec->ent3Id : null;
            
            if ($strategy = $this->getStrategyFor($accId, $ent1Id, $ent2Id, $ent3Id)) {
               
            	// "Захранваме" обекта стратегия с количество и сума
                $strategy->feed($rec->blQuantity, $rec->blAmount);
            }
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            $b['baseQuantity'] += $rec->blQuantity;
            $b['baseAmount'] += $rec->blAmount;
            $b['blQuantity'] += $rec->blQuantity;
            $b['blAmount'] += $rec->blAmount;
        }
    }
    
    
    /**
     * Изчислява стойността на счетоводен баланс за зададен период от време.
     *
     * @param string $from дата в MySQL формат
     * @param string $to дата в MySQL формат
     */
    function calcBalanceForPeriod($from, $to)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        acc_JournalDetails::filterQuery($query, $from, $to);
        $query->orderBy('valior,id', 'ASC');
        
        while ($rec = $query->fetch()) {
        	
            $this->calcAmount($rec);
        	$this->addEntry($rec, 'debit');
            $this->addEntry($rec, 'credit');
            $update = FALSE;
            
            // След като се изчисли сумата, презаписваме цените в журнала, само ако сметките са размерни
            if($rec->debitQuantity){
            	$debitPrice = round($rec->amount / $rec->debitQuantity, 4);
            	if(trim($rec->debitPrice) != trim($debitPrice)){
            		$rec->debitPrice = $debitPrice;
            		$update = TRUE;
            	}
            }
            
            if($rec->creditQuantity){
            	$creditPrice = round($rec->amount / $rec->creditQuantity, 4);
            	if(trim($rec->creditPrice) != trim($creditPrice)){
            		$rec->creditPrice = $creditPrice;
            		$update = TRUE;
            	}
            }
            
            // Обновява се записа само ако има промяна с цената
            if($update){
            	$JournalDetails->save($rec);
            }
        }
    }
    
    
    /**
     * Попълва с адекватна стойност с полето $rec->amount, в случай, че то е празно.
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     */
    private function calcAmount($rec)
    {
        $debitStrategy = $creditStrategy = NULL;
        
        // Намираме стратегиите на дебит и кредит с/ките (ако има)
        $debitStrategy = $this->getStrategyFor(
            $rec->debitAccId,
            $rec->debitItem1,
            $rec->debitItem2,
            $rec->debitItem3
        );
    	
        $creditStrategy = $this->getStrategyFor(
            $rec->creditAccId,
            $rec->creditItem1,
            $rec->creditItem2,
            $rec->creditItem3
        );
        
        if ($creditStrategy) {
            // Кредитната сметка има стратегия.
            // Ако е активна, извличаме цена от стратегията
            // Ако е пасивна - "захранваме" стратегията с данни;
            // (точно обратното на дебитната сметка)
            switch ($this->Accounts->getType($rec->creditAccId)) {
                case 'active' :
                	
                	if ($amount = $creditStrategy->consume($rec->creditQuantity)) {
                        $rec->amount = $amount;
                    }
                	
                	$creditStrategy->feed(-1 * $rec->creditQuantity, -1 * $rec->amount);
                    break;
                case 'passive' :
                	
                    $creditStrategy->feed($rec->creditQuantity, $rec->amount);
                    break;
            }
        }
        
        if ($debitStrategy) {
            // Дебитната сметка има стратегия.
            // Ако е активна, "захранваме" стратегията с данни;
            // Ако е пасивна - извличаме цена от стратегията
            
            switch ($this->Accounts->getType($rec->debitAccId)) {
                case 'active' :
                	
                    $debitStrategy->feed($rec->debitQuantity, $rec->amount);
                    break;
                case 'passive' :
                    if ($amount = $debitStrategy->consume($rec->debitQuantity)) {
                        $rec->amount = $amount;
                    }
                	
                    $debitStrategy->feed(-1 * $rec->debitQuantity, -1 * $rec->amount);
                    break;
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function &getStrategyFor($accountId, $ent1Id, $ent2Id, $ent3Id)
    {
        $e1 = !empty($ent1Id) ? $ent1Id : null;
        $e2 = !empty($ent2Id) ? $ent2Id : null;
        $e3 = !empty($ent3Id) ? $ent3Id : null;
        
        $strategy = NULL;
        
        if (isset($this->strategies[$accountId][$e1][$e2][$e3])) {
            // Имаме вече създаден обект-стратегия
            $strategy = $this->strategies[$accountId][$e1][$e2][$e3];
        } elseif (isset($this->strategies[$accountId]) &&
            $this->strategies[$accountId] === false) {
            // Тази сметка вече е била "питана" за стратегия (дебитна или кредитна) и
            // резултатът е бил отрицателен. За това си спестяваме ново питане - гарантирано е, 
            // че отговорът отново ще бъде същият.
            $strategy = FALSE;
        } elseif ($strategy = $this->Accounts->createStrategyObject($accountId)) {
            // Има стратегия - записваме инстанцията й.
            $this->strategies[$accountId][$e1][$e2][$e3] = &$strategy;
        } else {
            // Няма стратегия. И това не зависи от перата. За да спестим бъдещи извиквания,
            // записваме false
            $this->strategies[$accountId] = FALSE;
        }
        
        return $strategy;
    }
    
    
    /**
     * Добавя дебитната или кредитната част на ред от транзакция (@see acc_JournalDetails)
     * в баланса
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     * @param string $type 'debit' или 'credit'
     */
    private function addEntry($rec, $type)
    {
        expect(in_array($type, array('debit', 'credit')));
        
        $quantityField = "{$type}Quantity";

        $sign = ($type == 'debit') ? 1 : -1;
        
        $accId = $rec->{"{$type}AccId"};
        
        $ent1Id = !empty($rec->{"{$type}Item1"}) ? $rec->{"{$type}Item1"} : NULL;
        $ent2Id = !empty($rec->{"{$type}Item2"}) ? $rec->{"{$type}Item2"} : NULL;
        $ent3Id = !empty($rec->{"{$type}Item3"}) ? $rec->{"{$type}Item3"} : NULL;
        
        if ($ent1Id != NULL || $ent2Id != NULL || $ent3Id != NULL || $this->historyFor) {
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
 
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
            
            // Ако е посочено за кои пера да се помнят записите
            if($this->historyFor && $accId == $this->historyFor['accId'] && $ent1Id == $this->historyFor['item1'] && $ent2Id == $this->historyFor['item2'] && $ent3Id == $this->historyFor['item3']){
            	
            	$this->history[$rec->id] = array('id'            => $rec->id, 
            							         'docType'       => $rec->docType, 
            					                 'docId'         => $rec->docId,
            					                 "{$type}Amount" => $rec->amount,
            					                 $quantityField  => $rec->{$quantityField},
            					                 'blQuantity'    => $b['blQuantity'],
            					                 'blAmount'      => $b['blAmount'],
            					                 'reason'        => $rec->reason,
            					                 'valior'		 => $rec->valior);
            }
        }
       
        for ($accNum = $this->Accounts->getNumById($accId); !empty($accNum); $accNum = substr($accNum, 0, -1)) {
            if (!($accId = $this->Accounts->getIdByNum($accNum))) {
                continue;
            }
            
            $b = &$this->balance[$accId][null][null][null];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = NULL;
            $b['ent2Id'] = NULL;
            $b['ent3Id'] = NULL;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
    }
    
    
    /**
     * Ако вторият аргумент е с празна (empty()) стойност - не прави нищо. В противен случай
     * увеличава стойността на първия аргумент със стойността на втория.
     *
     * Ако $v = '', $add = '', то след $v += $add, $v ще има стойност нула. Целта на този метод
     * е стойността на $v да остане непроменена (празна) в такива случаи.
     *
     * @param number $v
     * @param mixed $add
     */
    private function inc(&$v, $add)
    {
        if (!empty($add)) {
            $v += $add;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява ръчното манипулиране на записи
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (!in_array($action, array('list', 'read', 'history'))){
            $requiredRoles = 'no_one';
        }
        
        if($action == 'history' && isset($rec)){
        	if(!haveRole('ceo, acc') && !$mvc->canReadRecord($rec)){
        		$requiredRoles = 'no_one';
        	}
        }
    }


    /**
     * Компресира диапазона на id-tata
     */
    function cron_CompressIds()
    {
     //   set @id:=0;
     //   update mytable
     //   set id = (@id := @id + 1)
     //   order by id;
    }
    
    
    /**
     * Филтрира заявка към модела за показване на определени данни
     * 
     * @param core_Query $query - Заявка към модела
     * @param mixed $accs       - списък от систем ид-та на сметките
     * @param mixed $itemsAll   - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1     - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2     - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3     - списък с пера, от които поне един може да е на трета позиция
     * @return array            - масив със всички извлечени записи
     */
	public static function filterQuery(core_Query &$query, $id, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
    	expect($query->mvc instanceof acc_BalanceDetails);
    	
    	// Трябва да има поне една зададена сметка
    	$accounts = arr::make($accs);
    	
    	if(count($accounts) >= 1){
	    	foreach ($accounts as $sysId){
		    	$query->orWhere("#accountNum = {$sysId}");
		    }
    	}
    	
	    // ... само детайлите от последния баланс
	    $query->where("#balanceId = {$id}");
	    
	    // Перата които може да са на произволна позиция
    	$itemsAll = arr::make($itemsAll);
    	
    	if(count($itemsAll)){
    		foreach ($itemsAll as $itemId){
    			
    			// Трябва да инт число
    			expect(ctype_digit($itemId));
    			
    			// .. и перото да участва на произволна позиция
		    	$query->where("#ent1Id = {$itemId}");
		    	$query->orWhere("#ent2Id = {$itemId}");
		    	$query->orWhere("#ent3Id = {$itemId}");
    		}
    	}
    	
    	// Проверка на останалите параметри от 1 до 3
    	foreach (range(1, 3) as $i){
    		$var = ${"items{$i}"};
    		
    		// Ако е NULL продалжаваме
    		if(!$var) continue;
    		$varArr = arr::make($var);
    		
    		// За перата се изисква поне едно от тях да е на текущата позиция
    		$j = 0;
    		foreach($varArr as $itemId){
    			$or = ($j == 0) ? FALSE : TRUE;
    			$query->where("#ent{$i}Id = {$itemId}", $or);
    			$j++;
    		}
    	}
    }
    
    
    /**
     * Екшън за показване историята на перата
     */
    public function act_History()
    {
    	$this->requireRightFor('history');
    	$this->currentTab = 'Хронология';
    	
    	expect($accNum = Request::get('accNum', 'int'));
    	expect($accId = acc_Accounts::fetchField("#num = '{$accNum}'", 'id'));
    	
    	$from = Request::get('fromDate');
    	$to = Request::get('toDate');
    	
    	$ent1 = Request::get('ent1Id', 'int');
    	if($ent1){
    		expect(acc_Items::fetch($ent1));
    	}
    	
    	$ent2 = Request::get('ent2Id', 'int');
    	if($ent2){
    		expect(acc_Items::fetch($ent2));
    	}
    	
    	$ent3 = Request::get('ent3Id', 'int');
    	if($ent3){
    		expect(acc_Items::fetch($ent3));
    	}
    	
    	$bQuery = $this->Master->getQuery();
    	$cloneQuery = clone $bQuery;
    	$bQuery->where("#fromDate >= '{$from}' && #toDate <= '{$to}'");
    	$bQuery->orderBy('id', 'ASC');
    	if($balanceId = $bQuery->fetch()->id){
    		$balanceRec = $this->Master->fetch($balanceId);
    	}
    	
    	$this->title = 'Хронологична справка';
    	
    	// Подготвяне на данните
    	$data = new stdClass();
    	
    	$data->rec = new stdClass();
    	$data->rec->accountId = $accId;
    	$data->rec->ent1Id = $ent1;
    	$data->rec->ent2Id = $ent2;
    	$data->rec->ent3Id = $ent3;
    	$data->rec->accountNum = $accNum;
    	
    	$this->requireRightFor('history', $data->rec);
    	
    	$data->balanceRec = $balanceRec;
    	$data->fromDate = $from;
    	$data->toDate = $to;
    	
    	// Подготовка на филтъра
    	$this->prepareHistoryFilter($data);
    	
    	// Подготовка на историята
    	$this->prepareHistory($data);
    	
    	// Рендиране на историята
    	$tpl = $this->renderHistory($data);
    	$tpl = $this->renderWrapping($tpl);
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
	/**
     * Изчислява стойността на счетоводен баланс за зададен период от време
     * за зададените сметки
     *
     * @param mixed $accs   - списък от систем ид-та на сметките
     * @param mixed $items1 - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2 - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3 - списък с пера, от които поне един може да е на трета позиция
     */
    function prepareDetailedBalanceForPeriod($from, $to, $accs = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL, $history = FALSE, $pager)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        $cloneQuery = clone $query;
        $cloneQuery->show('id,valior');
        
        // Филтриране на заявката да показва само записите от журнал за тази сметка
        acc_JournalDetails::filterQuery($query, $from, $to, $accs);
        
        $query->orderBy('valior,id', 'ASC');
        
        // Филтриране на копието, за показване на записите за тези пера
        acc_JournalDetails::filterQuery($cloneQuery, $from, $to, $accs, NULL, $items1, $items2, $items3, TRUE); 
        $cloneQuery->orderBy('valior,id', 'DESC');
        
        // Добавяне на странициране
        if($pager){
        	$pager->setLimit($cloneQuery);
        	
        	// Кои записи трябва да се показват
        	$displayedEntries = $cloneQuery->fetchAll();
        }
        
        // Изчисляване на сумите според стратегиите ако има, 
        // за да е всичко точно са ни нужни нефилтрираните записи
        while ($rec = $query->fetch()) {
            @$this->calcAmount($rec);
            $this->addEntry($rec, 'debit');
            $this->addEntry($rec, 'credit');
        }
        
        // В $history са всички излечени записи, в $recs ще са само тези които ще се показват
    	$this->recs = $this->history;
       
    	// Ако има записи, които трябва да се помнят, се проверява за всеки от тях
        // Дали присъства на страницата, ако не го махаме
        if(count($this->recs) && count($displayedEntries)){
        	foreach ($this->recs as $id => $rec){
        		if(!array_key_exists($id, $displayedEntries)){
        			unset($this->recs[$id]);
        		}
        	}
        }
        
        if(count($this->recs)){
        	 // Обръщаме историята в низходящ ред по дата, след изчисленията
        	$this->recs = array_reverse($this->recs);
        }
    }
    
    
    /**
     * Подготовка на историята за перара
     * 
     * @param stdClass $data
     */
    private function prepareHistory(&$data)
    {
    	$rec = &$data->rec;
    	$balanceRec = $data->balanceRec;
    	
    	// Подготвяне на данните на записа
    	$Date = cls::get('type_Date');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	// Подготовка на вербалното представяне
    	$row = new stdClass();
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	$accountRec = acc_Accounts::fetch($rec->accountId);
    	
    	foreach(range(1, 3) as $i){
    		if ($accountRec->{"groupId{$i}"} && $rec->{"ent{$i}Id"}) {
    			$row->{"ent{$i}Id"} = acc_Items::fetchField($rec->{"ent{$i}Id"}, 'titleLink');
    		}
    	}
    	
    	$data->row = $row;
    	
    	// Подготовка на пейджъра
    	$this->listItemsPerPage = $this->listHistoryItemsPerPage;
    	$this->prepareListPager($data);
    	
    	// Намиране на най-стария баланс можеш да послужи за основа на този
    	$balanceBefore = $this->Master->getBalanceBefore($data->fromDate);
    	
    	if($balanceBefore){
    		// Зареждаме баланса за посочения период с посочените сметки
    		$this->loadBalance($balanceBefore->id, $rec->accountNum, NULL, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id);
    	}
    	
    	// Запомняне за кои пера ще показваме историята
    	$this->historyFor = array('accId' => $rec->accountId, 'item1' => $rec->ent1Id, 'item2' => $rec->ent2Id, 'item3' => $rec->ent3Id);
    	
    	// Извличане на всички записи към избрания период за посочените пера
    	$accSysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    	$this->prepareDetailedBalanceForPeriod($data->fromDate, $data->toDate, $accSysId, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, TRUE, $data->pager);
    	
    	$b = $this->balance[$rec->accountId][$rec->ent1Id][$rec->ent2Id][$rec->ent3Id];
    	
    	$rec->baseAmount = $b['baseAmount'];
    	$rec->baseQuantity = $b['baseQuantity'];
    	
    	$rec->blAmount = $b['blAmount'];
    	$rec->blQuantity = $b['blQuantity'];
    	$row->blAmount = $Double->toVerbal($rec->blAmount);
    	$row->blQuantity = $Double->toVerbal($rec->blQuantity);
    	
    	$row->baseAmount = $Double->toVerbal($rec->baseAmount);
    	$row->baseQuantity = $Double->toVerbal($rec->baseQuantity);
    	
    	if($rec->baseAmount < 0){
    		$row->baseAmount = "<span style='color:red'>{$row->baseAmount}</span>";
    	}
    	if($rec->baseQuantity < 0){
    		$row->baseQuantity = "<span style='color:red'>{$row->baseQuantity}</span>";
    	}
    	
    	// Нулевия ред е винаги началното салдо
    	$zeroRec = array('docId'      => "Баланс", 
    					 'valior'	  => $data->fromDate,
    					 'reason'	  => 'Начално салдо',
    					 'blAmount'   => $rec->baseAmount, 
    					 'blQuantity' => $rec->baseQuantity,
    					 'ROW_ATTR'   => array('style' => 'background-color:#eee;font-weight:bold'));
    	
    	// Нулевия ред е винаги началното салдо
    	$lastRec = array('docId'      => "Баланс",
    					 'valior'	  => $data->toDate,
    					 'reason'	  => 'Крайно салдо',
    					 'blAmount'   => $rec->blAmount, 
    					 'blQuantity' => $rec->blQuantity,
    					 'ROW_ATTR'   => array('style' => 'background-color:#eee;font-weight:bold'));
    	
    	$data->recs = $this->recs;
    	unset($this->recs);
    	
    	// Добавяне на началното и крайното салдо към цялата история
    	(count($this->history)) ? array_unshift($this->history, $zeroRec) : $this->history[] = $zeroRec;
    	$this->history[] = $lastRec;
    	
    	if($data->pager->page == 1){
	    	// Добавяне на нулевия ред към историята
	    	if(count($data->recs)){
	    		array_unshift($data->recs, $lastRec);
	    	} else {
	    		$data->recs = array($lastRec);
	    	}
    	}
    	
    	// Ако сме на единствената страница или последната, показваме началното салдо
    	if($data->pager->page == $data->pager->pagesCount || $data->pager->pagesCount == 0){
    		$data->recs[] = $zeroRec;
    	}
    	
    	// Подготвя средното салдо
    	$this->prepareMiddleBalance($data);
    	
    	// За всеки запис, обръщаме го във вербален вид
    	if(count($data->recs)){
    		foreach ($data->recs as $jRec){
    			$data->rows[] = $this->getVerbalHistoryRow($jRec, $Double, $Date);
    		}
    	}
    }
    
    
    /**
     * Изчислява средното салдо
     */
    private function prepareMiddleBalance(&$data)
    {
    	$recs = $this->history;
    	
    	$tmpArray = array();
    	$quantity = $amount = 0;
    	
    	// Създаваме масив с ключ валйора на документа, така имаме списък с 
    	// последните записи за всяка дата
    	if(count($recs)){
	    	foreach ($recs as $rec){
	    		$tmpArray[$rec['valior']] = $rec;
	    	}
    	}
    	
    	// Нулираме му ключовете за по-лесно обхождане
    	$tmpArray = array_values($tmpArray);
    	if(count($tmpArray)){
    		
    		// За всеки запис
    		foreach ($tmpArray as $id => $arr){
    			
    			// Ако не е последния елемент
    			if($id != count($tmpArray)-1){
    				
    				// Взимаме дните между следващата дата и текущата от записа
    				$value = dt::daysBetween($tmpArray[$id+1]['valior'], $arr['valior']);
    			} else {
    				
    				// Ако сме на последната дата
    				$value = 1;
    			}
    			
    			// Умножяваме съответните количества по дните разлика
    			$quantity += $value * $arr['blQuantity'];
    			$amount += $value * $arr['blAmount'];
    		}
    	}
    	
    	// Колко са дните в избрания период
    	$daysInPeriod = dt::daysBetween($data->toDate, $data->fromDate) + 1;
    	
    	// Средното салдо е събраната сума върху дните в периода
    	@$data->rec->midQuantity = $quantity / $daysInPeriod;
    	@$data->rec->midAmount = $amount / $daysInPeriod;
    	
    	// Вербално представяне на средното салдо
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$data->row->midQuantity = $Double->toVerbal($data->rec->midQuantity);
    	$data->row->midAmount = $Double->toVerbal($data->rec->midAmount);
    }
    
    
    /**
     * Подготвя филтъра на историята на перата
     */
    private function prepareHistoryFilter(&$data)
    {
    	$data->listFilter = cls::get('core_Form');
    	$data->listFilter->method = 'GET';
    	$filter = &$data->listFilter;
    	$filter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$filter->class = 'simpleForm';
    	
    	$filter->FNC('fromDate', 'date', 'caption=От,input,width=15em');
    	$filter->FNC('toDate', 'date', 'caption=До,input,width=15em');
    	$filter->FNC('accNum', 'int', 'input=hidden');
    	$filter->FNC('ent1Id', 'int', 'input=hidden');
    	$filter->FNC('ent2Id', 'int', 'input=hidden');
    	$filter->FNC('ent3Id', 'int', 'input=hidden');
    	$filter->showFields = 'fromDate,toDate';
    	
    	$filter->setDefault('accNum', $data->rec->accountNum);
    	$filter->setDefault('ent1Id', $data->rec->ent1Id);
    	$filter->setDefault('ent2Id', $data->rec->ent2Id);
    	$filter->setDefault('ent3Id', $data->rec->ent3Id);
    	
    	$optionsTo = $optionsFrom = array();
    	
    	// За начална и крайна дата, слагаме по подразбиране, датите на периодите
    	// за които има изчислени оборотни ведомости
    	$balanceQuery = acc_Balances::getQuery();
    	$balanceQuery->orderBy("#fromDate", "ASC");
    	
    	while($bRec = $balanceQuery->fetch()){
    		$bRow = acc_Balances::recToVerbal($bRec, 'periodId,id,fromDate,toDate,-single');
    		$optionsFrom[$bRec->fromDate] = $bRow->periodId . " ({$bRow->fromDate})";
    		$optionsTo[$bRec->toDate] = $bRow->periodId . " ({$bRow->toDate})";
    	}
    	
    	$filter->setOptions('fromDate', $optionsFrom);
    	$filter->setOptions('toDate', $optionsTo);
    	$filter->setDefault('fromDate', $data->fromDate);
    	$filter->setDefault('toDate', $data->toDate);
    	
    	// Активиране на филтъра
        $filter->input();
        if($filter->isSubmitted()){
        	if($filter->rec->fromDate > $filter->rec->toDate){
        		$filter->setError('fromDate,toDate', 'Началната дата е по-голяма от крайната');
        	}
        }
        
        // Ако има изпратени данни
        if($filter->rec){
        	if($filter->rec->from){
        		$data->fromDate = $filter->rec->from;
        	}
        	
        	if($filter->rec->to){
        		$data->toDate = $filter->rec->to;
        	}
        }
    }
    
    
    /**
     * Подготовка на вербалното представяне на един ред от историята
     */
    private function getVerbalHistoryRow($rec, $Double, $Date)
    {
    	$arr['valior'] = $Date->toVerbal($rec['valior']);
    	
    	// Ако има отрицателна сума показва се в червено
    	foreach (array('debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
    		
    		$arr[$fld] = $Double->toVerbal($rec[$fld]);
    		if($rec[$fld] < 0){
    			$arr[$fld] = "<span style='color:red'>{$arr[$fld]}</span>";
    		}	
    	}
		
    	try{
    		$Class = cls::get($rec['docType']);
	    	$arr['docId'] = $Class->getLink($rec['docId']);
	    	$arr['reason'] = $Class->getContoReason($rec['docId']);
	    } catch(Exception $e){
	    	if(is_numeric($rec['docId'])){
	    		$arr['docId'] = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
	    	} else {
	    		$arr['docId'] = $rec['docId'];
	    	}
	    }
    	
	    if($rec['ROW_ATTR']){
	    	$arr['ROW_ATTR'] = $rec['ROW_ATTR'];
	    }
    	
    	return (object)$arr;
    }
    
    
    /**
     * Рендиране на историята
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    private function renderHistory(&$data)
    {
    	// Взимаме шаблона за историята
    	$tpl = getTplFromFile('acc/tpl/SingleLayoutBalanceHistory.shtml');
    	
    	if(!Mode::is('printing')){
    		if(acc_Balances::haveRightFor('read')){
    			if(empty($data->balanceRec->id)){
    				$btn = ht::createErrBtn("Обобщена", "Невалиден период");
    			} else {
    				$btn = ht::createBtn("Обобщена", array($this->Master, 'single', $data->balanceRec->id, 'accId' => $data->rec->accountId), FALSE, FALSE, "row=2,title=Обобщена оборотна ведомост");
    			}
    			
	    		$tpl->append($btn, 'SingleToolbar');
    		}
    		
    		$printUrl = getCurrentUrl();
	    	$printUrl['Printing'] = 'yes';
	    	$printBtn = ht::createBtn('Печат', $printUrl, FALSE, TRUE, 'id=btnPrint,row=2,ef_icon = img/16/printer.png,title=Печат на страницата');
	    	$tpl->append($printBtn, 'SingleToolbar');
    	}
    	
    	// Проверка дали всички к-ва равнят на сумите  
    	$equalBl = TRUE;
    	if(count($data->rows)){
    		foreach ($data->rows as $row){
    			if($row->blQuantity != $row->blAmount){
    				$equalBl = FALSE;
    			}
    		}
    	}
    	
    	// Подготвяме таблицата с данните извлечени от журнала
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$data->listFields = array('valior'   	   => 'Вальор',
                				  'docId'          => 'Документ',
    							  'reason'		   => 'Забележки',
                				  'debitQuantity'  => 'Дебит->К-во',
                			      'debitAmount'    => 'Дебит->Сума',
                				  'creditQuantity' => 'Кредит->К-во',
                				  'creditAmount'   => 'Кредит->Сума',
                				  'blQuantity'     => 'Остатък->К-во',
                				  'blAmount'       => 'Остатък->Сума',
            );
        
        // Ако равнят не показваме количествата
        if($equalBl){
        	unset($data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
        }
            
        // Ако сумите на крайното салдо са отрицателни - оцветяваме ги
        $details = $table->get($data->rows, $data->listFields);
        
        foreach (array('blQuantity', 'blAmount', 'midQuantity', 'midAmount') as $fld){
        	if($data->rec->$fld < 0){
        		$data->row->$fld = "<span style='color:red'>{$data->row->$fld}</span>";
        	}
        }
        
        $tpl->placeObject($data->row);
        
        // Добавяне в края на таблицата, данните от журнала
        $tpl->replace($details, 'DETAILS');
        
        // Рендиране на филтъра
        $tpl->append($this->renderListFilter($data), 'listFilter');
        
        // Рендиране на пейджъра
        if($data->pager){
        	$tpl->append($this->renderListPager($data));
        }
        
        // Връщаме шаблона
    	return $tpl; 
    }
    
    
	/**
	 * След рендиране на List Summary-то
	 */
	static function on_AfterRenderListSummary($mvc, &$tpl, $data)
    {
    	if($data->summary){
    		$table = getTplFromFile('acc/tpl/BalanceSummary.shtml');
    		$table->placeObject($data->summary);
    		
    		if(empty($tpl)){
    			$tpl = new ET("");
    		}
    		
    		$tpl->append($table, 'ListSummary');
    	}
    }
}
