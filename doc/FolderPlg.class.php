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
     * Добавя бутон "Папка" в единичния изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if($mvc->className == 'doc_Folders') return;

        if($data->rec->folderId && ($fRec = doc_Folders::fetch($data->rec->folderId))) {
            
            $openThreads =  $fRec->openThreadsCnt ? "&nbsp;({$fRec->openThreadsCnt})" : "";

            $data->toolbar->addBtn('Папка' . $openThreads, 
                                    array('doc_Threads', 'list', 
                                    'folderId' => $data->rec->folderId), 
                                    array('class' => $fRec->openThreadsCnt?'btn-folder':'btn-folder-y'));

        } else {
            $title = $mvc->getFolderTitle($data->rec->id);
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
     * Дефолт имплементация на метод, която форсира създаването на обект - корица 
     * на папка и след това форсира създаването на папка към този обект
     */
    function on_AfterForceCoverAndFolder($mvc, $folderId, $rec)
    {
        // Понеже този плъгин по съвместителство се ползва и за doc_Folders, а този
        // метод няма смисъл в doc_Folders, не очакваме да се вика в този случай
        expect($mvc->className != 'doc_Folders');
        
        if(is_numeric($rec)) {
            $rec = $mvc->fetch($rec);
        } elseif($rec->id) {
            $rec = $mvc->fetch($rec->id);
        } else {
            $res = $mvc->isUnique($rec, $fields, $exRec);  
            if($exRec) {
                $rec = $exRec;
            }
        }

        // Ако обекта няма папка (поле $rec->folderId), създаваме една нова
        if(!$rec->folderId) {
            $rec->folderId = doc_Folders::createNew($mvc);
            $mvc->save($rec);
        }
        
        return $rec->folderId;
    }


    /**
     * Функция, която представлява метоза ::getFolderTitle по подразбиране
     */
    function on_AfterGetFolderTitle($mvc, $title, $id)
    {
        if(!$title) {
            $title = $mvc->getTitleById($id);
        }
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
        
        // Вземаме текущия потребител
        $cu = core_Users::getCurrent(); // Текущия потребител
            
        // Ако текущия потребител не е отговорник на тази корица на папка, 
        // правим необходимот за да му я споделим
        if($cu != $rec->inCharge && $cu > 0) {
            $fRec->shared = type_Keylist::addKey($rec->shared, $cu);
        }

        $mvc->forceCoverAndFolder($rec);
 
        $res = new Redirect(array('doc_Threads', 'list', 'folderId' => $rec->folderId));
        
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
        // Ако записа все още не съществува, задаваме ми няколко подразбиращи се стойности
        if(!$rec->id) {
            setIfNot($rec->state, 'closed');
            
            // Вземаме текущия потребител
            $cu = core_Users::getCurrent();

            // Ако потребителя е -1 (системата), тогава се взема първия срещнат admin
            // @TODO да се махне този хак
            if($cu < 0) {
            	$cu = core_Users::getFirstAdmin();
            }

            setIfNot($rec->inCharge, $cu);

            setIfNot($rec->access, 'team');
        }
    }

    
    /**
     * Изпълнява се след запис на обект
     * Прави синхронизацията между данните записани в обекта-корица и папката
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($mvc->className == 'doc_Folders') return;
        
        if(!$rec->folderId) {
            $rec->folderId = $mvc->fetchField($rec->id, 'folderId');
        }

        if($rec->folderId) {
            doc_Folders::updateByCover($rec->folderId);
        }
    }

}