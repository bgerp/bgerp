<?php


/**
 * Клас 'log_Debug' - Мениджър за запис на действията на потребителите
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class log_Debug extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Дебъг лог';
    
    
    /**
     * Кой може да листва и разглежда?
     */
    public $canRead = 'no_one';
    
    
    /**
     * Кой може да добавя, редактира и изтрива?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да репортва грешките
     */
    public $canReport = 'user';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    public $loadList = 'plg_SystemWrapper';
    
    
    /**
     * При дъмп - колко нива преглеждаме
     */
    protected $dumpOpenLevels = 3;
    
    
    /**
     * При дъмп - колко нива са отворени
     */
    protected $dumpViewLevels = 5;
    
    
    /**
     * Връща линк към създаване на сигнал от грешката
     *
     * @param string      $debugFile
     * @param string      $btnName
     * @param string      $icon
     * @param NULL|string $class
     *
     * @return core_ET
     */
    public static function getReportLink($debugFile, $btnName = 'Сигнал', $icon = 'img/16/debug_bug.png', $class = null)
    {
        $btnName = tr($btnName);
        
        $urlArr = array('log_Debug', 'report', 'debugFile' => $debugFile, 'ret_url' => true);
        
        $url = toUrl($urlArr);
        
        // Ако е мобилен/тесем режим
        if (Mode::is('screenMode', 'narrow')) {
            // Парамтери към отварянето на прозореца
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=450,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        $attr = array('onClick' => "openWindow('{$url}', 'bgerp_tracer_report', '{$args}'); return false;", 'title' => 'Изпращане на сигнал към разработчиците на bgERP');
        
        if ($icon) {
            $attr['ef_icon'] = $icon;
        }
        
        if ($class) {
            $attr['class'] = $class;
        }
        
        $attr['target'] = '_blank';
        
        $link = ht::createLink($btnName, $urlArr, false, $attr);
        
        return $link;
    }
    
    
    /**
     * Показва дебъг лога
     */
    public function act_Default()
    {
        $this->requireRightFor('list');
        if (Mode::is('screenMode', 'wide')) {
            $tpl = new ET(tr('|*<div class="headerLine">[#SHOW_DEBUG_INFO#]<!--ET_BEGIN CREATED_DATE--><span style="margin-left: 20px;">[#CREATED_DATE#]</span><!--ET_END CREATED_DATE--><div class="aright"><span class="debugActions"> [#SIGNAL#]</span> <span class="debugActions"> [#DOWNLOAD_FILE#]</span> <span class="debugActions">[#BEFORE_LINK#]</span><span class="debugActions">[#AFTER_LINK#] </span></div><div style="clear: both;"></div></div><div class="debugHolder"><div class="debugList">[#LIST_FILE#]</div><div class="debugPreview">[#ERR_FILE#]</div></div>'));
        } else {
            $tpl = new ET(tr('|*<div class="headerLine">[#SHOW_DEBUG_INFO#]<!--ET_BEGIN CREATED_DATE--><span>[#CREATED_DATE#]</span><!--ET_END CREATED_DATE--><div class="aright"><span class="debugActions"> [#SIGNAL#]</span> <span class="debugActions"> [#DOWNLOAD_FILE#]</span> <span class="debugActions">[#BEFORE_LINK#]</span><span class="debugActions">[#AFTER_LINK#] </span></div><div style="clear: both;"></div></div><div class="debugList">[#LIST_FILE#]</div><div class="debugPreview">[#ERR_FILE#]</div>'));
        }
        
        $defUser = PHP_INT_MAX;
        $debugFileName = null;
        if ($debugFile = Request::get('debugFile')) {
            Mode::set('stopLoggingDebug', true);
            $debugFileName = $debugFile . '.debug';
            $defUser = core_Users::getCurrent();
        }
        
        // Подготвяме листовия изглед за избор на дебъг файл
        $data = new stdClass();
        $data->query = $this->getQuery();
        $this->prepareListFilter($data);
        $data->listFilter->layout = new ET("<form [#FORM_ATTR#]><div class='search-fields'>[#FORM_SEARCH_FIELD#]</div><div class='other-fileds'>[#FORM_FIELDS#]</div>[#FORM_HIDDEN#]</form>\n");
        
        $data->listFilter->view = 'horizontal';
        
        // Рендираме бутона за търсене и полета, за да са заедно в отделен див
        $data->listFilter->FNC('search', 'varchar', 'caption=Файл, input, silent');
        $data->listFilter->toolbar->addSbBtn(' ', 'default', 'id=filter', 'ef_icon = img/16/find.png');
        
        if ($debugFile) {
            $data->listFilter->input('search', true);
            $data->listFilter->layout->append($data->listFilter->renderFields(), 'FORM_SEARCH_FIELD');
            $data->listFilter->layout->append($data->listFilter->renderToolbar(), 'FORM_SEARCH_FIELD');
        } else {
            $data->listFilter->layout->prepend($data->listFilter->renderToolbar(), 'FORM_FIELDS');
        }
        
        $data->listFilter->FNC('user', 'varchar', 'caption=Потребител, input, silent');
        $data->listFilter->FNC('execTime', 'enum(,fast=Бързо,slow=Бавно,verySlow=Много бавно)', 'caption=Изпълнение, input, silent');
        $data->listFilter->FNC('execSize', 'enum(,small=Малък, big=Голям, veryBig=Много голям)', 'caption=Размер, input, silent');
        $data->listFilter->FNC('execTimeFrom', 'varchar', 'caption=Време->От, input, silent, suggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00');
        $data->listFilter->FNC('execTimeTo', 'varchar', 'caption=Време->До, input, silent, suggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00');
        $data->listFilter->FNC('status', 'enum(,2xx=Успешен, 8xx=Успешен по AJAX, 000=Неприключен, 150=Наблюдение, 404=Липсваща страница, 500|505|510|0=Икзлючение, 501|520=Грешка, 503=Прекъсване, 550=Грешка в БД)', 'caption=Статус, input, silent');
        $data->listFilter->FNC('debugFile', 'varchar', 'caption=Файл, input=hidden, silent');
        
        $data->listFilter->showFields = 'user, debugFile, execTime, execSize, execTimeFrom, execTimeTo, status';
        
        if (!$debugFile) {
            // Добавяме полето за търсене
            $data->listFilter->showFields = 'search, ' . $data->listFilter->showFields;
        }
        
        // Опциите за потребители
        $uArr = core_Cache::get('log_Debug', 'users', 1000, 'core_Users');
        if (!$uArr) {
            $uArr = array();
            $uQuery = core_Users::getQuery();
            $uQuery->show('id, nick, names');
            $uArr[PHP_INT_MAX] = tr('Всички потребители');
            while ($uRec = $uQuery->fetch()) {
                $uArr[$uRec->id] = $uRec->nick . ' (' . core_Users::prepareUserNames($uRec->names) . ')';
            }
            $uArr[-1] = core_Users::fetchField(-1, 'nick');
            $uArr[0] = core_Users::fetchField(0, 'nick');
            core_Cache::set('log_Debug', 'users', $uArr, 1000, 'core_Users');
        }
        $data->listFilter->setOptions('user', $uArr);
        
        $data->listFilter->title = 'Дебъг';
        
        $data->listFilter->setDefault('user', $defUser);
        
        $data->listFilter->input(null, true);
        
        $tplList = new ET(tr('|*[#ListFilter#]<!--ET_BEGIN DEBUG_LINK--><div class="linksGroup">[#DEBUG_LINK#]</div><!--ET_END DEBUG_LINK-->'));
        $tplList->append($this->renderListFilter($data), 'ListFilter');
        
        if ($debugFile) {
            // Добавяме полето за търсене, което липсва за да не се рендира повторно
            $data->listFilter->showFields = 'search, ' . $data->listFilter->showFields;
        }
        
        $otherFilesFromSameHit = array();
        
        $before = 25;
        $after = 25;
        
        $oDebugFileName = $debugFileName;
        
        $fPathStr = $this->getDebugFilePath($debugFileName, false);
        
        if (!file_exists($fPathStr) && strpos($debugFile, 'x') === false) {
            $dFileNameArr = explode('_', $debugFile, 2);
            $debugFileName = 'x_' . $dFileNameArr[1] . '_x.debug';
        }
        
        $sArr = array();
        $searchArr = arr::make($data->listFilter->showFields);
        foreach ($searchArr as $fName) {
            $sArr[$fName] = $data->listFilter->rec->{$fName};
        }
        
        // Вземаме файловете, които да се показват
        $fArr = $this->getDebugFilesArr($debugFileName, $before, $after, $otherFilesFromSameHit, $sArr);
        
        if ($oDebugFileName != $debugFileName) {
            list($debugFile) = explode('.', $debugFileName);
        }
        
        $fArrCnt = count($fArr);
        
        $fLink = '';
        
        if ($fArrCnt > 1) {
            $fArr = array_reverse($fArr);
        }
        
        // Показваме линкове за навигиране
        $aPos = array_search($debugFileName, array_keys($fArr));
        
        $otherLinkUrl = array($this, 'Default', 'search' => $data->listFilter->rec->search);
        
        if ($debugFile) {
            // Ако има следващ дебъг файл
            $bLinkArr = array();
            if ($fArrCnt != ($aPos + 1)) {
                if ($bPosArr = array_slice($fArr, $aPos + 1, 1)) {
                    if ($fNameBefore = key($bPosArr)) {
                        $fNameBefore = fileman::getNameAndExt($fNameBefore);
                        if ($fNameBefore['name']) {
                            $bLinkArr = $otherLinkUrl;
                            $bLinkArr['debugFile'] = $fNameBefore['name'];
                            $bLinkArr += $sArr;
                        }
                    }
                }
            }
            $aLink = ht::createLink(tr(' << '), $bLinkArr);
            $tpl->replace($aLink, 'BEFORE_LINK');
            
            // Ако има предишен дебъг файл
            $aLinkArr = array();
            if ($aPos) {
                if ($aPosArr = array_slice($fArr, $aPos - 1, 1)) {
                    if ($fNameAfter = key($aPosArr)) {
                        $fPathStr = $this->getDebugFilePath($fNameAfter, false);
                        if (DEBUG_FATAL_ERRORS_FILE != $fPathStr) {
                            $fNameAfter = fileman::getNameAndExt($fNameAfter);
                            if ($fNameAfter['name']) {
                                $aLinkArr = $otherLinkUrl;
                                $aLinkArr['debugFile'] = $fNameAfter['name'];
                                $aLinkArr += $sArr;
                            }
                        }
                    }
                }
            }
            $bLink = ht::createLink(tr(' >> '), $aLinkArr);
            $tpl->replace($bLink, 'AFTER_LINK');
        }
        
        // Показваме всички файлове
        foreach ($fArr as $fNameWithExt => $dummy) {
            list($fName) = explode('.', $fNameWithExt, 2);
            
            $fPathStr = $this->getDebugFilePath($fName);
            if (DEBUG_FATAL_ERRORS_FILE == $fPathStr) {
                continue;
            }
            
            $cls = 'debugLink';
            
            $linkUrl = array($this, 'Default', 'debugFile' => $fName);
            $linkUrl += $sArr;
            
            if ($data->listFilter->rec->search) {
                $linkUrl['search'] = $data->listFilter->rec->search;
            }
            
            if ($fName == $debugFile) {
                $cls .= ' current';
                $linkUrl = array();
            } elseif ($otherFilesFromSameHit[$fNameWithExt]) {
                $cls .= ' same';
            }
            
            $fLink .= ht::createLink($fName, $linkUrl, false, array('class' => $cls, 'target' => '_parent'));
            
            if ($mCnt++ > 200) {
                break;
            }
        }
        
        $tplList->append($fLink, 'DEBUG_LINK');
        
        $tpl->append($tplList, 'LIST_FILE');
        
        $tpl->append('bgERP tracer', 'PAGE_TITLE');
        
        $this->logInAct('Листване', null, 'read');
        
        // Показва съдъражаниете на дебъга, ако е избран файла
        if ($debugFile) {
            $fPath = $this->getDebugFilePath($debugFile);
            if ($fPath) {
                $dUrl = fileman_Download::getDownloadUrl($fPath, 1, 'path');
                
                if ($dUrl) {
                    $tpl->replace(ht::createLink(tr('Сваляне'), $dUrl, null, 'ef_icon=img/16/debug_download.png'), 'DOWNLOAD_FILE');
                }
                
                $tpl->replace("<iframe style='width:100%; height: 100%' src='" . toUrl(array($this, 'ShowDebug', 'debugFile' => $debugFile)). "'>" . '</iframe>', 'ERR_FILE');
                
                $rArr = $this->getDebugFileInfoArr($fPath);
                $tpl->replace($rArr['_info'], 'SHOW_DEBUG_INFO');
                
                if (is_file($fPath) && is_readable($fPath)) {
                    $date = @filemtime($fPath);
                    $date = dt::timestamp2Mysql($date);
                    $date = dt::mysql2verbal($date, 'smartTime');
                    
                    $tpl->replace($date, 'CREATED_DATE');
                }
            }
            
            if ($this->haveRightFor('report')) {
                $singal = $this->getReportLink($debugFile);
                
                $tpl->append($singal, 'SIGNAL');
            }
            
            Mode::set('wrapper', 'page_Empty');
            $tpl->push('css/debug.css', 'CSS');
            
            // Плъгин за лайаута
            jquery_Jquery::run($tpl, 'debugLayout();');
            
            // Рендираме страницата
            return  $tpl;
        }
        
        $allFArr = $this->getDebugFilesArr();
        $tpl->prepend(tr('Общо файлове') . ': ' . count($allFArr));
        
        // Рендираме страницата
        return  $this->renderWrapping($tpl);
    }
    
    
    /**
     * Показва дебъг страницата
     */
    public function act_ShowDebug()
    {
        $this->requireRightFor('list');
        
        $debugFile = Request::get('debugFile');
        
        expect($debugFile);
        
        Mode::set('stopLoggingDebug', true);
        
        $fPath = $this->getDebugFilePath($debugFile);
        
        $dFile = $this->getDebugFileInfo($fPath);
        
        echo $dFile;
        
        shutdown();
    }
    
    
    /**
     * Вика act_Default
     *
     * @see core_Manager::act_List()
     */
    public function act_List()
    {
        $res = $this->act_Default();
        
        return $res;
    }
    
    
    /**
     * Екшън за репортване на грешката
     *
     * @return Redirect|ET
     */
    public function act_Report()
    {
        $this->requireRightFor('report');
        
        $form = cls::get('core_Form');
        
        $form->FNC('title', 'varchar(128)', 'caption=Заглавие, mandatory, input');
        $form->FNC('description', 'text(rows=10)', 'caption=Описание, mandatory, input');
        $form->FNC('name', 'varchar(64)', 'caption=Данни за обратна връзка->Име, mandatory, input');
        $form->FNC('email', 'email', 'caption=Данни за обратна връзка->Имейл, mandatory, input');
        $form->FNC('debugFile', 'varchar(64)', 'caption=Данни за обратна връзка->Файл, silent, input=hidden');
        
        $img = ht::createElement('img', array('src' => sbf('img/16/headset.png', '')));
        $form->title = '|*' . $img . '   |Сигнал към разработчиците на bgERP';
        
        $form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/ticket.png,title=Изпращане на сигнала');
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('Portal', 'Show');
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl, 'id=cancel, ef_icon = img/16/close-red.png,title=Отказ, onclick=self.close();');
        
        $email = email_Inboxes::getUserEmail();
        if (!$email) {
            $email = core_Users::getCurrent('email');
        }
        list($user, $domain) = explode('@', $email);
        $name = core_Users::getCurrent('names');
        
        $form->setDefault('email', $email);
        $form->setDefault('name', $name);
        
        $form->input(null, true);
        
        $form->setDefault('title', $_SERVER['HTTP_HOST']);
        
        $form->input();
        
        Mode::set('wrapper', 'page_Dialog');
        
        if ($form->isSubmitted()) {
            $dataArr = array();
            
            $fPath = $this->getDebugFilePath($form->rec->debugFile);
            
            if ($fPath && is_file($fPath)) {
                $data = @file_get_contents($fPath);
                
                if ($data) {
                    $dataArr['data'] = gzcompress($data);
                    $dataArr['fName'] = $form->rec->debugFile;
                }
            }
            
            $dataArr['name'] = $form->rec->name;
            $dataArr['email'] = $form->rec->email;
            $dataArr['description'] = gzcompress($form->rec->description);
            $dataArr['Lg'] = core_Lg::getCurrent();
            $dataArr['streamReport'] = true;
            $dataArr['title'] = $form->rec->title;
            
            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($dataArr),
                ),
            );
            $context = stream_context_create($options);
            $url = help_Setup::get('BGERP_SUPPORT_URL', true);
            $resStr = @file_get_contents($url, false, $context);
            
            
            if ($resStr == 'OK') {
                $tpl = new ET();
                jquery_Jquery::run($tpl, 'self.close();');
            } else {
                $form->setError('description', 'Възникна грешка при изпращане на сигнала.');
                $tpl = $form->renderHtml();
            }
            
            $this->logInAct('Изпращане на сигнал');
        } else {
            $tpl = $form->renderHtml();
        }
        
        // Добавяме клас към бодито
        $tpl->append('dialog-window', 'BODY_CLASS_NAME');
        
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        return $tpl;
    }
    
    
    /**
     * Връща масив с данните
     *
     * @param string $fPath
     *
     * @return array|mixed
     */
    protected static function getDebugFileInfoArr($fPath)
    {
        expect($fPath);
        
        $rArr = array();
        
        if (is_file($fPath) && is_readable($fPath)) {
            $content = @file_get_contents($fPath);
            
            $rArr = @json_decode($content);
            
            // Вероятно не е json, a e сериализирано
            if (!$rArr) {
                list(, , $content) = explode(' ', $content, 3);
                
                $rArr = unserialize($content);
            }
            
            if ($rArr) {
                $rArr = (array) $rArr;
            }
        }
        
        return $rArr;
    }
    
    
    /**
     * Показва дебъг страницата
     *
     * @param string $fPath
     * @param array  $rArr
     */
    public function getDebugFileInfo($fPath)
    {
        $rArr = $this->getDebugFileInfoArr($fPath);
        expect($fPath);
        
        // Рендираме лога
        if (!empty($rArr)) {
            $rArr = (array) $rArr;
            
            $rArr['update'] = false;
            
            if (!$rArr['contex']) {
                $rArr['contex'] = (object) $rArr['SERVER'];
            } else {
                $rArr['contex'] = (object) $rArr['contex'];
                if ($rArr['SERVER']) {
                    $rArr['contex']->_SERVER = $rArr['SERVER'];
                }
            }
            
            if ($rArr['GET']) {
                $rArr['contex']->_GET = $rArr['GET'];
            }
            
            if ($rArr['POST']) {
                $rArr['contex']->_POST = $rArr['POST'];
            }
            
            if (!$rArr['errType']) {
                if ($rArr['_debugCode']) {
                    $rArr['header'] .= $rArr['_debugCode'];
                }
                
                if ($rArr['_Ctr']) {
                    $rArr['header'] .= ' ' . $rArr['_Ctr'];
                }
                
                if ($rArr['_Act']) {
                    $rArr['header'] .= ' » ' . $rArr['_Act'];
                }
                
                if ($rArr['_executionTime']) {
                    $rArr['header'] .= ' (' . number_format($rArr['_executionTime'], 2) . ' s)';
                }
                
                if (!trim($rArr['header'])) {
                    if ($rArr['GET']) {
                        $rArr['header'] = $rArr['GET']->virtual_url;
                    }
                }
                
                if ($rArr['_debugCode'] && ($rArr['_debugCode']{0} == 2 || $rArr['_debugCode']{0} == 8)) {
                    $rArr['headerCls'] = 'okMsg';
                } else {
                    $rArr['headerCls'] = 'warningMsg';
                }
            }
            
            $rArr['_showDownloadUrl'] = false;
            
            $res = $this->getDebugPage($rArr);
        }
        
        if (!$res) {
            $res = '<p style="padding-left: 20px">' . tr('Възникна грешка при показване на') . ' ' . $fPath . '</p>';
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя HTML страница с дебъг информация за съответното състояние
     *
     * @param array $state
     *
     * @return ET
     */
    protected function getDebugPage($state)
    {
        require_once(EF_APP_PATH . '/core/NT.class.php');
        require_once(EF_APP_PATH . '/core/ET.class.php');
        require_once(EF_APP_PATH . '/core/Sbf.class.php');
        require_once(EF_APP_PATH . '/core/Html.class.php');
        
        $data = array();
        
        $data['tabContent'] = $data['tabNav'] = '';
        
        // Дъмп
        if (!empty($state['dump'])) {
            $data['tabNav'] .= ' <li><a href="#">Дъмп</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::arrayToHtml($state['dump'], $this->dumpOpenLevels, $this->dumpViewLevels) . '</div>';
        }
        
        // Подготовка на стека
        if (isset($state['_stack'])) {
            $data['tabNav'] .= ' <li><a href="#">Стек</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Debug::getTraceAsHtml($state['_stack']) . '</div>';
        }
        
        if ($state['_code']) {
            $data['code'] = $state['_code'];
        }
        
        // Контекст
        if (isset($state['contex'])) {
            $data['tabNav'] .= ' <li><a href="#">Контекст</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::mixedToHtml($state['contex']) . '</div>';
        }
        
        // Лог
        if ($wpLog = $this->getwpLog($state['_debugTime'], $state['_executionTime'], $state['_cookie'])) {
            $data['tabNav'] .= ' <li><a href="#">Лог</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $wpLog . '</div>';
        }
        
        // Времена
        if ($timers = core_Debug::getTimers((array) $state['_timers'])) {
            $data['tabNav'] .= ' <li><a href="#">Времена</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $timers . '</div>';
        }
        
        $data['httpStatusCode'] = $state['httpStatusCode'];
        $data['httpStatusMsg'] = $state['httpStatusMsg'];
        $data['background'] = $state['background'];
        
        if (isset($state['errTitle']) && $state['errTitle'][0] == '@') {
            $state['errTitle'] = substr($state['errTitle'], 1);
        }
        
        if (isset($state['errTitle'])) {
            $data['errTitle'] = $state['errTitle'];
        }
        
        $lineHtml = core_Debug::getEditLink($state['_breakFile'], $state['_breakLine'], $state['_breakLine']);
        $fileHtml = core_Debug::getEditLink($state['_breakFile']);
        
        if (!$state['headerCls']) {
            $data['headerCls'] = 'errorMsg';
        } else {
            $data['headerCls'] = $state['headerCls'];
        }
        
        if (isset($state['header'])) {
            $data['header'] = $state['header'];
        } else {
            $data['header'] = $state['errType'];
            if ($state['_breakLine'] && !strpos($fileHtml, "eval()'d code")) {
                $data['header'] .= " на линия <i>{$lineHtml}</i>";
            }
            if ($state['_breakFile']) {
                $data['header'] .= " в <i>{$fileHtml}</i>";
            }
        }
        
        // Показваме линковете за работа със сигнала
        if ($state['_debugFileName']) {
            $bName = basename($state['_debugFileName'], '.debug');
            
            if ($bName) {
                $data['errTitle'] .= "<span class = 'errTitleLink'>";
                
                $canList = log_Debug::haveRightFor('list');
                $canReport = log_Debug::haveRightFor('report');
                
                if ($canList || $canReport) {
                    $data['errTitle'] .= ' - ';
                }
                
                if ($canList) {
                    $data['errTitle'] .= ht::createLink(tr('разглеждане'), array('log_Debug', 'default', 'debugFile' => $bName));
                    
                    $dUrl = fileman_Download::getDownloadUrl($state['_debugFileName'], 1, 'path');
                    if ($dUrl) {
                        $data['errTitle'] .= '|' . ht::createLink(tr('сваляне'), $dUrl);
                    }
                }
                
                if ($canReport) {
                    if ($canList) {
                        $data['errTitle'] .= '|';
                    }
                    
                    $data['errTitle'] .= log_Debug::getReportLink($bName, 'сигнал', false);
                }
                
                $data['errTitle'] .= '</span>';
            }
        }
        
        $tpl = new core_NT(getFileContent('core/tpl/Debug.shtml'));
        
        $res = $tpl->render($data);
        
        return $res;
    }
    
    
    /**
     * Връща watch point лога
     *
     * @param array      $tArr
     * @param null|float $dExTime
     * @param null|array $cookie
     *
     * @return string
     */
    private static function getWpLog($tArr = array(), $dExTime = null, $cookie = null)
    {
        $html = '';
        
        if (!empty($tArr)) {
            if ($dExTime) {
                $dExTime = ' - ' . tr('време за изпълнение') . ': ' . $dExTime;
            }
            
            $html .= "\n<div class='debug_block' style=''>" .
                            "\n<div style='background-color:#FFFF33; padding:5px; color:black;'>Debug log{$dExTime}</div><ul><li style='padding:15px 0px 15px 0px;'>";
            
            $html .= core_Html::mixedToHtml($cookie) . '</li>';
            
            foreach ($tArr as $rec) {
                $rec->name = core_ET::escape($rec->name);
                $html .= "\n<li style='padding:15px 0px 15px 0px;border-top:solid 1px #cc3;'>" .  number_format(($rec->start), 5) . ': ' . @htmlentities($rec->name, ENT_QUOTES, 'UTF-8');
            }
            
            $html .= "\n</ul></div>";
        }
        
        return $html;
    }
    
    
    /**
     * Връща името/пътя на дебъг файла
     *
     * @param string   $errCode
     * @param string   $fileName
     * @param bool     $addPath
     * @param bool     $addExt
     * @param NULL|int $cu
     *
     * @return string
     */
    public static function getDebugLogFile($errCode, $fileName = '', $addPath = true, $addExt = true, $cu = null)
    {
        if (!isset($cu)) {
            $cu = (int) @core_Users::getCurrent();
        }
        $cu = str_pad($cu, 5, '0', STR_PAD_LEFT);
        
        list(, $dFileName) = explode('_', $fileName, 2);
        
        $debugFile = $errCode . '_' . $cu . '_' . $dFileName;
        
        $debugPath = self::getDebugFilePath($debugFile, $addExt, $addPath);
        
        return $debugPath;
    }
    
    
    /**
     * Връща пътя до дебъг файла
     *
     * @param string $debugFile
     * @param bool   $addExt
     * @param bool   $addPath
     *
     * @return string
     */
    protected static function getDebugFilePath($debugFile, $addExt = true, $addPath = true)
    {
        $fPath = '';
        if ($addPath) {
            $fPath = rtrim(DEBUG_FATAL_ERRORS_PATH, '/') . '/';
        }
        
        $fPath .= $debugFile;
        
        if ($addExt) {
            $fPath .= '.debug';
        }
        
        return  $fPath;
    }
    
    
    /**
     * Връща файловете в дебъг директорията
     *
     * @param NULL|string $fName
     * @param NULL|int    $before
     * @param NULL|int    $after
     * @param array       $otherFilesFromSameHitArr
     * @param array       $searchArr
     *
     * @return array
     */
    protected static function getDebugFilesArr(&$fName = null, $before = null, $after = null, &$otherFilesFromSameHitArr = array(), $searchArr = array())
    {
        $fArr = array();
        
        if (!defined('DEBUG_FATAL_ERRORS_PATH')) {
            
            return $fArr;
        }
        
        $dir = DEBUG_FATAL_ERRORS_PATH;
        
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);
            
            $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        } catch (ErrorException $e) {
            self::logNotice('Не може да се обходи директорията', $dir);
            
            return $fArr;
        } catch (Throwable  $e) {
            self::logNotice('Не може да се обходи директорията', $dir);
            
            return $fArr;
        }
        
        // Намираме шаблонното име от файла
        $fNameTemplate = null;
        if (isset($fName)) {
            list($fNameTemplate) = explode('.', $fName, 2);
            
            $fNameTemplateArr = explode('_', $fNameTemplate);
            unset($fNameTemplateArr[0]);
            unset($fNameTemplateArr[1]);
            unset($fNameTemplateArr[6]);
            
            foreach ($fNameTemplateArr as $k => $t) {
                if ($t == 'x') {
                    unset($fNameTemplateArr[$k]);
                }
            }
            
            $fNameTemplate = implode('_', $fNameTemplateArr);
        }
        
        $sameFileArr = array();
        
        // Стойността на търсенето
        $search = trim($searchArr['search']);
        
        // Ако се филтрира по потребител
        $searchUser = null;
        if (isset($searchArr['user']) && ($searchArr['user'] != PHP_INT_MAX)) {
            $searchUser = $searchArr['user'];
            $searchUser = '_' . str_pad($searchUser, 5, '0', STR_PAD_LEFT) . '_';
        }
        
        // Ако се филтрира по бързина на изпълнение
        $searchTimeArr = array();
        if (isset($searchArr['execTime'])) {
            $eTimeArr = array();
            if ($searchArr['execTime'] == 'fast') {
                $eTimeArr[] = 0;
            } elseif ($searchArr['execTime'] == 'slow') {
                $eTimeArr[] = 5;
            } elseif ($searchArr['execTime'] == 'verySlow') {
                $eTimeArr[] = 20;
                $eTimeArr[] = 50;
            }
            
            foreach (array(2,8) as $eCode) {
                foreach ($eTimeArr as $eTime) {
                    $eTime = str_pad($eTime, 2, '0', STR_PAD_LEFT);
                    $searchTimeArr[] = $eCode . $eTime . '_';
                }
            }
        }
        
        // Ако се филтрира по размер
        $execSizeFrom = 0;
        $execSizeTo = 0;
        if (isset($searchArr['execSize'])) {
            if ($searchArr['execSize'] == 'small') {
                $execSizeTo = 100000;
            } elseif ($searchArr['execSize'] == 'big') {
                $execSizeFrom = 100000;
                $execSizeTo = 300000;
            } elseif ($searchArr['execSize'] == 'veryBig') {
                $execSizeFrom = 300000;
            }
        }
        
        // Ако се филтрира по време
        if (isset($searchArr['execTimeFrom']) || isset($searchArr['execTimeTo'])) {
            $eTimePeriodArr = array();
            foreach (array('execTimeFrom' => $searchArr['execTimeFrom'], 'execTimeTo' => $searchArr['execTimeTo']) as $execFiledName => $fVal) {
                if (!$fVal) {
                    continue ;
                }
                
                $eArr = explode(':', $fVal);
                
                setIfNot($eArr[1], '00');
                setIfNot($eArr[2], '00');
                $fVal = implode('', $eArr);
                $eTimePeriodArr[$execFiledName] = $fVal;
            }
            
            if (count($eTimePeriodArr) == 2) {
                if ($eTimePeriodArr['execTimeFrom'] > $eTimePeriodArr['execTimeTo']) {
                    $eTimePeriodArr['tmp'] = $eTimePeriodArr['execTimeFrom'];
                    $eTimePeriodArr['execTimeFrom'] = $eTimePeriodArr['execTimeTo'];
                    $eTimePeriodArr['execTimeTo'] = $eTimePeriodArr['tmp'];
                    unset($eTimePeriodArr['tmp']);
                }
            }
        }
        
        // Ако се филтрира по статус
        $statusPattern = '';
        if (isset($searchArr['status'])) {
            $statusPattern = $searchArr['status'];
            
            $statusPattern = str_replace('x', '[0-9]', $statusPattern);
            $statusPattern = "/^({$statusPattern})\_/";
        }
        
        // Намираме всички файлове и им вземаме времето на създаване
        while ($iterator->valid()) {
            try {
                $mTime = null;
                $fileName = $iterator->key();
                $path = $iterator->current()->getPath();
                @$currentDepth = $iterator->getDepth();
                
                if (($currentDepth < 1) && !$iterator->isDir()) {
                    $canShow = true;
                    
                    // Филтрираме по търсене
                    if ($search) {
                        if (strpos($fileName, $search) === false) {
                            $canShow = false;
                        }
                    }
                    
                    // Филтрираме по потребител
                    if ($canShow && isset($searchUser)) {
                        if (strpos($fileName, $searchUser) === false) {
                            $canShow = false;
                        }
                    }
                    
                    // Филтрираме по време на изпълненени
                    if ($canShow && !empty($searchTimeArr)) {
                        $exist = false;
                        foreach ($searchTimeArr as $searchTime) {
                            if (strpos($fileName, $searchTime) === 0) {
                                $exist = true;
                                break;
                            }
                        }
                        
                        if (!$exist) {
                            $canShow = false;
                        }
                    }
                    
                    // Филтрираме по размер
                    if ($canShow && ($execSizeFrom || $execSizeTo)) {
                        if (preg_match("/_(?'execSize'[0-9]+)\.debug$/i", $fileName, $matches)) {
                            if ($matches['execSize']) {
                                if ($execSizeFrom && ($matches['execSize'] < $execSizeFrom)) {
                                    $canShow = false;
                                }
                                
                                if ($canShow && $execSizeTo && ($matches['execSize'] > $execSizeTo)) {
                                    $canShow = false;
                                }
                            }
                        }
                    }
                    
                    // Филтрираме по време на създаване
                    if ($canShow && !empty($eTimePeriodArr)) {
                        if (preg_match("/_(?'h'[0-9]{2})_(?'m'[0-9]{2})_(?'s'[0-9]{2})_/", $fileName, $matches)) {
                            $t = $matches['h'] . $matches['m'] . $matches['s'];
                            
                            if ($eTimePeriodArr['execTimeFrom'] && ($t < $eTimePeriodArr['execTimeFrom'])) {
                                $canShow = false;
                            }
                            
                            if ($canShow && $eTimePeriodArr['execTimeTo'] && ($t > $eTimePeriodArr['execTimeTo'])) {
                                $canShow = false;
                            }
                        }
                    }
                    
                    // Филтрираме по статус
                    if ($canShow && $statusPattern) {
                        if (!preg_match($statusPattern, $fileName)) {
                            $canShow = false;
                        }
                    }
                    
                    // Ако се търси определен файл и отговаря на изискванията - го показваме
                    if ($canShow) {
                        $mTime = @$iterator->current()->getMTime();
                        $fArr[$fileName] = $mTime . '|' . $fileName;
                    }
                    
                    if ($fName) {
                        if (strpos($fileName, $fNameTemplate)) {
                            if (!isset($mTime)) {
                                $mTime = @$iterator->current()->getMTime();
                            }
                            
                            if ($fileName != $fName) {
                                // Ако има друг файл от същия хит
                                $otherFilesFromSameHitArr[$fileName] = $mTime . '|' . $fileName;
                            } else {
                                $sameFileArr[$fileName] = $mTime . '|' . $fileName;
                            }
                        }
                    }
                }
            } catch (Exception  $e) {
                // Не правим нищо
            } catch (Throwable  $e) {
                // Не правим нищо
            }
            
            $iterator->next();
        }
        
        if (($before || $after)) {
            if ($fName && !empty($otherFilesFromSameHitArr)) {
                // Премахваме файловете от същия хит - за да ги добавим по-късно
                $pregPattern = '/^' . preg_quote($fName, '/') . '$/';
                
                $pregPattern = str_replace('x', '.+', $pregPattern);
                
                $foundFName = false;
                
                if ($fArr[$fName]) {
                    $foundFName = true;
                }
                
                foreach ($otherFilesFromSameHitArr as $sameFName => $time) {
                    // Ако в името има неизвестни стойности, намираме файла от системата
                    if (!$foundFName && preg_match($pregPattern, $sameFName)) {
                        $fName = $sameFName;
                        $fArr[$fName] = $time;
                        $foundFName = true;
                        unset($otherFilesFromSameHitArr[$fName]);
                        continue;
                    }
                    
                    unset($fArr[$sameFName]);
                }
            }
        }
        
        $aPos = false;
        if (!empty($fArr)) {
            if (($before || $after)) {
                $limit = $before + $after;
                asort($fArr);
                $slice = true;
                if ($fName) {
                    $aPos = array_search($fName, array_keys($fArr));
                    
                    if ($aPos !== false) {
                        $slice = false;
                        $nArr = $fArr;
                        
                        $fArrCnt = count($fArr);
                        if ($fArrCnt > ($limit)) {
                            if ($fArrCnt > ($aPos + $before)) {
                                $bPos = $aPos - $before;
                            } else {
                                $bPos = $fArrCnt - $after - $before;
                            }
                            
                            $bPos = max(0, $bPos);
                            $nArr = array_slice($fArr, $bPos, $limit);
                        }
                    }
                }
                
                if ($slice) {
                    // Ако няма зададен файл, показваме по ограничение
                    $nArr = array_slice($fArr, -1 * ($limit));
                }
                
                // Добавяме файловете от същия хит
                if ($fName && !empty($otherFilesFromSameHitArr)) {
                    $nArr += $otherFilesFromSameHitArr;
                }
                
                $fArr = $nArr;
            }
        }
        
        if (($aPos === false) && !empty($sameFileArr)) {
            $fArr += $sameFileArr;
        }
        
        if (!empty($fArr)) {
            asort($fArr);
        }
        
        return $fArr;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'list' && $requiredRoles != 'no_one') {
            if (!isDebug()) {
                $requiredRoles = 'no_one';
            }
            
            if ($requiredRoles != 'no_one') {
                if (!haveRole('user', $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($requiredRoles != 'no_one') {
                if (!defined('DEBUG_FATAL_ERRORS_PATH')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'report' && $requiredRoles != 'no_one') {
            if (!defined('DEBUG_FATAL_ERRORS_PATH')) {
                $requiredRoles = 'no_one';
            }
            
            if ($requiredRoles != 'no_one') {
                $supportUrl = help_Setup::get('BGERP_SUPPORT_URL', true);
                if (!$supportUrl || strpos($supportUrl, '//') === false) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Крон метод за изтриване на старите дебъг файлове
     */
    public static function cron_clearOldDebugFiles()
    {
        $me = cls::get(get_called_class());
        
        $fArr = $me->getDebugFilesArr();
        
        if (empty($fArr)) {
            
            return ;
        }
        
        // Колко часа да се пазят грешките в директорията
        $delTimeMapArr = array('def' => 30, '000' => 30, '0' => 100, '150' => 100, '2' => 5, '8' => 5, '5' => 100, '404' => 5);
        
        $nowT = dt::mysql2timestamp();
        
        // Преобразуваме часовете в минути валидност
        $delTimeMapArr = array_map(function ($h) {
            $nowT = dt::mysql2timestamp();
            
            return ($nowT - ($h * 60 * 60));
        }, $delTimeMapArr);
        
        $cnt = 0;
        
        $allCnt = count($fArr);
        
        foreach ($fArr as $fName => $cDate) {
            list($v) = explode('_', $fName, 2);
            
            $delOn = $delTimeMapArr[$v];
            
            if (!$delOn) {
                $delOn = $delTimeMapArr[$v{0}];
            }
            
            if (!$delOn) {
                $delOn = $delTimeMapArr['def'];
            }
            
            list($cDate) = explode('|', $cDate, 2);
            
            if ($delOn < $cDate) {
                continue;
            }
            
            $fPath = $me->getDebugFilePath($fName, false);
            
            $cnt++;
            
            if (!@unlink($fPath)) {
                $me->logWarning("Грешка при изтриване на файла: '{$fPath}'");
            }
        }
        
        if ($cnt) {
            $me->logNotice("Изтрити дебъг файлове {$cnt} от {$allCnt}");
        }
    }
    
    
    /**
     * Начално установяване на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Нагласяване на Крон за изтриване на старите дебъг файлов
        $rec = new stdClass();
        $rec->systemId = 'Clear Old Debug Files';
        $rec->description = 'Изтриване на старите дебъг файлове';
        $rec->controller = $mvc->className;
        $rec->action = 'clearOldDebugFiles';
        $rec->period = 24 * 60;
        $rec->offset = rand(60, 180); // от 1h до 3h
        $rec->delay = 0;
        $rec->timeLimit = 600;
        $res .= core_Cron::addOnce($rec);
    }
}
