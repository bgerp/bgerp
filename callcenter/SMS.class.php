<?php 


/**
 * Мениджър за изпратените SMS-и
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_SMS extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'SMS';
    
    
    /**
     * 
     */
    var $singleTitle = 'SMS';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
	
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има право да изпраща SMS?
     */
    var $canSend = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools2, plg_Printing, plg_Search, plg_Sorting, plg_Created, plg_RefreshRows, callcenter_ListOperationsPlg, plg_Modified';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'callcenter/tpl/SingleLayoutSMS.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/sms.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'sender, text';
    
    
    /**
     * 
     */
    var $listFields = 'mobileNumData, mobileNum, createdBy=Информация->От, createdOn=Информация->Дата, service=Информация->Услуга, sender=Информация->Титла, receivedTime=Информация->Получено на, text';
    
    
    /**
     * 
     */
    static $cronSysId = 'checkSMSStatus';
    
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('service', 'class(interface=callcenter_SentSMSIntf, select=title)', 'caption=Услуга, mandatory');
        $this->FLD('sender', 'varchar(255)', 'caption=Изпращач');
        $this->FLD('serviceMsg', 'varchar(255)', 'caption=Съобщение от изпращача, input=none');
        $this->FLD('mobileNum', 'drdata_PhoneType', 'caption=Получател->Номер, mandatory, silent');
        $this->FLD('mobileNumData', 'key(mvc=callcenter_Numbers)', 'caption=Получател->Контакт, input=none');
        $this->FLD('text', 'text', 'caption=Текст, mandatory');
        
        $this->FLD('uid', 'varchar', 'caption=Хендлър, input=none');
        $this->FLD('status', 'enum(received=Получен, sended=Изпратен, receiveError=Грешка при получаване, sendError=Грешка при изпращане, pending=Чакащо)', 'caption=Статус, input=none, hint=Статус на съобщението');
        $this->FLD('receivedTime', 'datetime(format=smartTime)', 'caption=Получено на, input=none');
        
        $this->FLD('encoding', 'enum(auto=Автоматично, utf-8=Уникод|* (UTF-8), ascii=Латиница|* (ASCII))', 'caption=Знаци');
        
        $this->setDbUnique('service, uid');
    }

    
    /**
     * Подобна на функцията send
     * Част от параметрите се задават в масива $params
     * Добавен е параметръра $params['sendLockPeriod'] за времето (сек),
     * през което можем да изпращаме само един SMS на даден потребител
     *
     */
    public static function sendSmart($number, $message, $params = array())
    {
        if($lp = $params['sendLockPeriod']) {
            $mobileNum = drdata_PhoneType::getNumberStr($number, 0);
            $now = dt::verbal2mysql();
            if(self::fetch(array("#mobileNum = '[#1#]' AND #createdOn > DATE_SUB('{$now}', INTERVAL {$lp} SECOND)", $mobileNum))) {

                return FALSE;
            }
        }

        return self::send($number, $message, $params['sender'], $params['service'], $params['encoding'], $params['msgForSave']);
    }
    
    
    /**
     * Изпраща подаденото текстово съобщение към услугата за изпращане на SMS
     * 
     * @param string $number
     * @param string|array $message
     * @param string $sender
     * @param integer|string $service
     * @param string $encoding - auto, utf-8 или ascii
     * @param string $msgForSave - текста, който ще се записва в `text` полето вместо $message
     * 
     * @return integer - id на записа
     */
    public static function send($number, $message, $sender = NULL, $service = NULL, $encoding = 'auto', $msgForSave = NULL)
    {
        // Конфигурацията на пакета
        $conf = core_Packs::getConfig('callcenter');
        
        // Ако не е зададена услига
        if (!isset($service)) {
            
            $service = self::getDefaultService();
        }

        // Очакваме да има избрана някаква услуга
        expect($service, 'Не е зададена услуга за изпращане');
        
        // Инстанция на услугата
        $serviceInst = cls::get($service);
        
        // Ако не е избран изпращач, използваме изпращача от услугата
        if (!isset($sender)) {
            $sender = $conf->CALLCENTER_SMS_SENDER;
        }

        // Ако не сме посочили изпращач, пак да работи
        if(!$sender) {
            $params = $serviceInst->getParams();
            $sender = key($params['allowedUserNames']);
        }
        
        $mobileNum = drdata_PhoneType::getNumberStr($number, 0);
        
        expect($mobileNum, 'Липсва номер на получателя');
        
        // Подготвяме текстовата част
        $messageStr = self::prepareMessage($message);
        
        if ($encoding == 'ascii') {
            $messageStr = str::utf2ascii($messageStr);
        }
        
        // Очакваме да може да се изпрати съответния SMS
        expect(self::canSend($messageStr, $sender, $service), 'Не може да се изпрати');
        
        // Изпращаме съобщението към услугата за изпращане на SMS
        $sendStatusArr = $serviceInst->sendSMS($mobileNum, $messageStr, $sender);
        
        $rec = new stdClass();
        $rec->text = $message;
        
        if (isset($msgForSave)) {
            $rec->text = $msgForSave;
        } else if (is_array($message)) {
            $rec->text = $message[0];
        }

        // Вземаме статуса
        $rec->status = $sendStatusArr['sendStatus'];
            
        if ($sendStatusArr['uid']) {
            
            // Вземаме уникалния номер
            $rec->uid = $sendStatusArr['uid'];
        }
            
        // Вземаме последния запис за номера
        $extRecArr = callcenter_Numbers::getRecForNum($mobileNum);
        if ($extRecArr[0]) {
            
            // Вземаме класа и id' то на контрагента
            $rec->mobileNumData = $extRecArr[0]->id;
        }
        
        $rec->mobileNum = $mobileNum;
        $rec->sender = $sender;
        $rec->service = $service;
        $rec->encoding = $encoding;
        $rec->serviceMsg = $sendStatusArr['msg'];
        
        // Записваме
        $savedId = self::save($rec);
        
        return $savedId;
    }
    
    
    /**
     * Връща услугата за изпращане на съобщения
     * 
     * @param boolean $log
     * 
     * @return NULL|integer
     */
    public static function getDefaultService($log = TRUE)
    {
        // Използваме услугата от конфигурацията
        $service = callcenter_Setup::get('SMS_SERVICE');
        
        if(!$service) {
            $servicesOpt = core_Classes::getOptionsByInterface('callcenter_SentSMSIntf');
            if (is_array($servicesOpt) && !empty($servicesOpt)) {
                reset($servicesOpt);
                $service = key($servicesOpt);
            }
        }
        
        if (!$service && $log) {
            self::logWarning('Няма нито една налична услуга за изпращане на SMS');
        }
        
        return $service;
    }
    
    
    /**
     * Проверява дали може да се изпрати даденото съобщение
     * 
     * @param string|array $message
     * @param string $sender
     * @param integer|string $service
     * 
     * @return boolean
     */
    public static function canSend($message, $sender = NULL, $service = NULL)
    {
        $message = self::prepareMessage($message);
        
        // Ако не е зададена услига
        if (!isset($service)) {
            
            // Конфигурацията на пакета
            $conf = core_Packs::getConfig('callcenter');
            
            // Използваме услугата от конфигурацията
            $service = self::getDefaultService();
            
            // Очакваме да има избрана някаква услуга
            expect($service, 'Не е зададена услуга за изпращане');
        }
        
        $serviceInst = cls::get($service);
        
        // Вземаме масива с параметрите
        $params = $serviceInst->getParams();
        
        // Ако не може да се изпраща SMS 
        if ($params['utf8'] != 'yes') {
            
            // Ако не в 7 битов формат
            if (!i18n_Charset::is7Bit($message)) return FALSE;
        }
        
        // Ако е зададен максималната дължина
        if ($params['maxStrLen']) {
            
            // Вземаме дължината на текста
            $textLen = mb_strlen($message);
            
            // Ако текста е над допустимите символа
            if ($params['maxStrLen'] < $textLen) return FALSE;
        }
        
        // Ако са зададени позволени изпращачи
        if ($params['allowedUserNames'] && $sender) {
            
            // Ако не е в позволените
            if (!$params['allowedUserNames'][$sender]) return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Връща статуса от услугата за записа
     * 
     * @param integer $id
     * 
     * @return string
     */
    public static function getServiceStatus($id)
    {
        if (!$id) return ;
        
        $rec = self::fetch($id);
        
        $status = $rec->serviceMsg;
        
        return $status;
    }
    
    
    /**
     * Връща UID за записа
     * 
     * @param integer $id
     * 
     * @return string
     */
    public static function getUid($id)
    {
        if (!$id) return ;
        
        $rec = self::fetch($id);
        
        $uid = $rec->uid;
        
        return $uid;
    }
    
    
    /**
     * Подготвя текстовата част
     * 
     * @param string|array $message
     * 
     * @return string
     */
    public static function prepareMessage($message)
    {
        $messageStr = '';
        
        // Ако е масив
        if (is_array($message)) {
            
            // Текстова част
            $messageStr = $message[0];
            
            unset($message[0]);
            
            // Заместваме плейсхолдерите на останалата част
            foreach ((array)$message as $n => $text) {
                $place = "[#{$n}#]";
                $messageStr = str_replace($place, $text, $messageStr);
            }
        } else {
            
            // Ако е текст, не се правят обработки
            $messageStr = $message;
        }
        
        return $messageStr;
    }
    
    
    /**
     * Обновява състоянието на SMS-ите в логовете
     * Използва се от изпращачите за обновяване на състоянието
     * 
     * @param integer $service
     * @param string $uid
     * @param string $status
     * @param integer $receivedTimestamp
     */
    public static function update_($service, $uid, $status, $receivedTimestamp=NULL)
    {
        // Вземаме записа
        $rec = self::fetch(array("#uid = '[#1#]' AND #service = '[#2#]'", $uid, $service));
        
        // Ако няма такъв запис
        if (!$rec) return ;
        
        // Сменяме статуса и времето на получаване
        $rec->status = $status;
        
        $rec->receivedTime = NULL;
        if (isset($receivedTimestamp)) {
            $rec->receivedTime = dt::timestamp2Mysql($receivedTimestamp);
        }
        
        // Ако няма време на получаване или е подадено време преди създаването му
        if (!isset($rec->receivedTime) || ($rec->createdOn > $rec->receivedTime)) {
            
            // Вземаме текущото време
            $rec->receivedTime = dt::verbal2mysql();
        }
        
        // Ъпдейтваме записите
        self::save($rec, NULL, 'UPDATE');
    }
    
    
    /**
     * 
     * 
     * @param callcenter_SMS $mvc
     * @param object $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Добавяме бутон за изпращане на SMS
        if ($mvc->haveRightFor('send')) {
            $data->toolbar->addBtn('Нов SMS', array($mvc, 'send', 'ret_url' => TRUE), 'ef_icon = img/16/sms_icon.png');
        }
    }
    
    
    /**
     * Екшън за изпращане на SMS
     */
    function act_Send()
    {
        // Трябва да има права за изпращане на SMS
        $this->requireRightFor('send');
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Данните от конфигурацията
        $conf = core_Packs::getConfig('callcenter');
        $service = self::getDefaultService();
        $sender = $conf->CALLCENTER_SMS_SENDER;
        
        // Ако е зададена услуга
        if ($service) {
            
            // Задаваме стойността
            $form->setDefault('service', $service);
            $form->setReadOnly('service');
        }
        
        // Ако е зададен изпращач
        if ($sender) {
            
            // Задаваме изпращача
            $form->setDefault('sender', $sender);
            $form->setReadOnly('sender');
        }
        
        $retUrl = getRetUrl();
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        // Инпутваме формата
        $form->input(NULL, 'silent');
        
        $form->input();
        
        $rec = $form->rec;
        
        // Ако е инпутната формата без грешки
        if ($form->isSubmitted()) {
            
            // Вземаме номера
            $phoneArr = drdata_PhoneType::toArray($rec->mobileNum);
            
            // Ако няма номер
            if (!$phoneArr[0]) {
                
                // Сетваме грешка
                $form->setError('mobileNum', 'Невалиден номер');
            } else {
                
                // Ако номера не е мобилен
                if (!$phoneArr[0]->mobile) {
                    
                    // Сетваме предупреждение
                    $form->setWarning('mobileNum', 'Невалиден GSM номер');
                }
            }
        }
        
        // Ако е инпутната формата без грешки
        if ($form->isSubmitted()) {
            
            // Ако е избрана услуга
            if ($rec->service) {
                
                // Вземаме инстанцията на услугата
                $service = cls::get($rec->service);
                
                // Вземаме масива с параметрите
                $params = $service->getParams();
                
                // Ако не може да се изпраща SMS 
                if ($params['utf8'] != 'yes') {
                    
                    // Ако е зададен енкодинга да е UTF-8
                    if ($rec->encoding == 'utf-8') {
                        
                        // Сетвама грешката
                        $form->setError('encoding', 'Не може да се използва UTF-8');
                    }
                    
                    // Преобразуваме в ASCII
                    $rec->text = str::utf2ascii($rec->text);
                    $rec->sender = str::utf2ascii($rec->sender);
                }
                
                // Ако е зададен ascii
                if ($rec->encoding == 'ascii') {
                    
                    // Преобразуваме в ASCII
                    $rec->text = str::utf2ascii($rec->text);
                    $rec->sender = str::utf2ascii($rec->sender);
                }
                
                // Ако е зададен максималната дължина
                if ($params['maxStrLen']) {
                    
                    // Вземаме дължината на текста
                    $textLen = mb_strlen($rec->text);
                    
                    // Ако текста е над допустимите симвала
                    if ($params['maxStrLen'] < $textLen) {
                        
                        // Сетваме грешка
                        $form->setError('text', "Надвишавате максимално допустимата дължина от|* {$params['maxStrLen']} |символа");
                    }
                }
                
                // Името на изпращача
                $sender = trim($rec->sender);
                
                // Ако са зададени позволени изпращачи
                if ($params['allowedUserNames'] && $sender) {
                    
                    // Ако не е в масива
                    if (!$params['allowedUserNames'][$sender]) {
                        
                        // Стринг с позволените
                        $allowedUsers = implode(', ', $params['allowedUserNames']);
                        
                        // Сетваме грешката
                        $form->setError('text', "Невалиден изпращач. Позволените са|*: {$allowedUsers}");
                    }
                }
            }
        }
        
        // Ако е инпутната формата без грешки
        if ($form->isSubmitted()) {
            
            // Очакваме да може да се изпрати съответния SMS
//            expect(self::canSend($rec->text, $rec->sender, $rec->service));
            
            // Изпращаме SMS-a
            $sendedId = self::send($rec->mobileNum, $rec->text, $rec->sender, $rec->service, $rec->encoding);
            
            self::logWrite('Изпратено съобщение', $sendedId);
            
            $msg = self::getServiceStatus($sendedId);
            
            return new Redirect($retUrl, $msg);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Изпрати', 'save', 'ef_icon = img/16/sms_icon.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Добавяме титлата на формата
        $form->title = "Изпращане на SMS";
        
        // Рендираме изгледа
        return $this->renderWrapping($form->renderHtml());
    }
    
    
	/**
	 * 
	 * 
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Добавяме стил за телефони        
        $row->mobileNum = "<div class='mobile crm-icon'>" . $row->mobileNum . "</div>";
        
        $haveExternalData = FALSE;
        
        // Ако има данни за търсещия
        if ($rec->mobileNumData) {
            
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->mobileNumData);
            
            // Вербалния запис
            $externalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($externalNumRow->contragent) {
                
                // Флаг, за да отбележим, че има данни
                $haveExternalData = TRUE;
                
                // Добавяме данните
                $row->mobileNumData = $externalNumRow->contragent;
            }
        } 
        
        // Ако флага не е дигнат
        if (!$haveExternalData) {
            
            core_RowToolbar::createIfNotExists($row->_rowTools);
            
            // Ако има номер
            if ($rec->mobileNum) {
                
                // Уникално id
                $uniqId = $rec->id . 'mobileTo';
                
                // Добавяме линка
                callcenter_Talks::getTemplateForAddNum($row->_rowTools, $rec->mobileNum, $uniqId);
            }
        }
        
        // Ако има потребител
        if ($rec->createdBy > 0) {
            
            // Създаваме линк към профила му
            $row->createdBy = crm_Profiles::createLink($rec->createdBy);
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Ако не сме в сингъла
            // Добавяме данните към номера
            if(!$fields['-single']) {
                
                // Дива за разстояние
                $div = "<div style='margin-top:5px;'>";
                
                // Добавяме данните към номерата
                $row->mobileNum .=  $div. $row->mobileNumData . "</div>";
            }
        }
        
        // В зависмост от състоянието на съобщенията, опделяме клас за реда в таблицата
        if ($rec->status == 'received') {
            $row->SMSStatusClass .= ' sms-received';
        } elseif ($rec->status == 'sended') {
            $row->SMSStatusClass .= ' sms-sended';
        } elseif ($rec->status == 'receiveError') {
            $row->SMSStatusClass .= ' sms-receiveError';
        } elseif ($rec->status == 'sendError') {
            $row->SMSStatusClass .= ' sms-sendError';
        } elseif ($rec->status == 'pending') {
            $row->SMSStatusClass .= ' sms-pending';
        } 
        
        // Добавяме класа
        $row->ROW_ATTR['class'] = $row->SMSStatusClass;
    }
    
    
    /**
     * 
     * 
     * @param callcenter_SMS $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Променяме полетата, които ще се показват
            $data->listFields = arr::make('mobileNum=Получател, sender=Информация->Титла, service=Информация->Услуга, receivedTime=Информация->Получено на');
        }
    }
    
    
	/**
     * Добавя филтър за изпратените SMS-и
     * 
     * @param callcenter_SMS $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {    
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent,autoFilter');
        
        // Поле за търсене по номера
        $data->listFilter->FNC('number', 'drdata_PhoneType', 'caption=Номер,input,silent, recently');
        
        // Ако имаме статуси
        if ($typeOptions = &$data->listFilter->getField('status')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('status', 'all');
        }
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search, number, usersSearch, status';
        
        $data->listFilter->input('search, usersSearch, number, status', 'silent');
        
    	// Последно получените и изпратени и да са първи
        $data->query->orderBy('#createdOn', 'DESC');
    
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по номера
            if ($number = $filter->number) {
                
                // Премахваме нулите и + от началото на номера
                $number = ltrim($number, '0+');
                
                // Търсим в номерата на изпратените съобщения
                $data->query->where(array("#mobileNum LIKE '%[#1#]'", $number));
            }
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
                $userSearchArr = type_Keylist::toArray($filter->usersSearch);
                
                // Показваме само на потребителя
    			$data->query->orWhereArr('createdBy', $userSearchArr);

    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    
    			    // Показваме и празните резултати 
                    $data->query->orWhere("#createdBy IS NULL");
                }
    		}
    		
    		// Ако филтрираме по статус
            if($filter->status && $filter->status != 'all') {
                
                // Търсим по статус
                $data->query->where("#status = '{$filter->status}'");
            }
        }
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако добавяме
        if ($action == 'send') {
            
            // Ако няма класове, които имплементират интерфейса callcenter_SentSMSIntf
            if (!core_Classes::getInterfaceCount('callcenter_SentSMSIntf')) {
                
                // Никой не може да добавя
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако искаме да отворим сингъла на документа
        if ($rec->id && ($action == 'single') && $userId) {
            
            // Ако нямаме роля CEO
            if (!haveRole('ceo')) {
                
                // Ако сме мениджър
                if (haveRole('manager')) {
                    
                    // Ако създателя е ceo или не сме от същия екип, да може да се вижда сингъла
                    if (haveRole('ceo', $rec->createdBy) || !core_Users::isFromSameTeam($userId, $rec->createdBy)) {
                        
                        // Нямаме права
                        $requiredRoles = 'no_one';
                    }
                } elseif ($rec->createdBy != $userId) {
                    
                    // Ако номера не е на текущия потребител, няма права да разглежда
                    $requiredRoles = 'no_one';
                }
            }
        } 
    }
    
    
    /**
     * Екшън за изпращане на циркулярни съобщения от източник на данни
     */
    function act_blastSms()
    {
        Request::setProtected(array('perSrcClassId', 'perSrcObjectId'));
        
        $objId = Request::get('perSrcObjectId', 'int');
        $clsId = Request::get('perSrcClassId', 'int');
        
        expect($objId && $clsId);
        
        $clsInst = cls::get($clsId);
        
        expect($clsInst->canUsePersonalization($objId));
        
        $objRec = $clsInst->fetch($objId);
        
        expect($objRec);
        
        callcenter_SMS::requireRightFor('send');
        
        $groupChoiceArr = $clsInst->getPersonalizationOptionsForId($objId);
        expect($groupChoiceArr);
        
        foreach ($groupChoiceArr as &$groupName) {
            $groupName = str::mbUcfirst($groupName);
        }
        
        $form = cls::get('core_Form');
        $form->title = "Изпращане на циркулярни SMS-и";
        
        $form->FNC('service', 'class(interface=callcenter_SentSMSIntf, select=title)', 'caption=Услуга, mandatory,silent,input=input');
        $form->FNC('type', 'enum()', 'caption=Към,mandatory,silent,input=input');
        $form->FNC('message', 'text', 'caption=Съобщение,mandatory,silent,input=input');
        
        $form->setOptions('type', $groupChoiceArr);
        
        $service = self::getDefaultService();
        if ($service) {
            $form->setDefault('service', $service);
        }
        
        $form->toolbar->addSbBtn('Изпрати', 'save', 'ef_icon = img/16/sms_icon.png, title = Избор');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->input();
        
        $retUrl = getRetUrl($retUrl);
        
        if (empty($retUrl)) {
            $retUrl = array($clsInst, 'single', $objId);
        }
        
        $pArr = array();
        if ($form->isSubmitted()) {
            $pArr = $clsInst->getPresonalizationArr($form->rec->type);
            if (empty($pArr)) {
                $form->setError('type', 'Няма данни за изпращане');
            }
        }
        
        // Проверка дали всички плейсхолдери са коректни и дали няма да има проблем с дължината
        if ($form->isSubmitted()) {
            $fKey = key($pArr);
            $fArr = $pArr[$fKey];
            
            $msgTpl = new ET($form->rec->message);
            $msgTpl = $msgTpl->placeArray($fArr);
            $placeArr = $msgTpl->getPlaceholders();
            
            if (!empty($placeArr)) {
                $form->setError('message', 'Плейсхолдерите липсват в източника|*: ' . implode(', ', $placeArr));
            }
            
            $serviceInst = cls::get($form->rec->service);
            
            // Вземаме масива с параметрите
            $sParams = $serviceInst->getParams();
            
            $msgContent = $msgTpl->getContent();
            
            if ($sParams['utf8'] != 'yes') {
                $msgContent = str::utf2ascii($msgContent);
            }
            
            if (mb_strlen($msgContent) > $sParams['maxStrLen']) {
                $form->setError('message', 'Дължината на съобщението е над допустимите|* ' . $sParams['maxStrLen'] . ' |символа');
            }
        }
        
        if ($form->isSubmitted()) {
            
            $sendCnt = $errCnt = $notMobileCnt = 0;
            
            $sendArr = array();
            
            $type = $groupChoiceArr[$form->rec->type];
            
            $paramsArr = array();
            if ($sParams['utf8'] != 'yes') {
                $paramsArr['encoding'] = 'ascii';
            }
            
            $paramsArr['service'] = $form->rec->service;
            
            $countryCode = drdata_Setup::get('COUNTRY_PHONE_CODE', TRUE);
            
            foreach ($pArr as $pId => $p) {
                
                // Опитваме се да определим мобилния номер на получателя
                $tel = '';
                if (trim($p['mobile'])) {
                    $tel .= $p['mobile'];
                }
                
                if (trim($p['tel'])) {
                    $tel .= $tel ? ', ' : '';
                    $tel .= $p['tel'];
                }
                
                if (!trim($tel)) continue;
                
                $Phones = cls::get('drdata_Phones');
                $parsedTel = $Phones->parseTel($tel, $countryCode);
                
                if (empty($parsedTel)) continue;
                
                $mobileNum = '';
                foreach ($parsedTel as $t) {
                    if (!$t->mobile) continue;
                    
                    $mobileNum = '00' . $t->countryCode . $t->areaCode . $t->number;
                    
                    break;
                }
                
                // Ако не открием мобилен номер, няма да се праща съобщение
                if (!$mobileNum) {
                    $notMobileCnt++;
                    $clsInst->logNotice("Kъм '{$type}:$pId' не e изпратено циркулярно съобщение, защото няма мобилен номер: ", $objId);
                    continue;
                }
                
                // Защитата, за да не се праща няколко пъти
                if ($sendArr[$mobileNum]) continue;
                
                $sendArr[$mobileNum] = TRUE;
                
                $send = NULL;
                try {
                    
                    // Изпращаме съобщението, като заместваме плейсхолдерите в него
                    // По този начин в изпращанията ще се запише без да са заместени плейсхолдерите
                    $msgArr = $p;
                    $msgArr[0] = $form->rec->message;
                    $send = callcenter_SMS::sendSmart($mobileNum, $msgArr, $paramsArr);
                } catch (ErrorException $e) {
                    $clsInst->logWarning("Грешка при изпращане на циркулярно съобщение към '{$type}:$pId': " . $e->getMessage());
                    $errCnt++;
                }
                
                if ($send) $sendCnt++;
            }
            
            // Генерираме информационни съобщения
            if ($send) {
                $msg = "|Изпратени съобщения|*: {$sendCnt}";
            } else {
                $msg = "|Не е изпратено нито едно съобщение";
            }
            
            if ($errCnt) {
                $msg .= "|*<br>|Грешки при изпращане|*: {$errCnt}";
            }
            
            if ($notMobileCnt) {
                $msg .= "|*<br>|Пропуснати контрагенти поради липса на мобилен номер|*: {$notMobileCnt}";
            }
            
            return new Redirect($retUrl, $msg);
        }
        
        $tpl = $this->renderWrapping($form->renderHtml());
         
        return $tpl;
    }
    
    
	/**
     * Обновява записите за съответния номер
     * 
     * @param string $numStr - Номера
     */
    static function updateRecsForNum($numStr)
    {
        // Вземаме последния запис за съответния номер
        $nRecArr = callcenter_Numbers::getRecForNum($numStr);
        
        // Вземаме всички записи за съответния номер
        $query = self::getQuery();
        $query->where(array("#mobileNum = '[#1#]'", $numStr));
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            $rec->mobileNumData = $nRecArr[0]->id;
            
            // Записваме
            self::save($rec);
        }
    }
    
    
    /**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast
     */
    function cron_checkStatus()
    {
        $period = core_Cron::getPeriod(self::$cronSysId);
        
        $dateFrom = dt::subtractSecs($period);
        
        // Всички съобщения, които не са получени от последното изпращане
        $query = self::getQuery();
        $query->where("#status != 'received'");
        $query->where("#modifiedOn > '{$dateFrom}'");
        
        while ($rec = $query->fetch()) {
            if (!$rec->service) continue;
            
            // Опитваме се да определим статуса на съобщението
            try {
                $inst = cls::get($rec->service);
                $status = $inst->getStatus($rec->uid);
            } catch (core_exception_Expect $e) {
                
                continue;
            }
            
            if (!isset($status)) continue;
            
            $rec->status = $status;
            
            self::save($rec, 'status');
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $conf = core_Packs::getConfig('blast');
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = self::$cronSysId;
        $rec->description = 'Проверява статуса на съобщенията';
        $rec->controller = $mvc->className;
        $rec->action = 'checkStatus';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
    }
}
