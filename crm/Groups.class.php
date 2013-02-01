<?php



/**
 * Мениджър на групи с визитки
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Groups extends groups_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Групи с визитки";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Групи";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Rejected, doc_FolderPlg';
    
    
    /**
     * Кои полета да се листват
     */
    var $listFields = 'order,title=Заглавие,extenders';
    
    
    /**
     * Поле за инструментите
     */
    var $rowToolsField = 'order';
    
    
    /**
     * Права
     */
    var $canWrite = 'user';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Достъпа по подразбиране до папката, съответсваща на групата
     */
    var $defaultAccess = 'public';


    /**
     * Допустими екстендери
     * 
     * @var array
     * @see getEx
     */
    protected $extendersArr = array(
        'bankAccount' => array(
            'className' => 'bank_Accounts',
            'prefix' => 'ContragentBankAccounts',
            'title' => 'Банкови сметки',
        ),
        'profile' => array(
            'className' => 'crm_Profiles',
            'prefix'    => 'Profile',
            'title'     => 'Потребителски Профил',
        ),
        'idCard' => array(
            'className' => 'crm_ext_IdCards',
            'prefix'    => 'IdCard',
            'title'     => 'Лична карта',
        ),
        'locations' => array(
            'className' => 'crm_Locations',
            'prefix'    => 'ContragentLocations',
            'title'     => 'Локации',
        ),
        'lists' => array(
            'className' => 'acc_Items',
            'prefix'    => 'ObjectLists',
            'title'     => 'Номенклатура',
        ),
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('order', 'order', 'caption=Но.');
        $this->FLD('name', 'varchar(128)', 'caption=Име на групата,width=100%');
        $this->FLD('companiesCnt', 'int', 'caption=Брой->Фирми,input=none');
        $this->FLD('personsCnt', 'int', 'caption=Брой->Лица,input=none');
        $this->FLD('info', 'text', 'caption=Описание');
        
        $this->setDbUnique("name");
    }
   
   /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#order');
    }

    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$row->companiesCnt = $mvc->getVerbal($rec, 'companiesCnt');
    	$row->personsCnt = $mvc->getVerbal($rec, 'personsCnt');
    	
    	$row->companiesCnt = new ET("<b style='font-size:14px;'>[#1#]</b>", ht::createLink($row->companiesCnt, array('crm_Companies', 'groupId' => $rec->id, 'users' => 'all_users')));
        $row->personsCnt = new ET("<b style='font-size:14px;'>[#1#]</b>", ht::createLink($row->personsCnt, array('crm_Persons', 'groupId' => $rec->id, 'users' => 'all_users')));
       
        $name = $mvc->getVerbal($rec, 'name');
        $info = $mvc->getVerbal($rec, 'info');
        
        $row->title = "<b>$name</b><br />";
        if($info)  $row->title .= "<small>$info</small><br />";
        $row->title .= "<span style='font-size:14px;'>Брой фирми:</span> ". $row->companiesCnt;
        $row->title .= ", <span style='font-size:14px;'>Брой лица:</span> ".  $row->personsCnt;
       
        
        $extArr  = arr::make($rec->extenders);
        foreach($extArr as $ext) {
        	$row->extenders .= $mvc->extendersArr[$ext]['title'] .", ";
        }
        $row->extenders = trim($row->extenders, ', ');
        $row->extenders = "<span style='display:block; width:320px; font-size: 15px;'>".$row->extenders."</span>";
        
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // BEGIN В случай, че няма данни в таблицата, зареждаме от масив.
        if (! ($r = $mvc->fetch('1=1')) ) { 
            // BEGIN масив с данни за инициализация
            $data = array(
                array(
                    'name' => 'Клиенти',
                    'sortId' => 30,
                	'extenders' => 'lists,locations'
                ),
                array(
                    'name' => 'Доставчици',
                    'sortId' => 31,
                	'extenders' => 'bankAccount,lists,locations',
                ),
                array(
                    'name' => 'Дебитори',
                    'sortId' => 32,
                	'extenders' => 'locations'
                ),
                array(
                    'name' => 'Кредитори',
                    'sortId' => 33,
                	'extenders' => 'bankAccount,locations'
                ),
                array(
                    'name' => 'Служители',
                    'sortId' => 34,
                	'extenders' => 'bankAccount,locations,idCard'
                ),
                array(
                    'name' => 'Управители',
                    'sortId' => 36,
                	'extenders' => 'bankAccount,profile,idCard,lists'
                ),
                array(
                    'name' => 'Свързани лица',
                    'sortId' => 37,
                	'extenders' => 'bankAccount,idCard,lists'
                ),
                array(
                    'name' => 'Организации и институции',
                    'sortId' => 38,
                	'extenders' => 'bankAccount,locations,lists'
                )
            );
            
            // END масив с данни за инициализация
            
            
            $nAffected = 0;
            
            // BEGIN За всеки елемент от масива
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                $rec->companiesCnt = 0;
                $rec->PersonsCnt = 0;
                
                $mvc->save($rec, NULL, 'ignore');
                 
                $nAffected++;
            }
            
            // END За всеки елемент от масива
            
            if ($nAffected) {
                $res .= "<li style='color:green;'>Добавени са {$nAffected} групи.</li>";
            }
        }
        
        // END В случай, че няма данни в таблицата, зареждаме от масив.        
    }
    
    
    /**
     * Връща id' тата на всички записи в групите
     * 
     * @return array $idArr - Масив с id' тата на групите
     */
    static function getGroupRecsId()
    {
        //Масив с id' тата на групите
        $idArr = array();
        
        // Обхождаме всички записи
        $query = static::getQuery();
        while($rec = $query->fetch()) {
            
            // Добавяме id' тата им в масива
            $idArr[$rec->id] = $rec->id;
        }
        
        return $idArr;
    }
    
    function act_Test ()
    {
    	bp(crm_Profiles::fetchCrmGroup());
    }
}
