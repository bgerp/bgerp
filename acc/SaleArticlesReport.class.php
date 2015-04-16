<?php



/**
 * Мениджър на отчети от Приходи от продажби по продукти
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
class acc_SaleArticlesReport extends acc_BalanceReportImpl
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство->Приходи от продажби на Стоки и Продукти ';


    /**
     * Дефолт сметка
     */
    public $accountSysId = '701';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_Form &$form)
    {

        // Искаме да покажим оборотната ведомост за сметката на касите
        $accId = acc_Accounts::getRecBySystemId($mvc->accountSysId)->id;
        $form->setDefault('accountId', $accId);
        $form->setHidden('accountId');

        // Дефолт периода е текущия ден
        $today = dt::today();

        $form->setDefault('from',date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', $today);

        // Задаваме че ще филтрираме по перо
        $form->setDefault('action', 'group');
        $form->setHidden('orderField');
        $form->setHidden('orderBy');
    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $form->setHidden('action');

        foreach (range(1, 3) as $i){

            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");

        }

        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'cat_ProductAccRegIntf');

        $form->setDefault("feat{$articlePositionId}","*");


    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }


    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

    }

}