<?php


/**
 * Исторически данни за одит и обратна връзка
 *
 *
 * @category  bgerp
 * @package   log
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doclog_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'doclog_Documents';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Хронология на действията с документите";
    
    
    /**
     * 
     */
    public $managers = array(
            'doclog_Documents',
            'doclog_Files',
            'doclog_Used',
            'migrate::moveUsed'
        );

    
    /**
     * Миграция за преместване на използваният на документите в отделен модел
     */
    public static function moveUsed()
    {
        $query = doclog_Documents::getQuery();
        $act = doclog_Documents::ACTION_USED;
        $query->where("#action = '{$act}'");
        
        while ($rec = $query->fetch()) {
            $nRec = new stdClass();
            $nRec->usedContainerId = $rec->containerId;
            
            try {
                foreach ((array)$rec->data->{$act} as $key => $vRec) {
                    if (!$vRec->id) continue;
                    
                    if (!cls::load($vRec->class, TRUE)) continue;
                    
                    $inst = cls::get($vRec->class);
                    $usedCid = $inst->fetchField($vRec->id, 'containerId');
                    
                    if (!$usedCid) continue;
                    
                    $nRec->containerId = $usedCid;
                    
                    $nRec->createdOn = $vRec->lastUsedOn;
                    
                    if ($vRec->author) {
                        $nRec->createdBy = core_Users::fetchField(array("#nick = '[#1#]'", $vRec->author));
                    }
                    
                    doclog_Used::save($nRec, NULL, 'IGNORE');
                    unset($rec->data->{$act}[$key]);
                }
                
                if (!$rec->data->{$act}) {
                    unset($rec->data->{$act});
                }
                
                $arrData = (array) $rec->data;
                if (empty($arrData)) {
                    doclog_Documents::delete($rec->id);
                } else {
                    doclog_Documents::save($rec);
                }
                
                $threadId = doc_Containers::fetchField($nRec->usedContainerId, 'threadId');
                doclog_Documents::removeHistoryFromCache($threadId);
            } catch (Exception $e) {
                
                reportException($e);
                
                continue;
            }
        }
    }
}
