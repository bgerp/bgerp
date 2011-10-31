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

                // Поле за id на папката. Ако не е зададено - обекта няма папка
                $mvc->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
            }
            
            // Достъп
            $mvc->FLD('inCharge' , 'key(mvc=core_Users, select=nick)', 'caption=Права->Отговорник');
            $mvc->FLD('access',    'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Права->Достъп');
            $mvc->FLD('shared' ,   'keylist(mvc=core_Users, select=nick)', 'caption=Права->Споделяне');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_FolderIntf'], 'doc_FolderIntf');
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

            if($action == 'delete' && $rec->folderId) {
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
     * Функция по подразбиране за метода ::forceFolder
     */
    function on_AfterForceFolder($mvc, $folderId, $rec)
    {
        // Ако в записа вече имаме установен $folderId връщаме го него
        if(!$folderId) {
            $folderId = $rec->folderId;
        }
        
        // Опитваме се да намерим папката по клас и id на корицата
        if(!$folderId) {
            // Подготвяме полетата $coverClass и $coverId
            $fRec->coverClass     = core_Classes::fetchField(array("#name = '[#1#]'", $mvc->className), 'id');
            expect($fRec->coverId = $rec->id);

            // Ако имаме папка със същите $coverClass и $coverId, връщаме ги
            $folderId = doc_Folders::fetchField(array("#coverClass = [#1#] AND #coverId = [#2#]", $fRec->coverClass, $fRec->coverId), 'id');
        }

        if(!$folderId) {
            // Подготвяме полетата на $rec които се дублират и в $fRec (записа на папката)
            $cu = core_Users::getCurrent(); // Текущия потребител
            
            // Ако текущия потребител не е отговорник на тази папка, 
            // правим необходимот за да му я споделим
            if($cu != $rec->inCharge) {
                $fRec->shared = type_Keylist::addKey($rec->shared, $cu);
            }

            $fRec->inCharge = $rec->inCharge;
            $fRec->access   = $rec->access;
            $fRec->shared   = $rec->shared;
            $fRec->title    = $mvc->getFolderTitle($rec);
            
            $fRec->status = '';
            $fRec->state = 'closed';
            $fRec->allThreadsCnt  = o;
            $fRec->openThreadsCnt = 0;

            $rec->folderId = $folderId = doc_Folders::save($fRec);

            $mvc->save_($rec, 'folderId');
        }
    }



    /**
     * Функция, която представлява метоза ::getFolderTitle по подразбиране
     */
    function on_AfterGetFolderTitle($mvc, $title, $rec)
    {
        $title = $mvc->getRecTitle($rec);
    }


    /**
     * Реализация на екшъна 'act_CreateFolder'
     */
	function on_BeforeAction($mvc, &$res, $action) 
	{
	    if($action != 'createfolder' || $mvc->className == 'doc_Folders') return;
        
        // Входни параметри и проверка за права
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('single', $rec);
        $mvc->requireRightFor('write', $rec);
        
        $mvc->forceFolder($rec);
 
        $res = new Redirect(array('doc_Folders', 'single', $rec->folderId));
        
        return FALSE;
	}
    
    
    /**
     * Изпълнява се преди запис и задава стойности на някои полета, ако не им е зададена такава
     * 1) Прави състоянието 'closed' по подразбиране
     * 2) Текущия потребител е отговорник на обекта
     * 3) Обекта има "Екипен" режим за достъп
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    { 
        if(empty($rec->state) && arr::haveSection($fields, 'state')) {
            $rec->state = 'closed';
        }

        setIfNot($rec->inCharge, core_Users::getCurrent());
        setIfNot($rec->access, 'team');
    }

    
    /**
     * Изпълнява се след запис на обект
     * Прави синхронизацията между данните записани в обекта-корица и папката
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($mvc->className == 'doc_Folders') return;
        
        if($rec->folderId && arr::haveSection('folderId,inCharge,access,shared,state', $fields)) {
            if($fRec = doc_Folders::fetch($rec->folderId)) {
                $fRec->inCharge = $rec->inCharge;
                $fRec->access   = $rec->access;
                $fRec->shared   = $rec->shared;
                $fRec->state    = $rec->state == 'rejected' ? 'rejected' : 'closed';
                doc_Folders::save($fRec);
            }
        }
    }

}