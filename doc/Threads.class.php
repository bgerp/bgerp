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
class doc_Threads extends core_Manager
{   
    var $loadList = 'plg_Created,plg_Rejected,plg_Modified,plg_State,doc_Wrapper, plg_Select';

    var $title    = "Нишки от документи";
    
    var $listFields = 'id,title,status,author=Автор,createdOn=Създаване,replays=Отговори,last=Последно';

    
    /**
     *
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId' ,  'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(opened,waiting,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt' , 'int', 'caption=Брой документи->Всички');
        $this->FLD('pubDocCnt' , 'int', 'caption=Брой документи->Публични');
        $this->FLD('last' , 'datetime', 'caption=Последно');
        $this->FLD('firstDocId' ,     'int', 'caption=Документ->ID,input=none,column=none');
        $this->FLD('firstDocClass' ,  'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас,input=none,column=none');


        // Достъп
         $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
    }
    

    /**
     * Подготвя титлата на папката с теми
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("[#user#] » [#folder#]");
        
        $folder = doc_Folders::getTitleById($data->folderId);

        $folderRec = doc_Folders::fetch($data->folderId);

        $title->replace(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');

        $user = core_Users::fetchField($folderRec->inCharge, 'nick');

        $title->replace($user, 'user');
        
        $data->title = $title;
    }
    


    /**
     * Филтрира по папка
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $folderId = Request::get('folderId', 'int');
        doc_Folders::requireRightFor('single', $folderId);

        $data->query->where("#folderId = {$folderId} AND #allDocCnt > 0");
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->createdOn = dt::addVerbal($row->createdOn);
        
        $attr['class'] .= 'state-' . $rec->state;
        $row->title = ht::createLink($row->title, array('doc_ThreadDocuments', 'list', 'threadId' => $rec->id, 'folderId' => $rec->folderId), NULL, $attr);

    }


    /**
     * Обновява информацията за дадена тема. 
     * Обикновенно се извиква след промяна на threadDocumen
     */
    function updateThread_($id)
    {
        // Вземаме записа на треда
        $rec = doc_Threads::fetch($id);
        
        $tdQuery = doc_ThreadDocuments::getQuery();
        $tdQuery->where("#threadId = {$id}");
        $rec->allDocCnt = $tdQuery->count();
        
        $tdQuery = doc_ThreadDocuments::getQuery();
        $tdQuery->where("#threadId = {$id} AND #state != 'hidden'");
        $rec->pubDocCnt = $tdQuery->count();

        $tdQuery = doc_ThreadDocuments::getQuery();
        $tdQuery->where("#threadId = {$id}");
        $tdQuery->XPR('last', 'datetime', 'max(#createdOn)');
        $lastTdRec = $tdQuery->fetch();
        $rec->last = $lastTdRec->last;

        doc_Threads::save($rec, 'last, allDocCnt, pubDocCnt');

        doc_Folders::updateFolder($rec->folderId);
    }


    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('MO', array('acc_Articles', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
    }

 }