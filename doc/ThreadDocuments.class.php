<?php

/**
 * Клас 'doc_ThreadDocuments' - Контейнери за документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_ThreadDocuments extends core_Manager
{   
    var $loadList = 'plg_Created, plg_Rejected,plg_Modified,plg_RowTools,doc_Wrapper';

    var $title    = "Документи в нишките";

    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' ,  'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' ,  'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');

        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' ,  'varchar(128)', 'caption=Статус');
        $this->FLD('amount' ,  'double', 'caption=Сума');
     }
}