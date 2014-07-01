<?php 


/**
 * Подсистема за помощ - Информация
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_Info extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Помощни информационни текстове";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Помощен информационен текст";
        
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'help_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,class,action,lg,text,createdOn,createdBy';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,debug,help';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'debug, help';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('title', 'varchar', 'caption=Област');
		$this->FLD('class', 'varchar(64)', 'caption=Име на класа');
		$this->FLD('action', 'varchar(13)', 'caption=Метод');
        $this->FLD('lg', 'varchar(2)', 'caption=Език');
		$this->FLD('text', 'richtext', 'caption=Помощна информацията, hint=Текст на информацията за помощ');

        $this->setDbUnique('class,lg,action');
    }


    /**
     * Изчисляване на полето 'titla'
     */
    function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $rec->class . " ({$rec->lg})";
    }
    
    
 	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	// Подготвяме пътя до файла с данните 
    	$file = "help/data/HelpInfo.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array( 
    		0 => 'class',
    		1 => 'action',
            2 => 'lg',
    		3 => 'text',
    	);
    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL, TRUE); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
    }
    
    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'edit') {
    		$requiredRoles = 'debug';
    	}
    	
    	switch ($action) { 
    		// ако метода е добавяне 
            case 'add':
            	// и нямяме роля debug
    			if(!haveRole('debug')) {
				        // никой не може да пише в модела
						$requiredRoles = 'no_one';
				}
                break;
    	}
    }

}
