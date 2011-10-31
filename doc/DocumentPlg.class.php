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
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка');
        $mvc->FLD('threadId',  'key(mvc=doc_Threads,select=title)', 'caption=Нишка->Топик');
        $mvc->FLD('threadDocumentId',  'key(mvc=doc_ThreadDocuments,select=title)', 'caption=Нишка->Документ');

        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
    }

    /**
     * Изпълнява се преди запис 
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {   
        // Ако записваме документа за първи път, подсигуряваме му място 
        // в системата от папки, нишки и детайли на нишките
        if(!$rec->id && empty($fields)) {
            // Ако документа не е рутиран, опитваме се да му намерим адреса
            if(empty($rec->folderId) ) {
               $mvc->route($rec);
            }

            // Ако няма тред - създаваме нов 
            if(!$rec->threadId) {
                $tRec->folderId = $rec->folderId;
                $tRec->title    = $mvc->getThreadTitle($rec);
                $rec->threadId  = doc_Threads::save($tRec);
            }

            // Ако няма нишков детаил, който да отговаря за този документ - създаваме го
            if(!$rec->threadDocumentId) {
                $tdRec->folderId = $rec->folderId;
                $tdRec->threadId = $rec->threadId;
                
                $tdRec->docClass = core_Classes::fetchByName($mvc)->id;
                $rec->threadDocumentId  = doc_ThreadDocuments::save($tdRec);
                $rec->__mustUpdateDocId = TRUE;
            }
        }
    }


    /**
     * Изпълнява се след запис на обект
     * След като документа е вече записан, неговото ID се добавя в детайла на нишката
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($rec->__mustUpdateDocId) {
            $tdRec->id    = $rec->threadDocumentId;
            $tdRec->docId = $id;
            doc_ThreadDocuments::save($tdRec);
        }
    }
    

    /**
     * Ако в документа няма код, който да рутира документа до папка/тред, 
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, $res, $rec)
    {
        if(!$rec->folderId) {
            $unRec = new stdClass();
            $unRec->name = core_Classes::fetchByName($mvc)->title;
            $rec->folderId = doc_Unsorted::forceCoverAndFolder($unRec);
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

}