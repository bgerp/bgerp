<?php



/**
 * История на файловете
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_F extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Лог на файлове';
    
    
    /**
     * Да не се кодират id-тата
     */
    var $protectId = FALSE;
    
    
    /**
     * Екшън за показване на файловете, на нерегистрираните потребители
     */
    function act_S()
    {
        // MID' а на документа
        $mid = Request::get('id');
        
        // Името на файла
        $name = Request::get('n');
        
        // Името в долен регистър
        $name = mb_strtolower($name);
        
        // Очакваме да има изпратен документ с mid' а
        expect(($actRec = doclog_Documents::getActionRecForMid($mid, FALSE)) && ($actRec->containerId), 'Няма информация.');
        
        // Записваме, ако не е записоно, че файла е отворено от ip
        doclog_Documents::opened($actRec->containerId, $mid);
        
        // Вземаме документа
        $doc = doc_Containers::getDocument($actRec->containerId);
        
        // Ако екшъна не е за изпращане вземаме него
        if ($actRec->action != doclog_Documents::ACTION_SEND) {
            $actRecSend = doclog_Documents::getActionRecForMid($mid, doclog_Documents::ACTION_SEND);
            
            if ($actRecSend) {
                $actRec = $actRecSend;
            }
        }
        
        if ($actRec && $actRec->data->to) {
            log_Browsers::setVars(array('email' => $actRec->data->to));
        }
        
        // Записа на файла
        $docRec = $doc->fetch();
        
        expect($docRec);
        
        // Ако докъмента е отхвърлен, да не се показва на нерегистрирани потребители
        if ($docRec->state == 'rejected') {
            
            requireRole('user');
        }
        
        // Вземаме линкнатите файлове в документите
        $linkedFiles = $doc->getLinkedFiles();
        
        // Имената на файловете в долен регистър
        $linkedFiles = array_map('mb_strtolower', $linkedFiles);
        
        // Ако няма такъв файл
        if (!$fh = array_search($name, $linkedFiles)) {
            
            // Обхождаме масива с файловете в документа
            foreach ($linkedFiles as $fh => $dummy) {
                
                // Вземаме записа
                $fRec = fileman_Files::fetchByFh($fh);
                
                // Ако името съвпада
                if (mb_strtolower($fRec->name) == $name) {
                    
                    // Флаг
                    $exist = TRUE;
                    
                    // Прекъсваме
                    break;
                }
            }
            
            // Ако файла съществува в масива
            expect($exist, 'Няма такъв файл.');
        } else {
            
            // Записите за файла
            $fRec = fileman_Files::fetchByFh($fh);
        }
        
        // В зависимост от това дали има права за разгреждане - линк към сингъла или за сваляне
        $url = fileman_Files::generateUrl_($fh, TRUE);
        
        // Записваме в лога за файлове, информация за свалянето
        doclog_Documents::downloaded($mid, $fh);
        
        // Редиректваме към линка
        redirect($url);
    }
    
    
    /**
     * Екшън за показване на картинки на нерегистрирани потребители
     */
    function act_T()
    {
        // MID на изпратената картинка
        $mid = Request::get('id');
        
        // Името на картинката
        $name = Request::get('n');
        expect($name, 'Липсва име на файл');
        
        // Ако няма MID, трябва да е регистриран потребител
        if (!$mid) {
            requireRole('user');
        } else {
            
            // Опитваме се да определим изпращенето от MID'a
            expect(($actRec = doclog_Documents::getActionRecForMid($mid, FALSE)) && ($actRec->containerId), 'Няма информация.');
            
            // Записваме, ако не е записоно, че файла е отворено от ip
            doclog_Documents::opened($actRec->containerId, $mid);
            
            // Вземаме документа
            $doc = doc_Containers::getDocument($actRec->containerId);
        
            // Ако екшъна не е за изпращане вземаме него
            if ($actRec->action != doclog_Documents::ACTION_SEND) {
                $actRecSend = doclog_Documents::getActionRecForMid($mid, doclog_Documents::ACTION_SEND);
                
                if ($actRecSend) {
                    $actRec = $actRecSend;
                }
            }
            
            if ($actRec && $actRec->data->to) {
                log_Browsers::setVars(array('email' => $actRec->data->to));
            }
            
            // Запис за документа
            $docRec = $doc->fetch();
            
            // Ако е оттеглен
            if ($docRec->state == 'rejected') {
                
                // Само логнати могат да разглеждат
                requireRole('user');
            }
            
            // Вземаме линкнатите файлове в документите
            $linkedImages = $doc->getLinkedImages();
            
            // Очакваме зададения да е във файла
            expect($linkedImages[$name]);
        }
        
        // Запис за картинката
        $imgRec = fileman_GalleryImages::fetch(array("#title = '[#1#]'", $name));
        expect($imgRec, 'Няма информация за файла');
        
        // Запис за групата
        $groupRec = fileman_GalleryGroups::fetch($imgRec->groupId);
        expect($groupRec, 'Няма информация за файла');
        
        // Широчината и височината на картинката
        $width = ($groupRec->width) ? $groupRec->width : 900;
        $height = ($groupRec->height) ? $groupRec->height : 900;
        
        if ($mid) {
            $isAbsolute = FALSE;
        } else {
            $isAbsolute = TRUE;
        }
        
        // Генерираме thumbnail
        $Img = new thumb_Img(array($imgRec->src, $width, $height, 'fileman', 'isAbsolute' => $isAbsolute, 'mode' => 'small-no-change', 'verbalName' => $name));
        
        // Ако има MID
        if ($mid) {
            // Форсираме свалянето му
            $Img->forceDownload();
        } else {
            if (fileman_GalleryImages::haveRightFor('single', $imgRec)) {
                
                // Вземаме деферед URL
                $url = $Img->getUrl('deferred');
                
                return new Redirect($url);
            }
            
            expect(FALSE);
        }
    }
}
