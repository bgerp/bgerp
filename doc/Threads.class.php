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
    var $loadList = 'plg_Created,plg_Rejected,plg_Modified, doc_Wrapper, plg_Checkboxes';

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
        $this->FLD('state' , 'enum(open,waiting,close,rejected)', 'caption=Състояние');
        $this->FLD('allDocCnt' , 'int', 'caption=Брой документи->Всички');
        $this->FLD('pubDocCnt' , 'int', 'caption=Брой документи->Публични');
        $this->FLD('lastReplay' , 'datetime', 'caption=Последно');

        // Достъп
       // $this->FLD('access', 'enum(public=Публичен,team=Екипен)', 'caption=Достъп');
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
    }
    

    /**
     *
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

        $data->query->where("#folderId = {$folderId}");
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->createdOn = dt::addVerbal($row->createdOn);

        $row->title = ht::createLink($row->title, array('doc_ThreadDocuments', 'list', 'threadId' => $rec->id, 'folderId' => $rec->folderId));
    }

 }