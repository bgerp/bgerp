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
    var $canRead = 'admin,ceo';
    

    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin,ceo';

    
    /**
     * Заглавие
     */
    var $title = 'Групи от картинки';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'user';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,fileman_Wrapper,fileman_GalleryWrapper,plg_Created,fileman_GalleryTitlePlg, plg_Clone, plg_State2";
    
    
    /**
     * Полета за изглед
     */
    var $listFields = 'id,title,roles,columns,tWidth,tHeight,width,height,createdOn,createdBy,state';
    
    
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

        $this->FLD('columns', 'int', 'caption=Колони');
     
        $this->FLD('tWidth', 'int', 'caption=Тъмб->Широчина');
        $this->FLD('tHeight', 'int', 'caption=Тъмб->Височина');
        
        $this->FLD('width', 'int', 'caption=Картинка->Широчина');
        $this->FLD('height', 'int', 'caption=Картинка->Височина');
        
        $this->FLD('roles', 'keylist(mvc=core_Roles, select=role, allowEmpty,groupBy=type)', 'caption=Роли, width=100%,placeholder=Всички');
        
        $this->setDbUnique('title, position');
    }
    
    /**
     * допълнение към подготовката на вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
     	$row->{$mvc->galleryTitleFieldName} = "[gallery=#" . $rec->{$mvc->galleryTitleFieldName} . "]";
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
    		4 => "columns",
    		5 => "tWidth",
    		6 => "tHeight",
    		7 => "width",
    		8 => "height",
    		9 => "roles",
    	);
    	    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, array('delimiter' => '|'), FALSE); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
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
        // Ако има запис и потребителя не е CEO
        if ($rec && !haveRole('ceo')) {
            
            // Ако ще изтриваме или редактираме група
            if ($action == 'delete' || $action == 'edit') {
                
                // Ако не сме създател
                if ($rec->createdBy != $userId) {
                    
                    // Да не можем да редактираме
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
	/**
	 * 
	 * 
	 * @param fileman_GalleryGroups $mvc
	 * @param core_Query $query
	 */
    function on_AfterGetQuery($mvc, $query)
    {
        // Ограничаваме заявката да се показват само достъпните
        static::restrictRoles($query);
    }
    
    
    /**
     * Ограничаваме заявката да се показват само достъпните групи
     * 
     * @param core_Query $query
     * @param string $rolesFieldName
     */
    static function restrictRoles(&$query, $rolesFieldName='roles')
    {
        // Ако име роля ceo да може да вижда всички
        if (haveRole('ceo')) return ;
        
        // Ролите на текущия потребител
        $userRoles = core_Users::getRoles();
        
        // Всички групи без роли
        $query->where("#{$rolesFieldName} IS NULL");
        
        // Ако е зададена роля показваме само тях
        $query->likeKeylist($rolesFieldName, $userRoles, TRUE);
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
    
    
    /**
     * Подготовка на филтър формата
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
	{
//	    $data->query->orderBy('createdOn', 'DESC');
	}
}
