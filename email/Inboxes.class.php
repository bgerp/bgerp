<?php 


/**
 * @todo Да се премахне и да се сложи в конфигурационния файл
 * Пощенска кутия по - подразбиране
 */
defIfNot('MAIL_DOMAIN', 'ep-bags.com');


/**
 * Email адреси
 *
 */
class email_Inboxes extends core_Manager
{
	/**
     * 
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_Created, doc_FolderPlg, plg_RowTools';    
	
	/**
     *  Заглавие на таблицата
     */
    var $title = "Имейл адреси";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, email';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, email';
    
    
    /**
     *  
     */
    var $canView = 'admin, rip, user';
    
    
    /**
     *  
     */
    var $canList = 'admin, email, user';
    
    /**
     *  
     */
    var $canDelete = 'admin, email';
    
	
	/**
	 * 
	 */
	var $canRip = 'admin, email';
    
    
	/**
	 * Интерфайси, поддържани от този мениджър
	 */
	var $interfaces =  
                        // Интерфейс за корица на папка
                        'doc_FolderIntf';
    
    var $searchFields = 'name';

    var $singleTitle = 'Пощ. кутия';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';

    var $rowToolsSingleField = 'name';
	
    
    /**
     * Всички пощенски кутии
     */
    static $allBoxes;
    
    
    /**
     *  Описание на модела (таблицата)
     */
	function description()
    {
        // Поща
        $this->FLD('name', 'varchar(128)', 'caption=Име, mandatory');
        $this->FLD('domain', 'varchar(32)', 'caption=Домейн');
        
        $this->setDbUnique('name,domain');
    }
    
	
    /**
     * Връща името
     */
	function getFolderTitle($id)
    {   
        $rec = $this->fetch($id);

    	$title = $rec->name . '@' . $rec->domain;
    	
    	return strtolower($title);
    }


    /**
	 * Преди вкарване на запис в модела, проверява дали има вече регистрирана корица
	 */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		if (!($rec->domain)) {
    		$rec->domain = MAIL_DOMAIN;
    	}
	}
	
	
	/**
	 * Преди рендиране на формата за редактираен
	 */
	function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setDefault('access', 'private');
	}
	
	
	/**
	 * Намира първия мейл в стринга, който е записан в системата
	 */
	function findFirstInbox($str)
	{
		if (!self::$allBoxes) {
			$query = email_Inboxes::getQuery();
			$query->show('name, domain');
			
			while ($rec = $query->fetch()) {
				$mail = $rec->name . '@' . $rec->domain;
				self::$allBoxes[$mail] = TRUE;
			}
		}
		
		$pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
		$values = preg_split($pattern, $str, NULL, PREG_SPLIT_NO_EMPTY);
		
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				if (self::$allBoxes[$value]) {
					
					return $value;
				}
			}
		}
		
		return NULL;
	}
	
}