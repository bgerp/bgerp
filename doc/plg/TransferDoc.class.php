<?php



/**
 * Клас 'doc_plg_TransferDocs'
 *
 * Плъгин за за прехвурляне на документи от проектна папка към папка на потребител
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_plg_TransferDoc extends core_Plugin
{
	
	
	/**
     * След създаването на документа
     */
	public static function on_AfterCreate($mvc, $rec)
    {
        $transferFolderField = $mvc->transferFolderField;
        // кое поле ще изпозлваме за преместване
        $newFolderField = $mvc->getField($transferFolderField);

        // новото Mvc
        $newCoverMvc = cls::get($newFolderField->type->params['mvc']);
       
        if($rec->{$transferFolderField}) {
            
            // Форсираме папка на проект
            $newCoverRec = $newCoverMvc::fetch($rec->{$transferFolderField});
            $newFolderId = $newCoverMvc::forceCoverAndFolder($newCoverRec);

            // подменяме ид-то на папката на документа с ид-то на папката на човека
            $rec->folderId = $newFolderId;
        
            // Споделяме текущия потребител в нишката на документа
            $cu = core_Users::getCurrent();
            doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
            $rec->sharedUsers = keylist::addKey($rec->sharedUsers, $cu);
            
            $mvc::save($rec,'folderId, sharedUsers');
            
            // подменяме ид на папката в контейнера
            $cRec = doc_Containers::fetch($rec->containerId);
            $cRec->folderId = $newFolderId;
            
            doc_Containers::save($cRec, 'folderId');
            
            // подменяме ид на папката в треда
            $tRec = doc_Threads::fetch($rec->threadId);
            $tRec->folderId = $newFolderId;
     
            doc_Threads::save($tRec, 'folderId');
        }
    }
}