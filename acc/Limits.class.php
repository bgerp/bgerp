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
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	protected $hideListFieldsIfEmpty = 'limitDuration';
	
	
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
    public $loadList = 'plg_Created, plg_RowTools2, acc_WrapperSettings, plg_State2, plg_AlignDecimals2, plg_Search';
    
    
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
     * Кой може да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'accountId,when,startDate,limitDuration,limitQuantity,type,side,sharedUsers=Нотифициране,state';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,accMaster';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accountId', 'key(mvc=acc_Accounts,select=title,allowEmpty)', 'caption=Сметка, silent, mandatory,removeAndRefreshForm=limitQuantity|type|side|item1|item2|item3');
        $this->FLD('startDate', 'datetime(format=smartTime)', 'caption=Начало,mandatory');
        $this->FLD('limitDuration', 'time(suggestions=1 седмица|2 седмици|1 месец|3 месеца|6 месеца|1 година)', 'caption=Продължителност');
        
        $this->FLD('side', 'enum(debit=Дебит,credit=Кредит)', 'mandatory,caption=Лимит->Салдо,input=none');
        $this->FLD('type', 'enum(minimum=Минимум,maximum=Максимум)', 'mandatory,caption=Лимит->Тип,input=none');
        $this->FLD('limitQuantity', 'double(min=0,decimals=2)', 'mandatory,caption=Лимит->Стойност,input=none');
    	
        $this->FLD('item1', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 1, input=none');
        $this->FLD('item2', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 2, input=none');
        $this->FLD('item3', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 3, input=none');
        
        $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране->Потребители,mandatory');
        $this->FLD('when', 'datetime(format=smartTime)', 'caption=Надвишаване,input=none');
        $this->FLD('exceededAmount', 'double(decimals=2)', 'caption=Надвишаване,input=none');
        
        $this->FLD('classId', 'class(interface=acc_RegisterIntf)', 'silent,input=hidden');
        $this->FLD('objectId', 'int', 'silent,input=hidden');
        
        $this->FLD('status', 'enum(normal=Ненадвишен,exceeded=Надвишен)', 'caption=Видимост,input=none,notSorting,notNull,value=normal');
        $this->FLD('state', 'enum(active=Активен,closed=Затворен,)', 'caption=Видимост,input=none,notSorting,notNull,value=active');
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
        $rec = &$form->rec;
        
        if(isset($rec->classId) && isset($rec->objectId)){
        	
        	$Class = cls::get($rec->classId);
        	$accounts = $Class->getLimitAccounts($rec->objectId);
        	
        	if(count($accounts)){
        		$options = array();
        		foreach ($accounts as $sysId){
        			$accId = acc_Accounts::fetchField("#systemId = '{$sysId}'", 'id');
        			$options[$accId] = acc_Accounts::getTitleById($accId, FALSE);
        		}
        		
        		$form->setOptions('accountId', $options);
        		if(count($options) == 1){
        			$form->setDefault('accountId', key($options));
        			$form->setReadOnly('accountId');
        		}
        	}
        }
        
        // Ако е избрана сметка
    	if(isset($form->rec->accountId)){
    		$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    		
    		$form->setField('side', 'input');
    		$form->setField('type', 'input');
    		$form->setField('limitQuantity', 'input');
    		
    		// Показваме номенклатурите във сметката
    		foreach (range(1, 3) as $i){
    			if(isset($accInfo->groups[$i])){
    				$form->setField("item{$i}", "input,caption=Пера->{$accInfo->groups[$i]->rec->name}");
    				$form->setFieldTypeParams("item{$i}", array('lists' => $accInfo->groups[$i]->rec->num));
    			}
    		}
    		
    		// Алп сметката е пасивна - избираме кредит и максимим по дефолт
    		if($accInfo->rec->type == 'passive'){
    			$form->setDefault('side', 'credit');
    			$form->setDefault('type', 'maximum');
    		} else {
    			
    			// Ако е активна или смесена - дебит и минимум
    			$form->setDefault('side', 'debit');
    			$form->setDefault('type', 'minimum');
    		}
    		
    		// Ако лимита идва от корицата на обект
    		if(isset($rec->classId) && isset($rec->objectId)){
    			$form->setField('side', 'input=hidden');
    			
    			// Намираме на коя позиция е перото на обекта и го избираме
    			if($itemRec = acc_Items::fetchItem($rec->classId, $rec->objectId)){
    				foreach (range(1, 3) as $i){
    					if(isset($accInfo->groups[$i])){
    						if(cls::haveInterface($accInfo->groups[$i]->rec->regInterfaceId, $rec->classId)){
    							$form->setDefault("item{$i}", $itemRec->id);
    							$form->setReadOnly("item{$i}");
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Веднъж създаден записа, не може да се сменя сметката
    	if($rec->id){
    		$form->setReadOnly('accountId');
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->classId) && isset($rec->objectId)){
    		$data->form->title = core_Detail::getEditTitle($rec->classId, $rec->objectId, $mvc->singleTitle, $rec->id);
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if($form->isSubmitted()){
    		
    		// Зануляваме датата, на която лимита е бил нарушен
    		$form->rec->when = NULL;
    		$form->rec->exceededAmount = NULL;
    		$form->rec->status = 'normal';
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
    			$row->{"item{$i}"} = $itemRow;
    			$itemsTpl->append("<li>{$itemRow}</li>", 'item');
    		}
    	}
    	
    	$row->accountId .= $itemsTpl->getContent();
    	
    	if(isset($rec->when)){
    		$row->when = "<span style='color:darkred'>{$row->when}</span>";
    		$exceededAmount = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->exceededAmount);
    		$row->when .= "<br>( {$exceededAmount} )";
    	}
    	
    	if($rec->status == 'exceeded') {
    		$row->ROW_ATTR['class'] .= ' state-pending';
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
        $form->FNC("state2", 'enum(all=Всички,active=Активни,closed=Затворени,exceeded=Надвишени)', 'caption=Вид,input');
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->showFields = 'search,account,state2';
        
        $form->input();
        
        if(isset($form->rec)){
        	
        	if($form->rec->account){
        		$data->query->where(array("#accountId = [#1#]", $form->rec->account));
        	}
        	
        	if($searchState = $form->rec->state2){
        		if($searchState != 'all' && $searchState != 'exceeded'){
        			$data->query->where(array("#state = '[#1#]'", $searchState));
        		}elseif($searchState == 'exceeded'){
        			$data->query->where(array("#status = '[#1#]'", $searchState));
        		}
        	}
        }
        
        // Ceo и accMaster Може да вижда всички записи, иначе само тези до които е споделен
        if(!haveRole('ceo,accMaster')){
        	$cu = core_Users::getCurrent();
        	$data->query->like('sharedUsers', "|{$cu}|");
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->query->orderBy('#state=ASC,#status=DESC');
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
    	
    	if($action == 'add' || $action == 'edit' || $action == 'delete'){
    		if(isset($rec->objectId) && isset($rec->classId)){
    			$item = acc_Items::fetchItem($rec->classId, $rec->objectId);
    			if(!$item){
    				$requiredRoles = 'no_one';
    			} else {
    				if($item->state == 'closed'){
    					$requiredRoles = 'no_one';
    				} else {
    					$requiredRoles = cls::get($rec->classId)->getRequiredRoles('addacclimits');
    				}
    			}
    		}
    	}
    	
    	if($action == 'add' && isset($rec)){
    		if(!isset($rec->objectId) || !isset($rec->classId)){
    			$requiredRoles = 'ceo,accMaster';
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!haveRole('ceo,accMaster')){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    	
    	if(haveRole('ceo,accMaster')){
    		$data->toolbar->addBtn('Проверка', array($mvc, 'checkLimits'), 'ef_icon=img/16/arrow_refresh.png,title=Проверка на зададените лимити');
    	}
    	
    	
    	bgerp_Notifications::clear(array($mvc, 'list'));
    }
    	
    	
    /**
     * Еkшън проверяващ дали лимитите са надвишени
     */
    function act_checkLimits()
    {
    	requireRole('ceo,accMaster');
    	
    	core_Users::forceSystemUser();
    	$this->cron_CheckAccLimits();
    	core_Users::cancelSystemUser();
    	
    	// Записваме, че потребителя е разглеждал този списък
    	$this->logRead("Проверка на счетоводните лимити");
    	
    	return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * Метод по разписание, който проверява дали поставените лимити са нарушени
     */
    function cron_CheckAccLimits()
    {
    	// Кой е последния баланс
    	$balanceId = acc_Balances::getLastBalance()->id;
    	
    	// Ако няма баланс не правим нищо
    	if(!$balanceId) return;
    	
    	// Ако няма записи не правим нищо
    	if(!acc_Limits::count()) return;
    	
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
    	
    	$sendNotificationsTo = array();
    	
    	$now = dt::now();
    	while($rec = $newQuery->fetch()){
    		
    		// Ако има зададена продължителност
    		if(isset($rec->limitDuration)){
    			$endDate = dt::addSecs($rec->limitDuration, $rec->startDate);
    			
    			// И крайната дата е минала, деактивираме лимита и продължаваме напред
    			if($endDate < $now){
    				$rec->state = 'closed';
    				$this->save($rec);
    				continue;
    			}
    		}
    		
    		// Ще групираме данните от баланса
    		$accInfo = acc_Accounts::getAccountInfo($rec->accountId);
    		$groupedRec = new stdClass();
    		$groupedRec->blQuantity = $groupedRec->blAmount = 0;
    		
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
    				$groupedRec->blQuantity += $bRec->blQuantity;
    				$groupedRec->blAmount += $bRec->blAmount;
    			}
    		}
    		
    		// Запомняме кои са неразмерните пера и определяме имали избрано размерно перо
    		$notDimensionalItems = array();
    		$hasDimensionalItemSelected = FALSE;
    		foreach (range(1, 3) as $i){
    			if(isset($rec->{"item{$i}"})){
    				if($accInfo->groups[$i]->rec->isDimensional == 'yes'){
    					$hasDimensionalItemSelected = TRUE;
    				} else {
    					$itemName = acc_Items::getVerbal($rec->{"item{$i}"}, 'title');
    					$notDimensionalItems[$itemName] = $itemName;
    				}
    			}
    		}
    		
    		// Ако има размерно перо ще проверяваме крайното количевство, иначе крайната сума
    		$fieldToCompare = ($hasDimensionalItemSelected === TRUE) ? $groupedRec->blQuantity : $groupedRec->blAmount;
    		
    		// Сравняваме стойността спрямо зададения лимит
    		if($rec->type == 'minimum'){
    			$sendNotification = abs($fieldToCompare) < $rec->limitQuantity;
    		} else {
    			$sendNotification = abs($fieldToCompare) > $rec->limitQuantity;
    		}
    		
    		// Ако има надвишаване изпращаме нотифификация
    		if($sendNotification === TRUE){
    			
    			// Ако лимита вече е бил надвишен, ъпдейтваме сумата с която е надвишен
    			$rec->exceededAmount = abs(abs($fieldToCompare) - $rec->limitQuantity);
    			
    			// Ако досега не е бил надвишен отбелязваме го като такъв
    			if(!isset($rec->when)){
    				
    				// Обновяваме записа с информация, че е бил надвишен
    				$rec->when = $now;
    				$rec->status = 'exceeded';
    				
    				$sharedUsers = keylist::toArray($rec->sharedUsers);
    				
    				// Запомняме по кои не-размерни пера е имало надвишаване
    				foreach ($sharedUsers as $userId){
    					if(!array_key_exists($userId, $sendNotificationsTo)){
    						$sendNotificationsTo[$userId] = array();
    					}
    					 
    					$sendNotificationsTo[$userId] = array_merge($sendNotificationsTo[$userId], $notDimensionalItems);
    				}
    			}
    			
    			$this->save($rec, 'when,status,exceededAmount');
    		} else {
    			
    			// Ако е имало надвишаване но вече няма
    			if(isset($rec->when)){
    				$rec->status = 'normal';
    				$rec->when = NULL;
    				$rec->exceededAmount = NULL;
    				$this->save($rec, 'when,exceededAmount,status');
    			}
    		}
    	}
    	
    	// На всеки потребител, който трябва да се нотифицира, нотифицираме го
    	if(count($sendNotificationsTo)){
    		foreach ($sendNotificationsTo as $userId => $items){
    			
    			$msg = "|Има надвишаване на ограничения|*";
    			if(count($items)){
    				$msg .= " |в|* '" . implode(', ', $items) . " ... '";
    			}
    			
    			$urlArr = $customUrl = array($this, 'list');
    			bgerp_Notifications::add($msg, $urlArr, $userId, 'normal', $customUrl);
    		}
    	}
    }
}