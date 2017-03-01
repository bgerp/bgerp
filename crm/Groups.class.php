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
     * Страница от менюто
     */
    var $pageMenu = "Групи";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, crm_Wrapper,
                     plg_Rejected, plg_Search, plg_TreeObject, plg_Translate';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'bgerp_PersonalizationSourceIntf';
    
    
    /**
     * Кои полета да се листват
     */
    var $listFields = 'name=Заглавие,companiesCnt=Фирми,personsCnt=Лица';

    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/group.png';
    
        
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'sysId, name, allow, companiesCnt, personsCnt, info';
    
    
    /**
     * Кои полета да се сумират за наследниците
     */
    var $fieldsToSumOnChildren = 'companiesCnt, personsCnt';
    
    
    /**
     * Права
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да оттелгя?
     */
    var $canReject = 'powerUser';
    
    
    /**
     * Кой има право да възстановява?
     */
    var $canRestore = 'powerUser';
    
    
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
     * Ключ за персонализиране на данните на фирмите от групата
     */
    protected static $pCompanies = 'c';
    
    
    /**
     * Ключ за пероснализиране на данните на лицата от групата
     */
    protected static $pPersons = 'p';
    
    
    /**
     * Ключ за персонализиране на фирмените данн на лицата от групата
     */
    protected static $pPersonsBiz = 'pb';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=СисИД,input=none,column=none');
        $this->FLD('name', 'varchar(128,ci)', 'caption=Група,mandatory,translate');
        $this->FLD('allow', 'enum(companies_and_persons=Фирми и лица,companies=Само фирми,persons=Само лица)', 'caption=Съдържание,notNull');
        $this->FLD('companiesCnt', 'int', 'caption=Брой->Фирми,input=none,smartCenter');
        $this->FLD('personsCnt', 'int', 'caption=Брой->Лица,input=none,smartCenter');
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
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent,autoFilter');
        
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
        
        if ($rec) {
            if ($action == 'edit' || $action == 'delete' || $action == 'reject' || $action == 'restore') {
                if ($rec->createdBy != $userId) {
                    if (!haveRole('admin, ceo', $userId)) {
                        if (haveRole('manager', $userId)) {
                            
                            $subordinates = core_Users::getSubordinates($userId);
                            
                            if (!$subordinates[$rec->createdBy]) {
                                $requiredRoles = 'no_one';
                            }
                        } else {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
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
        if (!$rec->companiesCnt) $rec->companiesCnt = 0;
        
        if (!$rec->personsCnt) $rec->personsCnt = 0;
        
        $row->companiesCnt = $mvc->getVerbal($rec, 'companiesCnt');
        $row->personsCnt = $mvc->getVerbal($rec, 'personsCnt');
        $row->name = "<b>$row->name</b>";
        
        if($fields['-single']){
            $row->personsCnt = str_pad($row->personsCnt, '6', '0', STR_PAD_LEFT);
            $row->companiesCnt = str_pad($row->companiesCnt, '6', '0', STR_PAD_LEFT);
        }

     
        $row->companiesCnt = new ET("<b>[#1#]</b>", ht::createLink($row->companiesCnt, array('crm_Companies', 'groupId' => $rec->id, 'users' => 'all_users')));
        $row->personsCnt = new ET("<b>[#1#]</b>", ht::createLink($row->personsCnt, array('crm_Persons', 'groupId' => $rec->id, 'users' => 'all_users')));
             
        // Ако групата се състои само от фирми
        if ($rec->allow == 'companies') {
            unset($row->personsCnt);
        }
            
        if ($rec->allow == 'persons') {
            // ще показваме само броя на лицата
            unset($row->companiesCnt);
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
        
        
        $nAffected = $nUpdated = 0;
        
        // BEGIN За всеки елемент от масива
        foreach ($data as $newData) {
            
            $newRec = (object) $newData;
            
            $rec = $mvc->fetch("#sysId = '{$newRec->sysId}'");
            $flagChange = FALSE;

            if(!$rec) {
                $rec = $mvc->fetch("LOWER(#name) = LOWER('{$newRec->name}')");
                $flagChange = TRUE;
            }
            
            if(!$rec) {
                $rec = $mvc->fetch("LOWER(#name) = LOWER('{$newRec->exName}')");
                $flagChange = TRUE;
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

            if($flagChange) {
                $nUpdated++;
            }
            
            $mvc->save($rec);
        }
        
        // END За всеки елемент от масива
        
        if ($nAffected) {
            $res .= "<li class='debug-new'>Добавени са {$nAffected} групи.</li>";
        }

        if ($flagChange) {
            $res .= "<li class='debug-new'>Обновени са {$nUpdated} групи.</li>";
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
     * Форсира група от визитника
     * @TODO в cat_Groups има същата функция да се изнесе някъде най-добре
     *
     * @param   string  $name       Име на групата. Съдържа целия път
     * @param   int     $parentId   Id на родител
     * @param   boolean $force
     *
     * @return  int|NULL            id на групата
     */
    public static function force($name, $parentId = NULL, $force = TRUE)
    {
    	static $groups = array();
    	$parentIdNumb = (int) $parentId;
    
    	if(!($res = $groups[$parentIdNumb][$name])) {
    
    		if(strpos($name, '»')) {
    			$gArr = explode('»', $name);
    			foreach($gArr as $gName) {
    				$gName = trim($gName);
    				$parentId = self::force($gName, $parentId, $force);
    			}
    
    			$res = $parentId;
    		} else {
    
    			if($parentId === NULL) {
    				$cond = "AND #parentId IS NULL";
    			} else {
    				expect(is_numeric($parentId), $parentId);
    
    				$cond = "AND #parentId = {$parentId}";
    			}
    
    			$gRec = self::fetch(array("LOWER(#name) = LOWER('[#1#]'){$cond}", $name));
    
    			if(isset($gRec->name)) {
    				$res = $gRec->id;
    			} else {
    				if ($force) {
    					$gRec = (object) array('name' => $name, 'companiesCnt' => 0, 'personsCnt' => 0, 'parentId' => $parentId);
  						self::save($gRec);
    
    					$res = $gRec->id;
    				} else {
    					$res = NULL;
    				}
    			}
    		}
    
    		$groups[$parentIdNumb][$name] = $res;
    	}
    
    	return $res;
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
    
    
    /**
     * Връща имената на всички полета и аналога от модела в който се използват
     * 
     * @param string $p
     * 
     * @return array
     */
    protected function getFieldsFor($p)
    {
        $arr = array();
        
        switch ($p) {
            case self::$pCompanies:
                $arr['company'] = 'crm_Companies::name';
                $arr['country'] = 'crm_Companies::country';
                $arr['pCode'] = 'crm_Companies::pCode';
                $arr['place'] = 'crm_Companies::place';
                $arr['address'] = 'crm_Companies::address';
                $arr['email'] = 'crm_Companies::email';
                $arr['tel'] = 'crm_Companies::tel';
                $arr['fax'] = 'crm_Companies::fax';
            break;
            
            case self::$pPersons:
                
                $arr['salutation'] = 'crm_Persons::salutation';
                $arr['person'] = 'crm_Persons::name';
                $arr['country'] = 'crm_Persons::country';
                $arr['pCode'] = 'crm_Persons::pCode';
                $arr['place'] = 'crm_Persons::place';
                $arr['address'] = 'crm_Persons::address';
                $arr['email'] = 'crm_Persons::email';
                $arr['tel'] = 'crm_Persons::tel';
                $arr['mobile'] = 'crm_Persons::mobile';
                $arr['fax'] = 'crm_Persons::fax';
                
            break;
            
            case self::$pPersonsBiz:
                $arr['salutation'] = 'crm_Persons::salutation';
                $arr['person'] = 'crm_Persons::name';
                $arr['company'] = 'crm_Persons::buzCompanyId';
                $arr['position'] = 'crm_Persons::buzPosition';
                $arr['email'] = 'crm_Persons::buzEmail';
                $arr['tel'] = 'crm_Persons::buzTel';
                $arr['fax'] = 'crm_Persons::buzFax';
                $arr['country'] = 'crm_Companies::country';
                $arr['pCode'] = 'crm_Companies::pCode';
                $arr['place'] = 'crm_Companies::place';
                $arr['address'] = 'crm_Companies::address';
            break;
            
            default:
                
            break;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща масив с възможните избори за персонализиране на групата
     * 
     * @param integer $id
     * @param boolean $escaped
     * @param boolean $useTitle
     * 
     * @return array
     */
    protected function getGroupsChoise($id, $escaped = TRUE, $useTitle = TRUE)
    {
        $resArr = array();
        
        if (!isset($id) || ($id <= 0)) return $resArr;
        
        $rec = $this->fetch($id);
        
        if (!$rec) return $resArr;
        
        $title = '';
        
        if ($useTitle) {
            $title = $this->getPersonalizationTitle($id, $escaped) . ': ';
        }
        
        // Ако има фирми
        if (isset($rec->companiesCnt) && ($rec->companiesCnt > 0)) {
            $keyC = $id . '_' . self::$pCompanies;
            $resArr[$keyC] = $title . tr('фирми');
        }
        
        // Ако има лица
        if (isset($rec->personsCnt) && ($rec->personsCnt > 0)) {
            $keyP = $id . '_' . self::$pPersons;
            $keyPb = $id . '_' . self::$pPersonsBiz;
            
            $resArr[$keyP] = $title . tr('лица|* - |*лични данни');
            $resArr[$keyPb] = $title . tr('лица|* - |*бизнес данни');
        }
        
        return $resArr;
    }
    

    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        list(, $p) = explode('_', $id);
        
        $filedsArr = (array)$this->getFieldsFor($p);
        $keysArr = array_keys($filedsArr);
        $nArr = array_combine($keysArr, $keysArr);
        
        return $nArr;
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        $resArr = array();
        
        list($id, $p) = explode('_', $id);
        
        // Вземаме всички полета
        $fieldsArr = (array)$this->getFieldsFor($p);
        
        $allClass = array();
        
        // Вземаме всички класове
        foreach ($fieldsArr as $field => $val) {
            list($class, $f) = explode('::', $val);
            $allClass[$class][$field] = $f;
        }
        
        // Вземаме всички записи за класовете и ги добавяме в съответните полета
        foreach ($allClass as $class => $fArr) {
            
            $query = $class::getQuery();
            $query->likeKeylist('groupList', $id);
            
            if ($limit) {
                $query->limit($limit);
            }
            
            // Премахваме оттеглените и спрените
            $query->where("#state != 'rejected'");
            $query->where("#state != 'closed'");
            
            while ($rec = $query->fetch()) {
                foreach ((array)$fArr as $name => $fieldName)
                $resArr[$rec->id][$name] = $rec->$fieldName;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string|object $id
     * @param boolean $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = TRUE)
    {
        $groupChoiseArr = array();
        $fullId = $id;
        if (is_object($id)) {
            $rec = $id;
        } else {
            list($id) = explode('_', $id);
            $rec = $this->fetch($id);
            
            $groupChoiseArr = $this->getGroupsChoise($id, $verbal, FALSE);
        }
        
        // Ако трябва да е вебална стойност
        if ($verbal) {
            $title = $this->getVerbal($rec, 'name');
        } else {
            $title = $rec->name;
        }
        
        if ($groupChoiseArr[$fullId]) {
            $title .= ': ' . $groupChoiseArr[$fullId];
        }
        
        return $title;
    }
    
    
    /**
     * Дали потребителя може да използва дадения източник на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $userId
     *
     * @return boolean
     */
    public function canUsePersonalization($id, $userId = NULL)
    {
        // Всеки който има права до сингъла на записа, може да го използва
        if (isset($id)) {
            list($id) = explode('_', $id);
            if ($this->haveRightFor('single', $id, $userId)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас, които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $userId
     * @param integer $folderId
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        $query = $this->getQuery();
        $query->orderBy("createdOn", 'DESC');
        
        $query->where("#state != 'rejected'");
        
        $query->where("#companiesCnt IS NOT NULL");
        $query->orWhere("#companiesCnt != 0");
        $query->orWhere("#personsCnt IS NOT NULL");
        $query->orWhere("#personsCnt != 0");
        
        // Обхождаме откритите резултати
        while ($rec = $query->fetch()) {
            
            // Вземаме всички възможни избори за съответния запис
            $allGroupChoise = $this->getGroupsChoise($rec->id, FALSE, TRUE);
            
            $continue = TRUE;
            
            if (!$allGroupChoise) continue;
            
            // Ако има права за използване на поне един от изборите
            foreach ($allGroupChoise as $key => $dummy) {
                if ($this->canUsePersonalization($key, $userId)) {
                    $continue = FALSE;
                    continue;
                }
            }
            
            if ($continue) continue;
            
            $resArr += $allGroupChoise;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     * 
     * @param integer $id
     * 
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $resArr = $this->getGroupsChoise($id, FALSE, FALSE);
        
        return $resArr;
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        list($id, $p) = explode('_', $id);
        
        if ($p == self::$pCompanies) {
            $url = array('crm_Companies', 'groupId' => $id);
        } else {
            $url = array('crm_Persons', 'groupId' => $id);
        }
        
        // Създаваме линк към сингъла листа
        $title = $this->getPersonalizationTitle($id, TRUE);
        $link = ht::createLink($title, $url);
        
        return $link;
    }
    
    
    /**
     * Връща езика за източника на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return string
     */
    public function getPersonalizationLg($id)
    {
        
        return ;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if ($data->rec->companiesCnt || $data->rec->personsCnt) {
            if (blast_Emails::haveRightFor('add')) {
                $data->toolbar->addBtn('Циркулярен имейл', array($mvc, 'choseBlast', $data->rec->id, 'class' => 'blast_Emails', 'ret_url' => TRUE), 'id=btnEmails','ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
            }
            
            if (callcenter_SMS::haveRightFor('send')) {
                Request::setProtected(array('perSrcClassId', 'perSrcObjectId'));
                $data->toolbar->addBtn('Циркулярен SMS', array('callcenter_SMS', 'blastSms', 'perSrcClassId' => $mvc->getClassId(), 'perSrcObjectId' => $data->rec->id, 'ret_url' => TRUE), 'id=btnSms','ef_icon = img/16/sms.png,title=Създаване на циркулярен SMS');
            }
        }
    }
    
    
    /**
     * Екшън за избор на типа на циркулярен имейл за група
     */
    function act_choseBlast()
    {
        $id = Request::get('id', 'int');
        $class = Request::get('class');
        
        expect(in_array($class, array('blast_Emails')));
        
        $rec = $this->fetch($id);
        expect($rec);
        
        $this->requireRightFor('single', $rec);
        
        $blastClass = cls::get($class);
        $blastClass->requireRightFor('add');
        
        $groupChoiseArr = $this->getGroupsChoise($id, FALSE);
        expect($groupChoiseArr);
        
        Request::setProtected(array('perSrcObjectId', 'perSrcClassId'));
        
        $redirectTo = array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($this), 'ret_url' => TRUE);
        
        // Ако има само един възможен избор, редиректваме към създаването
        if (count($groupChoiseArr) == 1) {
            
            $redirectTo['perSrcObjectId'] = key($groupChoiseArr);
            
            return new Redirect($redirectTo);
        }
        
    	$form = cls::get('core_Form');
    	$form->title = "Избор на група";
    	
    	$form->FLD('type', 'enum()', 'caption=Тип,mandatory,silent');
        
    	$form->setOptions('type', $groupChoiseArr);
    	
        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png, title = Избор');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->input();
        
        // Ако е събмитната формата
        if($form->isSubmitted()){
        	$rec = $form->rec;
        	
            $redirectTo['perSrcObjectId'] = $rec->type;
            
            return new Redirect($redirectTo);
        }
        
    	$tpl = $this->renderWrapping($form->renderHtml());
    	
    	return $tpl;
    }
}
