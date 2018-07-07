<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка
 * по отклоняващи се цени в продажбите
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_reports_SalesPriceImpl extends frame_BaseDriver
{
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo,sales';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Продажби » Отклонение в цените по продажби';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('from', 'date(allowEmpty)', 'caption=От,input,mandatory');
        $form->FLD('to', 'date(allowEmpty)', 'caption=До,input,mandatory');
        $form->FLD('dealer', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Търговец,input');

        $form->FLD('orderState', 'set(active=Активно,draft=Чакащо,closed=Приключено,rejected=Оттеглено)', 'caption=Състояние,formOrder=110000,maxColumns=2');
        $form->FLD('orderBy', 'enum(,ASC=Възходящ,DESC=Низходящ)', 'caption=Подредба->Тип,formOrder=110001');
        
        $this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        
        // Дефолт периода е текущия ден
        $today = dt::today();
         
        $form->setDefault('from', date('Y-m-01', strtotime('-1 months', dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', dt::addDays(-1, $today));
        
        $form->setDefault('orderBy', 'ASC');
         
        $this->inputForm($form);
        
        $this->invoke('AfterPrepareEmbeddedForm', array($form));
    }


    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
        // Размяна, ако периодите са объркани
        if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
            $mid = $form->rec->from;
            $form->rec->from = $form->rec->to;
            $form->rec->to = $mid;
        }
        
        if ($form->isSubmitted()) {
            if ($form->rec->orderBy == '') {
                unset($form->rec->orderBy);
            }
        }
    }


    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
        // Подготвяне на данните
        $data = new stdClass();
        $data->recs = array();
        
        $data->rec = $this->innerForm;
        $this->prepareListFields($data);
        
        // Намиране на текущата валута
        for ($i = $data->rec->from. ' 00:00:00'; $i < $data->rec->to; $i = dt::addDays(1, $i)) {
            $currency = currency_Currencies::fetchField(acc_Periods::getBaseCurrencyId($i), 'code');
            $currArr[$currency][] = $i;
            $currCode[$i] = $currency;
        }
         
        $currencyNow = currency_Currencies::fetchField(acc_Periods::getBaseCurrencyId(dt::now()), 'code');
        
        $data->currencyNow = $currencyNow;

        // Правим заявка към "Продажбите", "Предавателните протоколи", "Експедиционните нареждания"
        $querySales = sales_Sales::getQuery();
        $queryServices = sales_Services::getQuery();
        $queryShipmentOrders = store_ShipmentOrders::getQuery();

        // Ако потребителя е избрал дадено състояние
        // генерираме заявките
        if (isset($data->rec->orderState)) {
            $states = type_Set::toArray($data->rec->orderState);
            
            $querySales->where("(#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}')");
            $querySales->whereArr('state', $states, true, false);
                    
            $queryServices->where("(#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}')");
            $queryServices->whereArr('state', $states, true, false);
                    
            $queryShipmentOrders->where("(#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}')");
            $queryShipmentOrders->whereArr('state', $states, true, false);
        }
        
        // Ако е избран даден търговец
        // генерираме заявките
        if (isset($data->rec->dealer)) {
            $states = type_Set::toArray($data->rec->orderState);
            $querySales->where("(#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}') AND #dealerId = '{$data->rec->dealer}'");
            $querySales->whereArr('state', $states, true, false);
        }

        // Ако нищо не е избрано
        // генерираме заявките
        $querySales->where("#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}'");
        $queryServices->where("#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}'");
        $queryShipmentOrders->where("#valior >= '{$data->rec->from}' AND #valior <= '{$data->rec->to}'");

        $listToCustomers = cls::get('price_ListToCustomers');
        
        $recSaleDetails = '';

        // Обикалям по всички "Продажби"
        while ($recSale = $querySales->fetch()) {
            // намираме на продажбата съответния детайл
            $query = sales_SalesDetails::getQuery();
            $query->where("#saleId = '{$recSale->id}'");
            $priceInfo = '';

            // Обикаляме по всеки детайл и намираме
            // изчислената цена от компютъра
            while ($recSaleDetails = $query->fetch()) {
                if (isset($recSaleDetails->productId)) {
                    $priceInfo = $listToCustomers->getPriceInfo(
                        $recSale->contragentClassId,
                                                                 $recSale->contragentId,
                                                                 $recSaleDetails->productId,
                                                                 $recSaleDetails->packagingId,
                                                                 $recSaleDetails->quantity,
                                                                 $recSale->valior
                    );
            
                    // Добавяме резултата в масив
                    // типа на документа
                    // датата
                    // състоянието
                    // артикула
                    // количеството
                    // цената
                    // изчислената цена
                    // всеки артикул е нов елемент в масива
                    $data->recs[] = (object) array('docType' => 'sale',
                                                     'id' => $recSale->id,
                                                     'valior' => $recSale->valior,
                                                     'state' => $recSale->state,
                                                     'article' => $recSaleDetails->productId,
                                                     'quantity' => $recSaleDetails->quantity,
                                                     'pricePc' => $priceInfo->price,
                                                     'price' => $recSaleDetails->price);
                }
            }
        }
        
        // Обикалям по всички "Предавателни протоколи"
        while ($recServices = $queryServices->fetch()) {
            // намираме на предавателния протокол съответния детайл
            $query = sales_ServicesDetails::getQuery();
            $query->where("#shipmentId = '{$recServices->id}'");
            
            // Обикаляме по всеки детайл и намираме
            // изчислената цена от компютъра
            while ($recServicesDetails = $query->fetch()) {
                if (isset($recServicesDetails->productId)) {
                    $priceInfo = $listToCustomers->getPriceInfo(
                        $recServices->contragentClassId,
                                                                 $recServices->contragentId,
                                                                 $recServicesDetails->productId,
                                                                 $recServicesDetails->packagingId,
                                                                 $recServicesDetails->quantity,
                                                                 $recServices->valior
                    );
                    // Добавяме резултата в масив
                    $data->recs[] = (object) array('docType' => 'services',
                                                     'id' => $recServices->id,
                                                     'valior' => $recServices->valior,
                                                     'article' => $recServicesDetails->productId,
                                                     'price' => $recServicesDetails->price,
                                                     'quantity' => $recServicesDetails->quantity,
                                                     'pricePc' => $priceInfo->price,
                                                     'state' => $recServices->state);
                }
            }
        }
        
        // Обикалям по всички "Експедиционни нареждания"
        while ($recShipment = $queryShipmentOrders->fetch()) {
            // намираме на екцпедиционното нареждане съответния детайл
            $query = store_ShipmentOrderDetails::getQuery();
            $query->where("#shipmentId = '{$recShipment->id}'");
            
            // Обикаляме по всеки детайл и намираме
            // изчислената цена от компютъра
            while ($recShipmentDetails = $query->fetch()) {
                if (isset($recShipmentDetails->productId)) {
                    $priceInfo = $listToCustomers->getPriceInfo(
                        $recShipment->contragentClassId,
                                                                 $recShipment->contragentId,
                                                                 $recShipmentDetails->productId,
                                                                 $recShipmentDetails->packagingId,
                                                                 $recShipmentDetails->quantity,
                                                                 $recShipment->valior
                    );
                    // Добавяме резултата в масив
                    $data->recs[] = (object) array('docType' => 'shipment',
                                                 'id' => $recShipment->id,
                                                 'valior' => $recShipment->valior,
                                                 'article' => $recShipmentDetails->productId,
                                                 'quantity' => $recShipmentDetails->quantity,
                                                 'price' => $recShipmentDetails->price,
                                                 'pricePc' => $priceInfo->price,
                                                 'state' => $recShipment->state);
                }
            }
        }

        // За всички генерирани елементи
        foreach ($data->recs as $id => $recs) {
            // Ако компютъра не е върнал изчислена цена
            // премахваме елемента от масива
            if ($recs->pricePc == null) {
                unset($data->recs[$id]);
            }
            
            // Ако продажната - изислената цена е 0
            // премахваме елемента от масива
            if (round($recs->price - $recs->pricePc) == 0) {
                unset($data->recs[$id]);
            }

            // Изчисляваме делтата на реда по формулата
            // (продажна-изчислена цена) * количеството
            $delta = $recs->quantity * ($recs->price - $recs->pricePc);
            
            $recs->delta = $delta;
        }
        
        // Сортираме масива по делтата, като
        // искаме най-отгоре да е най-голямата загуба
        arr::sortObjects($data->recs, 'delta', $data->rec->orderBy);

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
        // Подготвяме страницирането
        $data = $res;
         
        $pager = cls::get('core_Pager', array('pageVar' => 'P_' .  $mvc->EmbedderRec->that,'itemsPerPage' => $mvc->listItemsPerPage));
         
        $pager->itemsCount = count($data->recs, COUNT_RECURSIVE);
        $data->pager = $pager;

        if (count($data->recs)) {
            foreach ($data->recs as $rec) {
                if (!$pager->isOnPage()) {
                    continue;
                }
        
                $row = $mvc->getVerbal($rec);
        
                $data->rows[] = $row;
            }
        }

        $res = $data;
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('sales/tpl/SalesPriceLayout.shtml');
         
        return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        if (empty($data)) {
            return;
        }
  
        $tpl = $this->getReportLayout();
        
        $tpl->replace($this->getReportTitle(), 'TITLE');
    
        $form = cls::get('core_Form');
    
        $this->addEmbeddedFields($form);
    
        $form->rec = $data->rec;
        $form->class = 'simpleForm';
    
        $tpl->prepend($form->renderStaticHtml(), 'FORM');

        $tpl->replace($data->currencyNow, 'currency');
        
        $tpl->placeObject($data->rec);
        
        $fl = cls::get('core_FieldSet');
        $fl->FLD('doc', 'varchar');
         
         
        $f = cls::get('core_FieldSet');
        
        $f->FLD('date', 'date');
        $f->FLD('docType', 'varchar');
        $f->FLD('article', 'varchar');
        $f->FLD('quantity', 'varchar');
        $f->FLD('price', 'varchar');
        $f->FLD('pricePc', 'varchar');
        $f->FLD('delta', 'varchar');
        
        $table = cls::get('core_TableView', array('mvc' => $f));
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        if ($data->pager) {
            $tpl->append($data->pager->getHtml(), 'PAGER');
        }
        
        $embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
        $data->listFields = array(
                'date' => 'Дата',
                'docType' => 'Документ',
                'article' => 'Артикул',
                'quantity' => 'Количество',
                'price' => 'Цена->Продажна',
                'pricePc' => 'Цена->Изчислена',
                'delta' => 'Делта',
        );
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    private function getVerbal($rec)
    {
        $RichtextType = cls::get('type_Richtext');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
    
        $row = new stdClass();
        // Ще оцветим всеки ред в състоянието на записа
        $row->ROW_ATTR['class'] = "state-{$rec->state}";

        $row->date = dt::mysql2verbal($rec->valior, 'd.m.Y');

        // В зависимост от това, какъв тип е документа
        // генерираме неговите заглавия
        if ($rec->docType == 'sale') {
            $row->docType = sales_Sales::getShortHyperlink($rec->id);
        }
        
        if ($rec->docType == 'services') {
            $row->docType = sales_Services::getShortHyperlink($rec->id);
        }
        
        if ($rec->docType == 'shipment') {
            $row->docType = store_ShipmentOrders::getShortHyperlink($rec->id);
        }

        $row->article = cat_Products::getShortHyperlink($rec->article);
         
        foreach (array('price', 'pricePc', 'delta', 'quantity') as $fld) {
            if (isset($rec->{$fld})) {
                $row->{$fld} = $Double->toVerbal($rec->{$fld});
            }
        }
    
        return $row;
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
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
        $explodeTitle = explode(' » ', $this->title);
         
        $title = tr("|{$explodeTitle[1]}|*");
    
        return $title;
    }
    

    /**
     * Ако имаме в url-то export създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function exportCsv()
    {
        $exportFields = $this->innerState->listFields;

        $conf = core_Packs::getConfig('core');

        if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
            redirect(array($this), false, '|Броят на заявените записи за експорт надвишава максимално разрешения|* - ' . $conf->EF_MAX_EXPORT_CNT, 'error');
        }

        $csv = '';

        foreach ($exportFields as $caption) {
            $header .= $caption. ',';
        }

         
        if (count($this->innerState->recs)) {
            foreach ($this->innerState->recs as $id => $rec) {
                $rCsv = $this->generateCsvRows($rec);

                
                $csv .= $rCsv;
                $csv .= "\n";
            }

            $csv = $header . "\n" . $csv;
        }

        return $csv;
    }

    
    /**
     * Ще направим row-овете в CSV формат
     *
     * @return string $rCsv
     */
    protected function generateCsvRows_($rec)
    {
        $exportFields = $this->innerState->listFields;
        $rec = self::getVerbal($rec);
        //$rec = frame_CsvLib::prepareCsvRows($rec);
    
        $rCsv = '';
    
        foreach ($rec as $field => $value) {
            $rCsv = '';
    
            foreach ($exportFields as $field => $caption) {
                if ($rec->{$field}) {
                    $value = $rec->{$field};
                    $value = html2text_Converter::toRichText($value);
                    // escape
                    if (preg_match('/\\r|\\n|,|"/', $value)) {
                        $value = '"' . str_replace('"', '""', $value) . '"';
                    }
                    $rCsv .= $value .  ',';
                } else {
                    $rCsv .= '' . ',';
                }
            }
        }
        
        return $rCsv;
    }
}
