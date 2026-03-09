<?php
/**
 * Автоматични отговори към имейли
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Mustafa Mustаfov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_AutomaticResponse extends core_Master
{
    /**
     * Активен таб на менюто
     * @var string
     */
    public $menuPage = 'Автоматични отговори';


    /*
    *Кой може да пише?
    */
    public $canWrite = 'powerUser';


    /**
    * Брой записи на страница
    *
    * @var int
    */
    public $listItemsPerPage = 10;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'userId, title, dateFrom, dateTo, folders, sender, titleOfMessage';


     /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'titleOfMessage';


    /** 
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'email/tpl/SingleLayoutRule.shtml';


     /**
     * Заглавие
     */
    public $title = 'Автоматични отговори';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Автоматични отговори';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да изпраща факс?
     */
    public $canSend = 'fax, admin, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'powerUser';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, email_Wrapper';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('userId', 'user', 'caption=Шаблон за получени отговори->Потребител,silent,removeAndRefreshForm=folders');
        $this->FLD('dateFrom', 'date', 'caption=Шаблон за получени отговори->Дата от');
        $this->FLD('dateTo', 'date', 'caption=Шаблон за получени отговори->Дата до');
        $this->FLD('sender', 'varchar(128)', 'caption=Шаблон за получени отговори->Изпращач');
        $this->FLD('receiver', 'emails', 'caption=Шаблон за получени отговори->Получател');
        $this->FLD('folders', 'keylist(mvc=doc_Folders, select=title, allowEmpty)', 'caption=Шаблон за получени отговори->Папка');
        $this->FLD('title', 'varchar(128)', 'caption=Шаблон за получени отговори->Заглавие');
        $this->FLD('content', 'richtext(rows=4)', 'caption=Шаблон за получени отговори->Съдържание');
        $this->FLD('titleOfMessage', 'varchar(128)', 'caption=Автоматични отговори->Заглавие, mandatory');
        $this->FLD('text', 'richtext(rows=4)', 'caption=Автоматични отговори->Съдържание, mandatory');
        $this->FLD('state', 'enum(active=Активна, rejected=Отхвърлена)', 'caption=Автоматични отговори->Състояние');
        $this->FLD('inboxEmail', 'key(mvc=email_inboxes,select=email)', 'caption=Автоматични отговори->Имейл, mandatory');
        if(core_Packs::isInstalled('ai')){
            $this->FLD('aiInstructions', 'text(rows=3)', 'caption=Интелигентен Асистент->Инструкции, input');
        }

    }


    /**
    * 
    * Извиква се след въвеждане на данните във формата
    */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        // Проверяваме само ако формата е изпратена (Submit-ната)
        if ($form->isSubmitted()) {
        
        // Проверка на датите
            if (!empty($rec->dateFrom) && !empty($rec->dateTo)) {
            
                $from = dt::mysql2timestamp($rec->dateFrom);
                $to   = dt::mysql2timestamp($rec->dateTo);

                if ($from > $to) {
                // Това ще спре записа и ще оцвети полето в червено
                $form->setError('dateTo', 'Датата "До" не може да бъде преди "От"!');
                }
            }  
        }
    }
    

    /**
     * След подготовка на формата:
     *  Ако потребителят не е admin, полето `userId` става read-only.
     *  Зареждат се папките (doc_Folders), за които избраният
     *   потребител е отговорник (inCharge).
     *  Те се задават като предложения за полето `folders`.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    { 
        $form = &$data->form;
        $rec = $form->rec;

        if(core_Packs::isInstalled('ai')){
            $form->setField('aiInstructions', 'input');
            $form->setField('aiInstructions', 'after=text');        
        }
        
        //само админ може да избира имейл от който да се изпрати отговора
        if (!haveRole('admin')) {
            $queryEmails = email_Inboxes::getQuery();
            $queryEmails->where("#inCharge = $rec->userId");

            $emails = array();
            while($emailRec = $queryEmails->fetch()){
                $emails[$emailRec->Id] = $emailRec->email;
            }
            $form->setOptions('inboxEmail', $emails);
        }

        //само админ да може да избира потребител
        if (!haveRole('admin')) {
            $form->setReadOnly('userId');
        }

        //папките на потребителя
        $queryFolders = doc_Folders::getQuery();
        $queryFolders->where("#inCharge ={$rec->userId}");

        while($folderRec = $queryFolders->fetch()){
            $folders[$folderRec->id] = $folderRec->title;
        }
        $form->setSuggestions('folders', $folders);
    }


    /**
     * Подменя URL-то да сочи към профила.
     */
    public function on_AfterPrepareRetUrl($mvc, $data)
    {
         // Ако е субмитната формата
        if ($data->form && $data->form->isSubmitted()) {
            // Променяма да сочи към single'a
            $data->retUrl =  array('crm_Profiles', 'single', $data->form->rec->userId);        
        }

        //да може след изтриване да се връща в профила
        if($data->cmd == 'delete'){
            if($id = Request::get('id', 'int')){
                $rec = $mvc->fetch($id);
                $data->retUrl =  array('crm_Profiles', 'single', $rec->userId);
            }
        }   
    }


    /*
    * Подготвяне на шаблон за автоматичните отговори
    */
   public function prepareAutoResponses(&$data)
    {
        $data->rows = $data->recs = array();
        
        // Взимаме всички шаблони
        $query = email_AutomaticResponse::getQuery();
        $query->where("#userId LIKE {$data->masterId}");
        $query->orderBy('createdOn', 'DESC');
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 5));
        $data->Pager->setLimit($query);

        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
    }
    

    /**
     * Рендиране на шаблоните за автоматичен отговор на имейли
     *
     * @param stdClass $data
     * @return core_ET
     */
    public function renderAutoResponses($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $title = tr('Автоматични отговори на имейли');
        $tpl->append($title, 'title');
        
        $data->listFields = arr::make('title=Заглавие, dateFrom=Дата от, dateTo=Дата до, folders=Папка, sender=Изпращач, titleOfMessage=Заглавие на отг.');
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $details = $table->get($data->rows, $data->listFields);

        if (!empty($data->Pager)) {
            $details->append($data->Pager->getHtml());
        }
        $tpl->append($details, 'content');

        return $tpl;
    }


    /**
     * Ограничаване на достъпа:
     *  Само admin може да редактира чужди правила
     *  Потребителят може да редактира само своите
     *  Не може да се добавя правило без userId
     */   
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
         if(($action == 'add' || $action == 'delete' || $action == 'edit' || $action == 'single') && isset($rec)){
            if(!haveRole('admin', $userId)){
                if($rec->userId !== $userId){
                    $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'add' && !$rec->userId){
            $requiredRoles = 'no_one';
        }
    }
    

    /**
     * Cron за автоматичен отговор:
     *  Взема активните правила.
     *  Взема входящите имейли от последната минута (closed).
     *  При съвпадение изпраща автоматичен отговор.
     */    
    public function act_runAutoResponder()
    { 
        //валидиране на дата
        $now = dt::now();
        $today = dt::today();
        $fromDate = $today . " 00:00:00";
        $toDate = $today . " 23:59:59";

        $rulesQuery = self::getQuery();
        $rulesQuery->where("#state = 'active'");
        $rulesQuery->where("(#dateFrom IS NULL OR #dateFrom <= '{$fromDate}') AND (#dateTo IS NULL OR #dateTo >= '{$toDate}')");
      
        //Вземаме активните правила за текущия момент
        $rules = $rulesQuery->fetchAll();
        if (!countR($rules)) return;
        
        //Вземаме всички входящи имейли от последната минута със състояние 'closed'
        $beforeOneMin = dt::addSecs(-3360, $now);
        $incomQuery = email_Incomings::getQuery();
        $incomQuery->where("#createdOn > '{$beforeOneMin}' AND #state = 'closed'");
        $incomings = $incomQuery->fetchAll();
        if (!countR($incomings)) return;
       
        foreach ($incomings as $mail) {
            foreach ($rules as $rule) {
                //Проверяваме за съвападение 
                if ($this->matchesRule($mail, $rule)){
                    //изпращаме имейл
                    $this->createEmail($mail, $rule);
                }
            }
        }       
    }   


    /**
     * Проверява дали входящият имейл отговаря на дадено правило.
     *
     * Логиката включва:
     *  проверка дали имейлът е в допустима папка
     *  проверка на подател, получател, заглавие и съдържание
     *  чрез email_ServiceRules::match()
     *
     * @param stdClass $mail  Обект от email_Incomings
     * @param stdClass $rule  Обект от email_AutomaticResponse
     *
     * @return bool
     */
    public function matchesRule($mail, $rule){
        
        $query = doc_Folders::getQuery();

        if(empty($rule->folders)){
            $query->where("#inCharge = {$rule->userId}");
        } else{
            $query->in("id", keylist::toArray($rule->folders));
        }
        $recs = $query->fetchAll();

        if(!array_key_exists($mail->folderId, $recs)) return false;

        // Сравняваме директно стойността в правилото с входящия мейл
        if (!empty($rule->receiver)) {

            // Ако мейлът, на който е получено съобщението, не е същият като в правилото
            if ($mail->toBox != $rule->receiver) {
                return false;
            }
        }

        $subjectData = [
            'sender'   => $mail->fromEml,
            'receiver' => $mail->toBox,
            'title'    => $mail->subject,
            'content'  => $mail->textPart, 
        ];
        return email_ServiceRules::match($subjectData, $rule);
    }


    /**
    * Създава и изпраща автоматичен отговор към подателя.
    *
    * Методът:
    *  Създава запис в email_Outgoings
    *  Изпраща реален имейл чрез send()
    *
    */
    public function createEmail($mail, $rule){

        $Email = cls::get('email_Outgoings');
            
        // Подготовка на имейла
        $emailRec = (object) array('subject' => "Re: {$mail->subject}" . " {$rule->titleOfMessage}",
                                    'body' => $rule->text,
                                    'folderId' => $mail->folderId,
                                    'originId' => $mail->containerId,
                                    'threadId' => $mail->threadId,
                                    'state' => 'active',
                                    'email' => $mail->fromEml, 
                                    'recipient' => $mail->fromEml,
                                    'aiInstructions' => $rule->aiInstructions);
        
        $aiNotValid = false;
        
        //Логика за интеграция с ИИ (Задейства се при изпращане на формата)
        if (!empty($rule->aiInstructions) && core_Packs::isInstalled('ai')) {

            // Вземане на настройките на промпта от посочения път
            $promptPath = 'ai/prompts/AutoReply.xml';
            $promptRec =$Email->getPrompt($emailRec, $promptPath);

            if ($promptRec) {
                core_Lg::push($promptRec->_lg);

                // Тук се извличат стойностите на полетата, дефинирани в промпта
                $params = $Email->getPromptParams($emailRec, $promptRec->fields);
                core_Lg::pop();
                $params['lang'] = $mail->lg;

                // Определяне каква трудност да се използва
                $otherParams = array('useBase64' => true, 'question' => $rule->aiInstructions);
                foreach (ai_Dialogs::USE_DIFFICULTY_FOR_INSTRUCTION as $difficultyHint => $difficulty){
                     if (strpos($params['userRequirements'], $difficultyHint) !== false) {
                        $otherParams['difficulty'] = $otherParams['difficulty'] == 'high' ? $otherParams['difficulty'] : $difficulty;
                        $params['userRequirements'] = str_replace($difficultyHint, '', $params['userRequirements']);
                    }
                }

                // Извикване на услугата за ИИ
                $res = ai_Dialogs::get($promptRec->id, $params, array(), $otherParams, $emailRec->_dialogId);
                
                if (is_object($res)) {

                    //Замяна на съдържанието (ако ИИ го е върнал)
                    if (!empty($res->body)) {
                        $emailRec->body = $res->body;
                    }

                    //Замяна на заглавието (ако ИИ го е върнал)
                    if (!empty($rule->subject)) {
                        $emailRec->subject = $rule->subject;
                    } 
                } 
                else {
                    $aiNotValid = true;
                }
            }   
        }

        //Записваме записа предварително, за да генерираме ID, което ще ползваме в нотификацията при грешка
        core_Users::forceSystemUser();
        email_Outgoings::save($emailRec);
        email_Outgoings::logWrite('Автоматичен отговор', $emailRec->id);

        //Aко има инструкции и res не е обект - дава грешка
        if($aiNotValid){

            // В случай на празен или невалиден отговор от ИИ
            $msg = 'Проблем при изпращане на автоматичен отговор';

            // Линкът сочи към конкретната чернова, която потребителят да прегледа
            $urlArr = array('email_Outgoings', 'single', $emailRec->id);
            $emailRec->state = 'draft';
            $userId = $rule->userId;
            bgerp_Notifications::add($msg, $urlArr, $userId, 'normal');
            core_Users::cancelSystemUser();
            return;
        }

        core_Users::cancelSystemUser();
        $options = (object) array('encoding' => 'utf-8', 
                                        'boxFrom' => $rule->inboxEmail, 
                                        'emailsTo' => $mail  ->fromEml);

        // Изпращане на имейла
        email_Outgoings::send($emailRec, $options, 'bg');
    }
}