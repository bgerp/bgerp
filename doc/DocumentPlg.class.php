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
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none');
        $mvc->FLD('threadId',  'key(mvc=doc_Threads,select=title)', 'caption=Нишка->Топик,input=none,column=none');
        $mvc->FLD('threadDocumentId',  'key(mvc=doc_ThreadDocuments,select=title)', 'caption=Нишка->Документ,input=none,column=none');

        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
    }

    /**
     * Изпълнява се преди запис 
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {   
        if(!$rec->id) {
            $rec->_mustRoute = TRUE;
         }
    }


    /**
     * Изпълнява се след запис на обект
     * След като документа е вече записан, неговото ID се добавя в детайла на нишката
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if(!$rec->id || (!$rec->_mustRoute)) return;

        unset($rec->_mustRoute);

        // Ако записваме документа за първи път, подсигуряваме му място 
        // в системата от папки, нишки и детайли на нишките
        // Ако документа не е рутиран, опитваме се да му намерим адреса
        if(empty($rec->folderId) ) {
            $mvc->route($rec);
            $mustSave = TRUE;
        }

        // Ако няма тред - създаваме нов 
        if(!$rec->threadId) {
            $thRec->folderId = $rec->folderId;
            $thRec->title    = $mvc->getThreadTitle($rec);
            
            // Началното състояние на нишката е затворено
            $thRec->state    = 'closed'; 

            $rec->threadId  = doc_Threads::save($thRec);
            $mustSave = TRUE;
        }

        // Ако няма нишков детаил, който да отговаря за този документ - създаваме го
        if(!$rec->threadDocumentId) {
            $tdRec->folderId = $rec->folderId;
            $tdRec->threadId = $rec->threadId;
            $tdRec->docId    = $rec->id;
            $tdRec->docClass = core_Classes::fetchByName($mvc)->id;
            $rec->threadDocumentId  = doc_ThreadDocuments::save($tdRec);
            $mustSave = TRUE;
        }
        
        // Ако флегът е вдигнат, правим записите 
        if($mustSave) {
            $mvc->save($rec, 'folderId,threadId,threadDocumentId');
        }
    }
    

    /**
     * Ако в документа няма код, който да рутира документа до папка/тред, 
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, $res, $rec)
    {   
        // Ако рутирането е достигнало само до ThreadDetail намираме $threadId и $folderId
        if($rec->threadDocumentId && !$rec->threadId) {
            $tdRec = doc_ThreadDocuments::fetch($rec->threadDocumentId);
            $rec->threadId = $tdRec->threadId;
        }
        
        // 
        if($rec->threadId && !$rec->folderId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $rec->folder = $thRec->folderId;
        }

        if(!$rec->folderId) {
            $unRec = new stdClass();
            $unRec->name =  $mvc->title;
            $rec->folderId = email_Unsorted::forceCoverAndFolder($unRec);
        }
    }
    
    
    /**
     * Ако няма метод в документа, долния код сработва за да осигури титла за нишката
     */
    function on_AfterGetThreadTitle($mvc, $res, $rec)
    {
        if(!$res) {
            $res = $mvc->getRecTitle($rec);
        }
    }


    function on_BeforePrepareRetUrl($mvc, $res, $data)
    {
        $retUrl = getRetUrl();
        $folderId = $data->form->rec->folderId;
        $threadId = $data->form->rec->threadId;
        
       // bp($retUrl['Ctr'], $threadId, $folderId, $data);

        if($retUrl['Ctr'] == 'doc_Threads' && $threadId && $folderId) {
            $data->retUrl = toUrl(array('doc_ThreadDocuments', 'threadId' => $threadId, 'folderId' => $folderId));
            return FALSE;
        }

     }

}