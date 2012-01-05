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
    
    
	/**
	 * 
	 */
	function on_AfterPrepareEditForm($mvc, &$data)
	{
		//При нов запис да показва порта по подразбиране
		if (!$data->form->rec->id) {
			$data->form->rec->port = '143';
		}
	}
	
   	/**
	 * Добавя имаил акаунт ако има зададен такъв в конфигурационния файл
	 */
	function on_AfterSetupMVC($mvc, $res)
	{
		if (constant("BGERP_DEFAULT_EMAIL_USER") &&
			constant("BGERP_DEFAULT_EMAIL_HOST") &&
			constant("BGERP_DEFAULT_EMAIL_PASSWORD")) {
			
			$rec = $mvc->fetch("#eMail = '". BGERP_DEFAULT_EMAIL_USER ."'");
			
			$rec->eMail = BGERP_DEFAULT_EMAIL_USER;
			$rec->server = BGERP_DEFAULT_EMAIL_HOST;
			$rec->user = BGERP_DEFAULT_EMAIL_USER;
			$rec->password = BGERP_DEFAULT_EMAIL_PASSWORD;
			$rec->period = 1;
			$rec->port = 143;
			$rec->bypassRoutingRules = "no";
			if (!$rec->id) {
				$res .= "<li>Добавен емаил по подразбиране";
			} else $res .= "<li>Обновен емаил по подразбиране";
			
			$mvc->save($rec);
		} else {
			$res .= "<li>Липсват данни за емаил по подразбиране";
		}
	}
}