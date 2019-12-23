<?php


/**
 * История на файловете
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_F extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Лог на файлове';
    
    
    /**
     * Да не се кодират id-тата
     */
    public $protectId = false;
    
    
    public $loadList = 'plg_Created';
    
    
    public $canAdd = 'no_one';
    
    
    public $canDelete = 'no_one';
    
    
    public $canEdit = 'no_one';
    
    
    public function description()
    {
        cls::get('fileman_Files');
        
        $this->FLD('fileHnd', 'varchar(' . strlen(fileman_Setup::get('HANDLER_PTR')) . ')', 'notNull, caption=Манипулатор, input=none');
        $this->FLD('key', 'varchar(8)', 'notNull, caption=Ключ, input=none');
        $this->FLD('validity', 'time(suggestions=1 ден|1 седмица|1 месец|1 година)', 'notNull, caption=Валидност, mandatory');
    }
    
    
    /**
     * Екшън за показване на файловете, на нерегистрираните потребители
     */
    public function act_S()
    {
        // MID' а на документа
        $mid = Request::get('id');
        
        // Името на файла
        $name = Request::get('n');
        
        $bucketId = Request::get('b');
        
        // Името в долен регистър
        $name = mb_strtolower($name);
        
        // Очакваме да има изпратен документ с mid' а
        expect(($actRec = doclog_Documents::getActionRecForMid($mid, false)) && ($actRec->containerId), 'Няма информация.');
        
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
            log_Browsers::setVars(array('email' => $actRec->data->to), false, false);
        }
        
        // Записа на файла
        $docRec = $doc->fetch();
        
        expect($docRec);
        
        // Ако докъмента е отхвърлен, да не се показва на нерегистрирани потребители
        if ($docRec->state == 'rejected') {
            requireRole('powerUser');
        }
        
        Mode::push('saveObjectsToCid', $actRec->containerId);
        
        // Форсираме потребителя, който е изпратил документа
        $sudo = false;
        if ($actRec) {
            if (!$actRec->createdBy > 0) {
                $userId = $actRec->createdBy;
            } else {
                $options = bgerp_L::getDocOptions($actRec->containerId, $actRec->mid);
                $userId = $options['__userId'];
            }
            
            if ($userId > 0) {
                $sudo = core_Users::sudo($userId);
            }
        }
        
        // Вземаме линкнатите файлове в документите
        $linkedFiles = $doc->getLinkedFiles();
        
        if ($sudo) {
            core_Users::exitSudo();
        }
        
        Mode::pop('saveObjectsToCid');
        
        $resFileHnd = '';
        
        foreach ((array) $linkedFiles as $fh => $fName) {
            $fName = mb_strtolower($fName);
            
            if ($name == $fName) {
                $fRec = fileman_Files::fetchByFh($fh);
                
                // TODO - remove !isset($bucketId)
                if (!isset($bucketId) || $fRec->bucketId == $bucketId) {
                    $resFileHnd = $fh;
                    
                    break;
                }
            }
        }
        
        // Ако файлът липсва в подадения масив
        // Проверяваме записите
        if (!$resFileHnd) {
            foreach ((array) $linkedFiles as $fh => $fName) {
                $fRec = fileman_Files::fetchByFh($fh);
                
                if (mb_strtolower($fRec->name) == $name) {
                    // TODO - remove !isset($bucketId)
                    if (!isset($bucketId) || $fRec->bucketId == $bucketId) {
                        $resFileHnd = $fh;
                        
                        break;
                    }
                }
            }
        }
        
        expect($resFileHnd, 'Няма такъв файл');
        
        // В зависимост от това дали има права за разгреждане - линк към сингъла или за сваляне
        $url = fileman_Files::generateUrl_($resFileHnd, true);
        
        // Записваме в лога за файлове, информация за свалянето
        doclog_Documents::downloaded($mid, $resFileHnd);
        
        // Редиректваме към линка
        return new Redirect($url);
    }
    
    
    /**
     * Екшън за показване на картинки на нерегистрирани потребители
     */
    public function act_T()
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
            expect(($actRec = doclog_Documents::getActionRecForMid($mid, false)) && ($actRec->containerId), 'Няма информация.');
            
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
                log_Browsers::setVars(array('email' => $actRec->data->to), false, false);
            }
            
            // Запис за документа
            $docRec = $doc->fetch();
            
            // Ако е оттеглен
            if ($docRec->state == 'rejected') {
                
                // Само логнати могат да разглеждат
                requireRole('powerUser');
            }
            
            // Вземаме линкнатите файлове в документите
            $linkedImages = $doc->getLinkedImages();
            
            // Очакваме зададения да е във файла
            expect($linkedImages[$name]);
        }
        
        // Запис за картинката
        $imgRec = cms_GalleryImages::fetch(array("#title = '[#1#]'", $name));
        expect($imgRec, 'Няма информация за файла');
        
        // Запис за групата
        $groupRec = cms_GalleryGroups::fetch($imgRec->groupId);
        expect($groupRec, 'Няма информация за файла');
        
        // Широчината и височината на картинката
        $width = ($groupRec->width) ? $groupRec->width : 900;
        $height = ($groupRec->height) ? $groupRec->height : 900;
        
        if ($mid) {
            $isAbsolute = false;
        } else {
            $isAbsolute = true;
        }
        
        // Генерираме thumbnail
        $Img = new thumb_Img(array($imgRec->src, $width, $height, 'fileman', 'isAbsolute' => $isAbsolute, 'mode' => 'small-no-change', 'verbalName' => $name));
        
        // Ако има MID
        if ($mid) {
            // Форсираме свалянето му
            $Img->forceDownload();
        } else {
            if (cms_GalleryImages::haveRightFor('single', $imgRec)) {
                
                // Вземаме деферед URL
                $url = $Img->getUrl('deferred');
                
                return new Redirect($url);
            }
            
            expect(false);
        }
    }
    
    
    /**
     * Сваля подадения файл
     */
    public function act_D()
    {
        $fileHnd = Request::get('id');
        
        $fName = fileman_Files::fetchByFh($fileHnd, 'name');
        
        header("Content-Disposition: attachment; filename={$fName}");
        
        return Request::forward(array('fileman_Download', 'download', 'fh' => $fileHnd, 'forceDownload' => true));
    }
    
    
    /**
     *
     *
     * @param string $key
     * @param string $name
     *
     * @return string
     */
    public static function getShortLink($key, $name)
    {
        return toUrl(array('F', 'G', $key, 'n' => $name), 'absolute', true, array('n'));
    }
    
    
    /**
     *
     *
     * @param string $fileHnd
     * @param string $expireOn
     *
     * @return NULL|string
     */
    public static function getLink($fileHnd, &$expireOn = '')
    {
        $query = self::getQuery();
        $query->where(array("#fileHnd = '[#1#]'", $fileHnd));

//         $query->where(array("#createdBy = '[#1#]'", core_Users::getCurrent()));
        $query->XPR('expireOn', 'datetime', 'DATE_ADD(#createdOn, INTERVAL #validity SECOND)');
        
        $query->limit(1);
        
        $query->orderBy('expireOn', 'DESC');
        
        $rec = $query->fetch();
        
        if (!$rec) {
            
            return ;
        }
        
        $expireOn = $rec->expireOn;
        
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        return self::getShortLink($rec->key, $fRec->name);
    }
    
    
    /**
     * Екшън за генериране на линк за сваляне на файл с валидност
     */
    public function act_GetLink()
    {
        $fh = Request::get('fileHnd');
        
        $fRec = fileman_Files::fetchByFh($fh);
        
        expect($fRec);
        
        fileman_Files::requireRightFor('single', $fRec);
        
        $form = $this->getForm();
        
        $form->title = 'Генериране на линк за сваляне';
        
        $form->setDefault('validity', 86400 * 7);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array('fileman_Files', 'single', $fh);
        }
        
        $form->input();
        
        // Ако линка ще сочи към частна мрежа, показваме предупреждение
        if (core_App::checkCurrentHostIsPrivate()) {
            $host = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $form->info = "<div class='formNotice'>" . tr("Внимание|*! |Понеже линкът сочи към локален адрес|* ({$host}), |той няма да е достъпен от други компютри в Интернет|*.") . '</div>';
        }
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            $rec->key = str::getRand('********');
            $rec->fileHnd = $fh;
            
            fileman_Files::logWrite('Генериране на линк за сваляне', $fRec->id);
            
            self::save($rec);
            
            self::logWrite('Генериран линк', $rec->id);
            
            $form->info .= '<b>' . tr('Линк|*: ') . "</b><span onmouseUp='selectInnerText(this);'>" . self::getShortLink($rec->key, $fRec->name) . '</span>';
            
            $form->setField('validity', 'input=none');
            
            $form->toolbar->addBtn('Затваряне', $retUrl, 'ef_icon = img/16/close-red.png, title=' . tr('Връщане към файла') . ', class=fright');
            
            $form->title = 'Линк за сваляне активен|* ' . $this->getVerbal($rec, 'validity');
            
            fileman::updateLastUse($fRec, dt::addSecs($form->rec->validity));
        } else {
            $form->toolbar->addSbBtn('Генериране', 'save', 'ef_icon = img/16/world_link.png, title = ' . tr('Генериране на линк за сваляне'));
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title= ' . tr('Прекратяване на действията'));
        }
        
        Mode::set('pageMenu', 'Система');
        Mode::set('pageSubMenu', 'Файлове');
        $fInst = cls::get('fileman_Files');
        $fInst->currentTab = 'Файлове';
        
        return $fInst->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Екшън за сваляне на файла - с валидност
     */
    public function act_G()
    {
        $key = Request::get('id');
        
        expect($key);
        
        $rec = self::fetch(array("#key = '[#1#]'", $key));
        
        if (!$rec || (dt::addSecs($rec->validity, $rec->createdOn) < dt::now())) {
            
            return new Redirect(array('Index'), '|Изтекла или липсваща връзка', 'error');
        }
        
        $fName = fileman_Files::fetchByFh($rec->fileHnd, 'name');
        header("Content-Disposition: attachment; filename={$fName}");
        
        return Request::forward(array('fileman_Download', 'download', 'fh' => $rec->fileHnd, 'forceDownload' => true));
    }
    
    
    /**
     * Извиква се от крона. Премахва изтеклите връзки
     */
    public function cron_removeOldDownloadLinks()
    {
        // Текущото време
        $now = dt::verbal2mysql();
        
        // Изтриваме всички изтекли записи
        $delCnt = self::delete("DATE_ADD(#createdOn, INTERVAL #validity SECOND) < '{$now}'");
        
        return $delCnt;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'removeOldDownloadLinks';
        $rec->description = 'Премахване на изтеклите линкове за сваляне';
        $rec->controller = $mvc->className;
        $rec->action = 'removeOldDownloadLinks';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 40;
        $res .= core_Cron::addOnce($rec);
    }
}
