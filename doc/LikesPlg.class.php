<?php


/**
 * Плъгин за харесвания на документите
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_LikesPlg extends core_Plugin
{
    
    
 	/**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Дали мжое да се редактират активирани документи
        setIfNot($mvc->canLike, 'user');
        setIfNot($mvc->canDislike, 'user');
    }
    
    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if ($mvc->haveRightFor('like', $data->rec->id)) {
            $data->toolbar->addBtn("Харесвам", array($mvc, 'likeDocument', $data->rec->id, 'ret_url' => TRUE),
            "id=btnLike{$data->rec->containerId}, row=2, order=19.4,title=" . tr('Харесване на документа'),  'ef_icon = img/16/redheart.png');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec) {
            if ($action == 'like') {
                if (($rec->state == 'draft') || 
                    ($rec->state == 'rejected') || 
                    !$mvc->haveRightFor('single', $rec->id) || 
                    doc_Likes::isLiked($rec->containerId, $userId)) {
                    
                        $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'dislike') {
                if (($rec->state == 'draft') || 
                    ($rec->state == 'rejected') ||
                    !doc_Likes::isLiked($rec->containerId, $userId) ||
                    !$mvc->haveRightFor('single', $rec->id)) {
                    
                        $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param core_Redirect $res
     * @param string $action
     * 
     * @return NULL|boolean
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if (($action != 'likedocument') && ($action != 'dislikedocument')) return ;
        
        $id = Request::get('id', 'int');
        
        $rec = $mvc->fetch($id);
        
        expect($rec);
        
        if ($action == 'likedocument') {
            
            $mvc->requireRightFor('like', $rec);
            
            if (doc_Likes::like($rec->containerId)) {
                $mvc->logInfo('Харесване', $rec->id);
                status_Messages::newStatus('|Успешно харесахте документа');
                $mvc->touchRec($rec->id);
            }
        } elseif ($action == 'dislikedocument') {
            
            $mvc->requireRightFor('dislike', $rec);
            
            if (doc_Likes::dislike($rec->containerId)) {
                $mvc->logInfo('Премахнато харесване', $rec->id);
                status_Messages::newStatus('|Успешно премахнахте харесването на документа');
                $mvc->touchRec($rec->id);
            }
        }
        
        // Пренасочваме контрола
        if (!$res = getRetUrl()) {
        	$res = array($mvc, 'single', $id);
        }
        
        $res = new Redirect($res);
        
        return FALSE;
    }
    
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        // Ако не сме в xhtml режим
        if (!Mode::is('text', 'xhtml')) {
            
            $cid = $data->rec->containerId;
            
            if (doc_Likes::isLiked($cid)) {
                $tpl->replace(static::renderLikesLog($cid), 'likesLog');
                
                // Добавяме харесванията и линк
                $isLikedFromCurrUser = doc_Likes::isLiked($cid, core_Users::getCurrent());
                $likesTitle = tr('Харесване||Likes');
                if ($isLikedFromCurrUser) {
                    $likeArrUrl = array();
                    if ($mvc->haveRightFor('dislike', $data->rec->id)) {
                        $likeArrUrl = array($mvc, 'dislikeDocument', $data->rec->id, 'ret_url' => TRUE);
                    }
                    
                    $likesLink = ht::createLink($likesTitle, $likeArrUrl, NULL, 'ef_icon=img/16/redheart.png');
                } else {
                    $dislikeArrUrl = array();
                    if ($mvc->haveRightFor('like', $data->rec->id)) {
                        $dislikeArrUrl = array($mvc, 'likeDocument', $data->rec->id, 'ret_url' => TRUE);
                    }
                    
                    $likesLink = ht::createLink($likesTitle, $dislikeArrUrl, NULL, 'ef_icon=img/16/grayheart.png');
                }
                $tpl->replace($likesLink, 'likesLink');
            }
        }
    }
    
    
    /**
     * Рендира лога за харесванията
     * 
     * @param integer $cid
     * 
     * @return string
     */
    protected static function renderLikesLog($cid)
    {
        $likedArr = doc_Likes::getLikedArr($cid);
        
        $htmlArr = array();
        
        foreach ($likedArr as $likeRec) {
            $nick = crm_Profiles::createLink($likeRec->createdBy);
            $likeDate = mb_strtolower(core_DateTime::mysql2verbal($likeRec->createdOn, 'smartTime'));
            $likeDate = " ({$likeDate})";
            
            $htmlArr[] = "<span style='color:black;'>" . $nick . "</span>{$likeDate}";
        }
        
        $htmlStr = implode(', ', $htmlArr);
        
        return $htmlStr;
    }
}
