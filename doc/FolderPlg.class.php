<?php

/**
 * Клас 'doc_FolderPlg'
 *
 * Плъгин за обектите, които се явяват корици на папки
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: $
 * @link
 * @since
 */
class doc_FolderPlg extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['folderId']) {
            
           if($mvc->className != 'doc_Folders') {

                // Поле за папката. Ако не е зададено - обекта нма папка
                $mvc->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
           }
            
            // Достъп
            $mvc->FLD('inCharge' , 'key(mvc=core_Users, select=nick)', 'caption=Настройки->Отговорник');
            $mvc->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Настройки->Достъп');
            $mvc->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Настройки->Споделяне');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        $mvc->interfaces['doc_FolderIntf'] = 'doc_FolderIntf';
    }


    /**
     * Извиква се след подготовка на фирмата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {   
        if($mvc->className == 'doc_Folders') return;

        // Полета за Достъп
        $data->form->setField('inCharge', array('value' => core_Users::getCurrent())); 
    }


    /**
     *
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if($mvc->className == 'doc_Folders') return;

        if($data->rec->folderId && ($fRec = doc_Folders::fetch($data->rec->folderId))) {
            
            $openThreads =  $fRec->openThreadsCnt ? "&nbsp;({$fRec->openThreadsCnt})" : "";

            $data->toolbar->addBtn('Папка' . $openThreads, 
                                    array('doc_Folders', 'single', 
                                    $data->rec->folderId), 
                                    array('class' => $fRec->openThreadsCnt?'btn-folder':'btn-folder-y'));

        } else {
            $title = $mvc->getTitleById($data->rec->id);
            $data->toolbar->addBtn('Папка', array($mvc, 'createFolder', $data->rec->id), array(
                    'warning' => "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?", 
                    'class' => 'btn-new-folder'));
        }
    }


	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * Забранява изтриването на вече използвани сметки
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass|NULL $rec
	 * @param int|NULL $userId
	 */
	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if ($rec->id && ($action == 'delete' || $action == 'edit' || $action == 'write' || $action == 'single')) {
			
            $rec = $mvc->fetch($rec->id);
            
            
			if (!doc_Folders::haveRightToObject($rec)) {
				// Използвана сметка - забранено изтриване
				$requiredRoles = 'no_one';
			}
		}
	}


    /**
     * Премахва от резултатите скритите 
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        if(!haveRole('ceo')) {
            $cu =core_Users::getCurrent();
            $data->query->where("NOT (#access = 'secret' AND #inCharge != $cu)");
        }
    }
     

    /**
     * Реализация на екшъна 'act_CreateFolder'
     */
	function on_BeforeAction($mvc, &$res, $action) 
	{
	    if($action != 'createFolder' || $mvc->className == 'doc_Folders') return;

        $fRec = new stdClass();
        $fRec->coverClass = core_Classes::fetchField(array("#name = '[#1#]'", $mvc->className), 'id');
        expect($fRec->coverId    = Request::get('id', 'int'));
        $mvc->requireRightFor('single', $fRec->coverId);
        $mvc->requireRightFor('write', $fRec->coverId);
        
        $coverRec = $mvc->fetch($fRec->coverId);
        setIfNot($coverRec->inCharge, core_Users::getCurrent());
        setIfNot($coverRec->access, 'team');

        if($exFolderRec = doc_Folders::fetch( array("#coverClass = [#1#] AND #coverId = [#2#]", $fRec->coverClass, $fRec->coverId) )) {
            $coverRec->folderId = $exFolderRec->id;
        } else {
            $fRec->title = $mvc->getTitleById($fRec->coverId);
            $fRec->status = '';

            $fRec->state = 'active';
            $fRec->allThreadsCnt  = o;
            $fRec->openThreadsCnt = 0;
                  
            $fRec->inCharge = $coverRec->inCharge;
            $fRec->access   = $coverRec->access;
            $fRec->shared   = $coverRec->shared;
            $fRec->last     = DT::verbal2mysql();

            $coverRec->folderId = doc_Folders::save($fRec);

            $mvc->save($coverRec, 'folderId');
        }
 
        $res = new Redirect(array('doc_Folders', 'single', $coverRec->folderId));
        
        return FALSE;
	}


    function on_AfterSave($mvc, $id, $rec)
    {
        if($mvc->className == 'doc_Folders') return;

        if($rec->folderId) {
            $fRec = doc_Folders::fetch($rec->folderId);
            $fRec->inCharge = $rec->inCharge;
            $fRec->access   = $rec->access;
            $fRec->shared   = $rec->shared;
            doc_Folders::save($fRec);
        }
    }

}