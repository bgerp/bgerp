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
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper,
    				 plg_Rejected, doc_FolderPlg, plg_Search, plg_Translate';
    
    
    /**
     * Кои полета да се листват
     */
    var $listFields = 'id,name=Заглавие,content=Съдържание';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група->указател";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/group.png';
    
    
    /**
     * Поле за инструментите
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'sysId, name, allow, companiesCnt, personsCnt, info';
    
    /**
     * Права
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
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
        $this->FLD('name', 'varchar(128,ci)', 'caption=Група,mandatory,translate');
        $this->FLD('allow', 'enum(companies_and_persons=Фирми и лица,companies=Само фирми,persons=Само лица)', 'caption=Съдържание,notNull');
        $this->FLD('companiesCnt', 'int', 'caption=Брой->Фирми,input=none');
        $this->FLD('personsCnt', 'int', 'caption=Брой->Лица,input=none');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Бележки');
        
        $this->setDbUnique("name");
        $this->setDbUnique("sysId");
    }

    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // Вземаме стойността по подразбиране, която може да се покаже
        $default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('users', $default);
                        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,users';
        
        $rec = $data->listFilter->input('users,search', 'silent');
        
    	$data->query->orderBy('#name');
    	
        // Филтриране по потребител/и
        if(!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->users != 'all_users') && (strpos($data->listFilter->rec->users, '|-1|') === FALSE)) {  
            
        	$user = type_Keylist::toArray($data->listFilter->rec->users);
            
        	foreach($user as $u){
        		
        		$groupList = crm_Persons::fetchField($u, 'groupList');
        		$data->query->where("'{$groupList}' LIKE CONCAT('%|', #id, '|%')");
        	}
        }
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
        
        if($fields['-single']){
	        $row->personsCnt = str_pad($row->personsCnt, '6', '0', STR_PAD_LEFT);
	        $row->companiesCnt = str_pad($row->companiesCnt, '6', '0', STR_PAD_LEFT);
        }
        
        if (!$rec->companiesCnt && $fields['-single']) {
        	if ($rec->allow == 'persons') {
        		unset($row->companiesCnt);
        	}
        } else {
        	$row->companiesCnt = new ET("<b>[#1#]</b>", ht::createLink($row->companiesCnt, array('crm_Companies', 'groupId' => $rec->id, 'users' => 'all_users')));
        }
        
        if (!$rec->personsCnt && $fields['-single']) {
        	if ($rec->allow == 'companies') {
        		unset($row->personsCnt);
        	}
        } else {
        	$row->personsCnt = new ET("<b>[#1#]</b>", ht::createLink($row->personsCnt, array('crm_Persons', 'groupId' => $rec->id, 'users' => 'all_users')));
        }
        
        $row->name = "<b>$row->name</b>";
        
        if($fields['-list']){
            $row->content = '<div>';
            $row->content .= "<span style='font-size:14px;'>" . tr("Брой фирми") . ":</span> ". $row->companiesCnt;
            $row->content .= ", <span style='font-size:14px;'>" . tr("Брой лица") . ":</span> ".  $row->personsCnt;
            $row->content .= '</div>';
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

            $mvc->save($rec, NULL, 'IGNORE');
        }
        
        // END За всеки елемент от масива
        
        if ($nAffected) {
            $res .= "<li style='color:green;'>Добавени са {$nAffected} групи.</li>";
        }
    }


    /**
     * Създава, ако не е групата с посочениете данни и връща id-то и
     * $rec->name
     * $rec->sysId
     * $rec->allow (companies_and_persons, ...
     * $rec->info
     * $rec->inCharge => cu
     * $rec->shared
     * $rec->state = 'active'
     */
    public static function forceGroup($gRec)
    {
        $rec = self::fetch("#sysId = '{$gRec->sysId}'");

        if(!$rec) {
            $rec = self::fetch("LOWER(#name) = LOWER('{$gRec->name}')");
        }

        if(!$rec) {
            $rec = $gRec;
            
            setIfNot($rec->inCharge, core_Users::getCurrent());
            setIfNot($rec->allow, 'companies_and_persons');
            $rec->companiesCnt = 0;
            $rec->personsCnt = 0;
            setIfNot($rec->state, 'active');

            self::save($rec);
        }

        return $rec->id;

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
