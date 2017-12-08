<?php


/**
 * Клас, който се прикача към fileman_Files и се грижи за показване на файловете в документите
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_FilesPlg extends core_Plugin
{
    
    
    /**
     * Променяме wrapper' а да сочи към текущата избрана папка
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        if ($data->action != 'single') return ;
        
        // Ако нямам права за файла
        if (!doc_Files::haveRightFor('list')) return ;
        
        // Инстанция на класа
        $docFilesInst = cls::get('doc_Files');
        
        // Да е избран таба
        $docFilesInst->currentTab = 'Файлове';
        
        // Рендираме изгледа
        $res = $docFilesInst->renderWrapping($tpl, $data);
        
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Всички');
        
        // За да не се изпълнява по - нататък
        return FALSE;
    }
    
    
    /**
     * Прихваща извикването на getDocumentsWithFile във fileman_Files
     * 
     * @param core_Mvc $mvc - 
     * @param string $res - Променливата, в която се записва
     * @param fileman_Files $rec - Записите за файла
     * @param integer $limit - Брой записи, които да се показват
     */
    function on_AfterGetDocumentsWithFile($mvc, &$res, $rec, $limit=FALSE)
    {
        // Ако не е обект, а е подаден id
        if (!is_object($rec)) {
            
            // Опитваме се да извлечем данните
            $rec = fileman_Files::fetch($rec);
        }
        
        // Очакваме да има такъв запис
        expect($rec && $rec->dataId, 'Няма такъв запис');
        
        // Запитваме за извличане на данните
        $query = doc_Files::getQuery();
        
        // Извличаме всички, със съответното id
        $query->where("#dataId = '{$rec->dataId}'");
        
        // Как да са подредени резултатите
        $query->orderBy('id', 'DESC');
        
        if ($limit) {
            $limit = $query->limit($limit);
        }
        
        $threadArr = array();
        $folderArr = array();
        
        // Обхождаме всички извлечени резултати
        while ($fRec = $query->fetch()) {
            
            // Ако нямаме права за разглеждане на записа
            if (!doc_Files::haveRightFor('list', $fRec)) continue;
            
            // Ако сме обходили съответния контейнер, прескачаме
            if ($containerArr[$fRec->containerId]) continue;
            
            // Записваме, че сме обходили контейнера
            $containerArr[$fRec->containerId] = TRUE;
            
            // Първоначално да са празни стрингове
            $str = '';
            $documentLink = '';
            $threadLink = '';
            $folderLink = '';
            
            try {
                // Документа
                $doc = doc_Containers::getDocument($fRec->containerId);
            } catch(ErrorException $e) {
            	
                continue;
            }
            
            if (!$doc || !$doc->haveRightFor('single')) continue ;
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            $attr = array();
            
            // Атрибутеите на линка
            $attr['title'] = 'Документ|*: ' . $docRow->title;
            
            // Документа да е линк към single' а на документа
            $documentLink = $doc->getLink(35, $attr);
            
            // id' то на контейнера на пъривя документ
            $firstContainerId = doc_Threads::fetchField($fRec->threadId, 'firstContainerId');
            
            if ($firstContainerId && ($firstContainerId != $fRec->containerId)) {
                
                // Ако има първи контейнер
                if (!$threadArr[$fRec->threadId]) {
                    
                    try {
                        // Първия документ в нишката
                        $docProxy = doc_Containers::getDocument($firstContainerId);
                    } catch (Exception $e) {
                    
                        continue;
                    }
                    
                    if (!$docProxy || !$docProxy->haveRightFor('single')) continue ;
                    
                    // Полетата на документа във вербален вид
                    $docProxyRow = $docProxy->getDocumentRow();
                    
                    $attr = array();
                    
                    // Атрибутеите на линка
                    $attr['title'] = 'Нишка|*: ' . $docProxyRow->title;
                    
                    // Темата да е линк към single' а на първиа документ документа
                    $threadLink = $docProxy->getLink(35, $attr);    
                    
                    // Отбелязваме, че сме отработили нишката
                    $threadArr[$fRec->threadId] = $threadLink;
                } else {
                    
                    // Вземаме от масива, в който сме генерирали
                    $threadLink = $threadArr[$fRec->threadId];
                }
            }
            
            // Ако не сме минавали от папката
            if (!$folderArr[$fRec->folderId]) {
                
                // Линка към папката
                $folderLink = doc_Folders::getLink($fRec->folderId, 35);   

                // Отбелязваме, че сме отработили папката
                $folderArr[$fRec->folderId] = $folderLink;
            } else {
                
                // Вземаме от масива, в който сме генерирали
                $folderLink = $folderArr[$fRec->folderId];   
            }
            
            // Създаваме стринга за съответния път
            $str = "{$documentLink}";
            
            if ($threadLink) {
                $str .= " « {$threadLink}";
            }
            
            $str .= " « {$folderLink}";
            
            // Добавяме към ресурса
            $res .= ($res) ? ("\n") . $str : $str;
        }
    }
    
    
    /**
     * Връща масив с линковете на папката и документа, където се среща за първи път файла
     * 
     * @param core_Mvc $mvc
     * @param array $res - Двумерен масив, който съдържа линка и id' то на папкта и документите
     * array $res['folder'] - Масив с id' то и линка на папката
     * array $res['firstContainer'] - Масив с id' то и линка към първия документ на нишката
     * array $res['container'] - Масив с id' то и линка към контейнера
     * string $res[X]['id'] - id' то
     * core_Et $res[X]['content'] - Линка
     * @param fileman_Files $rec - Записите за файла
     */
    function on_AfterGetFirstContainerLinks($mvc, &$res, $rec)
    {
        // Ако не е обект, а е подаден id
        if (!is_object($rec)) {
            
            // Опитваме се да извлечем данните
            $rec = fileman_Files::fetch($rec);
        }
        
        // Очакваме да има такъв запис
        expect($rec && $rec->dataId, 'Няма такъв запис');
        
        // Запитваме за извличане на данните
        $query = doc_Files::getQuery();
        
        // Извличаме всички, със съответното id
        $query->where("#dataId = '{$rec->dataId}'");
        
        // Как да са подредени резултатите
        $query->orderBy('containerId', 'ASC');
        
        // Обхождаме всички извлечени резултати
        while ($fRec = $query->fetch()) {
            
            // Ако нямаме права за листване на записа продължаваме
            if (!doc_Files::haveRightFor('list', $fRec)) continue;
            
            try {
                // Документа
                $doc = doc_Containers::getDocument($fRec->containerId);
            } catch (ErrorException $e) {
            
                continue;
            }
            
            if (!$doc || !$doc->haveRightFor('single')) continue ;
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            // Атрибутеите на линка
            $attr = array();
            $attr['ef_icon'] = $doc->getIcon($doc->that);
            $attr['title'] = 'Документ|*: ' . $docRow->title;
            
            // Документа да е линк към single' а на документа
            $documentLink = ht::createLink(str::limitLen($docRow->title, 70), array($doc->className, 'single', $doc->that), NULL, $attr);
            
            // Добавяме в масива линка и id' то
            $res['container']['content'] = $documentLink;
            $res['container']['id'] = $fRec->containerId;
            
            // id' то на контейнера на пъривя документ
            $firstContainerId = doc_Threads::fetchField($fRec->threadId, 'firstContainerId');
        
            // Ако има първи контейнер и не е равен на съответния контейнер
            if (($firstContainerId) && ($firstContainerId != $fRec->containerId)) {
                
                try {
                    // Първия документ в нишката
                    $docProxy = doc_Containers::getDocument($firstContainerId);
                } catch (ErrorException $e) {
                    
                    continue;
                }
                
                // Полетата на документа във вербален вид
                $docProxyRow = $docProxy->getDocumentRow();
                
                // Атрибутеите на линка
                $attr = array();
                $attr['ef_icon'] = $docProxy->getIcon($doc->that);
                $attr['title'] = 'Нишка|*: ' . $docProxyRow->title;
                
                // Темата да е линк към single' а на първиа документ документа
                $threadLink = ht::createLink(str::limitLen($docProxyRow->title, 70), array($docProxy->className, 'single', $docProxy->that), NULL, $attr); 

                // Добавяме в масива линка и id' то
                $res['firstContainer']['content'] = $threadLink;
                $res['firstContainer']['id'] = $firstContainerId;
            } else {
                
                // Ако документа е начало на тред
                $res['firstContainer'] = $res['container'];
            }
            
             // Записите за съответната папка
            $folderRec = doc_Folders::fetch($fRec->folderId);
            
            if($folderRec) {
                // Записите във вербален вид
                $folderRow = doc_Folders::recToVerbal($folderRec);
                
                // Линка към папката
                $folderLink = $folderRow->title;   
                
                // Добавяме в масива линка и id' то
                $res['folder']['content'] = $folderLink;
                $res['folder']['id'] = $fRec->folderId;
            }
            
            // Прекратяваме по нататъчното изпълнение
            break;
        }
    }
    
    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Добавяме бутон за създаване на задача
        if ($data->rec->id && haveRole('powerUser')) {
            
            Request::setProtected(array('inType', 'foreignId'));
            
            $data->toolbar->addBtn('Връзка', array(
                    'doc_Linked',
                    'Link',
                    'foreignId' => $data->rec->id,
                    'inType' => 'file',
                    'ret_url'=> array($mvc, 'single', $data->rec->id)
            ), 'ef_icon = img/16/doc_tag.png, title=Връзка към документа,order=18');
        }
    }
}
