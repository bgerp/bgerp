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

    var $listFields = 'created=Създаване,document=Документи,createdOn=';

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


    /**
     * Филтрира по папка
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $threadId = Request::get('threadId', 'int');
 
        $data->query->where("#threadId = {$threadId}");
    }

    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        expect($data->threadId  = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));

        $data->folderId = $data->threadRec->folderId;

        doc_Threads::requireRightFor('read', $data->threadRec);
    }


    /**
     *
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $title = new ET("[#user#] » [#folder#] » [#threadTitle#]");

        $title->replace($data->threadRec->title, 'threadTitle');

        $folder = doc_Folders::getTitleById($data->folderId);

        $folderRec = doc_Folders::fetch($data->folderId);

        $title->replace(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');

        $user = core_Users::fetchField($folderRec->inCharge, 'nick');

        $title->replace($user, 'user');

        $data->title = $title;
    }



    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $userRec = core_Users::fetch($rec->createdBy);
        $userRow = core_Users::recToVerbal($userRec);

        $row->created = new ET( "<center><div style='font-size:0.8em'>[#1#]</div><div style='margin:10px;'>[#2#]</div>[#3#]<div></div></center>",
                                dt::addVerbal($mvc->getVerbal($rec, 'createdOn')),
                                $userRow->avatar,
                                $userRow->nick);

        $docMvc = cls::get($rec->docClass);

        // Създаваме обекта $data
        $data = new stdClass();
         
        // Трябва да има $rec за това $id
        expect($data->rec = $docMvc->fetch($rec->docId));
        
 
        // Подготвяме данните за единичния изглед
        $docMvc->prepareSingle($data);

        // Рендираме изгледа
        $row->document = $docMvc->renderSingle($data);

    }



    /**
     *
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        doc_Threads::updateThread($rec->threadId);
    }

}