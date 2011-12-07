<?php 


/**
 * Лог на изпращаните писма
 */
class blast_ListSend extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Лог на изпращаните писма";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, blast';
    
    
    /**
     *  
     */
    var $canEdit = 'no_one';
    
    
    /**
     *  
     */
    var $canAdd = 'no_one';
    
    
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
    var $canDelete = 'no_one';
    
	
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
