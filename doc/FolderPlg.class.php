<?php



/**
 * Клас 'doc_FolderPlg'
 *
 * Плъгин за обектите, които се явяват корици на папки
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_FolderPlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
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
            $mvc->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Права->Достъп');
            $mvc->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Права->Споделяне');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_FolderIntf'], 'doc_FolderIntf');
    }
    
    
    /**
     * Извиква се след подготовка на фирмата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if($mvc->className == 'doc_Folders') return;
        
        // Полета за Достъп
        if(!$data->form->rec->inCharge) {
            $data->form->setDefault('inCharge', core_Users::getCurrent());
        }
    }
    
    
    /**
     * Добавя бутон "Папка" в единичния изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if($mvc->className == 'doc_Folders') return;
        
        if($data->rec->folderId && ($fRec = doc_Folders::fetch($data->rec->folderId))) {
            
            $openThreads = $fRec->openThreadsCnt ? "|* ({$fRec->openThreadsCnt})" : "";
            
            $data->toolbar->addBtn('Папка' . $openThreads,
                array('doc_Threads', 'list',
                    'folderId' => $data->rec->folderId),
                array('class' => $fRec->openThreadsCnt ? 'btn-folder' : 'btn-folder-y'));
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
            
            // Ако модела е достъпен за всички потребители по подразбиране, 
            // но конкретния потребител няма права за конкретния обект
            // забраняваме достъпа
            if (!doc_Folders::haveRightToObject($rec, $userId) && $requiredRoles == 'user') {
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
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if(!haveRole('ceo')) {
            $cu = core_Users::getCurrent();
            $data->query->where("NOT (#access = 'secret' AND #inCharge != $cu AND !(#shared LIKE '%|{$cu}|%'))");
        }
    }
    
    
    /**
     * Дефолт имплементация на метод, която форсира създаването на обект - корица
     * на папка и след това форсира създаването на папка към този обект
     */
    function on_AfterForceCoverAndFolder($mvc, &$folderId, $rec)
    {
        if (!$folderId) {
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

            $folderId = $rec->folderId;
        }
    }
    
    
    /**
     * Функция, която представлява метод за ::getFolderTitle по подразбиране
     */
    function on_AfterGetFolderTitle($mvc, &$title, $id, $escaped = TRUE)
    {
        if(!$title) {
            $title = $mvc->getTitleById($id, $escaped);
        }
    }
    
    
    /**
     * Реализация на екшън-а 'act_CreateFolder'
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
        $cu = core_Users::getCurrent();     // Текущия потребител
        // Ако текущия потребител не е отговорник на тази корица на папка, 
        // правим необходимото за да му я споделим
        if($cu != $rec->inCharge && $cu > 0) {
            $rec->shared = type_Keylist::addKey($rec->shared, $cu);
        }

        // Този синтаксис заобикаля предупрежденията на PHP5.4 за Deprecated: Call-time pass-by-reference
        // но е доста грозен
        // call_user_func_array(array($mvc, 'forceCoverAndFolder'), array(&$rec));
        
        $rec->folderId = $mvc->forceCoverAndFolder($rec);
 
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
        // Вземаме текущия потребител
        $cu = core_Users::getCurrent();
        
        // Ако потребителя е -1 (системата), тогава се взема първия срещнат admin
        // @TODO да се махне този хак
        if($cu < 0) {
            $firstAdmin = core_Users::getFirstAdmin();
            
            //Ако има администратор в системата използваме него
            //При при първата инсталация на системата, нямаме администратор. Използваме системния потребител
            if ($firstAdmin) {
                $cu = $firstAdmin;    
            }
        }
        
        setIfNot($rec->inCharge, $cu);
        
        setIfNot($rec->access, 'team');
        
        if(!$rec->state) {
            $rec->state = 'active';
        }
    }
    
    
    /**
     * Изпълнява се след запис на обект
     * Прави синхронизацията между данните записани в обекта-корица и папката
     */
    static function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($mvc->className == 'doc_Folders') return;
        
        if(!$rec->folderId) {
            $rec->folderId = $mvc->fetchField($rec->id, 'folderId');
        }
        
        if($rec->folderId) {
            
            //Ако има папка - обновяме ковъра
            doc_Folders::updateByCover($rec->folderId);
        } else {
            
            //Ako няма папка и autoCreateFolder е TRUE, тогава създава папка
            if ($mvc->autoCreateFolder) {
                $mvc->forceCoverAndFolder($rec);
            }
        }   
    }
    
    
    /**
     * Ако отговорника на папката е системата
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->inCharge == -1) {
            $row->inCharge = '@system';
        }
    }
}