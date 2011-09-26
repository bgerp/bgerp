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
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg';

    var $title    = "Папки с нишки от документи";

    var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';

    var $canRead = 'user';
    var $canWrite = 'no_one';

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

        $this->setDbUnique('coverId,coverClass');
    }

    
    /**
     * Действия преди извличането на данните
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
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

        $object = ht::createElement("img", array('src' => sbf('img/16/view.png', ''), 'width' => 16, 'height' => 16, 'valign' =>'abs_middle'));
        $title  = new ET($row->title);

        if($mvc->haveRightFor('single', $rec)) {
            $object = ht::createLink($object, array($rec->coverClass, 'single', $rec->coverId));
            $title  = ht::createLink($title,  array('doc_Folders', 'single', $rec->id));
        } else {
            $title  = ht::createElement('span', array('style' =>'color:#777'), $title);
        }

        $row->title = new ET("[#1#]&nbsp;[#2#]", $object, $title);
    }
}