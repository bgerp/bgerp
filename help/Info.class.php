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
    var $canWrite = 'help';

    var $canAdd = 'help';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('title', 'varchar', 'caption=Област');
		$this->FLD('class', 'varchar(64)', 'caption=Име на класа,mandatory,silent');
		$this->FLD('action', 'varchar(13)', 'caption=Метод,mandatory,silent');
        $this->FLD('lg', 'varchar(2)', 'caption=Език,mandatory,silent');
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
     * 
     * 
     * @param string $cond
     * @param string $fields
     * @param boolean $cache
     * 
     * @return object
     */
    static function fetch($cond, $fields = '*', $cache = TRUE)
    {
        if ($cond == '-1') {
            $rec = new stdClass();
            $rec->title = 'Помощ за bgERP';
            $rec->class = '';
            $rec->action = '';
            $rec->lg = '';
            $rec->text = '';
            
            return $rec;
        } else {
            return parent::fetch($cond, $fields, $cache);
        }
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

        $mvc->importing = TRUE;
    	
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
    	if($action == 'edit' || $action == 'add') {
            if(!haveRole('help')) {
			    $requiredRoles = 'no_one';
		    } else {
    	        $requiredRoles = 'help';
            }
    	}
    }
    
    
    /**
     * След всяко обновяване на модела прави опит да запише csv файла
     */
    function on_AfterSave($mvc, $id, $rec) 
    {
        // За да не променяме излишно хелпа
        if($mvc->importing || !haveRole('help')) return;

        $query = self::getQuery();
        
        while($r = $query->fetch()) {
            $recs[] = $r;
        }
        
        $csv = csv_Lib::createCsv($recs, array('class', 'action', 'lg', 'text'), $mvc);
        
        $file = "help/data/HelpInfo.csv";
        $path = getFullPath($file);
        file_put_contents($path, $csv);
    }


}
