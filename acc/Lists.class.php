<?php



/**
 * Клас 'acc_Lists' -
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class acc_Lists extends core_Manager {
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_WrapperSettings, plg_RowTools,plg_State2, plg_Sorting, plg_Created';
    
    
    /**
     * Кои роли имат пълни права за този мениджър?
     */
    var $canAdmin = 'admin,acc';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';

    /**
     * Заглавие
     */
    var $title = 'Номенклатури';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,nameLink=Наименование,regInterfaceId,itemsCnt,itemMaxNum,systemId,lastUseOn,tools=Пулт';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description() {
        // Трибуквен, уникален номер
        $this->FLD('num', 'int(3,size=3)', 'caption=Номер,remember=info,mandatory,notNull,export');
        
        // Име на номенклатурата
        $this->FLD('name', 'varchar', 'caption=Номенклатура,mandatory,remember=info,mandatory,notNull,export');
        
        // Интерфейс, който трябва да поддържат класовете, генериращи пера в тази номенклатура
        $this->FLD('regInterfaceId', 'interface(suffix=AccRegIntf, allowEmpty, select=name)', 'caption=Интерфейс,export');
        
        // Колко пера има в тази номенклатура?
        $this->FLD('itemsCnt', 'int', 'caption=Пера->Брой,input=none');
        
        // Максимален номер използван за перата
        $this->FLD('itemMaxNum', 'int', 'caption=Пера->Макс. ном.,input=none');
        
        // Последно използване
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=none');
        
        // Състояние на номенклатурата
        $this->FLD('state', 'enum(active=Активна,closed=Затворена)', 'caption=Състояние,input=none');
        
        // System ID
        $this->FLD('systemId', 'varchar(32)', 'caption=System ID, export');
        
        // Заглавие 
        $this->FNC('caption', 'html', 'column=none');
        
        // Титла - хипервръзка
        $this->FNC('nameLink', 'html', 'column=none');
        
        // Титла - хипервръзка
        $this->FNC('title', 'html', 'column=none');
        
        // Уникални индекси
        $this->setDbUnique('num');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Изчислява полето 'caption', като конкатенира номера с името на номенклатурата
     */
    static function on_CalcCaption($mvc, $rec)
    {
        if (!$rec->name) {
            $rec->name = $mvc::fetchField($rec->id, 'name');
        }
        
        if (!$rec->num) {
            $rec->num = $mvc::fetchField($rec->id, 'num');
        }
        $rec->caption = $mvc->getVerbal($rec, 'name') . "&nbsp;(" . $mvc->getVerbal($rec, 'num') . ")";
    }
    
    
    /**
     * Изчислява полето 'nameLink', като име с хипервръзка към перата от тази номенклатура
     */
    static function on_CalcNameLink($mvc, $rec)
    {
        $name = $mvc->getVerbal($rec, 'name');
        
        $rec->nameLink = ht::createLink($name, array ('acc_Items', 'list', 'listId' => $rec->id));
    }
    
    
    /**
     * Изчислява полето 'title'
     */
    static function on_CalcTitle($mvc, $rec)
    {
        $name = $mvc->getVerbal($rec, 'name');
        $num = $mvc->getVerbal($rec, 'num');
        
        $rec->title = $num . '.&nbsp;' . $name;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function fetchByName($name)
    {
        return self::fetch(array ("#name = '[#1#]' COLLATE utf8_general_ci", $name));
    }
    
    
    /**
     * Извлича запис на модела acc_Lists според системен идентификатор
     * 
     * @param string $systemId
     * @return stdClass
     */
    static public function fetchBySystemId($systemId)
    {
        return self::fetch(array ("#systemId = '[#1#]'", $systemId));
    }
    
    
    /**
     * Изпълнява се преди запис на номенклатурата
     */
    static function on_BeforeSave($mvc, $id, $rec) {
        if (!$rec->id) {
            $rec->itemCount = 0;
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL)
    {
        if (($action == 'delete')) {
            
            //Позволява изтриването в дебъг режим от админ
            if (haveRole('admin') && isDebug()) return;
            
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
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if ($data->form->rec->id && $data->form->rec->itemsCnt) {
            // Забрана за промяна на интерфейса на непразните номенклатури
            $data->form->setReadonly('regInterfaceId');
        } else {
            $data->form->setField('regInterfaceId', 'allowEmpty');
        }
    }
    
    
    /**
     * Предизвиква обновяване на обобщената информация за
     * номенклатура с посоченото id
     */
    static function updateSummary($id)
    {
        $rec = self::fetch($id);
        
        $itemsQuery = acc_Items::getQuery();
        $itemsQuery->where("#state = 'active'");
        $itemsQuery->where("#lists LIKE '%|{$id}|%'");
        $rec->itemsCnt = $itemsQuery->count();
        
        $itemsQuery->XPR('maxNum', 'int', 'max(#num)');
        
        $rec->itemMaxNum = $itemsQuery->fetch()->maxNum;
        
        self::save($rec);
    }
    
    
    /**
     * Изпълнява се преди подготовката на показваните редове
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('num');
    }
    
    
    /**
     * Номенклатурите, в които е регистриран обект
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @return array ключове - ид-та на номенклатурите, в които е регистриран обекта,
     * стойности - наименования на номенклатурите.
     */
    static function getItemLists($class, $objectId) {
        $result = array ();
        
        expect($classId = core_Classes::getId($class));
        
        $listIds = acc_Items::fetchField("#classId = {$classId} AND #objectId = {$objectId}", 'lists');
        
        if (count($listIds = type_Keylist::toArray($listIds))) {
            foreach ($listIds as $listId) {
                $rec = self::fetch($listId);
                $result [$listId] = self::getVerbal($rec, 'title');
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
        $result = array ();
        
        if (is_null($class)) {
            $query = static::getQuery();
            $query->where("#regInterfaceId IS NULL OR #regInterfaceId = ''");
        } else {
            $ifaceIds = array_keys(core_Interfaces::getInterfaceIds($class));
            
            if (count($ifaceIds)) {
                $query = static::getQuery();
                $query->where('#regInterfaceId IN (' . implode(',', $ifaceIds) . ')');
            }
        }
        
        if (isset($query)) {
            while ($rec = $query->fetch()) {
                $result [$rec->id] = self::getVerbal($rec, 'title');
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
     * Използва се за обновяване на данните на перо след промяна на съответната обект от регистър
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @param int $listId ид на номенклатура, към която да се добави перото.
     * @param boolean $forced дали да обновяваме списъка към които е перото
     * Ако перото липсва - създава се
     * @return int ид на обновеното перо или null, ако няма такова перо
     */
    static function updateItem($class, $objectId, $lists = NULL, $forced = TRUE)
    {
		$result = NULL;
       
		if($lists !== NULL) {
			
			// Проверяваме дали списъка на номенклатурите е подаден като Кейлист
	 		if (strstr($lists, '|') === FALSE) { 
	      		$lists = arr::make($lists);
	        	$str = "|";
	        	foreach($lists as $list){
	        		
	        		// Ако елементите на масива са стрингове намираме на кои записи
	        		// отговарят те
	        		if(!is_numeric($list)) 
	        			$str .= static::fetchBySystemId($list)->id . "|";
	        		else 
	        			$str .= $list ."|";
	        	}
	        	
	        	// Заместваме подадения стрингов списък с списък от ключове
	        	$lists = $str;
	        }
		}
		
        $lists = type_Keylist::toArray($lists);
        
        
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
        
        // Ако поелто $forced е FALSE, към списъка със старите номенклатури добавяме новата
        // ( ако тя вече не е в него). Ако е TRUE, заместваме старите номенклатури с новите 
        if($forced !== TRUE) {
        	if(count($oldLists) > 0) {
        		foreach($lists as $list) {
	      			if(!in_array($list, $oldLists)){
	      				$oldLists[$list] = $list;     			
	      			}
	      		}
	      		
	      		// Добавяме новата номенклатура към старите
	      		$lists = $oldLists;
	      		$removedFromLists = array();
        	}
	    } else 
	    	$removedFromLists = array_diff($oldLists, $lists);
	    
       if ($itemRec || $lists) {
            if (!$itemRec) {
                $itemRec = new stdClass();
                $itemRec->classId = core_Classes::getId($class);
                $itemRec->objectId = $objectId;
            }
            
            self::setItemLists($itemRec, type_Keylist::fromArray($lists));
            
            // Извличаме от регистъра (през интерфейса `acc_RegisterIntf`), обновения запис за перо
            $AccRegister = cls::getInterface('acc_RegisterIntf', $class);
            
            acc_Items::syncItemRec($itemRec, $AccRegister, $objectId);
        }
        
        if ($itemRec) {
            $itemRec->state = empty($lists) ? 'closed' : 'active';
           
            if (($result = acc_Items::save($itemRec)) && $itemRec->state == 'active') {
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
        $result = NULL;
        
        // Извличаме съществуващия запис за перо
        if ($itemRec = self::fetchItem($class, $objectId)) {
            if ($itemRec->lastUseOn) {
                // Перото е използвано - маркираме като 'closed', но не изтриваме
                $itemRec->state = 'closed';
                $result = !!acc_Items::save($itemRec);
            } else {
                // Перото никога не е използвано - изтриваме го от БД.
                $result = (acc_Items::delete($itemRec->id) == 1);
            }
        }
        
        $AccRegister = cls::getInterface('acc_RegisterIntf', $class);
        $AccRegister->itemInUse($objectId, false);
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private static function fetchItem($class, $objectId)
    {
        expect($classId = core_Classes::getId($class));
        $itemRec = acc_Items::fetch("#classId = {$classId} AND #objectId = {$objectId}");
        
        return $itemRec;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private static function setItemLists($itemRec, $lists)
    {
        $lists = type_Keylist::toArray($lists);
        
        /*
         * Класът на перото трябва да поддържа интерфейса, зададен в номенклатурата. В противен
         * случай добавянето не е позволено!
         */
        $classIfaceIds = core_Interfaces::getInterfaceIds($itemRec->classId);     // Интерфейсите на класа
        foreach ($lists as $listId) {
            $listIfaceId = static::fetchField($listId, 'regInterfaceId');     // Интерф. на номенклатурата
            expect(in_array($listIfaceId, $classIfaceIds), "Класът не поддържа нужния интерфейс");
        }
        
        /*
         * Всичко е наред - перото може да се добави в тези номенклатури
         */
        $itemRec->lists = type_Keylist::fromArray($lists);
    }
    
    
    /**
     * @todo Чака за документация...
     */
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
        $form->fields['lists']->value = type_Keylist::fromArray(self::getItemLists($form->rec->classId, $form->rec->objectId));
        
        $form->input();
        
        if ($form->isSubmitted()) {
            if (self::updateItem($form->rec->classId, $form->rec->objectId, $form->rec->lists)) {
                return new Redirect(getRetUrl());
            }
        }
        
        $AccRegister = cls::getInterface('acc_RegisterIntf', $form->rec->classId);
        $form->title = 'Номенклатури на|* ' . strip_tags($AccRegister->getLinkToObj($form->rec->objectId));
        
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'));
        
        $class = cls::get($form->rec->classId);
        
        $tpl = $class->renderWrapping($form->renderHtml());
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static public function isDimensional($id)
    {
        $result = FALSE;
        
        if ($regInterfaceId = self::fetchField($id, 'regInterfaceId')) {
            $regInterfaceName = core_Interfaces::fetchField($regInterfaceId, 'name');
            $proxy = cls::get($regInterfaceName);
            $result = $proxy->isDimensional();
        }
        
        return $result;
    }
}
