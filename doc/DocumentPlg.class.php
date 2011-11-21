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
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none,silent,input=hidden');
        $mvc->FLD('threadId',  'key(mvc=doc_Threads,select=title)', 'caption=Нишка->Топик,input=none,column=none,silent,input=hidden');
        $mvc->FLD('containerId',  'key(mvc=doc_Containers,select=title)', 'caption=Нишка->Документ,input=none,column=none,oldFieldName=threadDocumentId');
        $mvc->FLD('originContainerId',  'key(mvc=doc_Containers,select=title)', 'caption=Нишка->Оригинал,input=hidden,column=none,silent');

        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
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
                $rec->containerId = doc_Containers::create($mvc);
            }


        }
    }
    
    
    function on_AfterMove($mvc, $res, $rec, $newLocation)
    {
    	$oldLocation = new doc_Location();
    	$oldLocation->folderId = $rec->folderId;
    	$oldLocation->threadId = $rec->threadId;
    	
    	// Ако е зададен нов тред, то този тред задължително е "собственост" на съществуваща 
    	// папка. Намираме тази папка.
    	if ($newLocation->threadId) {
    		$newLocation->folderId = doc_Threads::fetchField($newLocation->threadId, 'folderId');
    	}
    	
    	expect($newLocation->folderId);
    	
    	// Ако все още не е известен новия тред, трябва да се създаде нов тред в новата папка.
        if(!$newLocation->threadId) {
        	$newThreadRec = (object)array(
        		'folderId' => $newLocation->folderId,
        		'title'    => $mvc->getDocumentTitle($rec),
        		'state'    => 'closed', // Началното състояние на нишката е затворено 
        	);

            $newLocation->threadId = doc_Threads::save($newThreadRec);
        }
        
        expect($newLocation->threadId);
    	
    	if ($oldLocation->folderId != $newLocation->folderId || $oldLocation->threadId != $newLocation->threadId) {
	    	if (doc_Containers::move($rec->containerId, $newLocation, $oldLocation)) {
	    		$rec->folderId = $newLocation->folderId;
		    	$rec->threadId = $newLocation->threadId;
		    	
		    	$mvc->save($rec, 'folderId, threadId');
	    	}
    	}
    }


    /**
     * Изпълнява се след запис на документ.
     * Ако е може се извиква обновяването на контейнера му
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($rec->containerId) {  
            doc_Containers::update($rec->containerId);
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
     *
     */
    function on_BeforePrepareRetUrl($mvc, $res, $data)
    {
        $retUrl = getRetUrl();
        $folderId = $data->form->rec->folderId;
        $threadId = $data->form->rec->threadId;
        
       // bp($retUrl['Ctr'], $threadId, $folderId, $data);

        if($retUrl['Ctr'] == 'doc_Threads' && $threadId && $folderId) {
            $data->retUrl = toUrl(array('doc_Containers', 'threadId' => $threadId, 'folderId' => $folderId));
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
                    $res = new Redirect( array('doc_Containers', 'list', 'threadId' => $rec->threadId));

                    return FALSE;
                }
            }
        }
    }

}