<?php


/**
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ExpandComments extends core_Mvc
{
    public $interfaces = 'doc_ExpandCommentsIntf';
    
    
    
    public $title = 'Коментар';
    
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|integer $userId
     *
     * @return boolean
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Връща състоянието на нишката
     *
     * @param doc_ExpandComments $Driver
     * @param doc_Comments       $mvc
     * @param string|NULL        $res
     * @param integer            $id
     *
     * @return string
     */
    public static function on_AfterGetThreadState($Driver, $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $res = null;
        
        if (core_Packs::isInstalled('colab')) {
            if (core_Users::haveRole('partner', $rec->createdBy)) {
                $res = 'opened';
            } elseif (core_Users::isPowerUser($rec->createdBy) && $mvc->isVisibleForPartners($rec)) {
                $res = 'closed';
            }
        }
    }
    
    
    /**
     *
     * @param doc_ExpandComments $Driver
     * @param core_Mvc           $mvc
     * @param NULL|array         $res
     * @param stdClass           $rec
     * @param array              $otherParams
     */
    public function on_AfterGetDefaultData($Driver, $mvc, &$res, $rec, $otherParams = array())
    {
        $res = arr::make($res);
        
        $haveOrigin = false;
        //Ако имаме originId
        if ($rec->originId) {
            $cid = $rec->originId;
            $haveOrigin = true;
        } elseif ($rec->threadId) {
            // Ако добавяме коментар в нишката
            $cid = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
        }
        if ($cid) {
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($cid);
            $for = tr('|За|*: ');
            if ($haveOrigin) {
                $res['body'] = $for . '#' .$oDoc->getHandle() . "\n" . $rec->body;
            }
        }
    }
}
