<?php


/**
 * Плъгин за харесвания на документите
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_LikesPlg extends core_Plugin
{
    /**
     * Брой харесали потребители, над които няма да се показват имената им
     */
    public static $notifyNickShowCnt = 2;
    
    
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        // Дали мжое да се редактират активирани документи
        setIfNot($mvc->canLike, 'user');
        setIfNot($mvc->canDislike, 'user');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec) {
            if ($action == 'like') {
                if (($rec->state == 'draft') ||
                    ($rec->state == 'rejected') ||
                    !$mvc->haveRightFor('single', $rec->id, $userId) ||
                    doc_Likes::isLiked($rec->containerId, $rec->threadId, $userId)) {
                    $requiredRoles = 'no_one';
                } elseif ($rec && doc_HiddenContainers::isHidden($rec->containerId)) {
                    
                    // Да не може да се харесва, ако докуемнта е скрит
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'dislike') {
                if (($rec->state == 'draft') ||
                    ($rec->state == 'rejected') ||
                    !doc_Likes::isLiked($rec->containerId, $rec->threadId, $userId) ||
                    !$mvc->haveRightFor('single', $rec->id)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     *
     *
     * @param core_Master   $mvc
     * @param core_Redirect $res
     * @param string        $action
     *
     * @return NULL|bool
     */
    public function on_BeforeAction($mvc, &$res, $action)
    {
        $action = strtolower($action);
        
        // Изчиства нотификацията при натискане на линка
        if ($action == 'single' && !(Request::get('Printing')) && !Mode::is('text', 'xhtml')) {
            $id = Request::get('id', 'int');
            
            if ($id) {
                // Изчистваме нотификацията за харесване
                $url = array($mvc, 'single', Request::get('id', 'int'), 'like' => true);
                bgerp_Notifications::clear($url);
                
                $rec = $mvc->fetch($id);
                $url = array('doc_Threads', 'list', 'threadId' => $rec->threadId, 'like' => 1);
                bgerp_Notifications::clear($url);
            }
            
            return ;
        }
        
        if (($action != 'likedocument') && ($action != 'dislikedocument') && ($action != 'showlikes')) {
            
            return ;
        }
        
        $id = Request::get('id', 'int');
        
        $rec = $mvc->fetch($id);
        
        expect($rec);
        
        $ajaxMode = Request::get('ajax_mode');
        
        if ($action == 'likedocument') {
            
            // Харесване
            
            $redirect = true;
            
            $mvc->requireRightFor('like', $rec);
            
            if (doc_Likes::like($rec->containerId, $rec->threadId)) {
                $mvc->logWrite('Харесване', $rec->id);
                
                doc_DocumentCache::cacheInvalidation($rec->containerId);
                
                $mvc->notifyUsersForLike($rec);
            }
        } elseif ($action == 'dislikedocument') {
            
            // Премахване на харесаване
            
            $redirect = true;
            
            $mvc->requireRightFor('dislike', $rec);
            
            if (doc_Likes::dislike($rec->containerId)) {
                $mvc->logWrite('Премахнато харесване', $rec->id);
                
                doc_DocumentCache::cacheInvalidation($rec->containerId);
                
                // Изчистваме нотификацията за харесване
                bgerp_Notifications::setHidden(array($mvc, 'single', $rec->id, 'like' => true));
                bgerp_Notifications::setHidden(array('doc_Threads', 'list', 'threadId' => $rec->threadId, 'like' => 1));
            }
        } elseif ($action == 'showlikes') {
            
            // Показване на екшъните по ajax
            
            expect($ajaxMode);
            
            $redirect = false;
            
            $html = self::getLikesHtml($rec->containerId, $rec->threadId);
            
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => self::getElemId($rec), 'html' => $html, 'replace' => true);
            
            $res = array($resObj);
        }
        
        if ($redirect) {
            if (!$ajaxMode) {
                $res = new Redirect(array($mvc, 'single', $id));
            } else {
                if (doc_Threads::getFirstContainerId($rec->threadId) != $rec->containerId) {
                    // Показваме документа, ако е скрит
                    doc_HiddenContainers::showOrHideDocument($rec->containerId, false, true);
                }
                
                // Връщаме документа
                $res = doc_Containers::getDocumentForAjaxShow($rec->containerId);
            }
        }
        
        return false;
    }
    
    
    /**
     * Нотифицира потребителите, които са харесали документа за нови харесвания
     *
     * @param core_Master  $mvc
     * @param core_ET      $tpl
     * @param stdClass $data
     */
    public static function on_AfterNotifyUsersForLike($mvc, &$res, $rec)
    {
        if (!$rec->containerId) {
            
            return ;
        }
        
        $likedArr = doc_Likes::getLikedArr($rec->containerId, $rec->threadId);
        
        // Ако само текущия потребител е харесал документа
        if (!$likedArr) {
            
            return ;
        }
        
        $createdBy = doc_Containers::fetchField($rec->containerId, 'createdBy');
        
        $currUserId = core_Users::getCurrent();
        
        $documentTitle = $mvc->getTitleForId($rec->id, false);
        
        $likedFromCreator = false;
        
        foreach ($likedArr as $key => $lRec) {
            if ($lRec->createdBy == $currUserId) {
                continue;
            }
            
            if ($createdBy == $lRec->createdBy) {
                $likedFromCreator = true;
            }
            
            if (!$mvc->haveRightFor('single', $rec->id, $lRec->createdBy)) {
                continue;
            }
            
            $cLikedArr = $likedArr;
            unset($cLikedArr[$key]);
            
            $notifyStr = self::prepareNotifyStr($cLikedArr);
            
            if ($notifyStr) {
                $notifyStr .= ' "' . $documentTitle . '"';
                self::notifyUsers($notifyStr, $mvc->className, $lRec->createdBy, $rec);
            }
        }
        
        if (!$likedFromCreator) {
            $notifyStr = self::prepareNotifyStr($likedArr, false);
            if ($notifyStr) {
                $notifyStr .= ' "' . $documentTitle . '"';
                self::notifyUsers($notifyStr, $mvc->className, $createdBy, $rec);
            }
        }
    }
    
    
    /**
     * Праща нотификация на потребителите
     *
     * @param string   $notifyStr
     * @param string   $className
     * @param stdClass $lRec
     * @param stdClass $rec
     */
    protected static function notifyUsers($notifyStr, $className, $userId, $rec)
    {
        // Ако потребителя се е отписал за нови дикументи
        if ($rec->folderId) {
            $sKey = doc_Folders::getSettingsKey($rec->folderId);
            $noNotifyArr = core_Settings::fetchUsers($sKey, 'newDoc', 'no');
            
            if ($noNotifyArr[$userId]) {
                
                return ;
            }
        }
        
        // Ако потребителя се е отписал от нишката
        if ($rec->threadId) {
            $sKey = doc_Threads::getSettingsKey($rec->threadId);
            $noNotifyArr = core_Settings::fetchUsers($sKey, 'notify', 'no');
            
            if ($noNotifyArr[$userId]) {
                
                return ;
            }
        }
        
        $clearUrl = $linkUrl = array($className, 'single', $rec->id);
        $clearUrl['like'] = true;
        
        if ($rec->threadId) {
            $clearUrl = array('doc_Threads', 'list', 'threadId' => $rec->threadId, 'like' => 1);
        }
        
        bgerp_Notifications::add($notifyStr, $clearUrl, $userId, 'normal', $linkUrl);
    }
    
    
    /**
     * Връща стринг за нотификация с потребителите, които са харесали
     *
     * @param array $recArr
     * @param bool  $also
     *
     * @return string
     */
    protected static function prepareNotifyStr($recArr, $also = true)
    {
        $i = 0;
        $otherCnt = 0;
        $notifyStr .= '';
        foreach ((array) $recArr as $rec) {
            $nick = core_Users::getNick($rec->createdBy);
            $nick = type_Nick::normalize($nick);
            
            $i++;
            
            if (self::$notifyNickShowCnt >= $i) {
                $notifyStr .= $notifyStr ? ', ' : '';
                $notifyStr .= $nick;
            } else {
                $otherCnt++;
            }
        }
        
        $notifyEnd = $also ? ' |харесаха също|*' : ' |харесаха|*';
        
        if ($otherCnt) {
            if ($otherCnt == 1) {
                $notifyStr .= ' |и още|* ' . $otherCnt;
            } else {
                $notifyStr .= ' |и|* ' . $otherCnt . ' |други|* ';
            }
            
            $notifyStr .= $notifyEnd;
        } else {
            if ($i == 1) {
                $notifyEnd = $also ? ' |хареса също|*' : ' |хареса|*';
                $notifyStr .= $notifyEnd;
            } elseif ($i > 1) {
                $notifyStr .= $notifyEnd;
            }
        }
        
        return $notifyStr;
    }
    
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     *
     * @param core_Mvc     $mvc
     * @param core_ET      $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        // Ако не сме в xhtml режим
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            
            // Изчистваме нотификацията за харесване
            $url = array($mvc, 'single', $data->rec->id, 'like' => true);
            bgerp_Notifications::clear($url);
        }
    }
    
    
    /**
     * Подготвя лога за харесванията
     *
     * @param int $cid
     * @param int $threadId
     *
     * @return string
     */
    protected static function getLikesHtml($cid, $threadId)
    {
        $html = '';
        
        $likedArr = doc_Likes::getLikedArr($cid, $threadId);
        
        if ($likedArr) {
            foreach ($likedArr as $likeRec) {
                $nick = crm_Profiles::createLink($likeRec->createdBy);
                $likeDate = core_DateTime::mysql2verbal($likeRec->createdOn, 'smartTime');
                
                $html .= "<div class='nowrap'>" . $nick . ' - ' . $likeDate . '</div>';
            }
        }
        
        return $html;
    }
    
    
    /**
     *
     *
     * @param core_Master $invoker
     * @param object      $row
     * @param object      $rec
     * @param array       $fields
     */
    public function on_AfterRecToVerbal(&$mvc, &$row, &$rec, $fields = array())
    {
        if (Mode::is('inlineDocument')) {
            
            return ;
        }
        
        if ($fields && $fields['-single']) {
            if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
                if ($rec->state != 'draft' && $rec->state != 'rejected') {
                    $likesCnt = doc_Likes::getLikesCnt($rec->containerId, $rec->threadId);
                    
                    // Добавяме харесванията и линк
                    $isLikedFromCurrUser = doc_Likes::isLiked($rec->containerId, $rec->threadId, core_Users::getCurrent());
                    
                    $likesLink = '';
                    
                    if ($isLikedFromCurrUser) {
                        $dislikeUrl = array();
                        $attr = array();
                        if ($mvc->haveRightFor('dislike', $rec->id)) {
                            $dislikeUrl = array($mvc, 'dislikeDocument', $rec->id);
                            
                            $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
                            $attr['data-url'] = toUrl($dislikeUrl, 'local');
                        }
                        
                        $attr['ef_icon'] = 'img/16/heart.png';
                        $attr['class'] = 'liked';
                        $attr['title'] = 'Отказ от харесване';
                        
                        $likesLink = ht::createLink('', $dislikeUrl, null, $attr);
                    } else {
                        if (!doc_HiddenContainers::isHidden($rec->containerId) || $likesCnt) {
                            $likeUrl = array();
                            $attr = array();
                            $linkClass = 'disliked';
                            if ($mvc->haveRightFor('like', $rec->id)) {
                                $likeUrl = array($mvc, 'likeDocument', $rec->id);
                                
                                $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
                                $attr['data-url'] = toUrl($likeUrl, 'local');
                            } else {
                                $linkClass .= ' disable';
                            }
                            
                            $attr['ef_icon'] = 'img/16/heart_empty.png';
                            $attr['class'] = $linkClass;
                            $attr['title'] = 'Харесване';
                            
                            $likesLink = ht::createLink('', $likeUrl, null, $attr);
                        }
                    }
                    
                    if ($likesCnt) {
                        $attr = array();
                        $attr['class'] = 'showLikes docSettingsCnt tooltip-arrow-link';
                        $attr['data-url'] = toUrl(array($mvc, 'showLikes', $rec->id), 'local');
                        $attr['data-useHover'] = '1';
                        $attr['data-useCache'] = '1';
                        
                        $likesCntLink = ht::createElement('span', $attr, '<span>' . $likesCnt . '</span>', true);
                        
                        $likesCntLink = '<div class="pluginCountButtonNub"><s></s><i></i></div>' . $likesCntLink;
                        
                        $likesLink = $likesLink . $likesCntLink;
                        
                        $elemId = self::getElemId($rec);
                        
                        $cssClass = 'additionalInfo';
                        if ($likesCnt >= 5) {
                            $cssClass .= ' bottom';
                        }
                        
                        $likesLink .= "<div class='additionalInfo-holder'><span class='{$cssClass}' id='{$elemId}'></span></div>";
                    }
                    
                    if ($likesLink) {
                        $likesLink = '<span>' . $likesLink . '</span>';
                    }
                    
                    $row->DocumentSettingsLeft = new ET($row->DocumentSettingsLeft);
                    $row->DocumentSettingsLeft->append($likesLink);
                }
                
                jquery_Jquery::runAfterAjax($row->DocumentSettingsLeft, 'showTooltip');
                jquery_Jquery::runAfterAjax($row->DocumentSettingsLeft, 'smartCenter');
                jquery_Jquery::runAfterAjax($row->DocumentSettingsLeft, 'setThreadElemWidth');
                jquery_Jquery::runAfterAjax($row->DocumentSettingsLeft, 'getContextMenuFromAjax');
                jquery_Jquery::runAfterAjax($row->DocumentSettingsLeft, 'makeTooltipFromTitle');
            }
        }
    }
    
    
    /**
     * Връща id за html елемент
     *
     * @param stdClass $rec
     *
     * @return string
     */
    protected static function getElemId($rec)
    {
        return 'showLikes_' . $rec->containerId;
    }
}
