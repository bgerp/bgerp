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
class crm_Groups extends core_Master
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
    var $listFields = 'id,name=Заглавие';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група->указател";
    
    
    /**
     * Поле за инструментите
     */
    var $rowToolsField = 'id';
    
    
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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'crm/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=СисИД,input=none,column=none');
        $this->FLD('name', 'varchar(128)', 'caption=Група,width=100%,mandatory');
        $this->FLD('allow', 'enum(companies_and_persons=Фирми и лица,companies=Само фирми,persons=Само лица)', 'caption=Съдържание,notNull');
        $this->FLD('companiesCnt', 'int', 'caption=Брой->Фирми,input=none');
        $this->FLD('personsCnt', 'int', 'caption=Брой->Лица,input=none');
        $this->FLD('info', 'richtext', 'caption=Бележки');
        
        $this->setDbUnique("name");
    }
   
   /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#name');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($rec->sysId || $rec->companiesCnt ||  $rec->personsCnt) && $action == 'delete') {
            $requiredRoles = 'no_one';
        }
    }
    

    /**
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        // Ако няма стойности
        if (!$rec->companiesCnt) $rec->companiesCnt=0;
        if (!$rec->personsCnt) $rec->personsCnt=0;
        
    	$row->companiesCnt = $mvc->getVerbal($rec, 'companiesCnt');
    	$row->personsCnt = $mvc->getVerbal($rec, 'personsCnt');
    	
    	$row->companiesCnt = new ET("<b style='font-size:14px;'>[#1#]</b>", ht::createLink($row->companiesCnt, array('crm_Companies', 'groupId' => $rec->id, 'users' => 'all_users')));
        $row->personsCnt = new ET("<b style='font-size:14px;'>[#1#]</b>", ht::createLink($row->personsCnt, array('crm_Persons', 'groupId' => $rec->id, 'users' => 'all_users')));
       
        if($fields['-list']){
	        $row->name = "<b>$row->name</b>";
	        if($row->info)  $row->info .= "<div><small>$row->info</small></div>";
		    $row->name .= '<div>';
		    $row->name .= "<span style='font-size:14px;'>Брой фирми:</span> ". $row->companiesCnt;
	        $row->name .= ", <span style='font-size:14px;'>Брой лица:</span> ".  $row->personsCnt;
	        $row->name .= '</div>';
        }
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // BEGIN масив с данни за инициализация
        $data = array(
            array(
                'name'   => 'Клиенти',
                'sysId'  => 'customers',
                'exName' => 'КЛИЕНТИ',
            ),
            array(
                'name'   => 'Доставчици',
                'sysId'  => 'suppliers',
                'exName' => 'ДОСТАВЧИЦИ',
            ),
            array(
                'name'  => 'Дебитори',
                'sysId'  => 'debitors',
                'exName' => 'ДЕБИТОРИ',
            ),
            array(
                'name'   => 'Кредитори',
                'sysId'  => 'creditors',
                'exName' => 'КРЕДИТОРИ',
            ),
            array(
                'name'   => 'Служители',
                'sysId'  => 'employees',
                'exName' => 'Служители',
                'allow'  => 'persons',
            ),
            array(
                'name'   => 'Управители',
                'sysId'  => 'managers ',
                'exName' => 'Управители',
                'allow'  => 'persons',
            ),
            array(
                'name'   => 'Свързани лица',
                'sysId'  => 'related',
                'exName' => 'Свързани лица',
            ),
            array(
                'name'   => 'Институции',
                'sysId'  => 'institutions',
                'exName' => 'Организации и институции',
                'allow'  => 'companies',
            ),
            array(
                'name' => 'Потребители',
                'sysId' => 'users',
                'exName' => 'Потребителски профили',
                'allow'  => 'persons',
            ),

        );
        
        // END масив с данни за инициализация
        
        
        $nAffected = 0;
        
        // BEGIN За всеки елемент от масива
        foreach ($data as $newData) {
            
            $newRec = (object) $newData;

            $rec = $mvc->fetch("#sysId = '{$newRec->sysId}'");

            if(!$rec) {
                $rec = $mvc->fetch("LOWER(#name) = LOWER('{$newRec->name}')");
            }
            
            if(!$rec) {
                $rec = $mvc->fetch("LOWER(#name) = LOWER('{$newRec->exName}')");
            }

            if(!$rec) {
                $rec = new stdClass();
                $rec->companiesCnt = 0;
                $rec->personsCnt = 0;
            }
            
            setIfNot($newRec->allow, 'companies_and_persons');

            $rec->name  = $newRec->name;
            $rec->sysId = $newRec->sysId;
            $rec->allow = $newRec->allow;
			
            if(!$rec->id) {
                $nAffected++;
            }

            $mvc->save($rec, NULL, 'replace');
        }
        
        // END За всеки елемент от масива
        
        if ($nAffected) {
            $res .= "<li style='color:green;'>Добавени са {$nAffected} групи.</li>";
        }
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
    
    
    /**
     * Връща id то на записа от подадения sysId
     * 
     * @param string $sysId
     */
    static function getIdFromSysId($sysId)
    {
        
        return static::fetchField("#sysId = '{$sysId}'");
    }
}
