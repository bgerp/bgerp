<?php



/**
 * Мениджър на отчети от Произведени продукти
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ManufacturedProductsReport extends acc_CorespondingReportImpl
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство»Произведени продукти';


    /**
     * Дефолт сметка
     */
    public $baseAccountId = '321';


    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '611';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields ($mvc, core_Form &$form)
    {
     
        // Искаме да покажим оборотната ведомост за сметката на касите
        $baseAccId = acc_Accounts::getRecBySystemId($mvc->baseAccountId)->id;
        $form->setDefault('baseAccountId', $baseAccId);
        $form->setHidden('baseAccountId');
        
        $corespondentAccId = acc_Accounts::getRecBySystemId($mvc->corespondentAccountId)->id;
        $form->setDefault('corespondentAccountId', $corespondentAccId);
        $form->setHidden('corespondentAccountId');

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
        $activateOn = "{$this->innerForm->to} 23:59:59";

        return $activateOn;
    }
}