<?php

/**
 * Клас 'doc_UnsortedFolders' - Корици на папки с несортирани документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_UnsortedFolders extends core_Master
{   
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search ';

    var $title    = "Несортирани папки";

   // var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';

    var $oldClassName = 'email_Unsorted';

    var $searchFields = 'name';

    var $singleTitle = 'Кюп';
    
    var $singleIcon  = 'img/16/basket.png';

    var $rowToolsSingleField = 'name';
	
    
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
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Име');
        $this->setDbUnique('name');
    }

 }