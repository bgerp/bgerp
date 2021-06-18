<?php


/**
 * Драйвер за оттегляне на имейли по шаблон
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Оттегляне на имейли
 */
class email_drivers_RejectEmails extends core_BaseClass
{


    /**
     * @var string
     */
    public $oldClassName = 'email_drivers_DeleteEmails';

    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('keepDays', 'time(suggestions=10 дни|15 дни|20 дни|30 дни,uom=days)', 'caption=Оттегляне->След, before=note, mandatory, class=w100 clearSelect');
    }


    /**
     *
     *
     * @param email_Mime  $mime
     * @param stdClass  $serviceRec
     *
     * @return string|null
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {

        return ;
    }


    /**
     * Кронн процес за изтриване на имейли, които отговарят на условията
     *
     * @return string
     */
    public function cron_RejectEmails()
    {
        $rulesInst = cls::get('email_ServiceRules');
        $sQuery = $rulesInst->getQuery();
        $sQuery->orderBy('createdOn', 'DESC');

        $sQuery->where("#state = 'active'");
        $sQuery->where(array("#{$rulesInst->driverClassField} = '[#1#]'", $this->getClassId()));

        $sQuery->XPR('order', 'double', 'RAND()');
        $sQuery->orderBy('#order');

        $fieldArrMap = array();
        $fieldArrMap['body'] = 'textPart';
        $fieldArrMap['email'] = 'fromEml';
        $fieldArrMap['subject'] = 'subject';
        $fieldArrMap['emailTo'] = 'toEml';

        $msg = '';

        while ($sRec = $sQuery->fetch()) {
            $before = dt::subtractSecs($sRec->keepDays);
            $iQuery = email_Incomings::getQuery();
            $iQuery->where(array("#modifiedOn <= '[#1#]'", $before));

            $iQuery->EXT('docCnt', 'doc_Threads', 'externalName=allDocCnt,remoteKey=firstContainerId, externalFieldName=containerId');
            $iQuery->where('#docCnt <= 1');

            $iQuery->orderBy('modifiedOn', 'DESC');

            $iQuery->limit(100);

            $iQuery->show('id, threadId, containerId, subject');

            $iQuery->where("#state != 'rejected'");

            foreach ($fieldArrMap as $serviceFieldName => $recFieldName) {

                if (!strlen(trim($sRec->{$serviceFieldName}, '*')) || !strlen(trim($sRec->{$serviceFieldName}))) {

                    continue ;
                }

                if ($recFieldName == 'textPart') {
                    $iQuery->EXT('searchKeywords', 'doc_Containers', 'externalKey=containerId, externalName=searchKeywords');

                    plg_Search::applySearch('"' . $sRec->{$serviceFieldName} . '"', $iQuery, 'searchKeywords');
                } else {
                    $pattern = str_replace('*', '%', mb_strtolower($sRec->{$serviceFieldName}));

                    $pattern = preg_quote($pattern);

                    $iQuery->where(array("LOWER(#{$recFieldName}) LIKE '%[#1#]%'", $pattern));
                }
            }

            $delCnt = 0;

            while ($iRec = $iQuery->fetch()) {
                if ($iRec->docCnt < 1) {

                    $cQuery = doc_Containers::getQuery();
                    $cQuery->where(array("#threadId = '[#1#]'", $iRec->threadId));

                    $cQuery->limit(2);

                    if ($cQuery->count() != 1) {

                        continue ;
                    }
                }

                doc_Threads::logNotice('Оттеглена нишка със сервизно правило', $iRec->threadId);

                $incRec = email_Incomings::fetch($iRec->id);
                $incRec->brState = $incRec->state;
                $incRec->state = 'rejected';
                email_Incomings::save($incRec, 'state, brState, modifiedOn, modifiedBy');

                $cRec = doc_Containers::fetch($iRec->containerId);
                $cRec->state = 'rejected';
                doc_Containers::save($cRec, 'state, modifiedOn, modifiedBy');

                $tRec = doc_Threads::fetch($iRec->threadId);
                $tRec->state = 'rejected';
                doc_Threads::save($tRec, 'state, modifiedOn, modifiedBy');

                $delCnt++;
            }

            if ($delCnt) {
                $nMsg = "Оттеглени имейли - {$delCnt}";
                $msg .= $msg ? '\n' : '';
                $msg = email_ServiceRules::getLinkToSingle_($sRec->id, null, false, array('ef_icon' => false)) . ": {$nMsg}";

                $rulesInst->logNotice($nMsg, $sRec->id);
            }

            doc_Folders::updateFolderByContent($iRec->folderId);
        }

        return $msg;
    }
}
