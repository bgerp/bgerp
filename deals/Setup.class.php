<?php


/**
 * Толеранс за допустимо разминаване в салдото->Сума
 */
defIfNot('DEALS_BALANCE_TOLERANCE', '0.01');


/**
 * Напомняне за неконтиран документ със стар вальор първо
 */
defIfNot('DEALS_OVERDUE_PENDING_DAYS_1', '1');


/**
 * Напомняне за неконтиран документ със стар вальор второ
 */
defIfNot('DEALS_OVERDUE_PENDING_DAYS_2', '5');


/**
 * Напомняне за неконтиран документ със стар вальор трето
 */
defIfNot('DEALS_OVERDUE_PENDING_DAYS_3', '14');


/**
 * Напомняне за активни продажби и покупки без нови документи
 */
defIfNot('DEALS_ACTIVE_DEALS_WITHOUT_DOCUMENTS', dt::SECONDS_IN_MONTH * 3);


/**
 * Напомняне за активни финансови сделки без нови документи
 */
defIfNot('DEALS_ACTIVE_FINDEALS_WITHOUT_DOCUMENTS', dt::SECONDS_IN_MONTH * 12);


/**
 * Кой потребител да излиза като съставител на документите
 */
defIfNot('DEALS_ISSUER', 'activatedBy');


/**
 * Кой конкретен потребител да излиза като съставител на документите
 */
defIfNot('DEALS_ISSUER_USER', '');


/**
 * class deals_Setup
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Помощни класове за бизнес документите';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'deals_OpenDeals',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'DEALS_BALANCE_TOLERANCE' => array('percent(min=0)', 'caption=Процент за допустимо разминаване в салдото според сумата->Процент'),
        'DEALS_ISSUER_USER' => array('user(roles=ceo|salesMaster,allowEmpty)', 'caption=Съставител на бизнес документи->Конкретен потребител'),
        'DEALS_ISSUER' => array('enum(createdBy=Създателят,activatedBy=Активиралият)', 'caption=Съставител на бизнес документи->Или'),
        'DEALS_OVERDUE_PENDING_DAYS_1' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Първо след,unit=дни'),
        'DEALS_OVERDUE_PENDING_DAYS_2' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Второ след,unit=дни'),
        'DEALS_OVERDUE_PENDING_DAYS_3' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Трето след,unit=дни'),
        'DEALS_ACTIVE_DEALS_WITHOUT_DOCUMENTS' => array('time', 'caption=Напомняне за активни продажби и покупки без нови документи->Хоризонт'),
        'DEALS_ACTIVE_FINDEALS_WITHOUT_DOCUMENTS' => array('time)', 'caption=Напомняне за активни финансови сделки без нови документи->Хоризонт'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'deals_reports_ReportPaymentDocuments';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'dealJoin';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Check Pending Payment Documents',
            'description' => 'Напомняне за просрочени платежни документи',
            'controller' => 'deals_Setup',
            'action' => 'CheckPendingPaymentDocuments',
            'period' => 1440,
            'offset' => 120
        ),

        array(
            'systemId' => 'Check Forgotten Active Deals',
            'description' => 'Напомняне за забравени активни сделки',
            'controller' => 'deals_Setup',
            'action' => 'Check4ForgottenDeals',
            'period' => 10080,
            'offset' => 120
        ),
    );


    /**
     * Изпращане на нотификации на създателите на евентуално забравени активни сделки
     */
    public function cron_Check4ForgottenDeals()
    {
        $horizonDeals = deals_Setup::get('ACTIVE_DEALS_WITHOUT_DOCUMENTS');
        $horizonFinDeals = deals_Setup::get('ACTIVE_FINDEALS_WITHOUT_DOCUMENTS');

        $arr = array('sales_Sales' => $horizonDeals, 'purchase_Purchases' => $horizonDeals, 'findeals_Deals' => $horizonFinDeals);

        // За всяка сделка
        foreach ($arr as $className => $horizon) {
            $dealArr = $threads = array();
            $date = dt::addSecs(-1 * $horizon);
            $Class = cls::get($className);

            // Има ли активни сделки от посочения клас?
            $query = $Class->getQuery();
            $query->where("#state = 'active' AND #createdBy != '-1' AND #createdBy != 0");
            $query->show('threadId,createdBy');

            // Ако има групират се по създателя си
            while ($rec = $query->fetch()) {
                $threads[$rec->threadId] = $rec->threadId;
                $dealArr[$rec->createdBy][$rec->threadId] = $rec->threadId;
            }

            // Ако няма сделки, нищо не се прави
            if (!countR($threads)) continue;

            // Коя е най-голямата дата на създаване на документ в нишки на активни сделки
            $cQuery = doc_Containers::getQuery();
            $cQuery->XPR('maxCreatedOn', 'datetime', 'MAX(#createdOn)');
            $cQuery->in('threadId', $threads);
            $cQuery->where("#maxCreatedOn < '{$date}'");
            $cQuery->groupBy('threadId');
            $cQuery->show('threadId');

            // Кои са нишките, в които последния създаден документ е преди хоризонта
            $threadsWithoutNewDocuments = arr::extractValuesFromArray($cQuery->fetchAll(), 'threadId');

            // Ако няма такива нишки нищо не се прави
            if(!countR($threadsWithoutNewDocuments)) continue;
            $horizonVerbal = core_Type::getByName('time')->toVerbal($horizon);

            // За всеки създател на активна сделка
            foreach ($dealArr as $userId => $createdThreads) {
                if ($userId < 1) continue;

                // Ако в някоя от нишките му така с последно създаден документ преди хоризонта, изпраща се нотификация
                $intersect = array_intersect_key($threadsWithoutNewDocuments, $createdThreads);
                $intersectCount = countR($intersect);
                if ($intersectCount) {
                    $className = ($intersectCount == 1) ? mb_strtolower($Class->singleTitle) : mb_strtolower($Class->title);
                    $msg = "Имате|* {$intersectCount} |активни {$className} без движения в последните|* {$horizonVerbal}";

                    $url = array('doc_Search', 'list', 'docClass' => $Class->getClassId(), 'author' => $userId, 'state' => 'active', 'toDate' => dt::verbal2mysql($date, false));
                    bgerp_Notifications::add($msg, $url, $userId);
                }
            }
        }
    }


    /**
     * Проверка на платежни документи на заявка чакащи плащане по разписание
     */
    public function cron_CheckPendingPaymentDocuments()
    {
        $today = dt::today();
        $paymentClassesArr = array('cash_Pko', 'cash_Rko', 'bank_IncomeDocuments', 'bank_SpendingDocuments');
        foreach ($paymentClassesArr as $className){
            $Class = cls::get($className);
            
            // Всички платежни документи на заявка
            $dQuery = $Class->getQuery();
            $dQuery->EXT('inCharge', 'doc_Folders', 'externalName=inCharge,externalKey=folderId');
            $dQuery->where("#state = 'pending'");
            $dQuery->show("{$Class->termDateFld},modifiedOn,createdBy,inCharge,contragentId,contragentClassId,amount,currencyId, threadId");
            
            while($dRec = $dQuery->fetch()){
                
                // На коя дата се очаква да има направено плащане, ако не е посочена е 1 месец от създаването
                $expectedDate = empty($dRec->{$Class->termDateFld}) ? dt::addMonths(1, $dRec->modifiedOn, false) : dt::verbal2mysql($dRec->{$Class->termDateFld}, false);
                
                // Изпращане на първо/второ или трето напомняне
                foreach (array('1' => 'първо', '2' => 'второ', '3' => 'трето') as $i => $iVerbal){
                    $days = static::get("OVERDUE_PENDING_DAYS_{$i}");
                    $overdueDate = dt::addDays($days, $expectedDate, false);
                    if($overdueDate == $today){
                        
                        // Подготовка на текста на нотификацията
                        $amountVerbal = core_Type::getByName('double(smartRound)')->toVerbal($dRec->amount);
                        $amountVerbal = currency_Currencies::decorate($amountVerbal, $dRec->currencyId);
                        $amountVerbal = str_replace('&nbsp;', ' ', $amountVerbal);
                        $contragentName = cls::get($dRec->contragentClassId)->getVerbal($dRec->contragentId, 'name');
                        $msg = "|Просрочен документ|* #{$Class->getHandle($dRec->id)} |от|* {$contragentName} |за|* {$amountVerbal}";
                        if($i != '1'){
                            $msg .= " (|{$iVerbal} напомняне|*)";
                        }
                       
                        // Нотифицира се създателя на документа, дилъра на сделката и отговорника на папката
                        $usersToNotify = array($dRec->createdBy => $dRec->createdBy);
                        $usersToNotify[$dRec->inCharge] = $dRec->inCharge;
                        $firstDoc = doc_Threads::getFirstDocument($dRec->threadId);
                        if($dealerId = $firstDoc->fetchField('dealerId')){
                            $usersToNotify[$dealerId] = $dealerId;
                        }
                        
                        foreach ($usersToNotify as $userId){
                            bgerp_Notifications::add($msg, array($Class, 'single', $dRec->id), $userId);
                        }
                        
                        break;
                    }
                }
            }
        }
    }
    
    
    /**
     * Мигрира с коя сделка е приключено
     * 
     * @param mixed $mvc
     * @param mixed $ClosedDocumentMvc
     */
    public function updateClosedWith($mvc, $ClosedDocumentMvc)
    {
        $mvc = cls::get($mvc);
        $mvc->setupMvc();
        
        if(!$mvc->count()) return;
        
        $ClosedDocumentMvc = cls::get($ClosedDocumentMvc);
        $ClosedDocumentMvc->setupMvc();
        
        if(!$ClosedDocumentMvc->count()) return;
        
        $docIdColName = str::phpToMysqlName('docId');
        $closeWithColName = str::phpToMysqlName('closeWith');
        $classIdColName = str::phpToMysqlName('docClassId');
        $stateColName = str::phpToMysqlName('state');
        
        $query = "UPDATE {$mvc->dbTableName},{$ClosedDocumentMvc->dbTableName} SET {$mvc->dbTableName}.{$closeWithColName} = {$ClosedDocumentMvc->dbTableName}.{$closeWithColName} WHERE {$ClosedDocumentMvc->dbTableName}.{$docIdColName} = {$mvc->dbTableName}.id AND {$ClosedDocumentMvc->dbTableName}.{$classIdColName} = {$mvc->getClassId()} AND {$ClosedDocumentMvc->dbTableName}.{$closeWithColName} IS NOT NULL AND {$ClosedDocumentMvc->dbTableName}.{$stateColName} = 'active'";
        $mvc->db->query($query);
    }
}
