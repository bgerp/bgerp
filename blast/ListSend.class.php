<?php 


/**
 * Листа за изпращане
 */
class blast_ListSend extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Списък за изпращане";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, blast';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, blast';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, blast';
    
    
    /**
     *  
     */
    var $canView = 'admin, blast';
    
    
    /**
     *  
     */
    var $canList = 'admin, blast';
    
    /**
     *  
     */
    var $canDelete = 'admin, blast';
    
	
	/**
	 * 
	 */
	var $canBlast = 'admin, blast';
	
    
    /**
     * 
     */
	var $loadList = 'blast_Wrapper';
       	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('mail', 'key(mvc=blast_ListDetails, select=key)', 'caption=Имейл');
		$this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
		$this->FLD('sended', 'datetime', 'caption=Дата, input=none');
		
		$this->setDbUnique('mail,listId');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
?>