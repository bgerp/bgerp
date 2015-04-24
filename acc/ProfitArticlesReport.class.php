<?php



/**
 * Мениджър на отчети от Печалба от продажби по продукти
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
class acc_ProfitArticlesReport extends acc_BalanceReportImpl
{


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство»Печалба от продажби на Стоки и Продукти';


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

        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'cat_ProductAccRegIntf');

        $form->setDefault("feat{$articlePositionId}", "*");
    }


    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

        unset($data->listFields['baseQuantity']);
        unset($data->listFields['baseAmount']);
        unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['creditQuantity']);
        unset($data->listFields['creditAmount']);


        $data->listFields['blQuantity'] = "Крайно салдо (ДК)->К-во";
        $data->listFields['blAmount'] = "Крайно салдо (ДК)->Сума";

    }


    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
        if(empty($data)) return;

        $chart = Request::get('Chart');

        foreach ($data->recs as $id => $rec) {
            $balance += abs($rec->blAmount);

            $dArr[$rec->grouping3] = abs($rec->blAmount);
        }

        $arr = self::preparePie($dArr, 9, 'Others');

        foreach ($arr as $id => $recSort) {
            $info[$recSort->key] = $recSort->value;
            //$info[$recSort->key] = round(($recSort->value/$balance) * 100,2);
        }

        $pie = array (
                        'legendTitle' => "Печалбата от продажбите в проценти (%)",
                        'info' => $info,
        );

        $tpl = $this->getReportLayout();

        $tpl->replace($this->title, 'TITLE');
        $this->prependStaticForm($tpl, 'FORM');

        // слагаме бутони на къстам тулбара
        $btnList = ht::createBtn('Таблица', array(
                'doc_Containers',
                'list',
        		'Chart' => 'list',
                'threadId' => Request::get('threadId', 'int')

            ), NULL, NULL,
            'ef_icon = img/16/table.png');

        $tpl->replace($btnList, 'buttonList');

        $btnChart = ht::createBtn('Графика', array(
                'doc_Containers',
                'list',
        		'Chart' => 'pie',
                'threadId' => Request::get('threadId', 'int')

            ), NULL, NULL,
            'ef_icon = img/16/chart16.png');

        $tpl->replace($btnChart, 'buttonChart');

        if ($chart == 'pie') {
            $coreConf = core_Packs::getConfig('doc');
            $chartAdapter = $coreConf->DOC_CHART_ADAPTER;
            $chartHtml = cls::get($chartAdapter);
            $chart =  $chartHtml::prepare($pie,'pie');
            $tpl->append($chart, 'DETAILS');

        } else {
            // Името на перото да се показва като линк
            if(count($data->rows)){
                $articlePositionId = acc_Lists::getPosition($this->accountSysId, 'cat_ProductAccRegIntf');
                foreach ($data->rows as $id => &$row){
                    if (!$data->recs[$id]->{"ent{$articlePositionId}Id"}) continue;
                    $articleItem = acc_Items::fetch($data->recs[$id]->{"ent{$articlePositionId}Id"}, 'classId,objectId');
                    if (!cls::load($articleItem->classId, TRUE)) continue;
                    $row->{"ent{$articlePositionId}Id"} = cls::get($articleItem->classId)->getShortHyperLink($articleItem->objectId);
                }
            }

            $tpl->placeObject($data->row);

            $tableMvc = new core_Mvc;

            $tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
            $table = cls::get('core_TableView', array('mvc' => $tableMvc));

            $tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');

            $data->summary->colspan = count($data->listFields);

            if ($data->bShowQuantities) {
                $data->summary->colspan -= 4;
                if ($data->summary->colspan != 0 && count($data->rows)) {
                    $beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
                }
            }

            if ($beforeRow) {
                $beforeRow->placeObject($data->summary);
                $tpl->append($beforeRow, 'ROW_BEFORE');
            }

        }

        return $tpl;

    }


    /**
     * По даден масив, правим подготовка за
     * графика тип "торта"
     *
     * @param array $data
     * @param int $n
     * @param string $otherName
     */
    public static function preparePie ($data, $n, $otherName = 'Други')
    {
        // сортирваме масива от възходящ към низходящ
        arsort($data);

        foreach ($data as $key => $value) {
            $newArr [] = (object) array ('key' => $key, 'value' => $value);
        }

        // броя на елементите в получения масив
        $cntData = count($data);

        // ако, числото което сме определили за новия масив
        // е по-малко от общия брой елементи
        // на подадения масив
        if ($cntData <= $n) {

            // връщаме направо масива
            return $data;

        //в противен случай
        } else {
            // взимаме първите n елемента от сортирания масив
            for($k = 0; $k <= $n -1; $k++) {
                $res[] = $newArr[$k];
            }

            // останалите елементи ги събираме
            for ($i = $n; $i <= $cntData; $i++){
                $sum += $newArr[$i]->value;
            }

            // ако имаме изрично зададено име за обобщения елемент
            if ($otherName) {
                // използваме него и го добавяме към получения нов масив с
                // n еленета и сумата на останалите елементи
                $res[] = (object) array ('key' => $otherName, 'value' => $sum);
                // ако няма, използваме default
            } else {
                $res[] = (object) array ('key' => "Други", 'value' => $sum);
            }
        }

        return $res;
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