<?php

/**
 * Клас 'email_Unsorted' - Корици на папки с несортирани документи
 *
 * @category   Experta Framework
 * @package    email
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class email_Unsorted extends core_Master
{   
    var $loadList = 'plg_Created,plg_Rejected,email_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search ';

    var $title    = "Несортирани папки";

   // var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';

 
    var $searchFields = 'name';

    var $singleTitle = 'Кюп';
    
    var $singleIcon  = 'img/16/inbox-image-icon.png';

    var $rowToolsSingleField = 'name';

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Име');
        $this->setDbUnique('name');
    }

    
    /**
     * Намира записа, отговарящ на входния параметър. Ако няма такъв - създава го.
     * Връща id на папка, която отговаря на записа. Ако е необходимо - създава я
     */
    static function forceCoverAndFolder($rec)
    { 
        if(!$rec->id) {
            expect($lName = trim(mb_strtolower($rec->name)));
            $rec->id = email_Unsorted::fetchField("LOWER(#name) = '$lName'", 'id');
        }

        if(!$rec->id) {
            email_Unsorted::save($rec);
        }

        if(!$rec->folderId) {
            $rec->folderId = email_Unsorted::forceFolder($rec);
        }

        return $rec->folderId;
    }


 }