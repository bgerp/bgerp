<?php


/**
 * Мениджър на отчети от Задължения към доставчици
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
class acc_reports_OweProviders extends acc_reports_BalanceImpl
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_OweProvidersReport';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Задължения към доставчици';
    
    
    /**
     * Дефолт сметка
     */
    public $accountSysId = '401';
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {
        // Искаме да покажим оборотната ведомост за сметката на касите
        $accId = acc_Accounts::getRecBySystemId($mvc->accountSysId)->id;
        $form->setDefault('accountId', $accId);
        $form->setHidden('accountId');
        
        // Задаваме, че ще филтрираме по перо
        $form->setDefault('action', 'group');
        $form->setField('to', 'input=none');
        $positionId = acc_Lists::getPosition($mvc->accountSysId, 'crm_ContragentAccRegIntf');
        
        $form->setOptions('orderField', array('', "ent{$positionId}" => 'Контрагент',
            'baseAmount' => 'Начално салдо',
            'debitAmount' => 'Дебит',
            'creditAmount' => 'Кредит',
            'blAmount' => 'Крайно салдо'));
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $positionId = acc_Lists::getPosition($mvc->accountSysId, 'crm_ContragentAccRegIntf');
        
        $form->setOptions('orderField', array('', "ent1{$positionId}" => 'Контрагент',
            'baseAmount' => 'Начално салдо',
            'debitAmount' => 'Дебит',
            'creditAmount' => 'Кредит',
            'blAmount' => 'Крайно салдо'));
        
        $form->setHidden('action');
        
        
        foreach (range(1, 3) as $i) {
            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");
        }
        
        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'crm_ContragentAccRegIntf');
        $form->setDefault("feat{$articlePositionId}", '*');
    }
    
    
    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }
    
    
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
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
        $activateOn = "{$this->innerForm->from} 23:59:59";
        
        return $activateOn;
    }
}
