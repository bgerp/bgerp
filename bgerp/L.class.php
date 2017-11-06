<?php



/**
 * История на документите
 *
 * Активиране, изпращане по имейл, получаване, връщане, отпечатване, споделяне, виждане ..
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>, Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_L extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Хоронология на действията с на документи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'bgerp_Wrapper, plg_RowTools, plg_Printing, plg_Created';
    
    
    /**
     * Дължина на манипулатора 'mid'
     */
    const MID_LEN = 7;
    
    
    /**
     * Да не се кодират id-тата
     */
    var $protectId = FALSE;
    
    
    /**
     * Добавя запис в документния лог, за действие направено от потребител на системата
     */
    function add($action, $tid, $cid = 0, $res = NULL, $refId = NULL)
    {
        $rec = new stdClass();
        
        $L = cls::get('bgerp_L');
        
        // Очакваме само дайствие, допустимо за извършване от регистриран потребител
        $actType = $L->fields['action']->type;
        expect(isset($actType->options[$action]));
        $rec->action = $action;
        
        // Ако нямаме зададен ресурс, той се попълва с IP-то на текущия потребител
        if(!isset($res)) {
            $rec->res = core_Users::getRealIpAddr();
        }
        
        $rec->tid   = $tid;
        $rec->cid   = $cid;
        $rec->refId = $refId;
    }
    
    
    /**
     * Добавя запис в документния лог, за действие, производно на друго действие, записано в този лог
     */
    static function addRef($action, $refMid, $res = NULL)
    {
        // Очакваме действието да започва с долна чера, защото по този начин означаваме действията
        // Които 
        // Трябва да имаме референтен 'mid'.
        // Чрез него се извлича 'id', 'tid' и 'cid' на референтния запис
        expect($refMid);
        $refRec = static::fetchField("#mid = '{$refMid}'");
        $tid   = $refRec->tid;
        $cid   = $refRec->cid;
        $refId = $refRec->id;
        
        static::add($action, $tid, $cid, $res, $refId);
    }
    
    
    /**
     * Помощна функция, която връща 
     * 
     * @param integer $cId
     * @param integer $mId
     * 
     * @return array
     */
    protected static function getDocOptions($cId, $mId)
    {
        // Трасираме стека с действията докато намерим SEND екшън
        $i = 0;
        
        $options = array();
        
        while ($action = doclog_Documents::getAction($i--)) {
        
            $options = (array)$action->data;
        
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
                    log_Browsers::setVars(array('email' => $action->data->to), FALSE, FALSE);
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
    function act_S()
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
            
            $options = $this->getDocOptions($cid, $mid);
            
            // Ако потребителя има права до треда на документа, то той му се показва
            if($rec && $rec->threadId) {
                
                if($doc->getInstance()->haveRightFor('single', $rec) || doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    return new Redirect(array($doc->getInstance(), 'single', $rec->id));
                }
            }
            
            Mode::push('saveObjectsToCid', $cid);
            // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на 
            // документа за показване
            $html = $doc->getDocumentBody('xhtml', (object) $options);
            Mode::pop('saveObjectsToCid');
            
            Mode::set('wrapper', 'page_External');
            
            $html = new core_ET($html);
            
            // Инструкция към ботовете за да не индексират и не проследяват линковете
            // на тези по същество вътрешни, но достъпни без парола страници.
            $html->append("\n" . '<meta name="robots" content="noindex, nofollow">', 'HEAD');
            
            // Ако има потребител с такъв имейл и не е логнат, показваме линк за логване
            if ($options['to'] && !haveRole('user')) {

                $emailsArr = type_Emails::toArray($options['to']);
                foreach ($emailsArr as $email) {
                    if (!core_Users::fetch(array("#email = '[#1#]' AND #state = 'active'", $email))) continue;

                    $html->append(ht::createLink(tr('Логнете се, за да видите нишката'), array('core_Users', 'login', 'ret_url' => TRUE), NULL, array('class' => 'hideLink')));
                    
                    break;
                }
            }
            
            if (!haveRole('user')) {
                if (doc_PdfCreator::canConvert()) {
                    $html->append(ht::createLink(tr('Свали като PDF'), array($this, 'pdf', $cid, 'mid' => $mid, 'ret_url' => TRUE), NULL, array('class' => 'hideLink')));
                }
                
                $exportArr = array();
                try {
                    $exportArr = $doc->getExportUrl($mid);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                }
                
                if (!empty($exportArr)) {
                    $html->append(ht::createLink(tr('Експорт'), $exportArr, NULL, array('class' => 'hideLink')));
                }
            }
            
            return $html;
        } catch (core_exception_Expect $ex) {
            // Опит за зареждане на несъществуващ документ или документ с невалиден MID.
            
            // Нелогнатите потребители не трябва да могат да установят наличието / липсата на
            // документ. За тази цел системата трябва да реагира както когато документа е 
            // наличен, но няма достатъчно права за достъп до него, а именно - да покаже
            // логин форма.
            
            requireRole('user');  // Ако има логнат потребител, този ред няма никакъв ефект.
            // Ако няма - това ще форсира потребителя да се логне и ако
            // логинът е успешен, управлението ще се върне отново тук
            
            // До тук се стига ако логнат потребител заяви липсващ документ или документ с 
            // невалиден MID. 
            
            // Ако потребителя има права до треда на документа, то той му се показва
            if($doc) {
            	$urlArray = $doc->getSingleUrlArray();
            	
                if(is_array($urlArray) && count($urlArray)) {
                    
                    return new Redirect($urlArray);
                }
            }
            
            expect(FALSE);  // Същото се случва и ако документа съществува, но потребителя няма
            // достъп до него.
        }
    }
    
    
    /**
     * Екшън, който сваля подадения документ, като PDF
     */
    function act_Pdf()
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
            
            Request::forward(array('fileman_Download', 'download', 'fh' => $resFileHnd, 'forceDownload' => TRUE));
        } catch (core_exception_Expect $ex) {
            requireRole('user'); 
            
            if($doc) {
            	$urlArray = $doc->getSingleUrlArray();
            	
                if(is_array($urlArray) && count($urlArray)) {
                    
                    return new Redirect($urlArray);
                }
            }
            
            expect(FALSE);
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
    function act_B()
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
            doclog_Documents::received($mid, NULL, $ip);
            $action = doclog_Documents::getActionRecForMid($mid, doclog_Documents::ACTION_SEND);
            
            if ($action && $action->data->to) {
                log_Browsers::setVars(array('email' => $action->data->to), FALSE, FALSE);
            }
        }
        
        $docUrl = static::getDocLink($cid, $mid);
        
        barcode_Qr::getImg($docUrl, 3, 0, 'L', NULL);
        
        // Връщаме стария режим
        Mode::set('text', $text);
    }
    
    
    /**
     * Връща линк към този контролер, който показава документа от посочения контейнер
     *
     * @param integer $cid - containerId
     * @param inreger $mid - Шаблона, който ще се замества
     *
     * @return string $link - Линк към вювъра на документите
     */
    static function getDocLink($cid, $mid)
    {
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf');
        $url = toUrl(array('L', 'S', $cid, 'm' => $mid), $isAbsolute, TRUE, array('m'));
        
        return $url;
    }
    
    
    /**
     * Проверява контролната сума към id-то, ако всичко е ОК - връща id, ако не е - FALSE
     */
    function unprotectId($id)
    {
        // Ако е число
        if (!is_numeric($id)) {
            
            // Променлива, в която държим старото състояние
            $protectId = $this->protectId;
            
            // Задаваме да се защитава
            $this->protectId = TRUE;
            
            // Вземаме id' то
            $id = $this->unprotectId_($id);
            
            // Връщаме стойността
            $this->protectId = $protectId;
        }
        
        return $id;
    }
}
