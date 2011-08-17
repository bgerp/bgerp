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
class doc_Thread extends core_Manager
{   
    var $loadList = 'plg_Created,plg_rejected,plg_Modified,plg_RowTools,doc_Wrapper';

    var $title    = "Нишки от документи";

    function description()
    {
        // Информация за нишкаъа
        $this->FLD('folderId' ,  'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(open,waiting,close,rejected)', 'caption=Състояние');
        $this->FLD('allDocCnt' , 'int', 'caption=Бр. документи->Всички');
        $this->FLD('pubDocCnt' , 'int', 'caption=Бр. документи->Публични');

        // Достъп
        $this->FLD('access', 'enum(public=Публичен,team=Екипен)', 'caption=Достъп');
        $this->FLD('sharedWithUsers' , 'keylist(mvc=core_Users)', 'caption=Споделяне');
    }
}