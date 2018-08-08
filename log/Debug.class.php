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
     * Плъгини и MVC класове за предварително зареждане
     */
    public $loadList = 'plg_SystemWrapper';
    
    
    /**
     * Показва дебъг лога
     */
    public function act_Default()
    {
        $this->requireRightFor('list');
        
        $tpl = new ET(tr('|*<div class="headerLine">[#SHOW_DEBUG_INFO#]<!--ET_BEGIN CREATED_DATE--><span style="margin-left: 20px;">[#CREATED_DATE#]</span><!--ET_END CREATED_DATE--><div class="aright"><span style="display: inline-block;margin-right: 10px;"> [#DOWNLOAD_FILE#]</span> [#BEFORE_LINK#][#AFTER_LINK#]</div></div><div class="debugList"><div class="force-overflow">[#LIST_FILE#]</div></div><div class="debugPreview">[#ERR_FILE#]</div>'));
        
        // Подготвяме листовия изглед за избор на дебъг файл
        $data = new stdClass();
        $data->query = $this->getQuery();
        $this->prepareListFilter($data);
        $data->listFilter->layout =  "<form [#FORM_ATTR#] >[#FORM_FIELDS#][#FORM_TOOLBAR#][#FORM_HIDDEN#]</form>\n";

        $data->listFilter->FNC('search', 'varchar', 'caption=Файл, input, silent');
        $data->listFilter->FNC('debugFile', 'varchar', 'caption=Файл, input=hidden, silent');
        
        $data->listFilter->showFields = 'search, debugFile';
        
        $data->listFilter->toolbar->addSbBtn(' ', 'default', 'id=filter', 'ef_icon = img/16/find.png');
        
        $tplList = new ET(tr('|*[#ListFilter#]<!--ET_BEGIN DEBUG_LINK--><div>[#DEBUG_LINK#]</div><!--ET_END DEBUG_LINK-->'));
        
        $data->listFilter->title = 'Дебъг';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->input(null, true);
        
        $tplList->append($this->renderListFilter($data), 'ListFilter');
        
        $otherFilesFromSameHit = array();
        
        $debugFileName = null;
        if ($debugFile = Request::get('debugFile')) {
            Mode::set('stopLoggingDebug', true);
            $debugFileName = $debugFile . '.debug';
        }
        
        $before = 25;
        $after = 25;
        
        $oDebugFileName = $debugFileName;
        
        $fPathStr = $this->getDebugFilePath($debugFileName, false);
        
        if (!file_exists($fPathStr) && strpos($debugFile, 'x') === false) {
            $dFileNameArr = explode('_', $debugFile, 2);
            $debugFileName = 'x_' . $dFileNameArr[1] . '_x.debug';
        }
        
        // Вземаме файловете, които да се показват
        $fArr = $this->getDebugFilesArr($debugFileName, $before, $after, $otherFilesFromSameHit, $data->listFilter->rec->search);
        
        if ($oDebugFileName != $debugFileName) {
            list($debugFile) = explode('.', $debugFileName);
        }
        
        $fArrCnt = count($fArr);
        
        $fLink = '';
        
        if ($fArrCnt > 1) {
            arsort($fArr);
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
                            }
                        }
                    }
                }
            }
            $bLink = ht::createLink(tr(' >> '), $aLinkArr);
            $tpl->replace($bLink, 'AFTER_LINK');
        }
        
        // Показваме всички файлове
        foreach ($fArr as $fNameWithExt => $time) {
            list($fName) = explode('.', $fNameWithExt, 2);
            
            $fPathStr = $this->getDebugFilePath($fName);
            if (DEBUG_FATAL_ERRORS_FILE == $fPathStr) {
                continue;
            }
            
            $cls = 'debugLink';
            
            $linkUrl = array($this, 'Default', 'debugFile' => $fName);
            
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
        
        // Показва съдъражаниете на дебъга, ако е избран файла
        if ($debugFile) {
            $fPath = $this->getDebugFilePath($debugFile);
            if ($fPath) {
                $dUrl = fileman_Download::getDownloadUrl($fPath, 1, 'path');
                
                if ($dUrl) {
                    $tpl->replace(ht::createLink(tr('Сваляне'), $dUrl), 'DOWNLOAD_FILE');
                }
                
                $tpl->replace($this->getDebuFileInfo($fPath, $rArr), 'ERR_FILE');
                $tpl->replace($rArr['_info'], 'SHOW_DEBUG_INFO');
                
                if (is_file($fPath) && is_readable($fPath)) {
                    $date = @filemtime($fPath);
                    $date = dt::timestamp2Mysql($date);
                    $date = dt::mysql2verbal($date, 'smartTime');
                    
                    $tpl->replace($date, 'CREATED_DATE');
                }
            }
            
            Mode::set('wrapper', 'page_Empty');
            $tpl->push('css/debug.css', 'CSS');

            // Плъгин за лайаута
            jquery_Jquery::run( $tpl, 'debugLayout();');

            // Рендираме страницата
            return  $tpl;
        }
        
        // Рендираме страницата
        return  $this->renderWrapping($tpl);
    }
    
    
    /**
     * Показва дебъг страницата
     *
     * @param string $fPath
     * @param array  $rArr
     */
    public function getDebuFileInfo($fPath, &$rArr = array())
    {
        expect($fPath);
        
        // Рендираме лога
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
                
                $rArr['update'] = false;
                
                $bDebugTime = null;
                if ($rArr['debugTime']) {
                    $bDebugTime = core_Debug::$debugTime;
                    core_Debug::$debugTime = $rArr['debugTime'];
                }
                
                $bTimers = null;
                if ($rArr['timers']) {
                    $bTimers = core_Debug::$timers;
                    core_Debug::$timers = (array) $rArr['timers'];
                }
                
                if (!$rArr['contex']) {
                    $rArr['contex'] = $rArr['SERVER'];
                } else {
                    $rArr['contex']->_SERVER = $rArr['SERVER'];
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
                        $rArr['header'] .= ' (' . number_format($rArr['_executionTime'], 1) . 'sec)';
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
                
                Mode::set('debugExecutionTime', $rArr['_executionTime']);
                Mode::set('showDebug', true);
                $res = core_Debug::getDebugPage($rArr);
                Mode::set('showDebug', false);
                Mode::set('debugExecutionTime', null);
                
                if (isset($bDebugTime)) {
                    core_Debug::$debugTime = $bDebugTime;
                }
                
                if (isset($bTimers)) {
                    core_Debug::$timers = $bTimers;
                }
            }
        }
        
        if (!$res) {
            $res = tr('Възникна грешка при показване на') . ' ' . $fPath;
        }
        
        return $res;
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
     * @param NULL|string $search
     *
     * @return array
     */
    protected static function getDebugFilesArr(&$fName = null, $before = null, $after = null, &$otherFilesFromSameHitArr = array(), $search = null)
    {
        $fArr = array();
        
        if (!defined('DEBUG_FATAL_ERRORS_PATH')) {
            
            return $fArr;
        }
        
        $dir = DEBUG_FATAL_ERRORS_PATH;
        
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);
        } catch (ErrorException $e) {
            self::logNotice('Не може да се обходи директорията', $dir);
            
            return $fArr;
        }
        
        $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        
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
        
        // Намираме всички файлове и им вземаме времето на създаване
        while ($iterator->valid()) {
            $mTime = null;
            $fileName = $iterator->key();
            $path = $iterator->current()->getPath();
            
            if (!$iterator->isDir()) {
                $canShow = true;
                
                $search = trim($search);
                
                if ($search) {
                    if (strpos($fileName, $search) === false) {
                        $canShow = false;
                    }
                }
                
                // Ако се търси определен файл и отговаря на изискванията - го показваме
                if ($canShow) {
                    $mTime = $iterator->current()->getMTime();
                    $fArr[$fileName] = $mTime;
                }
                
                if ($fName) {
                    if (strpos($fileName, $fNameTemplate)) {
                        if ($fileName != $fName) {
                            if (!isset($mTime)) {
                                $mTime = $iterator->current()->getMTime();
                            }
                            
                            // Ако има друг файл от същия хит
                            $otherFilesFromSameHitArr[$fileName] = $mTime;
                        }
                    }
                }
            }
            
            $iterator->next();
        }
        
        if (!empty($fArr)) {
            if (($before || $after)) {
                if ($fName) {
                    // Премахваме файловете от същия хит - за да ги добавим по-късно
                    if (!empty($otherFilesFromSameHitArr)) {
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
                    
                    asort($fArr);
                    
                    $aPos = array_search($fName, array_keys($fArr));
                    
                    $fArrCnt = count($fArr);
                    
                    $nArr = $fArr;
                    
                    if ($fArrCnt > ($before + $after)) {
                        if ($fArrCnt > ($aPos + $before)) {
                            $bPos = $aPos - $before;
                        } else {
                            $bPos = $fArrCnt - $after - $before;
                        }
                        
                        $bPos = max(0, $bPos);
                        $nArr = array_slice($fArr, $bPos, $after + $before);
                    }
                    
                    // Добавяме файловете от същия хит
                    if (!empty($otherFilesFromSameHitArr)) {
                        $nArr += $otherFilesFromSameHitArr;
                        asort($nArr);
                    }
                } else {
                    asort($fArr);
                    
                    // Ако няма зададен файл, показваме по ограничение
                    $nArr = array_slice($fArr, -1 * ($before + $after));
                }
                
                $fArr = $nArr;
            }
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
        
        foreach ($fArr as $fName => $cDate) {
            list($v) = explode('_', $fName, 2);
            
            $delOn = $delTimeMapArr[$v];
            
            if (!$delOn) {
                $delOn = $delTimeMapArr[$v{0}];
            }
            
            if (!$delOn) {
                $delOn = $delTimeMapArr['def'];
            }
            
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
            $me->logNotice('Изтрити дебъг файлове: ' . $cnt);
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
