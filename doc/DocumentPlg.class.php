<?php
/**
 * Клас 'doc_DocumentPlg'
 *
 * Плъгин за мениджърите на документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @version    CVS: $Id: $
 */
class doc_DocumentPlg extends core_Plugin
{
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяме полета свързани с организацията на документооборота
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none,silent,input=hidden');
        $mvc->FLD('threadId',  'key(mvc=doc_Threads,select=title)', 'caption=Нишка->Топик,input=none,column=none,silent,input=hidden');
        $mvc->FLD('containerId',  'key(mvc=doc_Containers,select=title)', 'caption=Нишка->Документ,input=none,column=none,oldFieldName=threadDocumentId');
        $mvc->FLD('originId',  'key(mvc=doc_Containers,select=title)', 
            'caption=Нишка->Оригинал,input=hidden,column=none,silent,oldFieldName=originContainerId');
        
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->fields['state']) {
            $mvc->FLD('state',
            'enum(draft=Чернова,
                  pending=Чакащо,
                  active=Активирано,
                  opened=Отворено,
                  waiting=Чакащо,
                  closed=Приключено,
                  hidden=Скрито,
                  rejected=Оттеглено,
                  stopped=Спряно,
                  wakeup=Събудено,
                  free=Освободено)',
            'caption=Състояние,column=none,input=none');
        }

        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
        
        // Добавя поле за последно използване
        if(!isset($mvc->fields['lastUsedOn'])) {
            $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        }
    }



    /**
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    { 
        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    }

     
    /**
     * Добавя бутон за оттегляне
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {  
        if (isset($data->rec->id) && !$mvc->haveRightFor('delete', $data->rec) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected') ) {
            $data->toolbar->addBtn('Оттегляне', array(
                $mvc,
                'reject',
                $data->rec->id,
                'ret_url' => TRUE
            ),
            'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32');
        }

        if (isset($data->rec->id) && $mvc->haveRightFor('reject') && ($data->rec->state == 'rejected') ) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Въстановяване', array(
                $mvc,
                'restore',
                $data->rec->id,
                'ret_url' => TRUE
            ),
            'id=btnRestore,class=btn-restore,warning=Наистина ли желаете да възстановите документа?,order=32');
        }
    }



    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {  
        if(Request::get('Rejected')) {
           $data->toolbar->removeBtn('*');
           $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
        }
    }



    /**
     * Добавя към титлата на списъчния изглед "[оттеглени]"
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET(tr($data->title));
            $data->title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени'). "]&nbsp;</font>");
        }
    }

    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec)
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        $row->STATE_CLASS .= " state-{$rec->state}";

    }

    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->query->where("#state != 'rejected' || #state IS NULL");
            }
        }
    }


    /**
     * Изпълнява се преди запис 
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {   
        // Ако създаваме нов документ и ...
        if(!isset($rec->id)) {
            
            // ... този документ няма ключ към папка и нишка, тогава
            // извикваме метода за рутиране на документа
            if(!isset($rec->folderId) || !isset($rec->threadId)) {
                $mvc->route($rec);
            }
            
            // ... този документ няма ключ към контейнер, тогава 
            // създаваме нов контейнер за документите от този клас 
            // и записваме връзка към новия контейнер в този документ
            if(!isset($rec->containerId)) {
                $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId);
            }
            
            // Задаваме началното състояние по подразбиране
            if (!$rec->state) {
                $rec->state = $mvc->firstState ? $mvc->firstState : 'draft';
            }
        }
    }


    /**
     * Изпълнява се след запис на документ.
     * Ако е може се извиква обновяването на контейнера му
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {   
        $containerId = $rec->containerId ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');

        if($containerId) {  
            doc_Containers::update($containerId);
        }
    }
    

    /**
     * Ако в документа няма код, който да рутира документа до папка/тред, 
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, $res, $rec)
    {   
        // Ако имаме контейнер, но нямаме тред - определяме треда от контейнера
        if($rec->containerId && !$rec->threadId) {
            $tdRec = doc_Containers::fetch($rec->containerId);
            $rec->threadId = $tdRec->threadId;
        }

        // Ако имаме тред, но нямаме папка - определяме папката от контейнера
        if($rec->threadId && !$rec->folderId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $rec->folder = $thRec->folderId;
        }
        
        // Ако нямаме папка - форсираме папката по подразбиране за този клас
        if(!$rec->folderId) {
            $rec->folderId = $mvc->getUnsortedFolder();
        }

        // Ако нямаме тред - създаваме нов тред в тази папка
        if(!$rec->threadId) {
            $rec->threadId = doc_Threads::create($rec->folderId);
        }

        // Ако нямаме контейнер - създаваме нов контейнер за 
        // този клас документи в определения тред
        if(!$rec->containerId) {
            $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId);
        }
    }
    

    /**
     *
     */
    function on_AfterGetUnsortedFolder($mvc, $res)
    {
    	if (!$res) {
            $unRec = new stdClass();
            $unRec->name =  $mvc->title;
            $res = doc_UnsortedFolders::forceCoverAndFolder($unRec);
    	}
    }
    
    
    /**
     * Ако няма метод в документа, долния код сработва за да осигури титла за нишката
     */
    function on_AfterGetDocumentTitle($mvc, $res, $rec)
    {
        if(!$res) {
            $res = $mvc->getRecTitle($rec);
        }
    }

    
    /**
     * Когато действието е предизвикано от doc_Thread изглед, тогава
     * връщането е към single изгледа, който от своя страна редиректва към 
     * треда, при това с фокус на документа
     */
    function on_BeforePrepareRetUrl($mvc, $res, $data)
    { 
        $retUrl = getRetUrl();
        if($retUrl['Ctr'] == 'doc_Containers' ) {
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
            return FALSE;
        }
     }


    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, $res, $action)
    {
        if($action == 'single' && !(Request::get('Printing'))) {
        
            expect($id = Request::get('id', 'int'));
            
            $mvc->requireRightFor('single');

            $rec = $mvc->fetch($id);
            
            if($rec->threadId) {
                if(doc_Threads::haveRightFor('read', $rec->threadId)) {

                    $hnd = $mvc->getHandle($rec->id);
                    $res = new Redirect( array('doc_Containers', 'list', 'threadId' => $rec->threadId, '#' => $hnd));

                    return FALSE;
                }
            }
        }

        if($action == 'reject') {
        
            $id = Request::get('id', 'int');
            
            $mvc->requireRightFor('reject');

            $rec = $mvc->fetch($id);
            
            $mvc->requireRightFor('reject', $rec);
            
            if($rec->state != 'rejected') {

                $rec->state = 'rejected';
             
                $mvc->save($rec);
            
                $mvc->log('reject', $rec->id);
            }
              
            $res = new Redirect(array($mvc, 'single', $id));

            return FALSE;
        }

        if($action == 'restore') {
        
            $id = Request::get('id', 'int');
            
 
            $rec = $mvc->fetch($id);

            if (isset($rec->id) && $mvc->haveRightFor('reject') && ($rec->state == 'rejected') ) {
             
                 $rec->state = 'closed';
              
                 $mvc->save($rec);

                 $mvc->log('reject', $rec->id);
            }
            
            $res = new Redirect(array($mvc, 'single', $rec->id) );

            return FALSE;
        }
    }


    /**
     * Връща манупулатора на документа
     */
    function on_AfterGetHandle($mvc, &$hnd, $id)
    {
        if(!$hnd) {
		    $hnd = $mvc->abbr . $id;
        }
	}

    
    /**
     * Подготвя полетата threadId и folderId, ако има originId и threadId
     */
	function on_AfterPrepareEditForm($mvc, $data)
	{   
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId
        $rec = $data->form->rec;
        // Ако имаме $originId - намираме треда
        if($rec->originId) {
            expect($cRec = doc_Containers::fetch($rec->originId, 'threadId,folderId'));
            $rec->threadId = $cRec->threadId;
            $rec->folderId = $cRec->folderId;

        } elseif($rec->threadId) {
			$rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
		}

        if($rec->threadId) {
            doc_Threads::requireRightFor('single', $rec->threadId);
        } else {
            if(!$rec->folderId) {
                $rec->folderId = $mvc->GetUnsortedFolder();
            }
            doc_Folders::requireRightFor('add', $rec->folderId);
        }
        

        if($rec->threadId) {

            $thRec = doc_Threads::fetch($rec->threadId);
            $thRow = doc_Threads::recToVerbal($thRec);
            $data->form->title = $mvc->singleTitle . ' в ' . $thRow->title ;
        } elseif ($rec->folderId) {
            $fRec = doc_Folders::fetch($rec->folderId);
            $fRow = doc_Folders::recToVerbal($fRec);
            $data->form->title = $mvc->singleTitle . ' в ' . $fRow->title ;
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
		if ($rec->id) {
            if($action == 'delete') {
                $requiredRoles = 'no_one';  
            }
            
            // Системните записи не могат да се оттеглят или изтриват
            if($rec->createdBy == -1 &&  $action == 'reject') {
                $requiredRoles = 'no_one';  
            }
		}
	}


}