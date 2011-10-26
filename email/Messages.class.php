<?php 

/**
 * 
 * Емейли
 *
 */
class email_Messages extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Емейли";
    
    
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
	var $loadList = 'email_Wrapper';
    //var $loadList = 'plg_RowTools, plg_Created, plg_Selected, rip_Wrapper, plg_State';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('emailId', 'key(mvc=email_Accounts,select=eMail)', 'caption=Е-мейл');
		$this->FLD("messageId", "varchar", "caption=Съобщение ID");
		$this->FLD("subject", "varchar", "caption=Тема");
		$this->FLD("from", "varchar", 'caption=От');
		$this->FLD("fromName", "varchar", 'caption=От Име');
		$this->FLD("to", "varchar", 'caption=До');
		$this->FLD("toName", "varchar", 'caption=До Име');
		$this->FLD("headers", "blob(70000)", 'caption=Хедъри');
		$this->FLD("textPart", "blob(70000)", 'caption=Текстова част');
		$this->FLD("htmlPart", "blob(70000)", 'caption=HTML част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
		
		$this->FLD('files', 'keylist(mvc=fileman_Files,select=name,maxColumns=1)', 'caption=Файлове');
		
		
		//$this->setDbUnique('eMail');
		
	}
	
	
	/**
     * Изпълнява се след създаването на таблицата
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	cls::get('email_Imap');
        if(!is_dir(IMAP_TEMP_PATH)) {
            if( !mkdir(IMAP_TEMP_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . IMAP_TEMP_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . IMAP_TEMP_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . IMAP_TEMP_PATH . '"</font>';
        }
        
        return $res;
    }
}

?>