<?php


/**
 * История на документите
 *
 * Активиране, изпращане по имейл, получаване, връщане, отпечатване, споделяне, виждане ..
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>, Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_L extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Хронология на действията с документи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'bgerp_Wrapper, plg_RowTools, plg_Printing, plg_Created';
    
    
    /**
     * Дължина на манипулатора 'mid'
     */
    const MID_LEN = 7;
    
    
    /**
     * Да не се кодират id-тата
     */
    public $protectId = false;
    
    
    /**
     * Добавя запис в документния лог, за действие направено от потребител на системата
     */
    public function add($action, $tid, $cid = 0, $res = null, $refId = null)
    {
        $rec = new stdClass();
        
        $L = cls::get('bgerp_L');
        
        // Очакваме само действие, допустимо за извършване от регистриран потребител
        $actType = $L->fields['action']->type;
        expect(isset($actType->options[$action]));
        $rec->action = $action;
        
        // Ако нямаме зададен ресурс, той се попълва с IP-то на текущия потребител
        if (!isset($res)) {
            $rec->res = core_Users::getRealIpAddr();
        }
        
        $rec->tid = $tid;
        $rec->cid = $cid;
        $rec->refId = $refId;
    }
    
    
    /**
     * Добавя запис в документния лог, за действие, производно на друго действие, записано в този лог
     */
    public static function addRef($action, $refMid, $res = null)
    {
        // Очакваме действието да започва с долна черта, защото по този начин означаваме действията
        // Които
        // Трябва да имаме референтен 'mid'.
        // Чрез него се извлича 'id', 'tid' и 'cid' на референтния запис
        expect($refMid);
        $refRec = static::fetchField("#mid = '{$refMid}'");
        $tid = $refRec->tid;
        $cid = $refRec->cid;
        $refId = $refRec->id;
        
        static::add($action, $tid, $cid, $res, $refId);
    }
    
    
    /**
     * Помощна функция, която връща
     *
     * @param int $cId
     * @param int $mId
     *
     * @return array
     */
    public static function getDocOptions($cId, $mId)
    {
        // Трасираме стека с действията докато намерим SEND екшън
        $i = 0;
        
        $options = array();
        
        while ($action = doclog_Documents::getAction($i--)) {
            $options = (array) $action->data;
            
            // Ако има изпратено от
            if (($action->data->sendedBy > 0) && (!$options['__userId'] || $options['__userId'] <= 0)) {
                $options['__userId'] = $action->data->sendedBy;
            }
            
            // Ако е принтиран
            // TODO ще се оправи
            if ($action->action == doclog_Documents::ACTION_PRINT) {
                $options['__toListId'] = $action->data->toListId;
                
                if ($action->createdBy > 0 && !$options['__userId']) {
                    $options['__userId'] = $action->createdBy;
                }
            }
            
            // Ако е изпратен
            if ($action->action == doclog_Documents::ACTION_SEND) {
                if ($action && $action->data->to) {
                    $eArr = type_Emails::toArray($action->data->to);
                    log_Browsers::setVars(array('email' => $eArr[0]), false, false);
                }
                
                $activatedBy = $action->createdBy;
                
                // Активатора и последния модифицирал на изпратения документ
                if (!$activatedBy || $activatedBy <= 0) {
                    $activatedBy = $rec->activatedBy;
                }
                
                // Активатора и последния модифицирал на изпратения документ
                if (!$activatedBy || $activatedBy <= 0) {
                    $sendContainerRec = doc_Containers::fetch($action->containerId);
                    $activatedBy = $sendContainerRec->activatedBy;
                }
                
                // Ако няма потребител или е системата - за бласт
                if (!$options['__userId'] || $options['__userId'] <= 0) {
                    if ($activatedBy > 0) {
                        $options['__userId'] = $activatedBy;
                    }
                }
            }
        }
        
        return $options;
    }
    
    
    /**
     * Екшъна за показване на документи
     */
    public function act_S()
    {
        try {
            //Вземаме номера на контейнера
            expect($cid = Request::get('id', 'int'));
            
            // Вземаме документа
            expect($doc = doc_Containers::getDocument($cid));
            
            // Вземаме записа за документа
            $rec = $doc->fetch();
            
            // Очакваме да не е оттеглен документ
            expect($rec->state != 'rejected', 'Липсващ документ');
            
            if ($rec->state == 'draft') {
                expect($doc->canEmailDraft, 'Липсващ документ');
            }
            
            //
            // Проверка за право на достъп според MID
            //
            
            // Вземаме манипулатора на записа от този модел (bgerp_L)
            expect($mid = Request::get('m'));
            
            expect(doclog_Documents::opened($cid, $mid));
            
            vislog_History::add('Разглеждане на имейла');
            
            // Ако потребителя има права до треда на документа, то той му се показва
            if ($rec && $rec->threadId) {
                if ($doc->getInstance()->haveRightFor('single', $rec) || doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    return new Redirect(array($doc->getInstance(), 'single', $rec->id));
                }
            }
            
            $options = $this->getDocOptions($cid, $mid);
            
            // Пушваме езика, на който се е рендирал документа
            if (!haveRole('user')) {
                if ($options['lg']) {
                    core_Lg::set($options['lg']);
                }
            }
            
            Mode::push('saveObjectsToCid', $cid);
            
            $isSystemCanSingle = false;
            
            if (($options['sendedBy'] == -1) && $options['isSystemCanSingle']) {
                $isSystemCanSingle = true;
                Mode::set('isSystemCanSingle', true);
            }
            
            // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на
            // документа за показване
            $html = $doc->getDocumentBody('xhtml', (object) $options);
            
            if ($isSystemCanSingle) {
                Mode::set('isSystemCanSingle', false);
                $isSystemCanSingle = false;
            }
            
            Mode::pop('saveObjectsToCid');
            
            Mode::set('wrapper', 'page_External');
            
            $html = new core_ET($html);
            
            // Инструкция към ботовете за да не индексират и не проследяват линковете
            // на тези по същество вътрешни, но достъпни без парола страници.
            $html->append("\n" . '<meta name="robots" content="noindex, nofollow">', 'HEAD');

            $html->append("<div class='docToolbar'>");
            // Ако има потребител с такъв имейл и не е логнат, показваме линк за логване
            if (($options['to'] || $options['cc']) && !haveRole('user')) {
                $emailsStr = $options['to'];
                if ($options['cc']) {
                    $emailsStr .= ', ' . $options['cc'];
                }
                $emailsStr = strtolower($emailsStr);
                $emailsArr = type_Emails::toArray($emailsStr);
                foreach ($emailsArr as $email) {
                    if (!core_Users::fetch(array("#email = '[#1#]' AND #state = 'active'", $email))) {
                        continue;
                    }
                    
                    $html->append(ht::createLink(tr('Логване'), array('core_Users', 'login', 'ret_url' => true), null, array('class' => 'hideLink', 'ef_icon' => 'img/16/key.png')). "<span>|</span>");
                    break;
                }
                
                if (email_Setup::get('SHOW_THREAD_IN_EXTERNAL') == 'yes') {
                    // Ако има повече от един имейл в нишката
                    $tEmailArr = $this->getThreadEmails($cid, $mid, true);
                    if (countR($tEmailArr) > 1) {
                        $html->append(ht::createLink(tr('Цялата нишка'), array($this, 'T', $cid, 'm' => $mid, '#' => $doc->getHandle()), null, array('class' => 'hideLink', 'ef_icon' => 'img/16/chat.png')) . "<span>|</span>");
                    }
                }
            }
            
            // Показване на линкове за сваляна на документа
            if (!haveRole('user')) {
                $userId = $options['__userId'];
                
                $dLog = doclog_Documents::getAction();
                if ($dLog->createdBy > 0) {
                    $userId = $dLog->createdBy;
                }
                
                if ($userId > 0) {
                    $sudo = core_Users::sudo($userId);
                }
                
                $exportArr = export_Export::getPossibleExports($doc->instance->getClassId(), $rec->id);
                
                if ($sudo) {
                    core_Users::exitSudo();
                }
                
                $exportLinkArr = array();
                foreach ($exportArr as $clsId => $name) {
                    $clsInst = cls::getInterface('export_ExportTypeIntf', $clsId);
                    
                    $eLink = $clsInst->getExternalExportLink($doc->instance->getClassId(), $rec->id, $mid);
                    
                    if ($eLink) {
                        $exportLinkArr[] = $eLink;
                    }
                }
                
                if (!empty($exportLinkArr)) {
                    
                    $isFirst = true;
                    foreach ($exportLinkArr as $link) {
                        if (!$link) {
                            continue;
                        }
                        
                        if (!$isFirst) {
                            $html->append('<span>|</span>');
                        } else {
                            $isFirst = false;
                        }
                        
                        $html->append($link);
                    }
                }
            }
            $html->append("</div>");

            return $html;
        } catch (core_exception_Expect $ex) {
            // Опит за зареждане на несъществуващ документ или документ с невалиден MID.
            
            // До тук се стига, ако логнат потребител заяви липсващ документ или документ с
            // невалиден MID.
            
            // Ако потребителя има права до треда на документа, то той му се показва
            if ($doc) {
                $urlArray = $doc->getSingleUrlArray();
                
                if (is_array($urlArray) && countR($urlArray)) {
                    
                    return new Redirect($urlArray);
                }
            }
            
            redirect(array('Index'), false, '|Изтекла или липсваща връзка', 'error');
        }
    }
    
    
    /**
     * Екшъна за показване на нишката с входящи/изходящи имейли
     */
    public function act_T()
    {
        try {
            expect(email_Setup::get('SHOW_THREAD_IN_EXTERNAL') == 'yes');
            
            //Вземаме номера на контейнера
            expect($cid = Request::get('id', 'int'));
            
            // Вземаме документа
            expect($doc = doc_Containers::getDocument($cid));
            
            // Вземаме записа за документа
            $rec = $doc->fetch();
            
            // Очакваме да не е оттеглен документ
            expect($rec->state != 'rejected', 'Липсващ документ');
            
            if ($rec->state == 'draft') {
                expect($doc->canEmailDraft, 'Липсващ документ');
            }
            
            // Вземаме манипулатора на записа от този модел (bgerp_L)
            expect($mid = Request::get('m'));
            
            expect(doclog_Documents::opened($cid, $mid));
            
            vislog_History::add('Разглеждане на нишка от имейли');
            
            // Ако потребителя има права до треда на документа, то той му се показва
            if ($rec && $rec->threadId) {
                if ($doc->getInstance()->haveRightFor('single', $rec) || doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    return new Redirect(array($doc->getInstance(), 'single', $rec->id));
                }
            }
            
            $options = $this->getDocOptions($cid, $mid);
            
            // Пушваме езика, на който се е рендирал документа
            if (!haveRole('user')) {
                if ($options['lg']) {
                    core_Lg::set($options['lg']);
                }
            }
            
            // Вземаме всички имейли
            $tEmailsDocArr = $this->getThreadEmails($cid, $mid);
            
            $html = '<div class="externalThread">';
            
            Mode::set('externalThreadView', true);
            
            Mode::set('noBlank', true);
            
            $mRec = doclog_Documents::fetchByMid($mid);
            $mRecToArr = type_Emails::toArray(strtolower($mRec->data->to));
            $mRecCcArr = type_Emails::toArray(strtolower($mRec->data->cc));
            
            $mRecToArr = arr::make($mRecToArr, true);
            $mRecCcArr = arr::make($mRecCcArr, true);
            
            $mAllEmails = $mRecToArr + $mRecCcArr;
            
            foreach ($tEmailsDocArr as $containerId => $dRec) {
                $dDoc = doc_Containers::getDocument($containerId);
                
                $className = 'doc';
                if ($dDoc->className == 'email_Outgoings') {
                    $className .= 'Outgoings';
                } elseif ($dDoc->className == 'email_Incomings') {
                    $className .= 'Incomings';
                }
                
                if ($cid == $containerId) {
                    $className .= ' currentEmailDoc';
                }
                
                $options = array();
                if ($dRec->_mid) {
                    // Маркираме документа като отворен
                    doclog_Documents::opened($containerId, $dRec->_mid);
                    $options = $this->getDocOptions($containerId, $dRec->_mid);
                }
                $options['rec'] = $dDoc->fetch();
                
                // Подготвяме данните за имейла от изпращането
                if ($dRec->_mid) {
                    $cRecVal = doclog_Documents::fetchByMid($dRec->_mid);
                    
                    if ($cRecVal) {
                        $cRecToArr = type_Emails::toArray(strtolower($cRecVal->data->to));
                        $cRecCcArr = type_Emails::toArray(strtolower($cRecVal->data->cc));
                        
                        $options['rec']->ExternalThreadViewDate = dt::mysql2verbal($cRecVal->createdOn);
                        
                        if ($cRecToArr) {
                            $options['rec']->ExternalThreadViewTo = type_Emails::fromArray($cRecToArr);
                        }
                        
                        if ($cRecCcArr) {
                            $options['rec']->ExternalThreadViewCc = type_Emails::fromArray($cRecCcArr);
                        }
                        
                        $fromEmail = email_Inboxes::fetchField($cRecVal->data->from, 'email');
                        $options['rec']->ExternalThreadViewFrom = $fromEmail;
                        
                        if ($options['rec']->createdBy > 0) {
                            $avatar = avatar_Plugin::getImg($options['rec']->createdBy, $fromEmail);
                        } else {
                            $avatar = avatar_Plugin::getImg($cRecVal->data->sendedBy, $fromEmail);
                        }
                        $options['rec']->ExternalThreadViewAvatar = $avatar;
                    }
                }
                
                Mode::push('saveObjectsToCid', $containerId);
                
                $isSystemCanSingle = false;
                
                if (($dRec->__options['sendedBy'] == -1) && $dRec->__options['isSystemCanSingle']) {
                    $isSystemCanSingle = true;
                    Mode::set('isSystemCanSingle', true);
                }
                
                if (!$dRec->_mid) {
                    Mode::push('action', NULL);
                }
                
                $hnd = $dDoc->getHandle();
                
                // Рендираме документа
                $html .= "<div class='{$className}' id='{$hnd}'>" . $dDoc->getDocumentBody('xhtml', (object) $options) . '</div>';
                
                if (!$dRec->_mid) {
                    Mode::pop('action');
                }
                
                if ($isSystemCanSingle) {
                    Mode::set('isSystemCanSingle', false);
                    $isSystemCanSingle = false;
                }
                
                Mode::pop('saveObjectsToCid');
                
                if ($dRec->_mid) {
                    doclog_Documents::flushActions();
                }
            }
            
            $html .= '</div>';
            
            Mode::set('wrapper', 'page_External');
            
            $html = new core_ET($html);
            
            // Инструкция към ботовете за да не индексират и не проследяват линковете
            // на тези по същество вътрешни, но достъпни без парола страници.
            $html->append("\n" . '<meta name="robots" content="noindex, nofollow">', 'HEAD');
            
            return $html;
        } catch (core_exception_Expect $ex) {
            // Ако потребителя има права до треда на документа, то той му се показва
            if ($doc) {
                $urlArray = $doc->getSingleUrlArray();
                
                if (is_array($urlArray) && countR($urlArray)) {
                    
                    return new Redirect($urlArray);
                }
            }
            
            redirect(array('Index'), false, '|Изтекла или липсваща връзка', 'error');
        }
    }
    
    
    /**
     * Връща всички имейли (входящи/изходящи) от същата нишка, като документа
     *
     * @param int    $cid
     * @param string $mid
     * @param bool   $onlyCheck
     *
     * @return array
     */
    protected function getThreadEmails($cid, $mid, $onlyCheck = false)
    {
        $resArr = array();
        
        $mRec = doclog_Documents::fetchByMid($mid);
        
        if (!$mRec) {
            
            return $resArr;
        }
        
        // Имейлите от документа източник
        $midEmailsStr = $mRec->data->to;
        if ($mRec->data->cc) {
            $midEmailsStr .= ', ' . $mRec->data->cc;
        }
        $midEmailsStr = strtolower($midEmailsStr);
        $midEmailsArr = type_Emails::toArray($midEmailsStr);
        
        if (empty($midEmailsArr)) {
            
            return $resArr;
        }
        
        $midEmailsArr = arr::make($midEmailsArr, true);
        
        $doc = doc_Containers::getDocument($cid);
        
        if (!$doc) {
            
            return $resArr;
        }
        
        // Вземаме записа за документа
        $dRec = $doc->fetch();
        
        if (!$dRec || !$dRec->threadId) {
            
            return $resArr;
        }
        
        
        $inClsId = email_Incomings::getClassId();
        $outClsId = email_Outgoings::getClassId();
        
        $cQuery = doc_Containers::getQuery();
        $cQuery->where(array("#threadId = '[#1#]'", $dRec->threadId));
        $cQuery->where("#state != 'rejected'");
        $cQuery->where("#state != 'draft'");
        $cQuery->where(array("#docClass = '[#1#]'", $inClsId));
        $cQuery->orWhere(array("#docClass = '[#1#]'", $outClsId));
        
        $cQuery->orderBy('createdOn', 'ASC');
        $cQuery->orderBy('id', 'ASC');
        
        while ($cRec = $cQuery->fetch()) {
            $continue = false;
            
            if (!$cRec->docId) {
                continue;
            }
            
            // Подготвяме имейлите от документа
            $emailArr = array();
            if ($cRec->docClass == $inClsId) {
                $inRec = email_Incomings::fetch($cRec->docId);
                
                email_Incomings::calcAllToAndCc($inRec);
                
                $allEmailsArr = array_merge($inRec->AllTo, $inRec->AllCc);
                foreach ($allEmailsArr as $allTo) {
                    $email = $allTo['address'];
                    $email = trim($email);
                    $email = strtolower($email);
                    $emailArr[$email] = $email;
                }
                if ($inRec->fromEml) {
                    $fromEml = trim($inRec->fromEml);
                    $fromEml = strtolower($fromEml);
                    $emailArr[$fromEml] = $fromEml;
                }
            } elseif ($cRec->docClass == $outClsId) {
                $sLogArr = doclog_Documents::fetchByCid($cRec->id, doclog_Documents::ACTION_SEND);
                
                $emailsStr = '';
                foreach ($sLogArr as $sLog) {
                    if (!$cRec->_mid) {
                        $cRec->_mid = $sLog->mid;
                    }
                    
                    $emailsStr .= ($emailsStr) ? ', ' : '';
                    
                    $emailsStr .= $sLog->data->to;
                    
                    if ($sLog->data->cc) {
                        $emailsStr .= ', ' . $sLog->data->cc;
                    }
                }
                $emailsStr = strtolower($emailsStr);
                
                $emailArr = type_Emails::toArray($emailsStr);
                $emailArr = arr::make($emailArr, true);
                
                if (empty($emailArr)) {
                    continue;
                }
            }
            
            foreach ($midEmailsArr as $email) {
                if (!$emailArr[$email]) {
                    $continue = true;
                    
                    break;
                }
            }
            
            if ($continue) {
                continue;
            }
            
            $resArr[$cRec->id] = $cRec;
            
            // Ако само се проверява дали има имейли
            if ($onlyCheck) {
                if (countR($resArr) > 1) {
                    
                    return $resArr;
                }
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Екшън, който сваля подадения документ, като PDF
     */
    public function act_Pdf()
    {
        try {
            expect(doc_PdfCreator::canConvert());
            
            $cId = Request::get('id', 'int');
            $mId = Request::get('mid');
            
            expect($cId && $mId);
            
            expect($doc = doc_Containers::getDocument($cId));
            
            $rec = $doc->fetch();
            
            // Очакваме да не е оттеглен документ
            expect($rec->state != 'rejected', 'Липсващ документ');
            
            expect(doclog_Documents::opened($cId, $mId));
            
            $optArr = $this->getDocOptions($cId, $mId);
            
            Mode::push('saveObjectsToCid', $cid);
            
            // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на
            // документа за показване
            $html = $doc->getDocumentBody('xhtml', (object) $optArr);
            Mode::pop('saveObjectsToCid');
            
            $hnd = $doc->getHandle();
            $name = $hnd . '.pdf';
            $resFileHnd = doc_PdfCreator::convert($html, $name);
            
            Request::forward(array('fileman_Download', 'download', 'fh' => $resFileHnd, 'forceDownload' => true));
        } catch (core_exception_Expect $ex) {
            requireRole('user');
            
            if ($doc) {
                $urlArray = $doc->getSingleUrlArray();
                
                if (is_array($urlArray) && countR($urlArray)) {
                    
                    return new Redirect($urlArray);
                }
            }
            
            expect(false);
        }
        
        if ($retUrl = getRetUrl()) {
            
            return $retUrl;
        }
    }
    
    
    /**
     * Показва QR баркод, сочещт към съответния документ
     * Параметъра $id се приема като номер на контейнер
     * Параметъра $l се приема като id на запис в този модел
     */
    public function act_B()
    {
        // Пускаме xhtml режима при вземане на QR кода
        $text = Mode::get('text');
        Mode::set('text', 'xhtml');
        
        //Вземаме номера на контейнера
        $cid = Request::get('id', 'int');
        $mid = Request::get('m');
        
        // Вземаме IP' то
        $ip = core_Users::getRealIpAddr();
        
        // При отваряне на имейла от получателя, отбелязваме като видян.
        if ($mid) {
            doclog_Documents::received($mid, null, $ip);
            $action = doclog_Documents::getActionRecForMid($mid, doclog_Documents::ACTION_SEND);
            
            if ($action && $action->data->to) {
                $eArr = type_Emails::toArray($action->data->to);
                log_Browsers::setVars(array('email' => $eArr[0]), false, false);
            }
        }
        
        $docUrl = static::getDocLink($cid, $mid);
        
        barcode_Qr::getImg($docUrl, 3, 0, 'L', null);
        
        // Връщаме стария режим
        Mode::set('text', $text);
    }
    
    
    /**
     * Връща линк към този контролер, който показава документа от посочения контейнер
     *
     * @param int $cid - containerId
     * @param int $mid - Шаблона, който ще се замества
     *
     * @return string $link - Линк към вювъра на документите
     */
    public static function getDocLink($cid, $mid)
    {
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf');
        $url = toUrl(array('L', 'S', $cid, 'm' => $mid), $isAbsolute, true, array('m'));
        
        // Добавяме файла към списъка
        if ($sCid = Mode::get('saveObjectsToCid')) {
            doc_UsedInDocs::addObject(array($cid => $cid), $sCid, 'docs');
        }
        
        return $url;
    }
    
    
    /**
     * Проверява контролната сума към id-то, ако всичко е ОК - връща id, ако не е - false
     */
    public function unprotectId($id)
    {
        // Ако е число
        if (!is_numeric($id)) {
            
            // Променлива, в която държим старото състояние
            $protectId = $this->protectId;
            
            // Задаваме да се защитава
            $this->protectId = true;
            
            // Вземаме id' то
            $id = $this->unprotectId_($id);
            
            // Връщаме стойността
            $this->protectId = $protectId;
        }
        
        return $id;
    }
}
