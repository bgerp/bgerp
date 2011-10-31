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
class doc_Folders extends core_Master
{   
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_Search ';

    var $title    = "Папки с нишки от документи";

    var $listFields = 'id,title,type=Тип,inCharge=Отговорник,threads=Нишки,last=Последно';

    var $canRead   = 'user';
    var $canWrite  = 'no_one';
    var $canReject = 'no_one';

    var $searchFields = 'title';

    var $singleTitle = 'Папка';


    function description()
    {
        // Определящ обект за папката
        $this->FLD('coverClass' , 'class(interface=doc_FolderIntf)', 'caption=Корица->Клас');
        $this->FLD('coverId' , 'int', 'caption=Корица->Обект');
        
        // Информация за папката
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(active=Активно,opened=Отворено,rejected=Оттеглено)', 'caption=Състояние');
        $this->FLD('allThreadsCnt' , 'int', 'caption=Нишки->Всички');
        $this->FLD('openThreadsCnt' , 'int', 'caption=Нишки->Отворени');
        $this->FLD('last' , 'datetime', 'caption=Последно');

        $this->setDbUnique('coverId,coverClass');
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users', 'caption=Потребител,input,silent');
        $data->listFilter->FNC('order', 'enum(pending=Първо чакащите,last=Сортиране по "последно")', 'caption=Подредба,input,silent');
         
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'users,order,search';
        $data->listFilter->setField("users", array('value' =>  core_Users::getCurrent() ) );
        $data->listFilter->input('users,order,search', 'silent');
    }


    /**
     * Действия преди извличането на данните
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        if(!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }
        
        if(!$data->listFilter->rec->search) {
            $data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')"); 
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
            $data->title = 'Папките на |*<font color="green">' . 
                        $data->listFilter->fields['users']->type->toVerbal($data->listFilter->rec->users) . '</font>';
        } else {
            $data->title = 'Търсене във всички папки на |*<font color="green">"' . 
                   $data->listFilter->fields['search']->type->toVerbal($data->listFilter->rec->search) . '"</font>';

        }

    }


    /**
     * Връща информация дали потребителя има достъп до посочената папка
     */
    function haveRightToFolder($folderId, $userId = NULL)
    {
        $rec = doc_Folders::fetch($folderId);
        
        return doc_Folder::haveRightToObject($rec, $userId);
    }


    /**
     * Дали посоченият (или текущият ако не е посочен) потребител има право на достъп до този обект
     * Обекта трябва да има полета inCharge, access и shared
     */
    function haveRightToObject($rec, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }

        // Вземаме членовете на екипа на потребителя (TODO:)
        $teamMembers = core_Users::getTeammates($userId); 
        
        // 'ceo' има достъп до всяка папка
        if( haveRole('ceo') ) return TRUE;

        // Всеки има право на достъп до папката за която отговаря
        if($rec->inCharge === $userId) return TRUE;

        // Всеки има право на достъп до папките, които са му споделени
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;

        // Всеки има право на достъп до общите папки
        if( $rec->access == 'public' ) return TRUE;
   
        // Дали обекта има отговорник - съекипник
        $fromTeam = strpos($teamMembers, '|' . $rec->inCharge . '|') !== FALSE;

        // Ако папката е екипна, и е на член от екипа на потребителя, и потребителя е manager или officer - има достъп
        if( $rec->access == 'team' && $fromTeam && core_Users::haveRole('manager,officer', $userId)  ) return TRUE;

        // Ако собственика на папката има права 'manager' или 'ceo' отказваме достъпа
        if( core_Users::haveRole('manager,ceo', $rec->inCharge) ) return FALSE;

        // Ако папката е лична на член от екпа, и потребителя има права 'manager' - има достъп
        if( $rec->access == 'private' &&  $fromTeam && haveRole('manager')) return TRUE;

        // Ако никое от горните не е изпълнено - отказваме достъпа
        return FALSE;
    }
    

    /**
     * След преобразуване към вербални данни на записа
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->threads = "<div style='float:right;'>" . $mvc->getVerbal($rec, 'allThreadsCnt') . "</div>";
        
        $openThreads  = $mvc->getVerbal($rec, 'openThreadsCnt');
        
        if($openThreads) {
            $row->threads .= "<div style='float:left;'>($openThreads)</div>";
        }
        
        $attr['class'] = 'linkWithIcon';

        if($mvc->haveRightFor('single', $rec)) {
            $attr['style'] =  'background-image:url(' . sbf('img/16/folder-y.png') . ');';
            $row->title  = ht::createLink($row->title,  array('doc_Threads', 'list', 'folderId' => $rec->id), NULL, $attr);
        } else {
            $attr['style'] =  'color:#777;background-image:url(' . sbf('img/16/lock.png') . ');';
            $row->title  = ht::createElement('span', $attr, $row->title);
        }
        

        $typeMvc = cls::get($rec->coverClass);
        
        $attr['style'] =  'background-image:url(' . sbf($typeMvc->singleIcon) . ');';

        if($typeMvc->haveRightFor('single', $rec->coverId)) {
            $row->type = ht::createLink($typeMvc->singleTitle,  array($typeMvc, 'single', $rec->coverId), NULL, $attr);
        } else {
            $attr['style'] .=  'color:#777;';
            $row->type = ht::createElement('span', $attr, $typeMvc->singleTitle);
        }
    }

}