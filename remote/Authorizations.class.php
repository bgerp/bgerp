<?php 


/**
 * Оторизации от и към външни услуги
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 20165 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Authorizations extends embed_Manager
{
    
    /**
     * Заглавие
     */
    public $title = "Оторизации на отдалечени системи";
    

    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'remote_ServiceDriverIntf';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Оторизация";

    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'crm_Wrapper, plg_Created, plg_State2, plg_RowTools';
    

    /**
     * Текущ таб
     */
    public $currentTab = 'Профили';
   

    /**
     * Поле за инструментите на реда
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
        

    /**
     * Кой може да пише?
     */
    public $canWrite = 'powerUser';


    /**
     * Колонки в листовия изглед
     */
    public $listFields = 'id=✍,userId,url,auth=Оторизация,state,createdOn,createdBy';


    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('userId', 'user', 'caption=Потребител,mandatory,smartCenter');
        $this->FLD('url', 'url', 'caption=URL адрес,mandatory,smartCenter');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Състояние,column=none,single=none,input=none');
        $this->FNC('auth', 'varchar', 'caption=Оторизация,smartCenter');
  
        $this->setDbUnique('url,userId');
    }


    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        $rec->url = self::canonizeUrl($rec->url);
    }
    

    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public static function on_AfterPrepareEditform($mvc, $res, $data)
    {
        $form = $data->form;
        $rec  = $form->rec;
        
        $form->setDefault('userId', core_Users::getCurrent());

        if(!haveRole('admin')) {
            $form->setReadonly('userId');
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$data->form->title = "Създаване на нова оторизация за онлайн услуга";
    }


    /**
     * Връща канонично URL
     */
    static public function canonizeUrl($url)
    {
        return trim(strtolower(rtrim($url, '/')));
    }


    /**
     * Подготвя детайла с оторизациите в профила
     */
    public static function prepareAuthorizationsList($data)
    {
        $userId = $data->masterData->rec->userId;
        
        
        $data->action = 'list';


        $mvc = cls::get(__CLASS__);

        // Създаваме заявката
        $data->query = $mvc->getQuery();
        $data->query->where("#userId = $userId");
        
        // Подготвяме полетата за показване
        $data->listFields = arr::make("id=№,url=URL на услугата,auth=Оторизация,state=Състояние");
        
        // Подготвяме навигацията по страници
        $mvc->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $mvc->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата
        $mvc->prepareListRows($data);
    }


    /**
     * Рендира детайла с оторизациите в профила
     */
    public static function renderAuthorizationsList($data)
    {
        if(arr::count($data->recs)) {
            
            $mvc = cls::get(__CLASS__);

            $tpl = $mvc->renderList($data);

            return $tpl;
        }
    }

}