<?php 


/**
 * Пощенска кутия по - подразбиране
 */
defIfNot('BGERP_DEFAULT_EMAIL_DOMAIN', 'bgerp.com');


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
    var $loadList = 'email_Wrapper, plg_State, plg_Created, doc_FolderPlg, plg_RowTools';    
    
	/**
     *  Заглавие на таблицата
     */
    var $title = "Имейл кутии";
    
    
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
    var $canView = 'admin, email';
    
    
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
	var $canEmail = 'admin, email';
    
    
	/**
	 * Интерфайси, поддържани от този мениджър
	 */
	var $interfaces =  
                        // Интерфейс за корица на папка
                        'doc_FolderIntf';
    
    var $searchFields = 'email';

    var $singleTitle = 'Е-кутия';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';

    var $rowToolsSingleField = 'email';
    
    var $listFields = 'id, email, type, bypassRoutingRules=Общ, folderId, inCharge, access, shared, createdOn, createdBy';
	
    
    /**
     * Всички пощенски кутии
     */
    static $allBoxes;
    
    
    /**
     *  Описание на модела (таблицата)
     */
	function description()
    {
		$this->FLD("email", "varchar", "caption=Имейл");
		$this->FLD("type", "enum(internal=Вътрешен, pop3=POP3, imap=IMAP)", 'caption=Тип');
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
		
		$this->setDbUnique('email');
    }
    
    
    
    /**
     * Връща името
     */
	function getFolderTitle($id)
    {   
        $rec = $this->fetch($id);

    	$title = $rec->email;
    	
    	return strtolower($title);
    }


    /**
     *
     */
    static function getRecTitle($rec)
    {
    	return $rec->email;
    }
    
    
    /**
	 * Преди вкарване на запис в модела, проверява дали има вече регистрирана корица
	 */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		list($name, $domain) = explode('@', $rec->email, 2);
		 
		if (empty($domain)) {
    		$domain = BGERP_DEFAULT_EMAIL_DOMAIN;
    	}
    	
    	$rec->email = "{$name}@{$domain}";
	}
	
	
	/**
	 * Преди рендиране на формата за редактиране
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
			
			while ($rec = $query->fetch()) {
				self::$allBoxes[$rec->email] = TRUE;
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
	
	
   	/**
	 * Добавя имаил акаунт ако има зададен такъв в конфигурационния файл
	 */
	function on_AfterSetupMVC($mvc, $res)
	{
		if (defined("BGERP_DEFAULT_EMAIL_USER") &&
			defined("BGERP_DEFAULT_EMAIL_HOST") &&
			defined("BGERP_DEFAULT_EMAIL_PASSWORD")) {
			
			$rec = $mvc->fetch("#email = '". BGERP_DEFAULT_EMAIL_FROM ."'");
			
			$rec->email    = BGERP_DEFAULT_EMAIL_FROM;
			$rec->server   = BGERP_DEFAULT_EMAIL_HOST;
			$rec->user     = BGERP_DEFAULT_EMAIL_USER;
			$rec->password = BGERP_DEFAULT_EMAIL_PASSWORD;
			$rec->period   = 1;
			$rec->port     = 143;
			$rec->bypassRoutingRules = "no";
			if (!$rec->id) {
				$res .= "<li>Добавен имейл по подразбиране";
			} else {
				$res .= "<li>Обновен имейл по подразбиране";	
			}
			
			$mvc->save($rec);
		} else {
			$res .= "<li>Липсват данни за имейл по подразбиране";
		}
	}
	
	
	/**
	 * Определя дали един имейл адрес е "ОБЩ" или не е.
	 *
	 * @param string $email
	 * @return boolean
	 */
	public static function isGeneric($email)
	{
		$rec = static::fetch("#email = '{$email}'");
		
		return ($rec->bypassRoutingRules == 'no');
	}
}