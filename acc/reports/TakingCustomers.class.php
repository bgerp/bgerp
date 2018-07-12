<?php


/**
 * Мениджър на отчети от Движение по сметки на  доставчици
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_reports_TakingCustomers extends acc_reports_PeriodHistoryImpl
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_TakingCustomersReport';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Вземания от клиенти';
    
    
    /**
     * Дефолт сметка
     */
    public $defaultAccount = '411';
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $customersPositionId = acc_Lists::getPosition($mvc->defaultAccount, 'crm_ContragentAccRegIntf');
        $currencyPosition = acc_Lists::getPosition($mvc->defaultAccount, 'currency_CurrenciesAccRegIntf');
        
        $form->setField("ent{$customersPositionId}Id", 'caption=Клиент');
        $form->setField("ent{$currencyPosition}Id", 'caption=Валута');
        
        $form->setFieldTypeParams("ent{$customersPositionId}Id", array('select' => 'title'));
        $form->setFieldTypeParams("ent{$currencyPosition}Id", array('select' => 'title'));
    }
    
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
        $innerState = &$this->innerState;
        
        unset($innerState->recs);
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        $activateOn = "{$this->innerForm->toDate} 23:59:59";
        
        return $activateOn;
    }
}
