<?php



/**
 * Мениджър на машини за отдалечен достъп
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Hosts extends core_Master
{
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, remote_Wrapper';
                      
    
    /**
     * Заглавие
     */
    public $title = 'Отдалечени машини';
    
    
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
     * Описание на модела
     */
    function description()
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
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     */
    public static function on_BeforeSave($mvc, &$res,&$rec)
    {

    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->config = array('name' => $form->rec->name, 'ip' => $form->rec->ip,
                                      'port' => $form->rec->port, 'user' => $form->rec->user, 'pass' => $form->rec->pass);
         }
    }

	/**
	 * След подготвяне на формата добавяне/редкатиране
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
	    $form = &$data->form;
	    $rec  = &$form->rec;
	     
        foreach ($rec->config as $name => $value) {
            $form->setDefault($name, $value);
        }
    }
    
    
    /**
     * Показваме актуални данни за всеки от записите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    }
    

}
