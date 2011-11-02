<?php 


/**
 * 
 * Email адреси
 *
 */
class email_Inboxes extends core_Manager
{
	
	
	/**
     *  Заглавие на таблицата
     */
    var $title = "Email адреси";
    
    
    
	/**
	 * Интерфайси, поддържани от този мениджър
	 */
	var $interfaces = array(
		// Интерфейс за корица на папка
        'doc_FolderIntf'
    );
    
    
    /**
     * 
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper, plg_Created';
	
	
    /**
     *  Описание на модела (таблицата)
     */
	function description()
    {
        // Поща
        $this->FLD('email', 'varchar', 'caption=e-mail');
        
    }
    
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

}

?>