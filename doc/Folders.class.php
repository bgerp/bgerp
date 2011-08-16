<?php

/**
 * Клас 'doc_Folders' - Папки с нишки от документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_Folders extends core_Manager
{   
    var $loadList = 'plg_Created,plg_rejected,plg_Modified,plg_RowTools,doc_Wrapper';

    var $title    = "Папки с нишки от документи";

    function description()
    {
        // Определящ обект за папката
        $this->FLD('coverClass' , 'class(interface=doc_FolderIntf)', 'caption=Корица->Клас');
        $this->FLD('coverId' , 'int', 'caption=Корица->Обект');
        
        // Информация за папката
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(open,waiting,close,rejected)', 'caption=Състояние');
        $this->FLD('allTrdCnt' , 'int', 'caption=Нишки->Всички');
        $this->FLD('openTrdCnt' , 'int', 'caption=Нишки->Отворени');

        // Достъп
        $this->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Достъп');
        $this->FLD('inCharge' , 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('sharedWithRoles' , 'keylist(mvc=core_Roles)', 'caption=Споделено с->Роли');
        $this->FLD('sharedWithUsers' , 'keylist(mvc=core_Users)', 'caption=Споделено с->Потребители');

    }
}