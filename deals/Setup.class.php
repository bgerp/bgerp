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
 * Кой потребител да излиза като съставител на документите
 */
defIfNot('DEALS_ISSUER', 'activatedBy');

/**
 * class deals_Setup
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
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
        'DEALS_ISSUER' => array('enum(createdBy=Създателят,activatedBy=Активиралият)', 'caption=Съставител на бизнес документи->Избор'),
        'DEALS_OVERDUE_PENDING_DAYS_1' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Първо след,unit=дни'),
        'DEALS_OVERDUE_PENDING_DAYS_2' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Второ след,unit=дни'),
        'DEALS_OVERDUE_PENDING_DAYS_3' => array('int(Min=0)', 'caption=Напомняне за неконтиран документ със стар вальор->Трето след,unit=дни'),
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
            'description' => 'Проверка на платежни документи на заявка чакащи плащане',
            'controller' => 'deals_Setup',
            'action' => 'CheckPendingPaymentDocuments',
            'period' => 1440,
            'offset' => 120
        ),
    );
    
    
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
            $dQuery->show("{$Class->termDateFld},modifiedOn,createdBy,inCharge,contragentId,contragentClassId,amount,currencyId");
            
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
                        $msg = "Просрочен вальор на|* #{$Class->getHandle($dRec->id)} |от|* {$contragentName} |за|* {$amountVerbal}";
                        if($i != '1'){
                            $msg .= " (|{$iVerbal} напомняне|*)";
                        }
                       
                        bgerp_Notifications::add($msg, array($Class, 'single', $dRec->id), $dRec->createdBy);
                        if($dRec->createdBy != $dRec->inCharge){
                            bgerp_Notifications::add($msg, array($Class, 'single', $dRec->id), $dRec->inCharge);
                        }
                        break;
                    }
                }
            }
        }
        
    }
}
