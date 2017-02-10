<?php


/**
 * Клас 'auto_Calls' - Модел за ивенти, които генерират автоматизации
 *
 *
 * @category  bgerp
 * @package   auto
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class auto_Calls extends core_Manager
{
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    protected $canDelete = 'no_one';
	

	/**
	 * Заглавие
	 */
	public $title = 'Събития за автоматизации';
	
	
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'plg_Created,plg_State';
    
    
    /**
     * Да се извика ли на шътvarдаун
     * 
     * @param boolean
     */
    protected $callOnShutdown;
    
    
	/**
	 * Описание
	 */
	public function description()
	{
		$this->FLD('hash', 'varchar(32)', 'caption=Хеш, input=none');
		$this->FLD('event', 'varchar(128)', 'caption=Събитие');
	    $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни,column=none');
	    $this->FLD('state', 'enum(waiting=Чакащо,locked=Заключено,closed=Затворено)', 'caption=Състояние, input=none');
	}
	
	
	/**
	 * Добавя функция, която да се изпълни след определено време
	 * 
	 * @param varchar $event          - име на събитието
	 * @param mixed   $data           - данни за събитието
	 * @param boolean $once           - дали да се добави само веднъж
	 * @param boolean $callOnShutdown - да се изпълнили на шътдаун
	 */
	public static function setCall($event, $data = NULL, $once = FALSE, $callOnShutdown = FALSE)
	{
		$nRec = new stdClass();
		$nRec->event = $event;
		$nRec->data = $data;
		$nRec->state = 'waiting';

		// Ако ще се изпълнява само веднъж, трябва да е уникално
		if ($once === TRUE) {
			$hash = md5($event . ' ' . json_encode($data));
			if ($rec = self::fetch("#hash = '{$hash}'")) {
				$nRec->id = $rec->id;
			}
			$nRec->hash = $hash;
		}
		
		$mvc = cls::get(get_called_class());
		if($callOnShutdown === TRUE){
			$mvc->callOnShutdown = TRUE;
		}
		
		// Запис на извикването
		$mvc->save($nRec);
	}
	
	
	/**
	 * Обновява списъците със свойства на номенклатурите от които е имало засегнати пера
	 *
	 * @param acc_Items $mvc
	 */
	public static function on_Shutdown($mvc)
	{
		// Ако е вдигнат флага за автоматично извикване да сработи
		if($mvc->callOnShutdown === TRUE){
		    $mvc->logInfo('Извикване на автоматизациите на shutdown');
		    core_Users::forceSystemUser();
		    $mvc->cron_Automations();
		    core_Users::cancelSystemUser();
			unset($mvc->callOnShutdown);
		}
	}
	
	
	/**
	 * След подготовка на тулбара на списъчния изглед
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		// Бутон за изчистване на всички
		if(haveRole('admin,debug,ceo')){
			$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
		}
	}
	
	
	/**
	 * Изчиства записите в балансите
	 */
	public function act_Truncate()
	{
		requireRole('admin,debug,ceo');
			
		// Изчистваме записите от моделите
		self::truncate();
	
		return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
	}
	
	
	/**
	 * Крон метод за автоматизации
	 */
	function cron_Automations()
	{
		$res = '';
		$now = dt::now();
		
		// Ако процеса е заключен не се изпълнява
		$lockKey = "DoAutomations";
		if(!core_Locks::get($lockKey, 60, 1)) {
			$this->logWarning("Извършването на автоматизации е заключено от друг процес");
			return;
		}
		
		// Ако има чакащи автоматики
		if(!self::count()) return;
		
		// Взимане на всички класове поддържащи автоматизации
		$automationClasses = core_Classes::getOptionsByInterface('auto_AutomationIntf');
		
		// Отделят се чакащите записи
		$query = self::getQuery();
		$query->orderBy("id", "DESC");
		$query->where("#state = 'waiting'");
		
		// За всеки
		while($rec = $query->fetch()){
			
			// Заключване на процеса
			$nRec = clone $rec;
			$nRec->state = 'locked';
			$this->save_($nRec, 'state');
			self::logInfo("Заключване на автоматизация '{$rec->event}'");
			$status = 'успешно';
			
			try{
				// Ивента се подава на всеки клас за автоматизации
				foreach ($automationClasses as $classId => $className){
					if(cls::load($className, TRUE)){
						$Automation = cls::get($className);
						if(!$Automation->canHandleEvent($rec->event)) continue;
				
						$Automation->doAutomation($rec->event, $rec->data);
					}
				}
			} catch (core_exception_Expect $e){
				reportException($e);
				self::logDebug("Грешка при изпълнението на автоматизация '{$rec->event}'");
				self::logDebug($e->getTraceAsString(), $rec);
				$status = 'неуспешно';
			}
			
			// Ако няма период за изпълнение отново изтрива се
			self::logInfo("Изтриване на {$status} изпълнена автоматизация '{$rec->event}'");
			self::delete($rec->id);
		}
		
		// Освобождаваме заключването на процеса
		core_Locks::release($lockKey);
		
		// Връщане на резултат
		return $res;
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->query->orderBy('id', 'DESC');
	}
}