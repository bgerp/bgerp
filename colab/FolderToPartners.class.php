<?php


/**
 * Клас 'colab_FolderToPartners' - Релация между партньори и папки
 *
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class colab_FolderToPartners extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'doc_FolderToPartners';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, doc_Wrapper, plg_RowTools2';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canSendemail = 'officer';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'officer';
    
    
    /**
     * Кой има право да изтрива потребителите, създадени от системата?
     */
    public $canDeletesysdata = 'officer';
    
    
    /**
     * Заглавие
     */
    public $title = 'Споделения с партньори';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Споделяне с партньор';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    public function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key2(mvc=doc_Folders, selectSourceArr=colab_FolderToPartners::getFolderOptions, exludeContractors=' . Request::get('contractorId') . ')', 'caption=Папка,silent,input=hidden,after=contractorId,mandatory');
        $this->FLD('contractorId', 'key2(mvc=core_Users, titleFld=nick, rolesArr=partner,allowEmpty,selectSourceArr=colab_FolderToPartners::getContractorOptions, excludeFolders=' . Request::get('folderId') . ')', 'caption=Потребител,notNull,silent,mandatory');
        
        // Поставяне на уникални индекси
        $this->setDbUnique('folderId,contractorId');
        
        $this->setDbIndex('contractorId');
        $this->setDbIndex('folderId');
    }
    
    
    /**
     * Форсира папката като споделена към потребител-партньор
     *
     * @param int      $folderId
     * @param int|NULL $userId
     *
     * @return int|FALSE
     */
    public static function force($folderId, $userId = null)
    {
        $userId = isset($userId) ? $userId : core_Users::getCurrent('id', false);
        if (empty($userId) || !core_Users::isContractor($userId)) {
            
            return false;
        }
        
        $rec = self::fetchField("#folderId = {$folderId} AND #contractorId = {$userId}");
        if (!$rec) {
            $rec = (object) array('folderId' => $folderId, 'contractorId' => $userId);
            self::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Коя е първата споделена папка на контрагент на партньор
     *
     * @param int|NULL $userId - ид на партньор
     *
     * @return NULL|int $folderId - първата споделена папка
     */
    public static function getLastSharedContragentFolder($cu = null)
    {
        $cu = isset($cu) ? $cu : core_Users::getCurrent('id', false);
        if (empty($cu) || !core_Users::isContractor($cu)) {
            
            return;
        }
        
        // Коя е последно активната му папка на Фирма
        $folderId = self::getLastSharedFolder($cu, 'crm_Companies');
        
        // Ако няма тогава е последно активната му папка на Лице
        if (empty($folderId)) {
            $folderId = self::getLastSharedFolder($cu, 'crm_Persons');
        }
        
        return (!empty($folderId)) ? $folderId : null;
    }
    
    
    /**
     * Последната активна папка на потребителя
     *
     * @param int   $cu    - потребител
     * @param mixed $class - Клас на корицата
     *
     * @return NULL|int $folderId  - Ид на папка
     */
    private static function getLastSharedFolder($cu, $class)
    {
        $Class = cls::get($class);
        $query = self::getQuery();
        $query->where("#contractorId = {$cu}");
        $query->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
        $query->where('#coverClass =' . $Class->getClassId());
        
        $query->show('folderId');
        $fIds = arr::extractValuesFromArray($query->fetchAll(), 'folderId');
        
        if (countR($fIds)) {
            if (countR($fIds) == 1) {
                $folderId = key($fIds);
            } else {
                if (!$folderId) {
                    // Първа е папката в която този потребител последно е писал
                    $cQuery = doc_Containers::getQuery();
                    $cQuery->limit(1);
                    $cQuery->orderBy('#modifiedOn', 'DESC');
                    $cQuery->where("#createdBy = {$cu}");
                    $cQuery->where('#folderId IN (' . implode(',', $fIds) . ')');
                    $cQuery->where("#state != 'rejected'");
                    $cRec = $cQuery->fetch();
                    if ($cRec) {
                        $folderId = $cRec->folderId;
                    }
                }
                
                if (!$folderId) {
                    // След това е папката, в която има последно движение
                    $fQuery = doc_Folders::getQuery();
                    $fQuery->limit(1);
                    $fQuery->orderBy('#last', 'DESC');
                    $fQuery->where('#id IN (' . implode(',', $fIds) . ')');
                    $fQuery->where("#state != 'rejected'");
                    $fRec = $fQuery->fetch();
                    if ($fRec) {
                        $folderId = $fRec->id;
                    }
                }
            }
        }
        
        if (!empty($folderId) && !colab_Threads::haveRightFor('list', (object) array('folderId' => $folderId), $cu)) {
            $folderId = null;
        }
        
        return $folderId;
    }
    
    
    /**
     * Връща опциите за папки
     *
     * @param array          $params
     * @param NULL|int       $limit
     * @param string         $q
     * @param NULL|int|array $onlyIds
     * @param bool           $includeHiddens
     *
     * @return array
     */
    public static function getFolderOptions($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $excludeArr = array();
        if ($params['removeDuplicate'] || $params['exludeContractors']) {
            $query = self::getQuery();
            $query->show('folderId');
            
            if ($params['exludeContractors']) {
                $cArr = explode('|', $params['exludeContractors']);
                $query->orWhereArr('contractorId', $cArr);
            }
            
            while ($rec = $query->fetch()) {
                $excludeArr[$rec->folderId] = $rec->folderId;
            }
        }
        
        if (!empty($excludeArr)) {
            $params['excludeArr'] = $excludeArr;
        }
        
        $resArr = doc_Folders::getSelectArr($params, $limit, $q, $onlyIds, $includeHiddens);
        
        return $resArr;
    }
    
    
    /**
     * Опции за партньори
     */
    public static function getContractorOptions($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $excludeArr = array();
        if ($params['removeDuplicate'] || $params['excludeFolders']) {
            $query = self::getQuery();
            
            $query->show('contractorId');
            
            if ($params['excludeFolders']) {
                $foldersArr = explode('|', $params['excludeFolders']);
                $query->orWhereArr('folderId', $foldersArr);
            }
            
            while ($rec = $query->fetch()) {
                $excludeArr[$rec->contractorId] = $rec->contractorId;
            }
        }
        
        if (!empty($excludeArr)) {
            $params['excludeArr'] = $excludeArr;
        }
        
        $resArr = core_Users::getSelectArr($params, $limit, $q, $onlyIds, $includeHiddens);
        if(countR($resArr) && $params['titleFld'] == 'nick'){
            foreach ($resArr as $userId => $nick){
                $resArr[$userId] = "{$nick} (" . core_Users::fetchField($userId, 'names'). ")";
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * След подготовка на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;
        
        if (isset($form->rec->contractorId)) {
            $form->setReadOnly('contractorId');
            $form->setField('folderId', 'input');
        } else {
            // Ако няма избрана папка форсираме от данните за контрагента от урл-то
            if (empty($form->rec->folderId)) {
                expect($coverClassId = request::get('coverClassId', 'key(mvc=core_Classes)'));
                $coverName = cls::getClassName($coverClassId);
                expect($coverId = request::get('coverId', "key(mvc={$coverName})"));
                
                $form->setDefault('folderId', cls::get($coverClassId)->forceCoverAndFolder($coverId));
            }
        }
        
        if ($form->rec->folderId) {
            $form->fields['contractorId']->type->params['excludeFolders'] = $form->rec->folderId;
        }
        
        if ($form->rec->contractorId) {
            $form->fields['folderId']->type->params['exludeContractors'] = $form->rec->contractorId;
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        if (isset($data->form->rec->folderId)) {
            $Cover = doc_Folders::getCover($data->form->rec->folderId);
            $data->form->title = core_Detail::getEditTitle($Cover->getInstance(), $Cover->that, $mvc->singleTitle, $data->form->rec->id);
        }
    }
    
    
    /**
     * Подготвя данните на партньорите
     */
    public static function preparePartners($data)
    {
        if (!$data->isCurrent) {
            
            return;
        }
        
        $data->rows = array();
        $folderId = $data->masterData->rec->folderId;
        if ($folderId) {
            $query = self::getQuery();
            $query->EXT('lastLogin', 'core_Users', 'externalName=lastLoginTime,externalKey=contractorId');
            $query->where("#folderId = {$folderId}");
            $query->orderBy('lastLogin', 'DESC');
            
            $rejectedArr = array();
            $count = 1;
            while ($rec = $query->fetch()) {
                $uRec = core_Users::fetch($rec->contractorId);
                if ($uRec->state != 'rejected') {
                    $data->rows[$rec->contractorId] = self::recToVerbal($rec);
                    $data->rows[$rec->contractorId]->count = cls::get('type_Int')->toVerbal($count);
                    $count++;
                } else {
                    $rec->RestoreLink = true;
                    $rejectedArr[$rec->contractorId] = self::recToVerbal($rec);
                }
            }
            
            if (!empty($rejectedArr)) {
                foreach ($rejectedArr as $contractorId => $rejectedRow) {
                    $data->rows[$contractorId] = $rejectedRow;
                    $data->rows[$contractorId]->count = cls::get('type_Int')->toVerbal($count);
                    $count++;
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            
            // Само към папка на контрагент
            if ($rec->folderId) {
                $cover = doc_Folders::getCover($rec->folderId);
                if (false && !$cover->haveInterface('crm_ContragentAccRegIntf')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Можем ли да изпратим автоматичен имейл до обекта
        if ($action == 'sendemail' && isset($rec->className, $rec->objectId)) {
            if (!cls::haveInterface('crm_ContragentAccRegIntf', $rec->className)) {
                $requiredRoles = 'no_one';
            } else {
                $objectRec = cls::get($rec->className)->fetch($rec->objectId);
                if (empty($objectRec)) {
                    $requiredRoles = 'no_one';
                } elseif (!doc_Folders::haveRightToObject($objectRec)) {
                    $requiredRoles = 'no_one';
                } else {
                    $emailsFrom = email_Inboxes::getAllowedFromEmailOptions(null);
                    if (!countR($emailsFrom)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $names = core_Users::getVerbal($rec->contractorId, 'names');
        $nick = crm_Profiles::createLink($rec->contractorId);
        $row->names = "{$nick} ({$names}) ";
        $row->lastLogin = core_Users::getVerbal($rec->contractorId, 'lastLoginTime');
        
        if ($rec->RestoreLink) {
            if ($pId = crm_Profiles::getProfileId($rec->contractorId)) {
                if (crm_Profiles::haveRightFor('restore', $pId)) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Възстановяване', array('crm_Profiles', 'restore', $pId, 'ret_url' => true), "warning=Наистина ли желаете да възстановите потребителя|*?,ef_icon = img/16/restore.png,title=Възстановяване на профила на споделен партньор,id=rst{$rec->id}");
                }
            }
        }
    }
    
    
    /**
     * Рендира данните за партньорите
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public static function renderPartners($data, &$tpl)
    {
        $me = cls::get(get_called_class());
        
        $dTpl = getTplFromFile('colab/tpl/PartnerDetail.shtml');
        
        // Подготвяме таблицата с данните извлечени от журнала
        $table = cls::get('core_TableView');
        
        $data->listFields = arr::make('count=№,names=Свързани,lastLogin');
        $me->invoke('BeforeRenderListTable', array($dTpl, &$data));
        $details = $table->get($data->rows, $data->listFields);
        $dTpl->append($details, 'TABLE_PARTNERS');
        
        $folderId = $data->masterData->rec->folderId;
        $btns = new core_ET('');
        
        // Добавяме бутон за свързване на папка с партньор, ако имаме права
        if ($me->haveRightFor('add', (object) array('folderId' => $folderId))) {
            $ht = ht::createLink('', array($me, 'add', 'folderId' => $folderId, 'ret_url' => true, 'coverClassId' => $data->masterMvc->getClassId(), 'coverId' => $data->masterId), false, 'ef_icon=img/16/add.png,title=Свързване на партньор към папката на обекта');
            $dTpl->append($ht, 'addBtn');
        }
        
        if(cls::haveInterface('crm_ContragentAccRegIntf', $data->masterMvc)){
            Request::setProtected(array('companyId', 'className'));
            if (haveRole('admin')) {
                // Добавяме бутон за създаването на нов партньор, визитка и профил
                $ht = ht::createBtn('Нов партньор', array($me, 'createNewContractor', 'companyId' => $data->masterId, 'className' => $data->masterMvc->className, 'ret_url' => true), false, false, 'ef_icon=img/16/star_2.png,title=Създаване на нов партньор');
                $btns->append($ht);
            }
            
            // Ако фирмата има имейли и имаме имейл кутия, слагаме бутон за изпращане на имейл за регистрация
            if ($me->haveRightFor('sendemail', (object) array('className' => $data->masterMvc->className, 'objectId' => $data->masterId))) {
                $ht = ht::createBtn('Покана партньор', array($me, 'sendRegisteredEmail', 'companyId' => $data->masterId, 'className' => $data->masterMvc->className, 'onlyPartner' => 'no', 'ret_url' => true), false, false, 'ef_icon=img/16/email_edit.png,title=Изпращане на имейл за регистрация на партньори към фирмата');
                $btns->append($ht);
                
                if(core_Packs::isInstalled('eshop')){
                    $ht = ht::createBtn('Покана е-шоп', array($me, 'sendRegisteredEmail', 'companyId' => $data->masterId, 'className' => $data->masterMvc->className, 'onlyPartner' => 'yes', 'ret_url' => true), false, false, 'ef_icon=img/16/email_edit.png,title=Изпращане на имейл за регистрация на партньори към фирмата');
                    $btns->append($ht);
                }
            } else {
                $ht = ht::createErrBtn('Покана партньор', 'Фирмата няма имейли, или нямате имейл кутия');
                $btns->append($ht);
                
                if(core_Packs::isInstalled('eshop')){
                    $ht = ht::createErrBtn('Покана е-шоп', 'Фирмата няма имейли, или нямате имейл кутия');
                    $btns->append($ht);
                }
            }
            
            Request::removeProtected(array('companyId', 'className'));
        }
        
        $dTpl->append($btns, 'PARTNER_BTNS');
        $dTpl->removeBlocks();
        
        $tpl->append($dTpl, 'PARTNERS');
    }
    
    
    /**
     * Колбек функция, която се извиква екшъна за създаване на нов контрактор
     */
    public static function callback_Createnewcontractor($data)
    {
        Request::setProtected(array('companyId', 'rand', 'email', 'fromEmail', 'userNames', 'className', 'onlyPartner'));
        
        redirect(array('colab_FolderToPartners', 'Createnewcontractor', 'companyId' => $data['companyId'], 'email' => $data['email'], 'rand' => $data['rand'], 'userNames' => $data['userNames'], 'className' => $data['className'], 'onlyPartner' => $data['onlyPartner'], 'fromEmail' => true));
    }
    
    
    /**
     * Екшън за автоматично изпращане на имейл за регистрация
     *
     * @return core_ET - шаблона на екшъна
     */
    public function act_SendRegisteredEmail()
    {
        Request::setProtected(array('companyId,className'));
        
        $className = request::get('className', 'varchar');
        $className = isset($className) ? $className : 'crm_Companies';
        $objectId = Request::get('companyId', 'int');
        
        $this->requireRightFor('sendemail');
        $this->requireRightFor('sendemail', (object) array('className' => $className, 'objectId' => $objectId));
        $Class = cls::get($className);
        $objectRec = $Class->fetch($objectId);
        
        $contragentName = $Class->getVerbal($objectId, 'name');
        $form = cls::get('core_Form');
        $form->title = 'Изпращане на регистрация на партньори в|* ' . $Class->getFormTitleLink($objectId);
        
        $form->FNC('to', 'email', 'caption=До имейл, width=100%, mandatory, input');
        $form->FNC('from', 'key(mvc=email_Inboxes,select=email)', 'caption=От имейл, width=100%, mandatory, optionsFunc=email_Inboxes::getAllowedFromEmailOptions, input');
        $form->FNC('subject', 'varchar', 'caption=Относно,mandatory,width=100%, input');
        $form->FNC('body', 'richtext(rows=15,bucket=Postings)', 'caption=Съобщение,mandatory, input');
        $form->FNC('onlyPartner', 'enum(no,yes)', 'input=hidden,silent');
        $form->input(null, 'silent');
        
        $emailsArr = type_Emails::toArray($objectRec->email);
        if (!empty($emailsArr)) {
            $emailsArr = array_combine($emailsArr, $emailsArr);
            $emailsArr = array('' => '') + $emailsArr;
        }
        
        $form->setSuggestions('to', $emailsArr);
        $form->setDefault('from', email_Outgoings::getDefaultInboxId());
        core_Lg::push(drdata_Countries::getLang($objectRec->country));
        
        $subject = tr('Създайте нов акаунт в') . ' ' . core_Setup::get('EF_APP_TITLE', true);
        $form->setDefault('subject', $subject);
        $placeHolder = '{{' . tr('линк||link') . '}}';
        
        if($form->rec->onlyPartner == 'yes'){
            $middleMsg = tr('За да се регистрираш, като потребител в нашия онлайн магазин||To have registration as user in our e-shop') . " ";
        } else {
            $middleMsg = tr('За да се регистрираш като служител на фирма||To have registration as a member of company') . ' "[#company#]", ';
            $middleMsg = ($Class instanceof crm_Companies) ? $middleMsg : tr('За да се регистрираш||For registration') . ' ';
        }
        
        $body = new ET(
            tr('Уважаеми потребителю||Dear User') . ",\n\n" .
            $middleMsg .
            tr('моля последвай този||please follow this') .
            " {$placeHolder} - " .
            tr('изтича след 7 дена||it expires after 7 days')
        );
        
        $companyName = str_replace(array('&lt;', '&amp;'), array('<', '&'), $contragentName);
        $body->replace($companyName, 'company');
        
        $footer = cls::get('email_Outgoings')->getFooter($objectRec->country);
        $body = $body->getContent() . "\n\n" . $footer;
        $form->setDefault('body', $body);
        core_Lg::pop();
        $form->input();
        
        // Проверка за грешки
        if ($form->isSubmitted()) {
            if (!strpos($form->rec->body, $placeHolder)) {
                $form->setError('body', 'Липсва плейсхолдера на линка за регистриране|* - ' . $placeHolder);
            }
        }
        
        if ($form->isSubmitted()) {
            $form->rec->companyId = $objectId;
            $form->rec->className = $className;
            $form->rec->placeHolder = $placeHolder;
            $res = $this->sendRegistrationEmail($form->rec);
            $msg = ($res) ? 'Успешно изпратен имейл' : 'Проблем при изпращането на имейл';
            
            cls::get($className)->logInAct('Изпращане на имейл за регистрация на нов партньор', $objectId);
            
            return followRetUrl(null, $msg);
        }
        
        $form->toolbar->addSbBtn('Изпращане', 'save', 'id=save, ef_icon = img/16/lightning.png', 'title=Изпращане на имейл за регистрация на парньори');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Изпраща имейл за регистрация на имейла на контрагента
     */
    private function sendRegistrationEmail($rec)
    {
        $sentFrom = email_Inboxes::fetchField($rec->from, 'email');
        $sentFromName = email_Inboxes::getFromName($rec->from);
        
        // Изпращане на имейл с phpmailer
        $PML = email_Accounts::getPML($sentFrom);
        
        // Ако има дестинационни имейли, ще изпратим имейла до тези които са избрани
        if ($rec->to) {
            $toArr = type_Emails::toArray($rec->to);
            foreach ($toArr as $to) {
                $PML->AddAddress($to);
                if (!core_Users::fetch(array("#email = '[#1#]'", $to))) {
                    $userEmail = $to;
                }
            }
        }
        
        $PML->Encoding = 'quoted-printable';
        $url = core_Forwards::getUrl($this, 'Createnewcontractor', array('companyId' => (int) $rec->companyId, 'email' => $userEmail, 'rand' => str::getRand(), 'userNames' => '', 'className' => $rec->className, 'onlyPartner' => $rec->onlyPartner), 604800);
        $rec->body = str_replace($rec->placeHolder, "[link=${url}]link[/link]", $rec->body);
        
        Mode::push('text', 'plain');
        $bodyAlt = cls::get('type_Richtext')->toVerbal($rec->body);
        Mode::pop('text');
        
        Mode::push('text', 'xhtml');
        $bodyTpl = cls::get('type_Richtext')->toVerbal($rec->body);
        email_Sent::embedSbfImg($PML);
        
        Mode::pop('text');
        
        $PML->AltBody = $bodyAlt;
        $PML->Body = $bodyTpl->getContent();
        $PML->IsHTML(true);
        $PML->Subject = str::utf2ascii($rec->subject);
        $PML->AddCustomHeader("Customer-Origin-Email: {$rec->to}");
        
        $files = fileman_RichTextPlg::getFiles($rec->body);
        
        // Ако има прикачени файлове, добавяме ги
        if (countR($files)) {
            foreach ($files as $fh => $name) {
                $name = fileman_Files::fetchByFh($fh, 'name');
                $path = fileman_Files::fetchByFh($fh, 'path');
                $PML->AddAttachment($path, $name);
            }
        }
        
        // От кой адрес е изпратен
        $PML->SetFrom($sentFrom, $sentFromName);
        
        // Изпращане
        $isSended = $PML->Send();
        
        // Логване на евентуални грешки при изпращането
        if (!$isSended) {
            $error = trim($PML->ErrorInfo);
            if (isset($error)) {
                log_System::add('phpmailer_Instance', 'PML error: ' . $error, null, 'err');
            }
        }
        
        return $isSended;
    }
    
    
    /**
     * Форма за създаване на потребител контрактор, създавайки негов провил, визитка и го свързва към фирмата
     *
     * @return core_ET - шаблона на формата
     */
    public function act_Createnewcontractor()
    {
        Request::setProtected(array('companyId', 'rand', 'fromEmail', 'email', 'userNames', 'className', 'onlyPartner'));
        
        if (!$email = Request::get('email', 'email')) {
            Request::removeProtected(array('email'));
        }
        
        $requestClassName = Request::get('className', 'varchar');
        $className = !empty($requestClassName) ? $requestClassName : 'crm_Companies';
        expect($Class = cls::get($className));
        expect(cls::haveInterface('crm_ContragentAccRegIntf', $Class));
        expect($objectId = Request::get('companyId', 'int'));
        expect($contragentRec = $Class::fetch($objectId));
        $onlyPartner = Request::get('onlyPartner');
        
        $Users = cls::get('core_Users');
        core_Lg::push(drdata_Countries::getLang($contragentRec->country));
        $rand = Request::get('rand');
        $contragentName = $Class->getTitleById($objectId);
        
        // Ако не сме дошли от имейл, трябва потребителя да има достъп до обекта
        $fromEmail = Request::get('fromEmail');
        if (!$fromEmail) {
            requireRole('powerUser');
            expect(doc_Folders::haveRightToObject($contragentRec));
        } else {
            vislog_History::add("Форма за регистрация на партньор в «{$contragentName}»");
        }
        //core_Users::
        $form = $Users->getForm();
        $form->title = "Регистриране на нов акаунт на партньор";
        $form->FLD('contragentName', 'varchar', "caption=Папка,after=passRe");
        $form->setReadOnly('contragentName',  $contragentName);
        $form->setDefault('country', $contragentRec->country);
        
        // Ако има готово име, попълва се
        if ($userNames = Request::get('userNames', 'varchar')) {
            $form->setDefault('names', $userNames);
        }
        
        // Ако имаме хора от crm_Persons, които принадлежат на тази компания, и нямат свързани профили,
        // добавяме поле, преди nick, за избор на такъв човек. Ако той се подаде, данните за потребителя се вземат частично
        // от визитката, а новосъздадения профил се свързва със визитката
        if (haveRole('officer,manager,ceo') && $Class instanceof crm_Companies) {
            $pOpt = array();
            $pQuery = crm_Persons::getQuery();
            while ($pRec = $pQuery->fetch(array("#state = 'active' AND #buzCompanyId = [#1#]", $objectId))) {
                if (!crm_Profiles::fetch("#personId = {$pRec->id}")) {
                    $pOpt[$pRec->id] = $pRec->name;
                }
            }
            if (countR($pOpt)) {
                $form->FNC('personId', 'key(mvc=crm_Persons,allowEmpty)', 'caption=Лице,before=nick,input,silent,removeAndRefreshForm=names|email');
                $form->setOptions('personId', $pOpt);
            }
        }
        
        $form->setDefault('roleRank', core_Roles::fetchByName('partner'));
        $Users->invoke('AfterPrepareEditForm', array((object) array('form' => $form), (object) array('form' => $form)));
        $form->setDefault('state', 'active');
        
        if ($email) {
            $form->setDefault('email', $email);
            $form->setReadonly('email');
        }
        
        $form->setField('roleRank', 'input=none');
        $form->setField('roleOthers', 'caption=Достъп за външен потребител->Роли');
        
        if (!$Users->haveRightFor('add')) {
            $form->setField('rolesInput', 'input=none');
            $form->setField('roleOthers', 'input=none');
            $form->setField('state', 'input=none');
        }
        
        // Задаваме дефолтните роли
        $defaultRole = ($onlyPartner == 'yes') ? 'partner' : 'powerPartner';
        $dRolesArr = array($defaultRole);
        
        $defRoles = array();
        foreach ($dRolesArr as $role) {
            $id = core_Roles::fetchByName($role);
            $defRoles[$id] = $id;
        }
        
        // Добавяне на дефолтни роли
        if($onlyPartner != 'yes'){
            $additionalRoles = colab_Setup::get('DEFAULT_ROLES_FOR_NEW_PARTNER');
            $additionalRoles = keylist::toArray($additionalRoles);
            foreach ($additionalRoles as $roleId) {
                $defRoles[$roleId] = $roleId;
            }
        }
        
        if (!empty($defRoles)) {
            $form->setDefault('roleOthers', $defRoles);
        }
        
        $form->input();
        
        $fields = null;
        if ($form->isSubmitted()) {
            if (!$Users->isUnique($form->rec, $fields)) {
                $loginLink = ht::createLink(tr('тук'), array('core_Users', 'login'));
                $form->setError($fields, 'Има вече такъв потребител. Ако това сте Вие, може да се логнете от|* ' . $loginLink);
            }
        }
        
        if ($form->isSubmitted()) {
            
            // Проверка на имената да са поне две с поне 2 букви
            if (!core_Users::checkNames($form->rec->names)) {
                $form->setError('names', 'Невалидни имена');
            }
            
            $errorMsg = null;
            if (core_Users::isForbiddenNick($form->rec->nick, $errorMsg)) {
                $form->setError('nick', $errorMsg);
            }
        }
        
        $Users->invoke('AfterInputEditForm', array(&$form));
        
        if ($form->isSubmitted() && haveRole('powerUser')) {
            $cRec = clone $form->rec;
            $cRec->name = $form->rec->names;
            $wStr = crm_Persons::getSimilarWarningStr($cRec, $fields);
            
            if ($fields) {
                $fieldsArr = arr::make($fields, true);
                if ($fieldsArr['name']) {
                    $fieldsArr['names'] = 'names';
                    unset($fieldsArr['name']);
                    $fields = implode(',', $fieldsArr);
                }
            }
            
            if ($wStr) {
                $form->setWarning($fields, $wStr);
            }
        }
        
        // След събмит ако всичко е наред създаваме потребител, лице и профил
        if ($form->isSubmitted()) {
            $force = true;
            
            // Ако регистрацията ще е към папка на лице
            if($Class instanceof crm_Persons){
                if(empty(crm_Profiles::fetch("#personId = {$objectId}"))){
                    $personEmails = arr::make(type_Emails::toArray($contragentRec->email), true);
                    if(in_array($form->rec->email, $personEmails)){
                        
                        // И потребителя е със същия имейл и име, то ще му се присвои въпросната папка като лична
                        if(trim($contragentRec->name) == trim($form->rec->names)){
                            $form->rec->personId = $objectId;
                            $force = false;
                        }
                    }
                }
            }
            
            $uId = $Users->save($form->rec);
            
            if ($Class instanceof crm_Companies) {
                $personId = crm_Profiles::fetchField("#userId = {$uId}", 'personId');
                $personRec = crm_Persons::fetch($personId);
                
                // Свързваме лицето към фирмата
                $personRec->buzCompanyId = $objectId;
                $personRec->country = $form->rec->country;
                $personRec->inCharge = $contragentRec->inCharge;
                
                // Имейлът да е бизнес имейла му
                $buzEmailsArr = type_Emails::toArray($personRec->buzEmail);
                $buzEmailsArr[] = $personRec->email;
                $personRec->buzEmail = type_Emails::fromArray($buzEmailsArr);
                $personRec->email = '';
                
                crm_Persons::save($personRec);
            }
            
            $folderId = $Class->forceCoverAndFolder($objectId);
            if($force === true){
                static::save((object) array('contractorId' => $uId, 'folderId' => $folderId));
            }
            
            $Class->logInAct('Регистрация на нов партньор', $objectId);
            vislog_History::add("Регистрация на нов партньор «{$form->rec->nick}» |в|* «{$contragentName}»");
            
            // Изтриваме линка, да не може друг да се регистрира с него
            core_Forwards::deleteUrl($this, 'Createnewcontractor', array('companyId' => (int) $objectId, 'email' => $email, 'rand' => $rand, 'userNames' => $userNames, 'className' => $requestClassName), 604800);
            
            if($fromEmail){
                return new Redirect(array('colab_Threads', 'list', 'folderId' => $folderId), '|Успешно са създадени потребител и визитка на нов партньор');
            } else {
                return followRetUrl(array('colab_Threads', 'list', 'folderId' => $folderId), '|Успешно са създадени потребител и визитка на нов партньор');
            }
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис');
        
        if ($retUrl = getRetUrl()) {
            $form->toolbar->addBtn('Отказ', $retUrl, 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        }
        
        $cu = core_Users::getCurrent('id', false);
        if ($cu && core_Users::haveRole('powerUser', $cu)) {
            $tpl = $this->renderWrapping($form->renderHtml());
        } else {
            $tpl = $form->renderHtml();
        }
        core_Form::preventDoubleSubmission($tpl, $form);
        core_Lg::pop();
        
        $Class->logInAct('Разглеждане на формата за регистрация на нов партньор', $objectId, 'read');
        vislog_History::add("Разглеждане на форма за регистрация на нов партньор");
        
        return $tpl;
    }
    
    
    /**
     * Тестова функция за създаване на потребители
     */
    public function act_CreateTestUsers()
    {
        requireRole('admin');
        requireRole('debug');
        
        expect(core_Packs::isInstalled('colab'));
        
        core_App::setTimeLimit(600);
        
        $nickCnt = Request::get('cnt', 'int');
        setIfNot($nickCnt, 1000);
        if ($nickCnt > 20000) {
            $nickCnt = 20000;
        }
        
        $pass = Request::get('pass');
        setIfNot($pass, '123456');
        
        $nickPref = Request::get('nickPref');
        setIfNot($nickPref, 'test_');
        
        $pRoleId = core_Roles::fetchByName('partner');
        $dRoleId = core_Roles::fetchByName('distributor');
        $aRoleId = core_Roles::fetchByName('agent');
        
        while ($nickCnt--) {
            $uRec = new stdClass();
            $uRec->nick = $nickPref . str::getRand();
            $uRec->names = $uRec->nick . ' Name';
            $uRec->email = $uRec->nick . '@bgerp.com';
            $uRec->state = 'active';
            $uRec->rolesInput = array($pRoleId => $pRoleId);
            
            if (rand(1, 3) == 1) {
                $uRec->rolesInput[$dRoleId] = $dRoleId;
            }
            if (rand(1, 3) == 3) {
                $uRec->rolesInput[$aRoleId] = $aRoleId;
            }
            $uRec->rolesInput = type_Keylist::fromArray($uRec->rolesInput);
            $uRec->ps5Enc = core_Users::encodePwd($pass, $uRec->nick);
            
            core_Users::save($uRec, null, 'IGNORE');
        }
    }
    
    
    /**
     * Линк за регистрация на нов партньор към контрагент
     * 
     * @param mixed $class
     * @param int $objectId
     * @param mixed $retUrl
     * 
     * @return string|array|string
     */
    public static function getRegisterUserUrlByCardNumber($class, $objectId, $retUrl = null)
    {
        $Class = cls::get($class);
        expect(cls::haveInterface('crm_ContragentAccRegIntf', $Class));
        
        $url = array('colab_FolderToPartners', 'Createnewcontractor', 'fromEmail' => true, 'companyId' => $objectId, 'className' => $Class->className, 'rand' => str::getRand());
        if(isset($retUrl)){
            $url['ret_url'] = $retUrl;
        }
        
        if(core_Packs::isInstalled('eshop')){
            if($cartId = eshop_Carts::force(null, null, false)){
                $cartRec = eshop_Carts::fetch($cartId, 'personNames,email');
                $url['userNames'] = $cartRec->personNames;
                $url['email'] = $cartRec->email;
            }
        }
            
        if($Class instanceof crm_Persons){
            $personRec = crm_Persons::fetch($objectId);
            $url['userNames'] = $personRec->name;
            
            $emails = type_Emails::toArray($personRec->email);
            if(array_key_exists(0, $emails)){
                $url['email'] = $emails[0];
            }
        }
        
        Request::setProtected('companyId,rand,className,fromEmail,userNames,email');
        $url = toUrl($url);
        Request::removeProtected('companyId,rand,className,fromEmail,userNames,email');
        
        return $url;
    }
}
