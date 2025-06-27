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
    function prepareShipped_(&$data)
    {
        $data->TabCaption = 'Експедирания';
        $data->Tab = 'top';

        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if(in_array($prepareTab, array('payments', 'receipts'))) {
            $data->hide = true;
            return;
        }

        $this->prepareTabData($data, 'shipped');

        return $data;
    }


    /**
     * Подготовка на таба
     *
     * @param $data
     * @param $type
     * @return void
     */
    private function prepareTabData(&$data, $type)
    {
        // Табличната информация и пейджъра на плащанията
        $detail = (object) $data->masterData->rec->details;

        if ($type == 'shipped') {
            $actionVal = 'sale';
            $detail->listFields = "value=Артикул, pack=Мярка, quantity=К-во, amount=|*{$data->masterData->row->baseCurrency}, storeId=Склад,contragentId=Клиент,userId=Оператор";
        } else {
            $actionVal = 'payment';
            $detail->listFields = "value=Плащане, pack=Валута, amount=|*{$data->masterData->row->baseCurrency},contragentId=Клиент,userId=Оператор";
        }

        $detail->rows = array_filter($detail->receiptDetails, function($a) use ($actionVal){ return $a->action == $actionVal;});
        $detail->masterRec = $data->rec;

        // Инстанцираме пейджър-а
        $newRows = array();

        // Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
        if ($detail->rows) {
            $data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
            $data->pager->setPageVar($data->masterMvc->className, $data->masterId);
            $data->pager->itemsCount = countR($detail->rows);

            // Подготвяме поле по което да сортираме
            if ($type == 'shipped') {
                foreach ($detail->rows as &$value) {
                    $value->sortString = mb_strtolower(cat_Products::fetchField($value->value, 'name'));
                }
                usort($detail->rows, function($a, $b) {return strcmp($a->sortString, $b->sortString);});
            } else {
                arr::sortObjects($detail->rows, 'value');
            }

            // Пропускане на записите, които не трябва да са на тази страница
            foreach ($detail->rows as $key => $rec) {
                if (!$data->pager->isOnPage()) continue;
                $newRows[] = $this->getVerbalDetail($data->masterData->rec, $detail->rows[$key]);
            }

            // Заместваме стария масив с новия филтриран
            $detail->rows = $newRows;
        }

        $data->details = $detail;
    }


    /**
     * Подготовка на сумарната информация
     *
     * @param stdClass $data
     * @return void
     */
    function preparePayments_(&$data)
    {
        $data->TabCaption = 'Плащания';
        $data->Tab = 'top';

        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if(in_array($prepareTab, array('shipped', 'receipts')) || empty($prepareTab)) {
            $data->hide = true;
            return;
        }

        $this->prepareTabData($data, 'payments');

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
        $quantityVerbal = $Double->toVerbal($obj->quantity);
        $row->quantity = ht::styleNumber($quantityVerbal, $obj->amount);
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

            $quantity = $obj->quantity * $obj->quantityInPack;
            deals_Helper::getQuantityHint($row->quantity, $this, $obj->value, $obj->storeId, $quantity, $rec->state, $rec->valior);

        } else {

            // Ако детайла е плащане
            $row->pack = $currencyCode;
            $value = ($obj->value != -1) ? cond_Payments::getTitleById($obj->value) : tr('В брой');
            $row->value = "<i>{$value}</i>";
            $row->ROW_ATTR['class'] = 'report-payment';
            unset($row->quantity);

            if($obj->value != '-1'){
                $obj->amount = cond_Payments::toBaseCurrency($obj->value, $obj->amount, $obj->date);
            }
        }

        $amount = $Double->toVerbal($obj->amount);
        $amount = ht::styleNumber($amount, $obj->amount);
        if(isset($obj->param)){
            $amountHint = tr('ДДС') . ": " . core_Type::getByName('percent')->toVerbal($obj->param);
            $amount = ht::createHint($amount, $amountHint);
        }

        $row->amount = "<span style='float:right'>{$amount}</span>";
        $row->contragentId = pos_Receipts::getMaskedContragent($obj->contragentClassId, $obj->contragentId, $rec->pointId, array('date' => $obj->date, 'link' => true, 'icon' => true));
        if(isset($obj->userId)){
            $row->userId  = crm_Profiles::createLink($obj->userId);
        }

        return $row;
    }


    /**
     * Рендиране на таба
     *
     * @param $data
     * @return core_ET
     */
    private function renderTab($data)
    {
        $tpl = new core_ET('');
        if($data->hide) return $tpl;

        $data->details->listFields = core_TableView::filterEmptyColumns($data->details->rows, $data->details->listFields, 'userId');
        $data->details->listTableMvc = new core_FieldSet();
        $data->details->listTableMvc->FLD('value', 'varchar', 'tdClass=largeCell');
        $data->details->listTableMvc->FLD('quantity', 'double');
        $tpl->append(cls::get('pos_Reports')->renderListTable($data->details));
        if ($data->pager) {
            $tpl->append($data->pager->getHtml());
        }

        return $tpl;
    }


    /**
     * Рендиране на таба на артикулите
     *
     * @param $data
     * @return core_ET
     */
    function renderShipped_($data)
    {
        return $this->renderTab($data);
    }


    /**
     * Рендиране на таба на плащанията
     *
     * @param stdClass $data
     * @return void
     */
    function renderPayments_(&$data)
    {
        return $this->renderTab($data);
    }


    /**
     * Подготовка на бележките
     */
     public function prepareReceipts($data)
    {
        $detail = (object) $data->masterData->rec->details;
        $receiptIds = arr::extractValuesFromArray($detail->receipts, 'id');

        $data->TabCaption = "Бележки|* (" . countR($receiptIds) . ")";
        $data->Tab = 'top';

        $tabParam = $data->masterData->tabTopParam;
        $prepareTab = Request::get($tabParam);
        if($prepareTab != 'receipts') {
            $data->hide = true;
            return;
        }

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
            $waitingOn = $receiptRec->waitingOn ?? $receiptRec->modifiedOn;
            $waitingBy = $receiptRec->waitingBy ?? $receiptRec->modifiedBy;
            $row->waiting = core_Type::getByName('datetime(format=smartTime)')->toVerbal($waitingOn) . " " . tr('от') . " " . crm_Profiles::createLink($waitingBy);

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
    public function renderReceipts($data)
    {
        $tpl = new core_ET("");
        if($data->hide) return $tpl;

        $fieldset = clone cls::get('pos_Receipts');
        $fieldset->FLD('created', 'varchar', 'smartCenter');
        $fieldset->FLD('waiting', 'varchar', 'smartCenter');
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $fields = arr::make("receiptId=Бележка,total=|*{$data->masterData->row->baseCurrency},created=Създаване,waiting=Чакащо", true);

        // Рендиране на таблицата с резултатите
        $dTpl = $table->get($data->receiptRows, $fields);
        $tpl->append($dTpl);
        if ($data->pager) {
            $tpl->append($data->pager->getHtml());
        }

        return $tpl;
    }
}