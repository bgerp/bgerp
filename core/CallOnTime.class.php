<?php


/**
 * Клас 'core_CallOnTime' - Изпълняване на еднократни процеси
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_CallOnTime extends core_Manager
{
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'debug';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    protected $canDelete = 'debug';
	

	/**
	 * 
	 */
	public $title = 'Еднократни процеси';
	
	
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'plg_State, plg_SystemWrapper, plg_RowTools2';
    
    
	/**
	 * 
	 */
	public function description()
	{
	    $this->FLD('hash', 'varchar(32)', 'caption=Хеш, input=none');
	    $this->FLD('className', 'varchar(128)', 'caption=Kлас');
	    $this->FLD('methodName', 'varchar(128)', 'caption=Функция');
	    $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни');
	    $this->FLD('callOn', 'datetime(format=smartTime)', 'caption=Време');
	    $this->FLD('state', 'enum(draft=Чернова, pending=Чакащо)', 'caption=Състояние, input=none');
	    
	    $this->setDbUnique('hash');
	}
	
	
	/**
	 * Добавя функция, която да се изпълни след определено време
	 * 
	 * @param string $className
	 * @param string $methodName
	 * @param mixed $data
	 * @param date $callOn
	 * @param boolean $once
	 * 
	 * @return integer
	 */
	public static function setCall($className, $methodName, $data=NULL, $callOn=NULL, $once=FALSE)
	{
	    // Класа трябва да съществува
	    cls::load($className);
	    
	    // Очакваме да е подаден метод
        expect(trim($methodName));
	    
        // Ако не е подадено време, използваме текущото
        if (!$callOn) {
           $callOn = dt::now();
        }
	    
        $nRec = new stdClass();
        
        // Ако трябва да е уникално
        // Проверяваме дали има запис с този хеш
        if ($once) {
            
            $hash = static::getHash($className, $methodName, $data);
            $rec = self::fetch("#hash = '{$hash}'");
            if ($rec) {
    	        $nRec->id = $rec->id;
    	    }
    	    $nRec->hash = $hash;
        }
        
        // Попълване необходимите полета
        $nRec->className = $className;
	    $nRec->methodName = $methodName;
	    $nRec->data = $data;
	    $nRec->callOn = $callOn;
	    $nRec->state = 'draft';
	    
	    $savedId = self::save($nRec);
	    
	    return $savedId;
	}
	
	
	/**
	 * Еднократно добавя функция, която да се изпълни след определно време
	 * 
	 * @param string $className
	 * @param string $methodName
	 * @param mixed $data
	 * @param date $callOn
	 * 
	 * @return integer
	 */
	public static function setOnce($className, $methodName, $data=NULL, $callOn=NULL)
	{
	    $id = self::setCall($className, $methodName, $data, $callOn, TRUE);
	    
	    return $id;
	}
	
	
	/**
	 * Изтриване на вече зададен запис, който все още не е изпълнен
	 * 
	 * @param string $className
	 * @param string $methodName
	 * @param mixed $data
	 * @return void
	 */
	public static function remove($className, $methodName, $data)
	{
		$hash = self::getHash($className, $methodName, $data);
		
		self::delete("#hash = '{$hash}' AND #state = 'draft'");
	}
	
	
	/**
	 * Връща хеша за записа
	 * 
	 * @param string $className
	 * @param string $methodName
	 * @param mixed $data
	 * 
	 * @return string
	 */
	protected static function getHash($className, $methodName, $data)
	{
	    $hash = md5($className . ' ' . $methodName . ' ' . json_encode($data));
	    
	    return $hash;
	}
	
	
    /**
     * Извикване на функцията по cron
     * 
     * @return string
     */
    public function cron_Start()
    {
        // Вземаме всички записи, които не са фечнати преди и им е дошло времето
        $res = '';
        $now = dt::now();
        $query = self::getQuery();
        $query->where("#callOn <= '{$now}'");
        $query->where("#state != 'pending'");
        while ($rec = $query->fetch()) {
            
            // Променяме състоянието, за да не може да се извика повторно
            $nRec = clone $rec;
            $nRec->state = 'pending';
            self::save($nRec, 'state');
            
            try {
                $class = cls::get($rec->className);
                // Изпълняваме подадената функция с префикс callback_
                $callback = array($class, 'callback_' . $rec->methodName);
                $res .= call_user_func($callback, $rec->data) . "\n";
                
                // Изтриваме след като се изпълни веднъж
                self::delete($rec->id);
                
                sleep(1);
            } catch (core_exception_Expect $e) {
                $res .= "Грешка при извикване на '{$rec->className}->callback_{$rec->methodName}'";
                self::logErr("Грешка при извикване на функция", $rec->id);
            }
        }
        
        // Ако някой процес е гръмнал и е останал в чакащо състояние го оправяме
        $pQuery = self::getQuery();
        $pQuery->where("#state = 'pending'");
        $before = dt::subtractSecs(10000);
        $pQuery->where("#callOn <= '{$before}'");
        $pQuery->limit(1);
        while($pRec = $pQuery->fetch()) {
            $pRec->state = 'draft';
            self::save($pRec, 'state');
            self::logNotice('Променено състояние', $pRec->id);
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     * 
     * @param core_CallOnTime $mvc
     * @param string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'callOnTime';
        $rec->description = 'Стартиране на еднократни процеси';
        $rec->controller = $mvc->className;
        $rec->action = 'start';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
    }
}
