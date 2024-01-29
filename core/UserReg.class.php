<?php


/**
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_UserReg extends core_Manager
{

    /**
     * Заглавие на мениджъра
     */
    public $title = 'Регистриране на потребители';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, plg_Search, plg_SystemWrapper';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * @var string
     */
    public $searchFields = 'objStr, phone, email, uId';


    /**
     *
     */
    public $canRegister = 'every_one';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас, input=hidden, silent');
        $this->FLD('objStr', 'varchar(32)', 'caption=Източник, input=hidden, silent');
        $this->FLD('phone', 'drdata_PhoneType(type=tel,unrecognized=warning)', 'caption=GSM->Номер');
        $this->FLD('phoneIsVerified', 'enum(no=Не, yes=Да)', 'caption=GSM->Верифициране, input=none');
        $this->FLD('email', 'email', 'caption=Имейл->Имейл');
        $this->FLD('emailIsVerified', 'enum(no=Не, yes=Да)', 'caption=Имейл->Верифициране, input=none');
        $this->FLD('uId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител, input=none');

        $this->setDbIndex('classId, obj');
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        // Сортиране на записите по num
        $data->query->orderBy('modifiedOn', 'DESC');
    }


    /**
     * Екшън за регистриране на нов потребител
     */
    function act_RegisterUser()
    {
        self::requireRightFor('register');

        Request::setProtected(array('classId', 'objStr'));
        $classId = Request::get('classId');
        $objId = Request::get('objStr');

        expect($objId && $classId);

        $class = cls::getInterface('core_interfaces_RegUserIntf', $classId);

        expect($class->canCreateUser($objId));

        $query = self::getQuery();
        $query->where(array("#objStr = '[#1#]'", $objId));
        $query->where(array("#classId = '[#1#]'", $classId));
        $query->orderBy('modifiedOn', 'DESC');
        $query->limit(1);

        $rec = $query->fetch();

        if ($rec) {

            // Ако има добавен запис, преминаваме към активиране на акаунта
            $retUrl = $this->activateAccount($class, $objId, $rec);

            return new Redirect($retUrl);
        }

        $rec = new stdClass();
        $rec->classId = $classId;
        $rec->objStr = $objId;

        $Users = cls::get('core_Users');

        $form = $Users->getForm();

        $vType = core_Setup::get('REGISTER_USER');

        $mandatoryGsm = '';
        if (($vType == 'sms') || ($vType == 'emailSMS')) {
            $mandatoryGsm = 'mandatory,';
        }

        $form->FNC('phone', 'drdata_PhoneType(type=tel,unrecognized=warning)', "caption=Лице->GSM, {$mandatoryGsm} input, after=email");

        $Users->invoke('AfterPrepareEditForm', array((object) array('form' => $form), (object) array('form' => $form)));

        $form->setField('rolesInput', 'input=none');
        $form->setField('roleRank', 'input=none');
        $form->setField('id', 'input=none');
        $form->setField('state', 'input=none');
        $form->setDefault('state', 'draft');

        $form->title = "Добавяне на потребител";

        $form->input();

        if ($form->isSubmitted()) {
            $fields = null;
            if (!$Users->isUnique($form->rec, $fields)) {
                $loginLink = ht::createLink(tr('тук'), array('core_Users', 'login'));

                $msg = ($fields[0] == 'nick') ? 'Вече има регистриран потребител с този ник. Ако това сте Вие, може да се логнете от': (($fields[0] == 'email') ? 'Вече има регистриран потребител с този имейл. Ако това сте Вие, може да се логнете от' : 'Има вече такъв потребител. Ако това сте Вие, може да се логнете от');
                $msg = "{$msg}|* " . $loginLink;
                $form->setError($fields, $msg);
            }

            // Проверка на имената да са поне две с поне 2 букви
            if (!core_Users::checkNames($form->rec->names)) {
                $form->setError('names', 'Напишете поне две имена разделени с интервал');
            }

            if (core_Users::isForbiddenNick($form->rec->nick, $errorMsg)) {
                $form->setError('nick', $errorMsg);
            }
        }

        $Users->invoke('AfterInputEditForm', array(&$form));

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        if ($form->isSubmitted()) {
            $form->rec->rolesInput = $class->getRoles($objId);
            $form->rec->rolesInput = type_Keylist::fromArray($form->rec->rolesInput);

            $rec->phone = $form->rec->phone;
            $rec->email = $form->rec->email;
            if (!$rec->id) {
                $rec->phoneIsVerified = 'no';
                $rec->emailIsVerified = 'no';
                $rec->nick = $form->rec->nick;
            }

            expect($rec->nick == $form->rec->nick);

            $this->save($rec);

            $this->logNotice('Добавен запис за активиране на лице', $rec->id);

            $uId = $Users->save($form->rec);

            expect($uId);

            // Добавяне userId към записа
            $rec->uId = $uId;
            $this->save($rec, 'uId');

            // Добавяме мобилния към визитката
            $pId = crm_Profiles::getPersonByUser($uId);
            expect($pId);
            $pRec = crm_Persons::fetch($pId);
            $pRec->mobile = $form->rec->phone;
            crm_Persons::save($pRec, 'mobile');

            // Свързваме потребителя към съответната фирма
            $personId = crm_Profiles::fetchField("#userId = {$uId}", 'personId');
            if ($personId) {
                $personRec = crm_Persons::fetch($personId);
                if (!$personRec->buzCompanyId) {
                    // Свързваме лицето към фирмата
                    $personRec->buzCompanyId = $class->getUserBuzCompanyId($objId, $uId);
                    crm_Persons::save($personRec, 'buzCompanyId');

                    $this->logNotice('Лицето е добавено към фирма', $rec->id);
                }

                if ($personRec->buzCompanyId) {
                    $listId = cond_Parameters::getParameter(crm_Companies::getClassId(), $personRec->buzCompanyId, 'employeesList');
                    if ($listId) {
                        price_ListToCustomers::add($listId, crm_Persons::getClassId(), $personId);
                    }

                    $this->logNotice('Към потребителя е добавена ценова политика', $rec->id);
                }
            }

            // Преминаваме към активиране на акаунта
            $retUrl = $this->activateAccount($class, $objId, $rec);

            return new Redirect($retUrl);
        }

        return $form->renderHtml();
    }


    /**
     * Помощна функция, която проверява всички необходими условия за активиране на акаунта и го активира
     *
     * @param stdClass $class
     * @param string $objId
     * @param stdClass $rec
     *
     * @return array
     */
    protected function activateAccount($class, $objId, $rec)
    {
        $rec = $this->fetchRec($rec);

        $retUrl = array("Index");

        $vType = core_Setup::get('REGISTER_USER');

        // Ако трябва да се вериифицира имейл, изпращаме имейл за верификация
        if (($rec->emailIsVerified != 'yes') && (($vType == 'emailSMS') || ($vType == 'email'))) {
            $eDataArr = $class->getEmailData($objId, $rec->id);
            $sendRec = new stdClass();
            $sendRec->subject = $eDataArr['subject'];
            $sendRec->from = $eDataArr['from'];
            $sendRec->body = $eDataArr['body'];
            $sendRec->to = $rec->email;

            $isSend = $this->sendRegistrationEmail($sendRec);

            expect($isSend, $sendRec, $rec);

            return $retUrl;
        }

        if (($rec->phoneIsVerified != 'yes') && (($vType == 'emailSMS') || ($vType == 'sms'))) {
            $sData = $class->getSMSData($objId, $rec->id);

            $isSend = callcenter_SMS::sendSmart($rec->phone, $sData, array('sendLockPeriod' => 86400));

            expect($isSend, $sData, $rec);

            return $retUrl;
        }

        expect($rec->uId, $rec);

        $uRec = core_Users::fetch($rec->uId);
        if ($uRec->state == 'draft') {
            $uRec->state = 'active';
            core_Users::save($uRec, 'state');

            $this->logNotice('Активиране на потребител', $rec->id);
        }

        $retUrl = array("core_Users", "login", 'ret_url' => array("Portal", "Show"));

        $class->afterActivateAccount($objId);

        return $retUrl;
    }


    /**
     * Връща линк за верифиакация на имейл или телефона
     *
     * @param string $objId
     * @param integer $id
     * @param integer $lifetime
     * @param string $type - activateEmail|activateSMS
     * @param array $retUrlArr
     *
     * @return string
     */
    public static function getActivateLink($objId, $id, $lifetime, $type = 'activateEmail', $retUrlArr = array())
    {
        $urlArr = array('objStr' => $objId, 'id' => $id);
        if (!empty($retUrlArr)) {
            $urlArr['ret_url'] = $retUrlArr;
        }

        return core_Forwards::getUrl('core_UserReg', $type, $urlArr, $lifetime);
    }

    /**
     * Верифицира имейла
     *
     * @param array $data
     *
     * @return Redirect
     */
    public static function callback_activateEmail($data)
    {
        $rec = self::fetch(array("#objStr = '[#1#]' AND #id = '[#2#]'", $data['objStr'], $data['id']));
        expect($rec);

        $rec->emailIsVerified = 'yes';

        $sId = self::save($rec, 'emailIsVerified');

        expect($sId);

        expect($data['ret_url']);

        return new Redirect($data['ret_url'], '|Успешно удостоверихте на имейла');
    }


    /**
     * Верифицира телефона
     *
     * @param $data
     *
     * @return Redirect
     */
    public static function callback_activateSMS($data)
    {
        $rec = self::fetch(array("#objStr = '[#1#]' AND #id = '[#2#]'", $data['objStr'], $data['id']));
        expect($rec);

        $rec->phoneIsVerified = 'yes';

        $sId = self::save($rec, 'phoneIsVerified');

        expect($sId);

        expect($data['ret_url']);

        return new Redirect($data['ret_url'], '|Успешно удостоверихте GSM номера си');
    }


    /**
     * Връща масив с линк за създаване на нов потребител
     *
     * @param integer $classId
     * @param string $objStr
     *
     * @return array
     */
    public static function getRegisterUrl($classId, $objStr)
    {
        Request::setProtected(array('classId', 'objStr'));

        return array('core_UserReg', 'registerUser', 'classId' => $classId, 'objStr' => $objStr, 'ret_url' => true);
    }


    /**
     * Изпраща имейл за регистрация на имейла на контрагента
     */
    private function sendRegistrationEmail($rec)
    {
        if (!$rec->from) {
            // Търсим корпоративна сметка, ако има такава
            $corpAcc = email_Accounts::getCorporateAcc();

            if ($corpAcc) {
                $rec->from = $corpAcc->email;
            } else {
                // Ако е зададен имей по подразбиране, използваме него
                $defaultSentBox = email_Setup::get('DEFAULT_SENT_INBOX');
                if ($defaultSentBox && ($iRec = email_Inboxes::fetch($defaultSentBox))) {
                    $rec->from = $iRec->email;
                }
            }
        }

        expect($rec->from && $rec->to);

        $emailId = email_Inboxes::fetchField(array("#email = '[#1#]'", $rec->from), 'id');

        // Изпращане на имейл с phpmailer
        $PML = email_Accounts::getPML($rec->from);

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

        $sentFromName = email_Inboxes::getFromName($emailId);
        // От кой адрес е изпратен
        $PML->SetFrom($rec->from, $sentFromName);

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
        if ($action == 'register') {
            if (core_Setup::get('REGISTER_USER') === 'none') {

                $requiredRoles = 'no_one';
            } elseif (!core_Classes::getInterfaceCount('core_interfaces_RegUserIntf')) {

                $requiredRoles = 'no_one';
            }
        }
    }
}
