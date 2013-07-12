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
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_Created, plg_RefreshRows';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Нов темплейт за показване
     */
//    var $singleLayoutFile = '';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/sms.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'sender, mobileNum, text';
    
    
    /**
     * 
     */
    var $listFields = 'id, service, sender, mobileNum, contragent, text, receivedTime';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
//    var $singleFields = '';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('service', 'class(interface=callcenter_SentSMSIntf, select=title)', 'caption=Услуга, mandatory');
        $this->FLD('sender', 'varchar(255)', 'caption=Изпращач, mandatory');
        $this->FLD('mobileNum', 'drdata_PhoneType', 'caption=Мобилен номер, mandatory');
        $this->FLD('text', 'text', 'caption=Текст, mandatory');
        
        $this->FLD('uid', 'varchar', 'caption=Хендлър, input=none');
        $this->FLD('status', 'enum(received=Получен, sended=Изпратен, receiveError=Грешка при получаване, sendError=Грешка при изпращане)', 'caption=Статус, input=none, hint=Статус на съобщението');
        $this->FLD('receivedTime', 'datetime', 'caption=Получено на, input=none');
        $this->FLD('classId', 'key(mvc=core_Classes, select=name)', 'caption=Визитка->Клас, input=none');
        $this->FLD('contragentId', 'int', 'caption=Визитка->Номер, input=none');
        
        $this->FNC('contragent', 'varchar', 'caption=Контрагент');
        
        $this->setDbUnique('uid');
    }
	
	
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Вземаме записите
        $rec = $form->rec;
        
        // Ако формата е изпратена успешно
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
    }
    
	
	/**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_BeforeSave($mvc, &$id, &$rec)
    {
        // Ако формата се едитва връщаме
        if ($rec->id) return ;
        
        // Очакваме да има услуга
        expect($rec->service);
        
        // Вземаме инстанцията на услугата
        $service = cls::get($rec->service);
        
        // Параметри при изпращането на SMS'a
        $params['class'] = $mvc->className;
        $params['function'] = 'update';
        
        // Вземаме информация за номера
        $rec->mobileNum = callcenter_Numbers::getNumberStr($rec->mobileNum);
        
        // Изпращаме SMS'a
        $sendStatusArr = $service->sendSMS($rec->mobileNum, $rec->text, $rec->sender, $params);
        
        // Ако е изпратен успешно
        if ($sendStatusArr['sended']) {
            
            // Променяме статуса на изпратен
            $rec->status = 'sended';
            
            // Вземаме уникалния номер
            $rec->uid = $sendStatusArr['uid'];
        } else {
            
            // Ако има грешка при изпращане
            $rec->status = 'sendError';
        }
        
        // Вземаме последния запис за номера
        $extRec = callcenter_Numbers::getRecForNum($rec->mobileNum);
        
        // Вземаме класа и id' то на контрагента
        $rec->classId = $extRec->classId;
        $rec->contragentId = $extRec->contragentId;
        
        // Ако има съобщение
        if ($sendStatusArr['msg']) {
            
            // Показваме го
            core_Statuses::add($sendStatusArr['msg']);
        }
    }
    
    
    /**
     * Обновява запис в логовете
     * callBack фунцкия
     * Използва се от изпращачите за обновяване на състоянието
     */
    static function update($uid, $status, $receivedTimestamp = NULL)
    {
        // Вземаме записа
        $rec = static::fetch(array("#uid = '[#1#]'", $uid));
        
        // Сменяме статуса и времето на получаване
        $rec->status = $status;
        
        // Ако няма време на получаване
        if (!$receivedTimestamp) {
            
            // Вземаме текущото време
            $rec->receivedTime = dt::verbal2mysql();
        } else {
            
            // Преобразуваме времето
            $rec->receivedTime = dt::timestamp2Mysql($receivedTimestamp);
        }
        
        // Ъпдейтваме записите
        static::save($rec, NULL, 'UPDATE');
    }
    
    
	/**
	 * 
	 * 
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    { 
        // Ако има клас   
        if ($rec->classId) {
         
            // Инстанция на класа
            $class = cls::get($rec->classId);
            
            // Ако класа е профил
            if (strtolower($class->className) == 'crm_profiles') {
                
                // Вземаме линк към профила
                $card = crm_Profiles::createLink($rec->contragentId);
            } else {
                
                // Вземаме записите за съответния клас
                $cardRec = $class->fetch($rec->contragentId);
                
                // Ако имаме права за сингъл
                if ($class->haveRightFor('single', $cardRec)) {
                    
                    // Вземаме линка към сингъла
                    $card = ht::createLink($cardRec->name, array($class, 'single', $rec->contragentId)) ;
                } else {
                    
                    // Ако нямаме права
                    
                    // Вземаме линк към профила на отговорника
                    $inChargeLink = crm_Profiles::createLink($cardRec->inCharge);
                    
                    // Добавяме линка
                    $card = $class->getVerbal($cardRec, 'name') . " - {$inChargeLink}";
                }
            }
            
            // Добавяме линка към контрагента
            $row->contragent = $card;
        }

        // Ако има потребител
        if ($rec->createdBy) {
            
            // Създаваме линк към профила му
            $row->createdBy = crm_Profiles::createLink($rec->createdBy);
        }
        
        // В зависмост от състоянието на съобщенията, опделяме клас за реда в таблицата
        if ($rec->status == 'received') {
            $row->ROW_ATTR['class'] .= ' sms-received';
        } elseif ($rec->status == 'sended') {
            $row->ROW_ATTR['class'] .= ' sms-sended';
        } elseif ($rec->status == 'receiveError') {
            $row->ROW_ATTR['class'] .= ' sms-receiveError';
        } elseif ($rec->status == 'sendError') {
            $row->ROW_ATTR['class'] .= ' sms-sendError';
        } 
    }
    
    
	/**
     * Добавя филтър за изпратените SMS-и
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {    
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
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
        $data->listFilter->showFields = 'search, usersSearch, status';
        
        $data->listFilter->input('search,usersSearch, status', 'silent');
    }
    
    
	/**
     * Сортиране DESC - последния запис да е най-отгоре
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Последно получените и изпратени и да са първи
        $data->query->orderBy('#receivedTime', 'DESC');
        $data->query->orderBy('#createdOn', 'DESC');
    
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
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
        if ($action == 'add') {
            
            // Ако няма класове, които имплементират интерфейса callcenter_SentSMSIntf
            if (!core_Classes::getInterfaceCount('callcenter_SentSMSIntf')) {
                
                // Никой не може да добавя
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако искаме да отворим сингъла на документа
        if ($rec->id && $action == 'single' && $userId) {
            
            // Ако нямаме роля CEO
            if (!haveRole('ceo')) {
                
                // Ако сме мениджър
                if (haveRole('manager')) {
                    
                    // Ако ме от същи екип
                    if (!core_Users::isFromSameTeam($userId, $rec->createdBy)) {
                        
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
}
