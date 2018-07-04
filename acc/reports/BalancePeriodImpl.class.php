<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата
 * на справка на баланса по определен период
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_BalancePeriodImpl extends frame_BaseDriver
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_BalancePeriodReportImpl';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Балансов отчет по период';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,silent,removeAndRefreshForm=action|grouping1|grouping2|grouping3');
        $form->FLD('from', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=От,mandatory');
        $form->FLD('to', 'key(mvc=acc_Periods,select=title, allowEmpty)', 'caption=До,mandatory');

        $form->FLD('orderField', 'enum(baseQuantity=Начално количество,baseAmount=Начална сума,debitQuantity=Количество дебит,debitAmount=Сума дебит,creditQuantity=Количество кредит,creditAmount=Сума кредит,blQuantity=Крайно количество,blAmount=Крайно салдо)', 'caption=Подредба->Сума,formOrder=110000');
        
        $form->FLD('compare', 'enum(,yes=Да)', 'caption=Предходна година->Сравни,formOrder=110001,maxRadio=1');
    
        $this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {

        // Искаме всички счетоводни периоди за които
        // има изчислени оборотни ведомости
        $balanceQuery = acc_Balances::getQuery();
        $balanceQuery->where('#periodId IS NOT NULL');
        $balanceQuery->orderBy('#fromDate', 'DESC');
        
        while ($bRec = $balanceQuery->fetch()) {
            $b = acc_Balances::recToVerbal($bRec, 'periodId');
            $periods[$bRec->periodId] = $b->periodId;
        }
        
        $form->setOptions('from', array('' => '') + $periods);
        $form->setOptions('to', array('' => '') + $periods);
        
        $form->sethidden('compare');
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
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
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
        $data = new stdClass();

        $data->rec = $this->innerForm;
        $this->prepareListFields($data);

        // от избрания начален период до крайния
        for ($p = $data->rec->from; $p <= $data->rec->to; $p++) {
            $pRec = acc_Periods::fetch($p);
            
            $accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
            $Balance = new acc_ActiveShortBalance(array('from' => $pRec->start, 'to' => $pRec->end, 'accs' => $accSysId, 'cacheBalance' => false));
    
            $data->bData[$p] = $Balance->getBalance($accSysId);
        }
       
        foreach ($data->bData as $period => $date) {
            $data->summary = new stdClass();
            
            foreach ($date as $id => $rec) {
                foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld) {
                    if (!is_null($rec->{$fld})) {
                        $data->summary->{$fld} += $rec->{$fld};
                    }
                }
            }
            
            switch ($data->rec->orderField) {
                case 'debitAmount':
                    $data->recs[] = (object) array('period' => $period, 'debitAmount' => $data->summary->debitAmount);
                    break;
            
                case 'creditAmount':
                     $data->recs[] = (object) array('period' => $period, 'creditAmount' => $data->summary->creditAmount);
                    break;
            
                case 'blAmount':
                    $data->recs[] = (object) array('period' => $period, 'blAmount' => $data->summary->blAmount);
                    break;
            
                case 'baseQuantity':
                    $data->recs[] = (object) array('period' => $period, 'baseQuantity' => $data->summary->baseQuantity);
                    break;
            
                case 'baseAmount':
                    $data->recs[] = (object) array('period' => $period, 'baseAmount' => $data->summary->baseAmount);
                    break;
            
                case 'debitQuantity':
                    $data->recs[] = (object) array('period' => $period, 'debitQuantity' => $data->summary->debitQuantity);
                    break;
            
                case 'creditQuantity':
                    $data->recs[] = (object) array('period' => $period, 'creditQuantity' => $data->summary->creditQuantity);
                    break;
            
                case 'blQuantity':
                    $data->recs[] = (object) array('period' => $period, 'blQuantity' => $data->summary->blQuantity);
                    break;
            }
        }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
        // Подготвяме страницирането
        $data = $res;
        $data->summary = new stdClass();
        // подготвяме страницирането
        $pager = cls::get('core_Pager', array('itemsPerPage' => $mvc->listItemsPerPage));
        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));
       
        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        if (count($data->recs)) {
            foreach ($data->recs as $id => $rec) {
                if (!$pager->isOnPage()) {
                    continue;
                }
            
                $row = new stdClass();
                $row = $mvc->getVerbal($rec);

                $data->rows[$id] = $row;
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
        $tpl = getTplFromFile('acc/tpl/BalancePeriodReportLayout.shtml');
        
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
        
        //$chart = Request::get('Chart');
        //$id = Request::get('id', 'int');
        
        $tpl = $this->getReportLayout();
    
        $title = explode(' » ', $this->title);
         
        $tpl->replace($title[1], 'TITLE');
        
        $form = cls::get('core_Form');
        
        $this->addEmbeddedFields($form);
        
        $form->rec = $data->rec;
        $form->class = 'simpleForm';
        
        $this->prependStaticForm($tpl, 'FORM');
        
        $tpl->placeObject($data->rec);

        // ако имаме записи има и смисъл да
        // слагаме табове
        // @todo да не се ползва threadId  за константа
        //if($data->recs) {
        // слагаме бутони на къстам тулбара
        /*$btnList = ht::createBtn('Таблица', array(
    				'doc_Containers',
    				'list',
    				'threadId' => Request::get('threadId', 'int'),

    		), NULL, NULL,
    				'ef_icon = img/16/table.png');

    		$tpl->replace($btnList, 'buttonList');

    		$btnChart = ht::createBtn('Графика', array(
    				'doc_Containers',
    				'list',
    				'Chart' => 'bar'. $data->rec->containerId,
    				'threadId' => Request::get('threadId', 'int'),

    		), NULL, NULL,
    				'ef_icon = img/16/chart_bar.png');

    		$tpl->replace($btnChart, 'buttonChart');*/
        //}
        
        // подготвяме данните за графиката
   
        /*$labels = array();

        if (is_array($data->recs)) {
            foreach ($data->recs as $id => $rec) {
                $dateRec = dt::mysql2timestamp($rec->currentDate);
                $year = date('Y', $dateRec);
                $month = date ('m', $dateRec);

                $datePreviousRec = dt::mysql2timestamp($rec->previousDate);
                $previousYear = date('Y', $datePreviousRec);

                $current = acc_Periods::getTitleById($rec->periodId);
                $current = substr($current,0, strlen(trim($current))-5);
                $current = dt::$monthsShort[$month-1];

                $labels[] = $current;

                $currentValues [] = abs($data->recs[$id]->amount);
                $previousValues[] = abs($data->recs[$id]->amountPrevious);
            }
        }

        if ($chart == 'bar'.$data->rec->containerId && $data->recs) {
            $bar = array (
                    'legendTitle' => "Легенда",
                    'labels' => $labels,
                    'values' => array (
                            $year => $currentValues,
                            $previousYear => $previousValues,

                    )
            );

            $coreConf = core_Packs::getConfig('doc');
            $chartAdapter = $coreConf->DOC_CHART_ADAPTER;
            $chartHtml = cls::get($chartAdapter);
            $chart =  $chartHtml::prepare($bar,'bar');
            $tpl->append($chart, 'CONTENT');
        } else {

            $f = $this->getFields();

            $table = cls::get('core_TableView', array('mvc' => $f));

            $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

            if($data->pager){
                $tpl->append($data->pager->getHtml(), 'PAGER');
            }
        }*/
        
        $f = $this->getFields();
        
        $table = cls::get('core_TableView', array('mvc' => $f));
        
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        if ($data->pager) {
            $tpl->append($data->pager->getHtml(), 'PAGER');
        }
        
        $embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;
        $f->FLD('periodId', 'varchar');
        
        switch ($this->innerForm->orderField) {
            case 'debitAmount':
                $f->FLD('debitAmount', 'double');
                break;
        
            case 'creditAmount':
                 $f->FLD('creditAmount', 'double');
                break;
        
            case 'blAmount':
                $f->FLD('blAmount', 'double');
                break;
        
            case 'baseQuantity':
                $f->FLD('baseQuantity', 'double');
                break;
        
            case 'baseAmount':
                $f->FLD('baseAmount', 'double');
                break;
        
            case 'debitQuantity':
                $f->FLD('debitQuantity', 'double');
                break;
        
            case 'creditQuantity':
                $f->FLD('creditQuantity', 'double');
                break;
        
            case 'blQuantity':
                $f->FLD('blQuantity', 'double');
                break;
        }
        
        return $f;
    }
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
        switch ($data->rec->orderField) {
            case 'debitAmount':
                $data->listFields = array(
                    'periodId' => 'Период',
                    'debitAmount' => 'Сума дебит',
                );
                break;
                
            case 'creditAmount':
                $data->listFields = array(
                    'periodId' => 'Период',
                    'creditAmount' => 'Сума кредит',
                );
                break;
                
            case 'blAmount':
                $data->listFields = array(
                    'periodId' => 'Период',
                    'blAmount' => 'Крайно салдо',
                );
                break;
                
            case 'baseQuantity':
                $data->listFields = array(
                    'periodId' => 'Период',
                    'baseQuantity' => 'Начално количество',
                );
                break;
                    
            case 'baseAmount':
                $data->listFields = array(
                        'periodId' => 'Период',
                        'baseAmount' => 'Начална сума',
                );
                break;
              
            case 'debitQuantity':
                $data->listFields = array(
                  'periodId' => 'Период',
                  'debitQuantity' => 'Количество дебит',
                );
                break;
              
            case 'creditQuantity':
                $data->listFields = array(
                  'periodId' => 'Период',
                  'creditQuantity' => 'Количество кредит',
                );
                break;
              
            case 'blQuantity':
                $data->listFields = array(
                  'periodId' => 'Период',
                  'blQuantity' => 'Крайно количество',
                );
                break;
        }
    }


    /**
     * Вербалното представяне на записа
     */
    private function getVerbal($rec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $RichtextType = cls::get('type_Richtext');
        
        $row = new stdClass();
        
        $row->periodId = acc_Periods::getTitleById($rec->period);
        $bId = acc_Balances::fetchField("#periodId={$rec->period}", 'id');
        
        if (acc_Balances::haveRightFor('single', $bId)) {
            $row->periodId = ht::createLink($row->periodId, toUrl(array('acc_Balances', 'single', $bId), 'absolute'), false, "ef_icon=img/16/table_sum.png, title = Към баланса за {$row->periodId}");
        }
        
        foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld) {
            if (!is_null($rec->{$fld})) {
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
        $activateOn = "{$this->innerForm->createdOn} 23:59:59";
              
        return $activateOn;
    }


    /**
     * Ако имаме в url-то export създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function exportCsv()
    {
        $conf = core_Packs::getConfig('core');

        if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
            redirect(array($this), false, '|Броят на заявените записи за експорт надвишава максимално разрешения|* - ' . $conf->EF_MAX_EXPORT_CNT, 'error');
        }
        
        $exportFields = $this->innerState->listFields;

        foreach ($this->innerState->recs as $id => $rec) {
            $dataRecs[] = $this->getVerbal($rec);

            foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld) {
                if (!is_null($rec->{$fld})) {
                    $dataRecs[$id]->{$fld} = $rec->{$fld};
                }
            }

            $dataRecs[$id]->periodId = html_entity_decode(strip_tags($dataRecs[$id]->periodId->content));
        }
       
        $fields = $this->getFields();

        $csv = csv_Lib::createCsv($dataRecs, $fields, $exportFields);

        return $csv;
    }
}
