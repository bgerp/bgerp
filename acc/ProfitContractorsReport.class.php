<?php



/**
 * Мениджър на отчети от Печалба от продажби по клиенти
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
class acc_ProfitContractorsReport extends acc_BalanceReportImpl
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство»Печалба от продажби по клиенти';


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

        foreach (range(1, 3) as $i) {

            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");

        }

        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'crm_ContragentAccRegIntf');

        $form->setDefault("feat{$articlePositionId}", "*");
    }


    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }


    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

        unset($data->listFields['baseQuantity']);
        unset($data->listFields['baseAmount']);
        unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['creditQuantity']);
        unset($data->listFields['creditAmount']);


        $data->listFields['blQuantity'] = "Кредит->К-во";
        $data->listFields['blAmount'] = "Кредит->Сума";
    }


    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {

        $tpl = $this->getReportLayout();

        $tpl->replace($this->title, 'TITLE');
        $this->prependStaticForm($tpl, 'FORM');

        $tpl->placeObject($data->row);

        $tableMvc = new core_Mvc;

        //$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
        $tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');


        $table = cls::get('core_TableView', array('mvc' => $tableMvc));

        $tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');

        $data->summary->colspan = count($data->listFields);

        if($data->bShowQuantities ){
            $data->summary->colspan -= 4;
            if($data->summary->colspan != 0 && count($data->rows)){
                $beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
            }
        }

        if($beforeRow){
            $beforeRow->placeObject($data->summary);
            $tpl->append($beforeRow, 'ROW_BEFORE');
        }


        return $tpl;
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


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    public function getExportFields ()
    {

        $exportFields['ent1Id']  = "Контрагенти";
        $exportFields['blAmount']  = "Кредит";

        return $exportFields;
    }

}