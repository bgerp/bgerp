<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за справка на движенията по каса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_reports_AccountImpl extends acc_reports_PeriodHistoryImpl
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'bank_AccountReportImpl';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Финанси » Обороти банка';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Дефолт сметка
     */
    protected $defaultAccount = '503';
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $bItemPosition = acc_Lists::getPosition($mvc->defaultAccount, 'bank_OwnAccRegIntf');
        $currencyPosition = acc_Lists::getPosition($mvc->defaultAccount, 'currency_CurrenciesAccRegIntf');
         
        $form->setField("ent{$bItemPosition}Id", 'caption=Б. сметка');
        $form->setField("ent{$currencyPosition}Id", 'caption=Валута');
        
        $form->setFieldTypeParams("ent{$bItemPosition}Id", array('select' => 'title'));
        $form->setFieldTypeParams("ent{$currencyPosition}Id", array('select' => 'title'));
        
        // Слагаме избраната каса, ако има такава
        if ($bankAccount = bank_OwnAccounts::getCurrent('id', false)) {
            $bankItemId = acc_Items::fetchItem('bank_OwnAccounts', $bankAccount)->id;
            $form->setDefault("ent{$bItemPosition}Id", $bankItemId);
        }
        
        $curCurrecy = acc_Periods::getBaseCurrencyId();
        $curItemId = acc_Items::fetchItem('currency_Currencies', $curCurrecy)->id;
        $form->setDefault("ent{$currencyPosition}Id", $curItemId);
    }
    
    
    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        $data->listFields['baseQuantity'] = 'Начално';
        $data->listFields['blQuantity'] = 'Остатък';
        $data->listFields['debitQuantity'] = 'Приход';
        $data->listFields['creditQuantity'] = 'Разход';
        
        unset($data->listFields['baseAmount'],$data->listFields['debitAmount'],$data->listFields['creditAmount'],$data->listFields['blAmount']);
    }
}
