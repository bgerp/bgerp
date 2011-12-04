<?php 

/**
 * 
 * Акаунти за използване на имейли
 *
 */
class email_Accounts extends core_Manager
{
    /**
     *  Заглавие на таблицата
     */
    var $title = "Акаунти за имейлите";
    
    var $singleTitle = 'Пощ. сметка';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';
    
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
    var $canView = 'admin, rip';
    
    
    /**
     *  
     */
    var $canList = 'admin, email';
    
    /**
     *  
     */
    var $canDelete = 'admin, email';
    
	
	/**
	 * 
	 */
	var $canRip = 'admin, email';
	
    
    /**
     * 
     */
	var $loadList = 'plg_State, email_Wrapper, plg_RowTools, doc_FolderPlg';
    //var $loadList = 'plg_RowTools, plg_Created, plg_Current, rip_Wrapper, plg_State';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("eMail", "varchar", "caption=Имейл");
		$this->FLD("server", "varchar", 'caption=Сървър');
		$this->FLD('user', 'varchar', 'caption=Потребителско име');
		$this->FLD('password', 'password(64)', 'caption=Парола');
		$this->FLD('state', 'enum(active=Активен, stopped=Спрян)', 'caption=Статус');
		$this->FLD('period', 'int', 'caption=Период');
		
		$this->FLD('port', 'int', 'caption=Порт');
		$this->FLD('subHost', 'varchar', 'caption=Суб Хост');
		$this->FLD('ssl', 'varchar', 'caption=Сертификат');
		
		// Идеално това поле би било чек-бокс, но нещо не се получава с рендирането.
		$this->FLD('bypassRoutingRules', 'enum(no=Да, yes=Не)', 'caption=Сортиране на писмата');
		
		$this->setDbUnique('eMail');
	}


    /**
     *
     */
    static function getRecTitle($rec)
    {
    	return $rec->eMail;
    }
}