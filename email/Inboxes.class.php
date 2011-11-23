<?php 


/**
 * @todo Да се премахне и да се сложи в конфигурационния файл
 * Пощенска кутия по - подразбиране
 */
defIfNot('MAIL_DOMAIN', 'ep-bags.com');


/**
 * 
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
    //    var $loadList = 'plg_Created,plg_Rejected,email_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search ';
    
	
	/**
     *  Заглавие на таблицата
     */
    var $title = "Емайл адреси";
    
    
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
	var $interfaces = array(
		// Интерфейс за корица на папка
        'doc_FolderIntf'
    );
    
    var $searchFields = 'name';

    var $singleTitle = 'Кюп';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';

    var $rowToolsSingleField = 'name';
	
    
    /**
     * Всички пощенски кутии
     */
    protected $allBoxes;
    
    
    /**
     *  Описание на модела (таблицата)
     */
	function description()
    {
        // Поща
        $this->FLD('name', 'varchar(128)', 'caption=Име, mandatory');
        $this->FLD('domain', 'varchar(32)', 'caption=Домейн');
        
    }
    
	
	/**
     * Намира записа, отговарящ на входния параметър. Ако няма такъв - създава го.
     * Връща id на папка, която отговаря на записа. Ако е необходимо - създава я
     */
    static function forceCoverAndFolder($rec)
    {
    	
    	if (!$rec->name) 
    	{
    		return ;
    	}
    	
        if(!$rec->id) {
            //expect($lName = trim(mb_strtolower($rec->name)));
            $rec->id = email_Inboxes::fetchField("LOWER(#name) = '$lName'", 'id');
        }

        if(!$rec->id) {
            email_Inboxes::save($rec);
        }

        if(!$rec->folderId) {
            $rec->folderId = email_Inboxes::forceFolder($rec);
        }
		
        return $rec->folderId;
    }
	
	
    /**
     * Връща името
     */
	function getFolderTitle($rec)
    {
    	$name = strtolower($rec->name);
    	$title = $name . '@' . $rec->domain;
    	
    	return $title;
    }
	
	
	/**
	 * След вкарване на записа в модела 
	 */
	function on_AfterSave($mvc, $id, $rec)
	{
		email_Inboxes::forceCoverAndFolder($rec);
	}
	
	
	/**
	 * Преди вкарване на запис в модела, проверява дали има вече регистрирана корица
	 */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		if (!($rec->domain)) {
    		$rec->domain = MAIL_DOMAIN;
    	}
    	
		$query = email_Inboxes::getQuery();
		$query->where("#name = '$rec->name'");
		$query->where("#domain = '$rec->domain'");
		
		if ($recNew = $query->fetch()) {
			$rec->id = $recNew->id;
			$rec->folderId = $recNew->folderId;
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
		if (!$this->allBoxes) {
			$query = email_Inboxes::getQuery();
			$query->show('name, domain');
			
			while ($rec = $query->fetch()) {
				$mail = $rec->name . '@' . $rec->domain;
				$this->allBoxes[$mail] = TRUE;
			}
		}
		
		$pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
		$values = preg_split( $pattern, $str, NULL, PREG_SPLIT_NO_EMPTY );
		
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				if (type_Email::isValidEmail($value)) {
					if ($this->allBoxes[$value]) {
						$firstMail = $value;
						break;
					}
					if (!($first)) {
						$first = $value;
					}
				}
				
			}
		}
		
		if (!$firstMail) {
			$firstMail = $first;
		}
		
		return $firstMail;
	}
	
}