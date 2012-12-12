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
        // Ако разглеждаме single' а
        if($data->action != 'single') return;
        
        // Ако не сме избрали папка
        if (!($folderId = mode::get('lastfolderId'))) return ;
        
        // Ако няма такъв файл
        if (!($fRec = doc_Files::fetch("#folderId = '{$folderId}' AND #fileHnd = '{$data->rec->fileHnd}'"))) return ;
        
        // Ако нямам права за файла
        if (!doc_Files::haveRightFor('list', $fRec)) return ;
        
        // Инстанция на класа
        $docFilesInst = cls::get('doc_Files');
        
        // Да е избран таба
        $docFilesInst->currentTab = 'Файлове';
        
        // Рендираме изгледа
        $res = $docFilesInst->renderWrapping($tpl, $data);
        
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Общи');
        
        // За да не се изпълнява по - нататък
        return FALSE;
    }
    
    
    /**
     * Прихваща извикването на getDocumentsWithFile във fileman_Files
     * 
     * @param core_Mvc $mvc - 
     * @param string $res - Променливата, в която се записва
     * @param fileman_Files $rec - Записите за файла
     */
    function on_AfterGetDocumentsWithFile($mvc, &$res, $rec)
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
        $query->orderBy('folderId', 'ASC');
        $query->orderBy('threadId', 'ASC');
        $query->orderBy('containerId', 'ASC');
        
        // Обхождаме всички извлечени резултати
        while ($fRec = $query->fetch()) {
            
            // Ако нямаме права за листване на записа продължаваме
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
            
            // Документа
            $doc = doc_Containers::getDocument($fRec->containerId);
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            // Атрибутеите на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . sbf($doc->getIcon()) . ');';
            $attr['title'] = tr('Документ') . ': ' . $docRow->title;
            
            // Документа да е линк към single' а на документа
            $documentLink = ht::createLink(str::limitLen($docRow->title, 70), array($doc, 'single', $doc->that), NULL, $attr);
            
            // id' то на контейнера на пъривя документ
            $firstContainerId = doc_Threads::fetchField($fRec->threadId, 'firstContainerId');
            
            // Ако има първи контейнер и не е равен на съответния контейнер
            if (($firstContainerId) && ($firstContainerId != $fRec->containerId)) {
                
                // Първия документ в нишката
                $docProxy = doc_Containers::getDocument($firstContainerId);
                
                // Полетата на документа във вербален вид
                $docProxyRow = $docProxy->getDocumentRow();
                
                // Атрибутеите на линка
                $attr['class'] = 'linkWithIcon';
                $attr['style'] = 'background-image:url(' . sbf($docProxy->getIcon()) . ');';
                $attr['title'] = tr('Нишка') . ': ' . $docProxyRow->title;
                
                // Темата да е линк към single' а на първиа документ документа
                $threadLink = ht::createLink(str::limitLen($docProxyRow->title, 70), array($docProxy, 'single', $docProxy->that), NULL, $attr);    
            }
            
            // Ако не сме минавали от папката
            if (!$folderArr[$fRec->folderId]) {
                
                // Записите за съответната папка
                $folderRec = doc_Folders::fetch($fRec->folderId);
                
                // Записите във вербален вид
                $folderRow = doc_Folders::recToVerbal($folderRec);
                
                // Линка към папката
                $folderLink = $folderRow->title;   

                // Отбелязваме, че сме отработили папката
                $folderArr[$fRec->folderId] = TRUE;
                
                // Добавяме линка към папката
                $str .= $folderLink . "\n";
            }
            
            // Ако има линк към нишката и не сме минавали през нишката
            if (!$threadArr[$fRec->threadId] && $threadLink) {
                
                // Отбелязваме, че сме отработили нишката
                $threadArr[$fRec->threadId] = TRUE;
                
                // Добавяме линка към нишката
                $str .= "\t" . $threadLink . "\n";
            }
            
            // Ако сме вътре в нишката, отместваме документите
            if ($threadArr[$fRec->threadId]) $str .= "\t";
            
            // Добавяме документа
            $str .= "\t" . $documentLink;
            
            // Добавяме към ресурса
            $res .= ($res) ? ("\n") . $str : $str;
            
        }
    }
    
    
    
    /**
     * Връща масив с линковете на папката и документа, където се среща за първи път файла
     * 
     * @param core_Mvc $mvc - 
     * @param array $res - Двумерен масив, който съдържа линка и id' то на папкта и документите
     * @param array $res['folder'] - Масив с id' то и линка на папката
     * @param array $res['firstContainer'] - Масив с id' то и линка към първия документ на нишката
     * @param array $res['container'] - Масив с id' то и линка към контейнера
     * @param string $res[X]['id'] - id' то
     * @param core_Et $res[X]['content'] - Линка
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
            
            // Документа
            $doc = doc_Containers::getDocument($fRec->containerId);
            
            // Полетата на документа във вербален вид
            $docRow = $doc->getDocumentRow();
            
            // Атрибутеите на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . sbf($doc->getIcon()) . ');';
            $attr['title'] = tr('Документ') . ': ' . $docRow->title;
            
            // Документа да е линк към single' а на документа
            $documentLink = ht::createLink(str::limitLen($docRow->title, 70), array($doc, 'single', $doc->that), NULL, $attr);
            
            // Добавяме в масива линка и id' то
            $res['container']['content'] = $documentLink;
            $res['container']['id'] = $fRec->containerId;
            
            // id' то на контейнера на пъривя документ
            $firstContainerId = doc_Threads::fetchField($fRec->threadId, 'firstContainerId');
        
            // Ако има първи контейнер и не е равен на съответния контейнер
            if (($firstContainerId) && ($firstContainerId != $fRec->containerId)) {
                
                // Първия документ в нишката
                $docProxy = doc_Containers::getDocument($firstContainerId);
                
                // Полетата на документа във вербален вид
                $docProxyRow = $docProxy->getDocumentRow();
                
                // Атрибутеите на линка
                $attr['class'] = 'linkWithIcon';
                $attr['style'] = 'background-image:url(' . sbf($docProxy->getIcon()) . ');';
                $attr['title'] = tr('Нишка') . ': ' . $docProxyRow->title;
                
                // Темата да е линк към single' а на първиа документ документа
                $threadLink = ht::createLink(str::limitLen($docProxyRow->title, 70), array($docProxy, 'single', $docProxy->that), NULL, $attr); 

                // Добавяме в масива линка и id' то
                $res['firstContainer']['content'] = $threadLink;
                $res['firstContainer']['id'] = $firstContainerId;
            } else {
                
                // Ако документа е начало на тред
                $res['firstContainer'] = $res['container'];
            }
            
             // Записите за съответната папка
            $folderRec = doc_Folders::fetch($fRec->folderId);
            
            // Записите във вербален вид
            $folderRow = doc_Folders::recToVerbal($folderRec);
            
            // Линка към папката
            $folderLink = $folderRow->title;   
            
            // Добавяме в масива линка и id' то
            $res['folder']['content'] = $folderLink;
            $res['folder']['id'] = $fRec->folderId;
            
            // Прекратяваме по нататъчното изпълнение
            break;
        }
    }
}