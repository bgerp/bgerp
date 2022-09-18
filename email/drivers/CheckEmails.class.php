<?php


/**
 * Драйвер за оп
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Затваряне, оттегляне или изтриване
 */
class email_drivers_CheckEmails extends core_BaseClass
{
    
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('closeAfter', 'time(suggestions=Веднага|10 дни|15 дни|20 дни|30 дни,uom=days)', 'caption=Затваряне на нишката след->Дни, before=note, class=w100 clearSelect');
        $mvc->FLD('rejectAfter', 'time(suggestions=Веднага|10 дни|15 дни|20 дни|30 дни,uom=days)', 'caption=Оттегляне на нишката след->Дни, before=note, class=w100 clearSelect');
        $mvc->FLD('deleteAfter', 'time(suggestions=Веднага|10 дни|15 дни|20 дни|30 дни,uom=days)', 'caption=Изтриване на имейла след->Дни, before=note, class=w100 clearSelect');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriverWeb $Driver
     * @param peripheral_Devices     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (!strlen(trim($form->rec->closeAfter)) && !strlen(trim($form->rec->rejectAfter)) && !strlen(trim($form->rec->deleteAfter))) {
                $form->setError('closeAfter, rejectAfter, deleteAfter', 'Поне едно от полетата трябва да бъде попълнено');
            }
        }
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
        // Ако ще се изтрива веднага
        if (($serviceRec->deleteAfter === 0) || ($serviceRec->deleteAfter === 0.0)) {

            email_ServiceRules::logNotice('Игнориран имейл при сваляне', $serviceRec->id);

            return 'ignored';
        }

        // Ако ще се затваря веднага
        if (($serviceRec->closeAfter === 0) || ($serviceRec->closeAfter === 0.0)) {
            email_ServiceRules::logNotice('Свален имейл без отваряне на нишка', $serviceRec->id);

            return array('closeThread' => 'closeThread');
        }

        // Ако ще се оттегля веднага
        if (($serviceRec->rejectAfter === 0) || ($serviceRec->rejectAfter === 0.0)) {

            email_ServiceRules::logNotice('Свален имейл и оттеглен веднага', $serviceRec->id);

            return array('rejectThread' => 'rejectThread');
        }
    }


    /**
     * Кронн процес за изтриване на имейли, които отговарят на условията
     *
     * @return string
     */
    public function cron_CheckEmails()
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

        $allMsg = '';

        while ($sRec = $sQuery->fetch()) {

            $iQuery = email_Incomings::getQuery();

            $beforeClose = $beforeReject = $beforeDelete = 0;

            $or = false;

            if (isset($sRec->closeAfter)) {
                $beforeClose = dt::subtractSecs($sRec->closeAfter);
                $iQuery->EXT('docThreadState', 'doc_Threads', 'externalName=state,remoteKey=firstContainerId, externalFieldName=containerId');
                $iQuery->where(array("#docThreadState = 'opened' AND #modifiedOn <= '[#1#]'", $beforeClose));
                $or = true;
            }

            if (isset($sRec->rejectAfter)) {
                $beforeReject = dt::subtractSecs($sRec->rejectAfter);
                $iQuery->where(array("#state != 'rejected' AND #modifiedOn <= '[#1#]'", $beforeReject), $or);
                $or = true;
            }

            if (isset($sRec->deleteAfter)) {
                $beforeDelete = dt::subtractSecs($sRec->deleteAfter);
                $iQuery->where(array("#modifiedOn <= '[#1#]'", $beforeDelete), $or);
            }

            $iQuery->EXT('docCnt', 'doc_Threads', 'externalName=allDocCnt,remoteKey=firstContainerId, externalFieldName=containerId');
            $iQuery->where('#docCnt <= 1');

            $iQuery->orderBy('modifiedOn', 'DESC');

            $iQuery->limit(100);

            $iQuery->show('id, threadId, containerId, subject, modifiedOn');

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

            $delCnt = $rejCnt = $closeCnt = 0;

            while ($iRec = $iQuery->fetch()) {
                if ($iRec->docCnt < 1) {

                    $cQuery = doc_Containers::getQuery();
                    $cQuery->where(array("#threadId = '[#1#]'", $iRec->threadId));

                    $cQuery->limit(2);

                    if ($cQuery->count() != 1) {

                        continue ;
                    }
                }

                if ($beforeDelete) {
                    if ($iRec->modifiedOn <= $beforeDelete) {
                        email_Incomings::logNotice('Изтрит имейл със сервизно правило - ' . $iRec->subject, $iRec->id);

                        doc_Threads::delete($iRec->threadId);
                        doc_Containers::delete($iRec->containerId);
                        email_Incomings::delete($iRec->id);

                        $delCnt++;

                        continue ;
                    }
                }

                if ($beforeReject) {
                    if ($iRec->modifiedOn <= $beforeReject) {
                        doc_Threads::logNotice('Оттеглена нишка със сервизно правило - ', $iRec->threadId);

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

                        $rejCnt++;

                        continue ;
                    }
                }

                if ($beforeClose) {
                    if ($iRec->modifiedOn <= $beforeClose) {
                        $tRec = doc_Threads::fetch($iRec->threadId);
                        $tRec->state = 'closed';

                        doc_Threads::save($tRec, 'state, modifiedOn, modifiedBy');

                        doc_Threads::logNotice('Затворена нишка със сервизно правило - ', $iRec->threadId);

                        $closeCnt++;

                        continue ;
                    }
                }
            }

            $nMsg = $msg =  '';

            if ($delCnt) {
                $msg = "Изтрити имейли - {$delCnt}";
                $rulesInst->logNotice($msg, $sRec->id);
                $nMsg = $msg;
            }

            if ($rejCnt) {
                $msg = "Оттеглени имейли - {$rejCnt}";
                $rulesInst->logNotice($msg, $sRec->id);
                $nMsg .= $nMsg ? "<br>" : '';
                $nMsg .= $msg;
            }

            if ($closeCnt) {
                $msg = "Затоворени нишки - {$closeCnt}";
                $rulesInst->logNotice($msg, $sRec->id);
                $nMsg .= $nMsg ? "<br>" : '';
                $nMsg .= $msg;
            }

            if ($nMsg) {
                $msg = email_ServiceRules::getLinkToSingle_($sRec->id, null, false, array('ef_icon' => false)) . ": {$nMsg}";
            }

            $allMsg .= $allMsg ? "<br>" : '';
            $allMsg .= $msg;

            doc_Folders::updateFolderByContent($iRec->folderId);
        }

        return $allMsg;
    }
}
