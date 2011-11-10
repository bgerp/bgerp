<?php 


/**
 * Шаблон за писма за масово разпращане
 */
class blast_Emails extends core_Master
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Шаблон за масови писма";
    
    
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
	var $loadList = 'blast_Wrapper, plg_Created, doc_DocumentPlg, plg_KeyToLink, plg_State';
       	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
		$this->FLD('from', 'varchar', 'caption=От');
		$this->FLD('subject', 'varchar', 'caption=Тема');
		$this->FLD('textPart', 'richtext', 'caption=Tекстова част');
		$this->FLD('htmlPart', 'text', 'caption=HTML част');
		$this->FLD('file1', 'key(mvc=fileman_Files, select=name)', 'caption=Файл1, hyperlink');
		$this->FLD('file2', 'key(mvc=fileman_Files, select=name)', 'caption=Файл2, hyperlink');
		$this->FLD('file3', 'key(mvc=fileman_Files, select=name)', 'caption=Файл3, hyperlink');
		$this->FLD('sendPerMinut', 'int', 'caption=Изпращания в минута');
		$this->FLD('startOn', 'datetime', 'caption=Време на започване');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
?>