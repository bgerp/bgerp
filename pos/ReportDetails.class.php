<?php


/**
 * Детайл на ПОС отчета
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_ReportDetails extends core_Manager
{
    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'no_one';


    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';


    /**
     * Брой продажби на страница
     */
    public $listDetailsPerPage = '50';


    /**
     * Подготовка на сумарната информация
     *
     * @param stdClass $data
     * @return void
     */
    function prepareTotal_(&$data)
    {
        $data->TabCaption = 'Всичко';
        $data->Tab = 'top';

        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if($prepareTab == 'receipts') {
            $data->hide = true;
            return;
        }

        $detail = (object) $data->masterData->rec->details;
        arr::sortObjects($detail->receiptDetails, 'action');

        // Табличната информация и пейджъра на плащанията
        $detail->listFields = "value=Действие, pack=Мярка, quantity=К-во, amount=|*{$data->masterData->row->baseCurrency}, storeId=Склад,contragentId=Клиент";
        $detail->rows = $detail->receiptDetails;
        $detail->masterRec = $data->rec;

        // Инстанцираме пейджър-а
        $newRows = array();

        // Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
        if ($detail->rows) {
            $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
            $data->pager->setPageVar($data->masterMvc->className, $data->masterId);
            $data->pager->itemsCount = countR($detail->rows);

            // Подготвяме поле по което да сортираме
            foreach ($detail->rows as &$value) {
                if ($value->action == 'sale') {
                    $value->sortString = mb_strtolower(cat_Products::fetchField($value->value, 'name'));
                }
            }
            usort($detail->rows, function($a, $b) {return strcmp($a->sortString, $b->sortString);});

            foreach ($detail->rows as $key => $rec) {

                // Пропускане на записите, които не трябва да са на тази страница
                if (!$data->pager->isOnPage()) continue;
                $newRows[] = $this->getVerbalDetail($detail->masterRec, $detail->rows[$key]);
            }

            // Заместваме стария масив с новия филтриран
            $detail->rows = $newRows;
        }

        $data->details = $detail;

        return $data;
    }


    /**
     * Функция обработваща детайл на репорта във вербален вид
     *
     * @param stdClass $rec запис на продажба или плащане
     * @return stdClass $obj вербалния вид на записа
     */
    private function getVerbalDetail($rec, $obj)
    {
        $row = new stdClass();

        $Double = core_Type::getByName('double(decimals=2)');
        $currencyCode = acc_Periods::getBaseCurrencyCode($obj->date);
        $row->quantity = "<span style='float:right'>{$Double->toVerbal($obj->quantity)}</span>";
        if ($obj->action == 'sale') {

            // Ако детайла е продажба
            $row->ROW_ATTR['class'] = 'report-sale';
            if(isset($obj->storeId)){
                $row->storeId = store_Stores::getHyperlink($obj->storeId, true);
            }

            $row->pack = cat_UoM::getShortName($obj->pack);
            deals_Helper::getPackInfo($row->pack, $obj->value, $obj->pack, $obj->quantityInPack);

            $row->value = cat_Products::getHyperlink($obj->value, true);
            $obj->amount *= 1 + $obj->param;

            if(core_Packs::isInstalled('batch')){
                $batchDef = batch_Defs::getBatchDef($obj->value);
                if(is_object($batchDef)){
                    if(!empty($obj->batch)){
                        $batch = batch_Movements::getLinkArr($obj->value, $obj->batch);
                        $row->value .= "<br><span class='richtext'>" . $batch[$obj->batch] . "</span>";
                    } else {
                        $row->value .= "<br><span class='richtext quiet'>" . tr("Без партида") . "</span>";
                    }
                }
            }

            deals_Helper::getQuantityHint($row->quantity, $this, $obj->value, $obj->storeId, $obj->quantity, $rec->state, $rec->valior);

        } else {

            // Ако детайла е плащане
            $row->pack = $currencyCode;
            $value = ($obj->value != -1) ? cond_Payments::getTitleById($obj->value) : tr('В брой');
            $row->value = "<b>" . tr('Плащане') . "</b>: &nbsp;<i>{$value}</i>";
            $row->ROW_ATTR['class'] = 'report-payment';
            unset($row->quantity);

            if($obj->value != '-1'){
                $obj->amount = cond_Payments::toBaseCurrency($obj->value, $obj->amount, $obj->date);
            }
        }

        $amount = $Double->toVerbal($obj->amount);
        if(isset($obj->param)){
            $amountHint = tr('ДДС') . ": " . core_Type::getByName('percent')->toVerbal($obj->param);
            $amount = ht::createHint($amount, $amountHint);
        }

        $row->amount = "<span style='float:right'>{$amount}</span>";
        $row->contragentId = cls::get($obj->contragentClassId)->getHyperlink($obj->contragentId, true);

        return $row;
    }


    /**
     * Рендиране на таба
     *
     * @param $data
     * @return core_ET
     */
    function renderTotal_($data)
    {
        $tpl = new core_ET('');
        if($data->hide) return $tpl;

        $data->details->listTableMvc = new core_FieldSet();
        $data->details->listTableMvc->FLD('value', 'varchar', 'tdClass=largeCell');
        $tpl->append(cls::get('pos_Reports')->renderListTable($data->details));
        if ($data->pager) {
            $tpl->append($data->pager->getHtml());
        }

        return $tpl;
    }


    /**
     * Подготовка на бележките
     */
    function prepareReceipts($data)
    {
        $data->TabCaption = 'Бележки';
        $data->Tab = 'top';

        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if($prepareTab != 'receipts') {
            $data->hide = true;
            return;
        }

        $detail = (object) $data->masterData->rec->details;
        $receiptIds = arr::extractValuesFromArray($detail->receipts, 'id');

        $query = pos_Receipts::getQuery();
        $query->in('id', $receiptIds);
        $data->receiptRows = array();

        $receiptArr = $query->fetchAll();
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->pager->setPageVar($data->masterMvc->className, $data->masterId);
        $data->pager->itemsCount = countR($receiptArr);

        foreach ($receiptArr as $receiptRec){
            if (!$data->pager->isOnPage()) continue;

            $row = pos_Receipts::recToVerbal($receiptRec);
            $row->created = $row->createdOn . " " . tr('от') . " " . crm_Profiles::createLink($receiptRec->createdBy);
            $row->receiptId = pos_Receipts::getHyperlink($receiptRec->id, true);
            $data->receiptRows[$receiptRec->id] = $row;
        }
    }


    /**
     * Рендиране на бележките
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    function renderReceipts($data)
    {
        $tpl = new core_ET("");
        if($data->hide) return $tpl;

        $fieldSet = new core_FieldSet();
        $table = cls::get('core_TableView', array('mvc' => $fieldSet));
        $fields = arr::make('receiptId=Бележка,created=Създаване,waitingOn=Чакащо', true);

        // Рендиране на таблицата с резултатите
        $dTpl = $table->get($data->receiptRows, $fields);
        if ($data->pager) {
            $tpl->append($data->pager->getHtml());
        }

        $tpl->append($dTpl);

        return $tpl;
    }
}