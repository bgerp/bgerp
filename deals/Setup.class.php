<?php


/**
 * Толеранс за допустимо разминаване в салдото->Сума
 */
defIfNot('DEALS_BALANCE_TOLERANCE', '0.01');


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
            $dQuery->where("#state = 'pending'");
            $dQuery->show("{$Class->termDateFld},modifiedOn,createdBy");
            while($dRec = $dQuery->fetch()){
                
                // На коя дата се очаква да има направено плащане, ако не е посочена е 1 месец от създаването
                $expectedDate = empty($dRec->{$Class->termDateFld}) ? dt::addMonths(1, $dRec->modifiedOn, false) : $dRec->{$Class->termDateFld};
                
                // Ако датата е просрочена да бие нотификация
                if($expectedDate < $today){
                    $msg = "Просрочено плащане по|* #{$Class->getHandle($dRec->id)}";
                    bgerp_Notifications::add($msg, array($Class, 'single', $dRec->id), $dRec->createdBy, 'alert');
                }
            }
        }
        
    }
}
