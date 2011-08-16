<?php
/**
 * Клас 'acc_Lists' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_Lists extends core_Manager {
	/**
	 * @todo Чака за документация...
	 */
	var $loadList = 'acc_Wrapper, Items=acc_Items,plg_RowTools,plg_State2, plg_Sorting';
	
	/**
	 * @todo Чака за документация...
	 */
	var $canAdmin = 'admin,acc';
	
	/**
	 * @todo Чака за документация...
	 */
	var $title = 'Номенклатури';
	
	/**
	 * @todo Чака за документация...
	 */
	var $currentTab = 'acc_Lists';
	
	/**
	 * Инстанция на детайл-мениджъра на пера.
	 *
	 * @var acc_Items
	 */
	var $Items;
	
	/**
	 * @todo Чака за документация...
	 */
	var $rowToolsField = 'tools';
	
	/**
	 * @todo Чака за документация...
	 */
	var $listFields = 'num,nameLink=Наименование,regInterfaceId,dimensional,itemsCnt,itemMaxNum,lastUseOn,tools=Пулт';
	
	/**
	 * Описание на модела (таблицата)
	 */
	function description() {
		// Трибуквен, уникален номер
		$this->FLD('num', 'int(3,size=3)', 'caption=Номер,remember=info,mandatory,notNull,export');
		
		// Име на номенклатурата
		$this->FLD('name', 'varchar', 'caption=Номенклатура,mandatory,remember=info,mandatory,notNull,export');
		
		// Интерфейс, който трябва да поддържат класовете, генериращи пера в тази номенклатура
		$this->FLD('regInterfaceId', 'interface(suffix=AccRegIntf,allowEmpty)', 'caption=Интерфейс,export');
		
		// Дали перата в номенклатурата имат размерност (измерими ли са?). 
		// Например стоките и продуктите са измерими, докато контрагентите са не-измерими
		$this->FLD('dimensional', 'enum(no=Не,yes=Да)', 'caption=Измерима,remember,mandatory,export');
		
		// Колко пера има в тази номенклатура?
		$this->FLD('itemsCnt', 'int', 'caption=Пера->Брой,input=none,export');
		
		// Максимален номер използван за перата
		$this->FLD('itemMaxNum', 'int', 'caption=Пера->Макс. ном.,input=none,export');
		
		// Последно използване
		$this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=none');
		
		// Състояние на номенклатурата
		$this->FLD('state', 'enum(active=Активна,closed=Затворена)', 'caption=Състояние,input=none,export');
		
		// Заглавие 
		$this->FNC('caption', 'html', 'column=none');
		
		// Титла - хипервръзка
		$this->FNC('nameLink', 'html', 'column=none');
		
		// Уникални индекси
		$this->setDbUnique('num');
		$this->setDbUnique('name');
	}
	
	/**
	 * Изчислява полето 'caption', като конкатинира номера с името на номенклатурата
	 */
	function on_CalcCaption($mvc, $rec) {
		$rec->caption = $mvc->getVerbal($rec, 'name') . "&nbsp;(" . $mvc->getVerbal($rec, 'num') . ")";
	}
	
	/**
	 * Изчислява полето 'nameLink', като име с хипервръзка към перата от тази номенклатура
	 */
	function on_CalcNameLink($mvc, $rec) {
		$name = $mvc->getVerbal($rec, 'name');
		
		$rec->nameLink = ht::createLink($name, array ('acc_Items', 'list', 'listId' => $rec->id ));
	}
	
	/**
	 * @todo Чака за документация...
	 */
	function fetchByName($name) {
		return $this->fetch(array ("#name = '[#1#]' COLLATE utf8_general_ci", $name ));
	}
	
	/**
	 * Изпълнява се преди запис на номенклатурата
	 */
	function on_BeforeSave($mvc, $id, $rec) {
		if (! $rec->id) {
			$rec->itemCount = 0;
		}
	}
	
	/**
	 * Извиква се след изчисляването на необходимите роли за това действие
	 */
	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL) {
		if ($action == 'delete') {
			if ($rec->id && ! isset($rec->itemsCnt)) {
				$rec = $mvc->fetch($rec->id);
			}
			
			if ($rec->itemsCnt || $rec->lastUseOn) {
				$requiredRoles = 'no_one';
			}
		}
	}
	
	/**
	 * Изпълнява се след подготовка на формата за редактиране
	 */
	function on_AfterPrepareEditForm($mvc, $data) {
		if ($data->form->rec->id && $data->form->rec->itemsCnt) {
			//            $data->form->setReadonly('regInterfaceId');
			$data->form->setReadonly('dimensional');
		}
	}
	
	/**
	 * Предизвикава обновяване на обобщената информация за
	 * номенклатура с посоченото id
	 */
	static function updateSummary($id) {
		$self = cls::get(__CLASS__); // Би било излишно, ако getQuery() стане static
		$rec = $self->fetch($id);
		
		$itemsQuery = $self->Items->getQuery();
		$itemsQuery->where("#state = 'active'");
		$itemsQuery->where("#lists LIKE '%|{$id}|%'");
		$rec->itemsCnt = $itemsQuery->count();
		
		$itemsQuery->XPR('maxNum', 'int', 'max(#num)');
		
		$rec->itemMaxNum = $itemsQuery->fetch()->maxNum;
		
		$self->save($rec);
	}
	
	/**
	 * Изпълнява се преди подготовката на показваните редове
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data) {
		$data->query->orderBy('num');
	}
	
	
	/**
	 * Метода зарежда данни за инициализация от CSV файл
	 */
<<<<<<< HEAD
=======
	function getFeatures($rec) {
		$result = FALSE;
		
		if ($register = $this->getRegisterInstance($rec)) {
			$result = $register->getFeatures();
		}
		
		return $result;
	}
	
	/**
	 * @todo Чака за документация...
	 */
	function getGroupOf($rec, $itemId, $featureId) {
		$featureValue = NULL;
		
		if ($register = $this->getRegisterInstance($rec)) {
			$featureObj = $register->features [$featureId];
			$objectId = $this->Items->fetchField($itemId, 'objectId');
			$featureValue = $featureObj->valueOf($objectId);
		}
		
		return $featureValue;
	}
	
	/**
	 * @todo Чака за документация...
	 */
	function getItemsByGroup($rec, $featureId, $featureValue) {
		$ids = array ();
		$flag = FALSE;
		
		if ($register = $this->getRegisterInstance($rec)) {
			$query = $register->getQuery();
			$query->EXT('objectId', 'acc_Items', 'externalName=objectId');
			$query->EXT('listId', 'acc_Items', 'externalName=listId');
			$query->EXT('itemId', 'acc_Items', 'externalName=id');
			$query->where("#objectId = #id");
			$query->where("#listId = {$rec->id}");
			
			$featureObj = $register->features [$featureId];
			
			$featureObj->prepareGroupQuery($featureValue, $query);
			
			while ( $r = $query->fetch() ) {
				$ids [] = $r->itemId;
			}
		}
		
		return $ids;
	}
	
	/**
	 * Метода зарежда данни за изнициализация от CSV файл
	 */
>>>>>>> refs/remotes/origin/master
	function on_AfterSetupMVC($mvc, $res)
    {
        $res .= acc_setup_Lists::loadData();
	}
	
	/**
	 * 
	 * Номенклатурите, в които е регистриран обект
	 *
	 * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
	 * @param int $objectId
	 * @return array ключове - ид-та на номенклатурите, в които е регистриран обекта,
	 * стойности - наименования на номенклатурите.
	 */
	static function getItemLists($class, $objectId) {
		$self = cls::get(__CLASS__); // Би било излишно, ако getQuery() стане static
		$result = array ();
		
		expect($classId = core_Classes::getId($class));
		
		$listIds = $self->Items->fetchField("#classId = {$classId} AND #objectId = {$objectId}", 'lists');
		
		$listIdsType = $self->Items->fields ['lists']->type;
		
		if (count($listIds = $listIdsType::toArray($listIds))) {
			foreach ( $listIds as $listId ) {
				$result [$listId] = $listIdsType->getVerbal($listId);
			}
		}
		
		return $result;
	}
	
	/**
	 * Номенклатурите, в които могат да бъдат включвани като пера обектите от този клас
	 *
	 * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
	 * @param int $objectId
	 * @return array ключове - ид-та на номенклатурите, в които е регистриран обекта,
	 * стойности - наименования на номенклатурите.
	 */
	static function getPossibleLists($class) {
		$self = cls::get(__CLASS__); // Би било излишно, ако getQuery() стане static
		$result = array ();
	
		if (is_null($class)) {
			$query = $self->getQuery(); // self::getQuery(), ако беше static
			$query->where('#regInterfaceId IS NULL');
		} else {
			$ifaceIds = array_keys(core_Interfaces::getInterfaceIds($class));
			
			if (count($ifaceIds)) {
				$query = $self->getQuery(); // self::getQuery(), ако беше static
				$query->where('#regInterfaceId IN (' . implode(',', $ifaceIds) . ')');
			}
		}

		if (isset($query)) {
			$query->show('id,name');
			while ( $rec = $query->fetch() ) {
				$result [$rec->id] = $rec->name;
			}
		}
			
		return $result;
	}
	
	/**
	 * Добавя обект към номенклатура.
	 * 
	 * Ако обекта не е в номенклатурата се добавя; ако е бил добавен преди - само състоянието 
	 * на перото става active.
	 *
	 * @param int $listId ид на номенклатура
	 * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
	 * @param int $objectId
	 * @return int ид на перото, съответстващо на обекта в тази номенклатура
	 */
	static function addItem($listId, $class, $objectId)
	{
		if ($itemRec = self::fetchItem($class, $objectId)) {
			$lists = type_Keylist::addKey($itemRec->lists, $listId);
		} else {
			$lists = $listId;
		}
		
		return self::updateItem($class, $objectId, $lists);
	}
	
	/**
	 * Обновява информацията за перо или създава ново перо.
	 * 
	 * Използва се за обновяване на данните на перо след промяна на съотв. обект от регистър
	 *
	 * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
	 * @param int $objectId
	 * @param int $listId ид на номенклатура, към която да се добави перото. 
	 * 						Ако перото липсва - създава се
	 * @return int ид на обновеното перо или null, ако няма такова перо
	 */
	static function updateItem($class, $objectId, $lists = null) {
		$self = cls::get(__CLASS__);
		
		$result = null;
		$lists  = type_Keylist::toArray($lists);
		
		// Извличаме запис за перо (ако има)
		$itemRec = self::fetchItem($class, $objectId);
		
		// Намираме номенклатурите, от които перото ще бъде изключено. Целта да е да ги 
		// нотифицираме за да си обновят кешовете (@see acc_Lists::updateSummary). 
		// Номенклатурите, в които перото ще бъде включено сега също ще бъдат нотифицирани:
		// @see acc_Items::onAfterSave()
		$oldLists = array();
		if ($itemRec) {
			$oldLists = type_Keylist::toArray($itemRec->lists);
		}
		$removedFromLists = array_diff($oldLists, $lists);
		
		if ($itemRec || $lists) {
			if (!$itemRec) {
				$itemRec->classId = core_Classes::getId($class);
				$itemRec->objectId = $objectId;
			}
			
			if ($lists) {
				self::setItemLists($itemRec, type_Keylist::fromVerbal($lists));
			}
			
			// Извличаме от регистъра (през интерфейса `acc_RegisterIntf`), обновения запис за перо
			$AccRegister = cls::getInterface('acc_RegisterIntf', $class);
			$newItemRec = $AccRegister->getItemRec($objectId);
			
			$itemRec->nom = $newItemRec->nom;
			$itemRec->title = $newItemRec->title;
			$itemRec->uomId = $newItemRec->uomId;
			$itemRec->features = $newItemRec->features;
		}
		
		if ($itemRec) {
			if (!empty($lists)) {
				$itemRec->state = 'active';
			}
			
			if (($result = $self->Items->save($itemRec)) && $itemRec->state == 'active') {
				$AccRegister->itemInUse($objectId, true);
				
				// Нотифициране на номенклатурите, от които перото е било премахнато
				foreach ($removedFromLists as $lid) {
					self::updateSummary($lid);
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Изтрива перо, съответстващо на обект от регистър
	 * 
	 * Ако перото е използвано само го скрива (`state`:='closed'), иначе изтрива записа от БД 
	 *
	 * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
	 * @param int $objectId
	 * @return boolean true при успех, false при грешка, null при липсващо перо
	 */
	static function removeItem($class, $objectId) {
		$self = cls::get(__CLASS__);
		
		$result = null;
		
		// Извличаме съществуващия запис за перо
		if ($itemRec = self::fetchItem($class, $objectId)) {
			if ($itemRec->lastUseOn) {
				// Перото е използвано - маркираме като 'closed', но не изтриваме
				$itemRec->state = 'closed';
				$result = !!$self->Items->save($itemRec);
			} else {
				// Перото никога не е използвано - изтриваме го от БД.
				$result = ($self->Items->delete($itemRec->id) == 1);
			}
		}
		
		$AccRegister = cls::getInterface('acc_RegisterIntf', $class);
		$AccRegister->itemInUse($objectId, false);
		
		return $result;
	}
	
	private static function fetchItem($class, $objectId)
	{
		$self = cls::get(__CLASS__);
		
		expect($classId = core_Classes::getId($class));
		$itemRec = $self->Items->fetch("#classId = {$classId} AND #objectId = {$objectId}");
		
		return $itemRec;
	}
	
	
	private static function fetchInterfaceId($id)
	{
		$self = cls::get(__CLASS__);
		
		return $self->fetchField($id, 'regInterfaceId');
	}
	
	private static function setItemLists($itemRec, $lists)
	{
		$lists = type_Keylist::toArray($lists);
		
		/*
		 * Класът на перото трябва да поддържа интерфейса, зададен в номенклатурата. В противен
		 * случай добавянето не е позволено!
		 */
		$classIfaceIds = core_Interfaces::getInterfaceIds($itemRec->classId); // Интерфейсите на класа
		foreach ($lists as $listId) {
			$listIfaceId = self::fetchInterfaceId($listId, 'regInterfaceId'); // Интерф. на номенклатурата
			expect(in_array($listIfaceId, $classIfaceIds), "Класът не поддържа нужния интерфейс");
		}
		
		/*
		 * Всичко е наред - перото може да се добави в тези номенклатури
		 */
		$itemRec->lists = type_Keylist::fromVerbal($lists);
	}
	
	
	static function act_Lists()
	{
		$form = cls::get('core_Form');
		$form->setAction('acc_Lists', 'lists');
		$form->FLD('classId', 'varchar', 'input=hidden,silent');
		$form->FLD('objectId', 'int', 'input=hidden,silent');
		$form->FLD('ret_url', 'varchar', 'input=hidden,silent');
		$form->FLD('lists', 'keylist', 'caption=Номенклатури');
		
		$form->input(null, true);

		$form->fields['lists']->type->suggestions = self::getPossibleLists($form->rec->classId);
		$form->fields['lists']->value = type_Keylist::fromVerbal(self::getItemLists($form->rec->classId, $form->rec->objectId));
		
		$form->input();
		
		if ($form->isSubmitted()) {
			$self = cls::get(__CLASS__);
			
			$itemRec = self::fetchItem($form->rec->classId, $form->rec->objectId);
			self::setItemLists($itemRec, $form->rec->lists);
			
			if ($self->Items->save($itemRec)) {
				return new Redirect(getRetUrl());
			}
		}
		
		$AccRegister = cls::getInterface('acc_RegisterIntf', $form->rec->classId);
		$form->title = $AccRegister->getLinkToObj($form->rec->objectId);

        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $data->retUrl, array('class' => 'btn-cancel'));
        
        $class = cls::get($form->rec->classId);
		
		$tpl = $class->renderWrapping($form->renderHtml());
		
		return $tpl;
	}
}
