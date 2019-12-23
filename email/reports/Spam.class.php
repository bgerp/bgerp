<?php


/**
 * Отчет за имейлите, по техния СПАМ рейтинг
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Имейли » Спам филтър
 */
class email_reports_Spam extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки, after=title');
        $fieldset->FLD('spamFrom', 'double(min=-1000, max=1000, decimals=1, smartRound)', 'caption=СПАМ рейтинг->От, mandatory, after=folders');
        $fieldset->FLD('spamTo', 'double(min=-1000, max=1000, decimals=1, smartRound)', 'caption=СПАМ рейтинг->До, mandatory, after=spamFrom');
        $fieldset->FLD('period', 'time(suggestions=1 седмица|2 седмици|1 месец)', 'caption=Период, mandatory, after=spamTo');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $emailsLimit = 10000;
        if ($data->form->rec->id) {
            $emailsLimit = 0;
        }
        
        $fQuery = self::getFolderQuery('title', null, $emailsLimit);
        
        $fArr = array();
        while ($fRec = $fQuery->fetch()) {
            $fArr[$fRec->id] = doc_Folders::getVerbal($fRec, 'title');
        }
        
        $data->form->setSuggestions('folders', $fArr);
        
        $data->form->setDefault('spamFrom', 0);
        $data->form->setDefault('spamTo', 10);
        $data->form->setDefault('period', 604800); // 1 седмица
    }
    
    
    /**
     * Помощна фунция за връщане на всички папки
     *
     * @param string   $show
     * @param NULL|int $userId
     * @param int      $emailsLimit
     *
     * @return core_Query
     */
    protected static function getFolderQuery($show = 'title', $userId = null, $emailsLimit = 10000)
    {
        if ($userId) {
            core_Users::sudo($userId);
        }
        
        $fQuery = doc_Folders::getQuery();
        $fQuery->where("#state != 'rejected'");
        $fQuery->where(array("#coverClass = '[#1#]'", doc_UnsortedFolders::getClassId()));
        $fQuery->orWhere(array("#coverClass = '[#1#]'", email_Inboxes::getClassId()));
        
        $fQuery->show($show);
        
        doc_Folders::restrictAccess($fQuery, null, false);
        
        if ($userId) {
            core_Users::exitSudo($userId);
        }
        
        // Ако има ограничение за папки с имейли
        if ($emailsLimit) {
            $eQuery = email_Incomings::getQuery();
            
            $eQuery->show('folderId');
            
            $eQuery->limit($emailsLimit);
            
            $eQuery->orderBy('createdOn', 'DESC');
            
            $eArr = array();
            while ($eRec = $eQuery->fetch()) {
                $eArr[$eRec->folderId] = $eRec->folderId;
            }
            
            if (!empty($eArr)) {
                $fQuery->in('id', $eArr);
            }
        }
        
        return $fQuery;
    }
    
    
    /**
     * След изпращане на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->rec->spamFrom == $form->rec->spamTo) {
                $form->setError('spamFrom, spamTo', 'Стойностите не трябва да са еднакви');
            }
            
            if ($form->rec->spamFrom > $form->rec->spamTo) {
                $form->setError('spamFrom, spamTo', "'От' трябва да е по-малко от 'До'");
            }
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $eQuery = email_Incomings::getQuery();
        
        if ($rec->folders) {
            $eQuery->orWhereArr('folderId', type_Keylist::toArray($rec->folders));
        } else {
            
            // Ако не е избрана папка - да са всички достъпни на създателя
            
            $fQuery = $this->getFolderQuery('id', $rec->createdBy, 0);
            $fArr = $fQuery->fetchAll();
            $fArr = array_keys($fArr);
            $eQuery->in('folderId', $fArr);
        }
        
        $eQuery->where(array("#modifiedOn >= '[#1#]'", dt::subtractSecs($rec->period)));
        $eQuery->where(array("#spamScore >= '[#1#]'", $rec->spamFrom));
        $eQuery->where(array("#spamScore <= '[#1#]'", $rec->spamTo));
        
        $eQuery->EXT('docCnt', 'doc_Threads', 'externalName=allDocCnt, remoteKey=firstContainerId, externalFieldName=containerId');
        $eQuery->where('#docCnt <= 1');
        
        $eQuery->orderBy('createdOn', 'DESC');
        
        $resArr = array();
        
        while ($eRec = $eQuery->fetch()) {
            
            // Проверяваме оттеглените документи дали са сами в нишката
            if (($eRec->state == 'rejected') && $eRec->docCnt == 0) {
                $cQuery = doc_Containers::getQuery();
                $cQuery->where(array("#threadId = '[#1#]'", $eRec->threadId));
                $cQuery->limit(2);
                $cQuery->show('threadId');
                if ($cQuery->count() > 1) {
                    continue;
                }
            }
            
            $resArr[$eRec->id] = new stdClass();
            $resArr[$eRec->id]->subject = $eRec->subject;
            $resArr[$eRec->id]->folderId = $eRec->folderId;
            $resArr[$eRec->id]->spamScore = $eRec->spamScore;
            $resArr[$eRec->id]->id = $eRec->id;
            if ($eRec->state == 'rejected') {
                $resArr[$eRec->id]->state = $eRec->state;
                $resArr[$eRec->id]->modifiedBy = $eRec->modifiedBy;
            } else {
                $resArr[$eRec->id]->brState = $eRec->brState;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('subject', 'varchar', 'caption=Документ');
        $fld->FLD('spamScore', 'double(smartRound)', 'caption = Точки');
        $fld->FLD('folderId', 'key(mvc=doc_Folders, select=title)', 'caption=Папка');
        
        if($export === false){
            $fld->FLD('action', 'varchar', 'caption = Действие');
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass;
        
        $row->folderId = doc_Folders::getLink($dRec->folderId);
        
        $row->spamScore = cls::get('type_Double', array('params' => array('decimals' => 1, 'smartRound' => 'smartRound', 'smartCenter' => 'smartCenter')))->toVerbal($dRec->spamScore);
        
        $urlWithAccess = email_Incomings::getUrlWithAccess($dRec->id);
        $attr = array();
        
        if ($dRec->state == 'rejected') {
            $attr['class'] = 'soft-rejected';
        }
        
        setIfNot($attr['ef_icon'], cls::get('email_Incomings')->getIcon($dRec->id));
        $row->subject = ht::createLink(type_Varchar::escape(str::limitLen($dRec->subject, 50)), $urlWithAccess, null, $attr);
        
        $data = $dRec->id . '|' . $rec->id . '|' . core_Users::getCurrent();
        $data = str::addHash($data);
        $urlArr = array($this, 'updateEmailState', 'data' => $data);
        
        if ($pageVar = core_Pager::getPageVar('frame2_Reports', $rec->id)) {
            $pageVal = core_Request::get($pageVar, 'int');
            $urlArr['pageVar'] = $pageVar;
            $urlArr['pageVal'] = $pageVal;
        }
        
        $attr = array('onclick' => 'return startUrlFromDataAttr(this, true);', 'class' => 'button');
        
        if ($dRec->state == 'rejected') {
            $urlArr['action'] = 'restore';
            $attr['ef_icon'] = 'img/16/restore.png';
            $attr['title'] = 'Възстановяване на имейла';
            
            $row->subject .= '<div class="small"> (' . tr('оттеглено от') . ' ' . crm_Profiles::createLink($dRec->modifiedBy) . ')</div>';
            
            $act = 'Възстановяване';
        } else {
            $urlArr['action'] = 'reject';
            $attr['ef_icon'] = 'img/16/reject.png';
            $attr['title'] = 'Оттегляне на имейла';
            
            if ($dRec->brState == 'rejected') {
                $row->subject .= '<div class="small"> (' . tr('възстановено от') . ' ' . crm_Profiles::createLink($dRec->modifiedBy) . ')</div>';
            }
            
            $act = 'Оттегляне';
        }
        
        $attr['data-url'] = toUrl($urlArr, 'local');
        
        $row->action = ht::createBtn($act, array(''), false, false, $attr);
        
        return $row;
    }
    
    
    /**
     * Екшън за обновяване на състоянието на документите
     *
     * @return Redirect
     */
    public function act_UpdateEmailState()
    {
        expect(Request::get('ajax_mode'));
        
        $action = Request::get('action');
        
        $data = Request::get('data');
        
        $data = urldecode($data);
        
        $data = str::checkHash($data);
        
        expect($data);
        
        list($emailId, $repId, $cUserId) = explode('|', $data);
        
        expect($emailId && $repId && cUserId);
        
        $eRec = email_Incomings::fetch($emailId);
        expect($eRec);
        
        expect($cUserId == core_Users::getCurrent());
        
        requireRole('powerUser', $cUserId);
        
        $repRec = frame2_Reports::fetch($repId);
        
        expect($repRec);
        
        frame2_Reports::requireRightFor('single', $repRec);
        
        $resArr = array();
        
        if ($action == 'restore' && cls::get('email_Incomings')->restore($emailId)) {
            doc_Threads::restoreThread($eRec->threadId);
            
            cls::get('email_Incomings')->logInAct('Възстановяване', $eRec);
        } elseif ($action == 'reject' && cls::get('email_Incomings')->reject($emailId)) {
            doc_Threads::rejectThread($eRec->threadId);
            
            cls::get('email_Incomings')->logInAct('Оттегляне', $eRec);
        }
        
        frame2_Reports::refresh($repRec);
        
        if ($pageVar = Request::get('pageVar')) {
            $pageVal = Request::get('pageVal', 'int');
            Request::push(array($pageVar => $pageVal));
        }
        
        $resArr = doc_Containers::getDocumentForAjaxShow($repRec->containerId);
        
        core_App::outputJson($resArr);
    }
}
