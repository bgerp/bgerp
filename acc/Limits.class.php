<?php



/**
 * acc_Limists модел за определяне на счетоводни лимити
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Limits extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Лимити";
    
    
    /**
     * Заглавие
     */
    public $singleTitle = "Лимит";
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, acc_WrapperSettings, plg_State2, plg_AlignDecimals2, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'accountId,item1,item2,item3';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,accMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,acc';
    
    
    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'id,accountId,startDate,limitDuration,debitQuantityMin=Дебит->Минимум,debitQuantityMax=Дебит->Максимум,creditQuantityMin=Кредит->Минимум,creditQuantityMax=Кредит->Максимум,when,sharedUsers=Нотифициране,state';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,accMaster';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка, silent, mandatory,removeAndRefreshForm=debitQuantityMin|debitQuantityMax|creditQuantityMin|creditQuantityMax|item1|item2|item3');
        $this->FLD('startDate', 'datetime(format=smartTime)', 'caption=Начална дата,mandatory');
        $this->FLD('limitDuration', 'time', 'caption=Продължителност');
        
        $this->FLD('debitQuantityMin', 'double', 'input=hidden,caption=Дебит->Минимум');
    	$this->FLD('debitQuantityMax', 'double', 'input=hidden,caption=Дебит->Максимум');
    	$this->FLD('creditQuantityMin', 'double', 'input=hidden,caption=Кредит->Минимум');
    	$this->FLD('creditQuantityMax', 'double', 'input=hidden,caption=Кредит->Максимум');
    	
        $this->FLD('item1', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 1, input=none');
        $this->FLD('item2', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 2, input=none');
        $this->FLD('item3', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 3, input=none');
        
        $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране->Потребители,mandatory');
        $this->FLD('when', 'date', 'caption=Надвишаване,input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->setDefault('startDate', dt::now());
    	
        // Ако е избрана сметка
    	if(isset($form->rec->accountId)){
    		$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    		
    		// Показваме номенклатурите във сметката
    		foreach (range(1, 3) as $i){
    			if(isset($accInfo->groups[$i])){
    				$form->setField("item{$i}", "input,caption=Пера->{$accInfo->groups[$i]->rec->name}");
    				$form->setFieldTypeParams("item{$i}", array('lists' => $accInfo->groups[$i]->rec->num));
    			}
    		}
    		
    		// Кои полета за минимум и максимум ще показваме, определяме спрямо типа на сметката
    		switch($accInfo->rec->type){
    			case 'active':
    				
    				// На активните сметки ще се следят дебитните количества
    				$fieldsToShow = array('debitQuantityMin', 'debitQuantityMax');
    				break;
    			case 'passive':
    				
    				// На пасивните сметки ще се следят кредитните количества
    				$fieldsToShow = array('creditQuantityMin', 'creditQuantityMax');
    				break;
    			case 'transit':
    			case 'dynamic':
    				
    				// За останалите може да се следят и дебитните и кредитните количества
    				$fieldsToShow = array('debitQuantityMin', 'debitQuantityMax', 'creditQuantityMin', 'creditQuantityMax');
    				break;
    		}
    		
    		// Показваме само нужните полета
    		foreach ($fieldsToShow as $fld){
    			$form->setField($fld, 'input');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if($form->isSubmitted()){
    		
    		// Проверяваме колко количества са попълнени
    		$hasFilledLimit = FALSE;
    		foreach (array('debitQuantityMin', 'debitQuantityMax', 'creditQuantityMin', 'creditQuantityMax') as $fld){
    			if(isset($rec->{$fld})){
    				$hasFilledLimit = TRUE;
    			}
    		}
    		
    		// Трябва да е попълнено поне едно количество
    		if($hasFilledLimit === FALSE){
    			$form->setError('debitQuantityMin,debitQuantityMax,creditQuantityMin,creditQuantityMax', 'Няма зададени ограничения');
    		}
    		
    		// Зануляваме датата, на която лимита е бил нарушен
    		$form->rec->when = NULL;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->accountId = acc_Balances::getAccountLink($rec->accountId, NULL, TRUE, TRUE);
    	$itemsTpl = new ET("<ul style='margin-top:3px;margin-bottom:3px'>[#item#]</ul>");
    	
    	foreach (range(1, 3) as $i){
    		if(isset($rec->{"item{$i}"})){
    			$itemRow = acc_Items::getVerbal($rec->{"item{$i}"}, 'titleLink');
    			$itemsTpl->append("<li>{$itemRow}</li>", 'item');
    		}
    	}
    	
    	$row->accountId .= $itemsTpl->getContent();
    	
    	if(isset($rec->when)){
    		$row->when = "<span style='color:darkred'>{$row->when}</span>";
    		//$row->ROW_ATTR['style'] = 'background-color:#F78787';
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        $form->view = 'horizontal';
        
        $form->FNC('account', 'acc_type_Account(allowEmpty)', 'caption=Сметка,input');
        $form->FNC("state2", 'enum(all=Всички,active=Активни,closed=Затворени,breached=Нарушени)', 'caption=Вид,input');
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->showFields = 'search,account,state2';
        
        $form->input();
        
        if(isset($form->rec)){
        	if($form->rec->account){
        		$data->query->where(array("#accountId = [#1#]", $form->rec->account));
        	}
        	
        	if($searchState = $form->rec->state2){
        		if($searchState != 'all'){
        			if($searchState == 'active' || $searchState == 'closed'){
        				$data->query->where(array("#state = '[#1#]'", $searchState));
        			} elseif($searchState == 'breached'){
        				$data->query->where("#when IS NOT NULL");
        			}
        		}
        	}
        }
        
        // Ceo и accMaster Може да вижда всички записи, иначе само тези до които е споделен
        if(!haveRole('ceo,accMaster')){
        	$cu = core_Users::getCurrent();
        	$data->query->like('sharedUsers', "|{$cu}|");
        }
        
        $data->query->orderBy('id', 'ASC');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'list'){
    		
    		// Ако няма ceo,accMaster проверяваме дали има споделени записи до потребителя
    		if(!core_Users::haveRole('ceo,accMaster', $userId)){
    			$query = static::getQuery();
    			$query->like('sharedUsers', "|{$userId}|");
    			
    			// Ако няма той няма достъп до лист изгледа
    			if(!$query->count()){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    //function act_Test()
  //  {
    	//$this->cron_CheckAccLimits();
   // }
    
    
    /**
     * Метод по разписание, който проверява дали поставените лимити са нарушени
     */
    function cron_CheckAccLimits()
    {
    	// Кой е последния баланс
    	$balanceId = acc_Balances::getLastBalance()->id;
    	
    	// Ако няма баланс не правим нищо
    	if(!$balanceId) return;
    	
    	// Кои сметки имат зададени лимити
    	$limitedAccounts = array();
    	$query = $this->getQuery();
    	$query->show('accountId');
    	$query->groupBy('accountId');
    	while($rec = $query->fetch()){
    		$sysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    		$limitedAccounts[$sysId] = $sysId;
    	}
    	
    	// Ако няма зададени лимити не правим нищо
    	if(!count($limitedAccounts)) return;
    	
    	// Взимаме данните от последния баланс, филтрирано по сметките по които има лимити
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $balanceId, $limitedAccounts);
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	$balanceArr = $bQuery->fetchAll();
    	
    	// Намираме всички активни ограничения
    	$newQuery = $this->getQuery();
    	$newQuery->where("#state != 'closed'");
    	while($rec = $newQuery->fetch()){
    		
    		// Ще групираме данните от баланса
    		$groupedRec = new stdClass();
    		$groupedRec->creditQuantity = $groupedRec->debitQuantity = 0;
    		
    		// За всеки запис от баланса
    		if(count($balanceArr)){
    			foreach ($balanceArr as $bRec){
    				 
    				// Ако сметката е различна, пропускаме
    				if($bRec->accountId != $rec->accountId) continue;
    				 
    				// За перата от 1 до 3, ако са зададени и на записа са различни, записа не участва в групирането
    				if(isset($rec->item1) && $rec->item1 != $bRec->ent1Id) continue;
    				if(isset($rec->item2) && $rec->item2 != $bRec->ent2Id) continue;
    				if(isset($rec->item3) && $rec->item3 != $bRec->ent3Id) continue;
    				 
    				// Сумираме количеството на дебита и на кредита
    				$groupedRec->debitQuantity += $bRec->debitQuantity;
    				$groupedRec->creditQuantity += $bRec->creditQuantity;
    			}
    		}
    		
    		$sendNotification = FALSE;
    		
    		// Проверяваме дали поставените ограничения са нарушени
    		foreach (array('debit', 'credit') as $type){
    			if(isset($rec->{"{$type}QuantityMin"})){
    				if($groupedRec->{"{$type}Quantity"} < $rec->{"{$type}QuantityMin"}){
    					$sendNotification = TRUE;
    				}
    			}
    			
    			if(isset($rec->{"{$type}QuantityMax"})){
    				if($groupedRec->{"{$type}Quantity"} > $rec->{"{$type}QuantityMax"}){
    					$sendNotification = TRUE;
    				}
    			}
    		}
    	
    		// Ако има надвишаване изпращаме нотифификация
    		if($sendNotification === TRUE){
    			$rec->when = dt::today();
    			$this->save($rec, 'when');
    			
    			$sharedUsers = keylist::toArray($rec->sharedUsers);
    			
    			// Всеки споделен потребител, нотифицираме го
    			foreach ($sharedUsers as $userId){
    				$urlArr = $customUrl = array();
    				//bgerp_Notifications::add('love', $urlArr, $userId, 'normal', $customUrl);
    			}
    		} else {
    			
    			// Ако е имало надвишаване но вече няма
    			if(isset($rec->when)){
    				$rec->when = NULL;
    				$this->save($rec, 'when');
    			}
    		}
    	}
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    protected static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	// От колоните за к-ва, проверяваме кои от тях не трябва да се показват
    	$debitQuantityMin = $debitQuantityMax = $creditQuantityMin = $creditQuantityMax = FALSE;
    	foreach ($data->recs as $rec){
    		foreach (array('debitQuantityMin', 'debitQuantityMax', 'creditQuantityMin', 'creditQuantityMax') as $fld){
    			if(isset($rec->$fld)){
    				${$fld} = TRUE;
    			}
    		}
    	}
    	
    	// Скриваме някой колони, ако всички записи нямат стойност за тях
    	foreach (array('debitQuantityMin', 'debitQuantityMax', 'creditQuantityMin', 'creditQuantityMax') as $var){
    		if(${$var} === FALSE){
    			unset($data->listFields[$var]);
    		}
    	}
    }
}