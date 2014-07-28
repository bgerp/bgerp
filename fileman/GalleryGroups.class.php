<?php


/**
 * Клас 'fileman_GalleryGroups' - групи от картинки
 *
 *
 * @category  bgerp
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_GalleryGroups extends core_Manager
{
    
    
    /**
     * 
     */
    var $canRead = 'user';
    

    /**
     * Кой  може да пише?
     */
    var $canWrite = 'user';

    
    /**
     * Заглавие
     */
    var $title = 'Групи от картинки';
    
    
    /**
	 * Кой може да използва групите
	 */
    var $canUsegroup = 'user';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'user';
    
	
	/**
	 * Кой може да променя съсъоянието
	 * @see plg_State2
	 */
    var $canChangestate = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,fileman_Wrapper,plg_Created, plg_Modified, fileman_GalleryTitlePlg, plg_Clone, plg_State2";
    
    
    /**
     * Полета за изглед
     */
    var $listFields = 'id,title,roles,sharedTo,tWidth,tHeight,width,height,createdOn,createdBy,state';
    
    
    /**
     * Името на полето, което ще се използва от плъгина
     * @see fileman_GalleryTitlePlg
     */
    var $galleryTitleFieldName = 'title';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'cms_GalleryGroups';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('position', 'enum(none=Без стил,center=Център,left=Ляво,right=Дясно)', 'caption=Позиция,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие');
        $this->FLD('tpl', 'html', 'caption=Шаблон');
        
        $this->FLD('style', 'varchar', 'caption=Стил');

        $this->FLD('tWidth', 'int', 'caption=Тъмб->Широчина');
        $this->FLD('tHeight', 'int', 'caption=Тъмб->Височина');
        
        $this->FLD('width', 'int', 'caption=Картинка->Широчина');
        $this->FLD('height', 'int', 'caption=Картинка->Височина');
        
        $this->FLD('roles', 'keylist(mvc=core_Roles, select=role, allowEmpty,groupBy=type)', 'caption=Споделяне->Роли, width=100%');
        $this->FLD('sharedTo', 'type_UserList', 'caption=Споделяне->Потребители, width=100%');
        
        $this->setDbUnique('title, position');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res) 
    {
    	// Пътя до файла с данните 
    	$file = "fileman/csv/GalleryGroups.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array( 
    		0 => "title", 
    		1 => "position",
    		2 => "tpl",
    		3 => "style",
    		4 => "tWidth",
    		5 => "tHeight",
    		6 => "width",
    		7 => "height",
    		8 => "roles",
    	);
    	    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, array('delimiter' => '|'), FALSE); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        // Ако не са подадени роли
        if (!$rec->roles) return ;
        
        // Обхождаме всички роли и от името им определяме id-то
        $rolesStrArr = arr::make($rec->roles);
        foreach ($rolesStrArr as $role) {
            
            $roleId = core_Roles::fetchByName($role);
            
            if (!$roleId) continue;
            
            $roleArr[$roleId] = $roleId;
        }
        
        // Добавяме id-тата на записите
        $rec->roles = type_Keylist::fromArray($roleArr);
    }
    
    
    /**
     * Връща id на групата по подразбиране
     * 
     * @return integer
     */
    static function getDefaultGroupId()
    {
        
        // По подразбиране да се използва групата централни
        return fileman_GalleryGroups::fetchField("#title = 'Централни'");
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
        // Ако има запис и потребителя не е CEO или admin
        if ($rec && !haveRole('ceo, admin')) {
            
            // Ако ще изтриваме или редактираме група
            if ($action == 'delete' || $action == 'edit' || $action == 'changestate') {
                
                // Ако не сме създател
                if ($rec->createdBy != $userId) {
                    
                    // Да не можем да редактираме
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'usegroup') {
                $groupQuery = fileman_GalleryGroups::getQuery();
                $mvc->restrictQuery($groupQuery, $userId);
                $groupQuery->where($rec->id);
                $groupQuery->limit(1);
                
                if (!$groupQuery->fetch()) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако все още има права за изтриване
        if ($requiredRoles != 'no_one' && $rec && $action == 'delete') {
            
            // Да не могат да се трият групи, които са използвани в картиниките
            if (fileman_GalleryImages::fetch("#groupId = '{$rec->id}'")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, &$data)
	{
	    // Ограничаваме записите, които да се показват
	    $mvc->restrictQuery($data->query);
	}
	
	
	/**
     * Поставя изискване да се селектират достъпните записи
     */
    function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
    {
        $nQuery = $mvc->getQuery();
        
        // Ако има условие, от преди това
        $nQuery->where($where);
        
        $mvc->restrictQuery($nQuery);
        
        $nWhere = $nQuery->getWhereAndHaving(TRUE)->w;
        
        $where = trim($nWhere);
    }
	
    
	/**
	 * 
	 * 
	 * @param core_Query $query
	 */
    static function restrictQuery(&$query, $userId=NULL)
    {
        $orToPrevious = FALSE;
        
        // Ограничаваме заявката да се показват само групите споделени с определени потребители
        if (static::restrictRoles($query, $orToPrevious, 'roles', $userId)) {
            $orToPrevious = TRUE;
        }
        
        // Ограничаваме заявката да се показват само групите споделени до определени потребители
        if (static::restrictSharedTo($query, $orToPrevious, 'sharedTo', $userId)) {
            $orToPrevious = TRUE;
        }
        
        // Ограничаваме да се показва само групите създадени от съответния потребител
        static::restrictCreated($query, $orToPrevious, 'createdBy', $userId);
    }
    
    
    /**
     * Ограничаваме заявката да се показват само групите споделени с определени потребители
     * 
     * @param core_Query $query - Заявката
     * @param boolean $orToPrevious - Дали да се залепи с OR към предишната заявка
     * @param string $rolesFieldName - Името на полето
     * @param integer $userId - id на потребителя
     * 
     * @return boolean
     */
    static function restrictRoles(&$query, $orToPrevious=FALSE, $rolesFieldName='roles', $userId=NULL)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) return ;
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) return ;
        
        // Ролите на текущия потребител
        $userRoles = core_Users::getRoles($userId);
        
        // Да се показва групите за които е зададене някоя роля от тези на потребителя
        $query->likeKeylist($rolesFieldName, $userRoles, $orToPrevious);
        
        return TRUE;
    }
    

    
    /**
     * Ограничаваме заявката да се показват само групите споделени до определени потребители
     * 
     * @param core_Query $query - Заявката
     * @param boolean $orToPrevious - Дали да се залепи с OR към предишната заявка
     * @param string $rolesFieldName - Името на полето
     * @param integer $userId - id на потребителя
     * 
     * @return boolean
     */
    static function restrictSharedTo(&$query, $orToPrevious=FALSE, $rolesFieldName='sharedTo', $userId=NULL)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) return ;
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) return ;
        
        // Масив с текущия потребител
        $userIdArr = type_Keylist::fromArray(array($userId=>$userId));
        
        // Да се показва групите за които е зададен е зададен потребителя
        $query->likeKeylist($rolesFieldName, $userIdArr, $orToPrevious);
        
        return TRUE;
    }
    
    
	/**
     * Ограничаваме да се показва само групите създадени от съответния потребител
     * 
     * @param core_Query $query - Заявката
     * @param boolean $orToPrevious - Дали да се залепи с OR към предишната заявка
     * @param string $rolesFieldName - Името на полето
     * @param integer $userId - id на потребителя
     * 
     * @return boolean
     */
    static function restrictCreated(&$query, $orToPrevious=FALSE, $rolesFieldName='createdBy', $userId=NULL)
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) return ;
        
        // Ако не е подаден потребител, да се изпозлва текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) return ;
        
        // Да се показва групите, които са създадени от потребителя
        $query->where("#{$rolesFieldName} = '{$userId}'", $orToPrevious);
        
        return TRUE;
    }
    
    
    /**
     * Подготвя полето за заглавие
     * 
     * @param object $rec
     * @see fileman_GalleryTitlePlg
     */
    function prepareRecTitle(&$rec)
    {
        // Името на полето
        $titleField = $this->galleryTitleFieldName;
        
        // Ако не е зададено заглавието
        if (!$rec->{$titleField} && $rec->position) {
            
            // Определяме заглавието от името на файла
            $rec->{$titleField} = $rec->position;
        }
    }
}
