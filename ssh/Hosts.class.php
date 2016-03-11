<?php




/**
 * Мениджър на машини за отдалечен достъп
 *
 *
 * @category  bgerp
 * @package   ssh
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ssh_Hosts extends core_Master
{
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, ssh_Wrapper';
                      
    
    /**
     * Заглавие
     */
    public $title = 'Отдалечени SSH машини';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, remote, admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, remote, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'remote, debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, remote, admin';


	/**
	 * Кой може да разглежда сингъла на машините?
	 */
	public $canSingle = 'ceo, remote, admin';

	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'tools=Пулт,name=Име,state=Състояние,ip,port,user,createdBy';
	
	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'tools';
	
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('config', 'blob(serialize, compress)', 'caption=Конфигурация,input=none,single=none,column=none');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Състояние,input=none');
        $this->FNC('ip', 'ip()', 'caption=IP aдрес, input, mandatory');
        $this->FNC('port', 'int(min=1,max=9999)', 'caption=Порт, input, mandatory');
        $this->FNC('user', 'varchar(255)', 'caption=Потребител, input, mandatory');
        $this->FNC('pass', 'password()', 'caption=Парола, input');
        
        $this->setDbUnique('name');
    }
    

    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->config = array('name' => $form->rec->name, 'ip' => $form->rec->ip,
                                      'port' => $form->rec->port, 'user' => $form->rec->user, 'pass' => $form->rec->pass);
         }
    }

	/**
	 * След подготвяне на формата добавяне/редактиране
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
	    $form = &$data->form;
	    $rec  = &$form->rec;

	    if (is_array($rec->config)) {
    	    foreach ($rec->config as $name => $value) {
                $form->setDefault($name, $value);
            }
	    }
    }
    
    
    /**
     * Показваме актуални данни за всеки от записите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->ip = $rec->config['ip'];
        $row->port = $rec->config['port'];
        $row->user = $rec->config['user'];
    }
    
    /**
     * Извлича запис по име
     */
//     public static function fetchByName($name)
//     {
//         return self::fetch(array ("#name = '[#1#]' COLLATE utf8_general_ci", $name));
//     }
    
    /**
     * Връща конфигурацията на хост по id
     * 
     * @param int $id
     * @return array конфигурацията
     */
    public static function fetchConfig($id)
    {
    	$rec = self::fetch(array ("#id = '[#1#]' AND state='active'", $id));
    	
    	if (!$rec) {
    		throw new core_exception_Expect("Няма такъв активен ssh хост!");
    	}
    	
    	return $rec->config;
    }
}
