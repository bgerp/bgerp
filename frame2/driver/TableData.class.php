<?php


/**
 * Базов драйвер за справки показващи стандартни таблични данни
 *
 *
 * @category  bgerp
 * @package   frame2
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class frame2_driver_TableData extends frame2_driver_Proto
{
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;
    
    
    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;
    
   
    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Какъв да е класа на групирания ред
     */
    protected $groupByFieldClass = null;
    
    
    /**
     * Дали групиращото поле да е на отделен ред или не
     */
    protected $groupedFieldOnNewRow = true;
    
    
    /**
     * Активиране на таб с графика
     */
    protected $enableChartTab = false;
    
    
    /**
     * Дефолтен етикет на таба за графиката
     */
    protected $chartTabCaption = 'Графика';


    /**
     * Връща заглавието на отчета
     *
     * @param stdClass $rec - запис
     *
     * @return string|NULL - заглавието или NULL, ако няма
     */
    public function getTitle($rec)
    {
        $title = core_Classes::fetchField("#id = {$this->getClassId()}", 'title');
        $title = explode(' » ', $title);
        $title = (count($title) == 2) ? $title[1] : $title[0];
        
        return $title;
    }
    
    
    /**
     * Подготвя данните на справката от нулата, които се записват в модела
     *
     * @param stdClass $rec - запис на справката
     *
     * @return stdClass|NULL $data - подготвените данни
     */
    public function prepareData($rec)
    {
        $data = new stdClass();
        $data->recs = $this->prepareRecs($rec, $data);
        setIfNot($data->groupByField, $this->groupByField);
        setIfNot($data->groupedFieldOnNewRow, $this->groupedFieldOnNewRow);
        setIfNot($data->summaryListFields, $this->summaryListFields);
        setIfNot($data->summaryRowCaption, $this->summaryRowCaption);
        
        return $data;
    }
    
    
    /**
     * Рендиране на данните на справката
     *
     * @param stdClass $rec - запис на справката
     *
     * @return core_ET - рендирания шаблон
     */
    public function renderData($rec)
    {
        $tpl = new core_ET('[#TABS#][#PAGER_TOP#][#TABLE_BEFORE#][#TABLE#][#TABLE_AFTER#][#PAGER_BOTTOM#]');
        
        $data = (is_object($rec->data)) ? $rec->data : new stdClass();
        setIfNot($data->chartTabCaption, $this->chartTabCaption);
        $data->listFields = $this->getListFields($rec);
        $data->rows = array();
        
        if($this->enableChartTab === true){
            $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'urlParam' => "frameTab"));
            
            $url = getCurrentUrl();
            $url[$tabs->getUrlParam()] = "table{$rec->containerId}";
            $tabs->TAB("table{$rec->containerId}", 'Таблица', toUrl($url));
            
            $url[$tabs->getUrlParam()] = "chart{$rec->containerId}";
            $tabs->TAB("chart{$rec->containerId}", $data->chartTabCaption, toUrl($url));
            
            $selectedTab = $tabs->getSelected();
            $data->selectedTab = ($selectedTab) ? $selectedTab : $tabs->getFirstTab();
            
            // Ако има избран детайл от горния таб рендираме го
            if($data->selectedTab == "chart{$rec->containerId}"){
                $dtpl = $this->renderChart($rec, $data);
                $tabCaption = $data->chartTabCaption;
            } else {
                $dtpl = $this->renderTable($rec, $data);
            }
            
            if(!(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'))){
                $tabHtml = $tabs->renderHtml('', $data->selectedTab);
                $tpl->replace($tabHtml, 'TABS');
            } elseif(isset($tabCaption)){
                $tpl->replace("<div>{$tabCaption}</div>", 'TABS');
            }
        } else {
            $dtpl = $this->renderTable($rec, $data);
        }
        
        $tpl->append($dtpl);
        $tpl->removeBlocks();
        $tpl->removePlaces();
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на графиката
     *
     * @param stdCLass $rec
     * @param stdCLass $data
     * @return core_ET $tpl
     */
    protected function renderChart($rec, &$data)
    {
        $tpl = new core_ET("");
        
        return $tpl;
    }
    
    
    /**
     * Подготвя сумиращия ред
     * 
     * @param stdClass $data
     * @param array $fieldsToSumArr
     * 
     * @return void|stdClass $summaryRow
     */
    protected function getSummaryListRow($data, $fieldsToSumArr)
    {
        $summaryRow = new stdClass();
        if(!count($data->recs) || !count($fieldsToSumArr)) {
            
            return;
        }
        
        // Ако има полета за сумиране
        array_walk($data->recs, function ($a) use (&$summaryRow, $fieldsToSumArr){
            foreach ($fieldsToSumArr as $fld){
                if(is_numeric($a->{$fld})){
                    $summaryRow->{$fld} += $a->{$fld};
                }
            }
        });
          
        // Добавяне на сумиращия ред
        $firstKey = key($data->listFields);
        $summaryRow->{$firstKey} = tr($data->summaryRowCaption);
        $summaryRow->_isSummary = true;
        $summaryRow->ROW_ATTR['class'] = 'reportTableDataTotal';
        
        return $summaryRow;
    }
    
    /**
     * Сортира полетата на записите в указаната стойност
     * 
     * @param array $recs
     * @param null|string $sortFld
     * @param null|string $sortDirection
     * 
     * @return void
     */
    private function sortRecsByDirection(&$recs, $sortFld = null, $sortDirection = null)
    {
        if(!isset($sortFld) || !isset($sortDirection)) {
            
            return;
        }
       
        if($sortDirection != 'none'){
            uasort($recs, function($a, $b) use ($sortFld, $sortDirection) {
                return (strip_tags($a->{$sortFld}) > strip_tags($b->{$sortFld})) ? (($sortDirection  == 'up') ? -1 : 1) : (($sortDirection  == 'up')? 1 : -1);
            });
        }
    }
    
    
    /**
     * рендиране на таблицата
     * 
     * @param stdClass $rec
     * @param stdClass $data
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        $tpl = new core_ET('');
     
        // Подготовка на пейджъра
        $itemsPerPage = null;
        if (!(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'))) {
            setIfNot($itemsPerPage, $rec->listItemsPerPage, $this->listItemsPerPage);
            $data->Pager = cls::get('core_Pager', array('itemsPerPage' => $itemsPerPage));
            $data->Pager->setPageVar('frame2_Reports', $rec->id);
        }
        
        // Вербализиране само на нужните записи
        if (is_array($data->recs)) {
            
            // Добавяне на обобщаващия ред, ако е указано да се показва
            $summaryFields = arr::make($data->summaryListFields);
            $fieldsToSumArr = array_intersect($summaryFields, array_keys($data->listFields));
            $summaryRow = $this->getSummaryListRow($data, $fieldsToSumArr);
            
            // Ако е указано сортиране, сортират се записите, ако има сумарен ред той не участва в сортирането
            $sortDirection = Request::get("Sort{$rec->containerId}");
            $sortDirectionArr  = explode('|', $sortDirection);
            $sortFld = !empty($sortDirectionArr[0]) ? $sortDirectionArr[0] : null;
            $sortDirection = !empty($sortDirectionArr[1]) ? $sortDirectionArr[1] : null;
            
            // Ако има поле за групиране, предварително се групират записите
            if (!empty($data->groupByField)) {
                $data->recs = $this->orderByGroupField($data->recs, $data->groupByField, $sortFld, $sortDirection);
            } else {
                $this->sortRecsByDirection($data->recs, $sortFld, $sortDirection);
            }
            
            // Добавяне на сумарния ред, ако има такъв към записите, за да участва в страницирането
            if(is_object($summaryRow)){
                $data->recs = array('_total' => $summaryRow) + $data->recs;
            }
            
            if (isset($data->Pager)) {
                $data->Pager->itemsCount = countR($data->recs);
            }
            
            foreach ($data->recs as $index => $dRec) {
                if (isset($data->Pager) && !$data->Pager->isOnPage()) {
                    continue;
                }
                
                $data->rows[$index] = ($dRec->_isSummary !== true) ? $this->detailRecToVerbal($rec, $dRec) : $dRec;
                
                // Ако реда е обобщаващ вербализира се отделно
                if($dRec->_isSummary === true && count($fieldsToSumArr)){
                    foreach ($fieldsToSumArr as $fld){
                        $data->rows[$index]->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
                        $data->rows[$index]->{$fld} = ht::styleNumber($data->rows[$index]->{$fld}, $dRec->{$fld});
                    }
                }
            }
        }
       
        // Рендиране на пейджъра
        if (isset($data->Pager)) {
            $tpl->replace($data->Pager->getHtml(), 'PAGER_TOP');
            $tpl->replace($data->Pager->getHtml(), 'PAGER_BOTTOM');
        }
        
        // Рендиране на лист таблицата
        $fld = $this->getTableFieldSet($rec);
        $table = cls::get('core_TableView', array('mvc' => $fld));
        
        // Показване на тагове
        if (core_Packs::isInstalled('uiext')) {
            uiext_Labels::showLabels($this, 'frame2_Reports', $rec->id, $data->recs, $data->rows, $data->listFields, $this->hashField, 'Таг', $tpl, $fld);
        }
        
        $filterFields = arr::make($this->filterEmptyListFields, true);
        $filterFields['_tagField'] = '_tagField';
        
        // Ако има поле за групиране
        if (isset($data->groupByField)) {
            $totalRow = $data->rows['_total'];
            unset($data->rows['_total']);
            $found = false;
            
            // Групиране само ако има поне една стойност за групиране
            $groupByField = $data->groupByField;
            
            if (is_array($data->recs)) {
                array_walk($data->recs, function ($r) use ($groupByField, &$found) {
                    if (isset($r->{$groupByField})) {
                        $found = true;
                    }
                });
            }
            
            if ($found === true) {
                $this->groupRows($data->recs, $data->rows, $data->listFields, $data->groupByField, $data);
                $filterFields[$data->groupByField] = $data->groupByField;
            }
           
            if(is_object($totalRow)){
                $data->rows = array('_total' => $totalRow) + $data->rows;
            }
        }
       
        // Филтриране на празните колони и рендиране на таблицата
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, implode(',', $filterFields));
        $tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
        
        return $tpl;
    }
    
    
    /**
     * Подреждане на записите първо по-поле и после групиране по полр
     *
     * @param array    $recs
     * @param string $field
     *
     * @return void
     */
    private function orderByGroupField($recs, $groupField, $sortFld = null, $sortDirection = null)
    {
        $newRecs = array();
        foreach ($recs as $i => $r) {
            
            // Извличане на тези записи от със същата стойност за групиране
            $groupedArr = array($i => $r);
            
            
            $subArr = array_filter($recs, function ($a) use ($r, $groupField) {
                return ($a->{$groupField} == $r->{$groupField});
            });
            
            // Сортират се допълнително ако е указано
            $groupedArr += $subArr;
            $this->sortRecsByDirection($groupedArr, $sortFld, $sortDirection);
            $newRecs += $groupedArr;
        }
       
        return $newRecs;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        uiext_Labels::enable($tpl);
    }
    
    
    /**
     * Групира записите по поле
     *
     * @param array $recs
     * @param array $rows
     * @param array $listFields
     */
    protected function groupRows($recs, &$rows, $listFields, $field, $data)
    {
        if (!count($rows)) {
            
            return;
        }
        $columns = count($listFields);
        
        $groups = array();
        foreach ($rows as $index => $row) {
            $groups[$recs[$index]->{$field}] = $row->{$field};
        }
        
        $newRows = $rowAttr = array();
        $rowAttr['class'] = ' group-by-field-row';
        if(isset($this->groupByFieldClass)){
            $rowAttr['class'] .= " {$this->groupByFieldClass}";
        }
        foreach ($groups as $groupId => $groupVerbal) {
            if ($data->groupedFieldOnNewRow === true) {
                $groupVerbal = ($groupVerbal instanceof core_ET) ? $groupVerbal->getContent() : $groupVerbal;
                $groupVerbal = $this->getGroupedTr($columns, $groupId, $groupVerbal, $data);
                
                $newRows['|' . $groupId] = ht::createElement('tr', $rowAttr, $groupVerbal);
                $newRows['|' . $groupId]->removeBlocks();
                $newRows['|' . $groupId]->removePlaces();
            }
            
            $firstRow = true;
            
            // За всички записи
            foreach ($rows as $index => $row1) {
                $r = $recs[$index];
                if ($r->{$field} == $groupId) {
                    if ($data->groupedFieldOnNewRow === true || ($data->groupedFieldOnNewRow === false && $firstRow !== true)) {
                        unset($rows[$index]->{$field});
                    }
                    
                    if (is_object($rows[$index])) {
                        $newRows[$index] = clone $rows[$index];
                        
                        // Веднъж групирано, премахваме записа от старите записи
                        unset($rows[$index]);
                    }
                    
                    $firstRow = false;
                }
            }
        }
        
        $rows = $newRows;
    }
    
    
    /**
     * Подготовка на реда за групиране
     *
     * @param int      $columnsCount - брой колони
     * @param string   $groupValue   - невербалното име на групата
     * @param mixed   $groupVerbal  - вербалното име на групата
     * @param stdClass $data         - датата
     *
     * @return string - съдържанието на групиращия ред
     */
    protected function getGroupedTr($columnsCount, $groupValue, $groupVerbal, &$data)
    {
        $groupVerbal = "<td style='padding-top:9px;padding-left:5px;' colspan='{$columnsCount}'><b>" . $groupVerbal . '</b></td>';
        
        return $groupVerbal;
    }
    
    
    /**
     * Връща полетата за експортиране във csv
     *
     * @param stdClass $rec
     *
     * @return core_FieldSet $fld
     */
    public function getCsvExportFieldset($rec)
    {
        $fld = $this->getTableFieldSet($rec, true);
        
        return $fld;
    }
    
    
    /**
     * Добавя бутони за сортиране на поле от таблицата
     * 
     * @param string $sortUrlParam
     * @param string $fieldName
     * @param string $fieldCaption
     * 
     * @return string $fieldCaption
     */
    private function addSortingBtnsToField($sortUrlParam, $fieldName, $fieldCaption)
    {
        $direction = Request::get($sortUrlParam);
        $directionArr = (!empty($direction)) ? explode('|', $direction) : null;
        
        // Подготовка на бутоните за сортиране
        $sort = "{$fieldName}|up";
        $img = 'img/icon_sort.gif';
        if(is_array($directionArr)){
            if($directionArr[0] == $fieldName){
                $img = ($directionArr[1] == 'up') ? 'img/icon_sort_up.gif' : (($directionArr[1] == 'down') ? 'img/icon_sort_down.gif' : $img);
                $sort = ($directionArr[1] == 'up') ? "{$fieldName}|down" : (($directionArr[1] == 'down') ? "{$fieldName}|none" : "{$fieldName}|up");
            }
        }
        
        $currUrl = getCurrentUrl();
        $currUrl[$sortUrlParam] = $sort;
        $href = ht::escapeAttr(toUrl($currUrl));
        
        // Добавя се на кепшъна бутони за сортиране
        $captionArr = explode('->', $fieldCaption);
        $startCapttion = (count($captionArr) == 2) ? "{$captionArr[0]}->" : "";
        $midCapttion = (count($captionArr) == 2) ? $captionArr[1] : $captionArr[0];
        
        $fieldCaption = "{$startCapttion}|*<div class='rowtools'><div class='l'>|{$midCapttion}|*</div><a class='r' href='{$href}' ><img  src=" . sbf($img) .
        " width='16' height='16' alt='sort' class='sortBtn'></a></div>";
        
        return $fieldCaption;
    }
    
    
    /**
     * Взима полетата, които ще се показват в листовия изглед на данните
     *
     * @param stdClass $rec    - запис на справката
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return array $da$listFieldsta - полетата за листовия изглед
     */
    protected function getListFields($rec, $export = false)
    {
        $listFields = array();
        $sortUrlParam = "Sort{$rec->containerId}";
        $listFieldsToSort = arr::make($this->sortableListFields, true);
        
        $fieldset = $this->getTableFieldSet($rec, $export);
        $fields = $fieldset->selectFields();
        
        foreach ($fields as $name => $fld) {
            // Ако полето ще се сортира, добавя се функционалност за сортиране
            if(array_key_exists($name, $listFieldsToSort)) {
                $fld->caption = $this->addSortingBtnsToField($sortUrlParam, $name, $fld->caption);
            }
            $listFields[$name] = $fld->caption;
        }
        
        return $listFields;
    }
    
    
    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array $recs                - записите за експорт
     */
    public function getExportRecs($rec, $ExportClass)
    {
        expect(cls::haveInterface('export_ExportTypeIntf', $ExportClass));
        $recsToExport = $this->getRecsForExport($rec, $ExportClass);
        
        $recs = array();
        if (is_array($recsToExport)) {
            foreach ($recsToExport as $dRec) {
                $recs[] = $this->getExportRec($rec, $dRec, $ExportClass);
            }
        }
        
        return $recs;
    }
    
    
    /**
     * Връща редовете, които ще се експортират от справката
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array                      - записите за експорт
     */
    protected function getRecsForExport($rec, $ExportClass)
    {
        return $rec->data->recs;
    }
    
    
    /**
     * Подготовка на реда за експорт във CSV
     *
     * @param stdClass       $rec
     * @param stdClass       $dRec
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return stdClass
     */
    public function getExportRec_($rec, $dRec, $ExportClass)
    {
        return $dRec;
    }
    
    
    /**
     * Кои полета да се следят при обновяване, за да се бие нотификация
     *
     * @param stdClass       $rec
     *
     * @return string
     */
    public function getNewFieldsToCheckOnRefresh($rec)
    {
        return $this->newFieldsToCheck;
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        // Намира се последните две версии
        $query = frame2_ReportVersions::getQuery();
        $query->where("#reportId = {$rec->id}");
        $query->orderBy('id', 'DESC');
        $query->limit(2);
        
        // Маха се последната
        $all = $query->fetchAll();
        unset($all[key($all)]);
        
        // Ако няма предпоследна, бие се нотификация
        if (!count($all)) {
            
            return true;
        }
        
        // Комбинацията при промяна на кои полета ще се следи
        $newFieldsToCheck = $this->getNewFieldsToCheckOnRefresh($rec);
        if (empty($newFieldsToCheck)) {
            
            return false;
        }
        
        $oldRec = $all[key($all)]->oldRec;
        $newValuesToCheck = $oldValuesToCheck = array();
        
        // Извличане на стойностите за следене от текущата версия
        if (is_array($rec->data->recs)) {
            $newValuesToCheck = static::extractFieldsFromArr($rec->data->recs, $newFieldsToCheck);
        }
        
        // Извличане на стойностите за следене от предишната версия
        if (is_array($oldRec->data->recs)) {
            $oldValuesToCheck = static::extractFieldsFromArr($oldRec->data->recs, $newFieldsToCheck);
        }
       
        // Ако има промяна в следената комбинация от полета, да се бие нотификация
        $diff = array_diff_key($newValuesToCheck, $oldValuesToCheck);
        $res = (is_array($diff) && count($diff));
        
        return $res;
    }
    
    
    /**
     * Помощна ф-я за извличане на желаните полета от масив
     * 
     * @param mixed $arr
     * @param mixed $fieldsToCheck
     * 
     * @return array $result
     */
    protected static function extractFieldsFromArr($arr, $fieldsToCheck)
    {
        $fieldsToCheckArr = arr::make($fieldsToCheck, true);
        $result = array_values(array_map(function ($obj) use ($fieldsToCheckArr) {
            $value = array();
            foreach ($fieldsToCheckArr as $fld){
                $value[] = (is_object($obj)) ? $obj->{$fld} : $obj[$fld];
            }
            return implode('|', $value);
        }, $arr));
        
        $result = array_combine($result, $result);
        
        return is_array($result) ? $result : array();
    }
    
    
    /**
     * След полетата за добавяне
     *
     * @param frame2_driver_Proto $Driver   - драйвер
     * @param embed_Manager       $Embedder - ембедър
     * @param core_Fieldset       $fieldset - форма
     */
    protected static function on_AfterAddFields(frame2_driver_Proto $Driver, embed_Manager $Embedder, core_Fieldset &$fieldset)
    {
        $fieldset->FLD('listItemsPerPage', 'int(min=10,Max=100)', "caption=Други настройки->Елементи на страница,after=changeFields,autohide,placeholder={$Driver->listItemsPerPage}");
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param frame2_driver_Proto $Driver   - драйвер
     * @param embed_Manager       $Embedder - ембедър
     * @param core_Fieldset       $fieldset - форма
     */
    protected static function on_BeforeRenderSingleToolbar(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, &$data)
    {
        if ($Driver->enableChartTab === false) return;
        
        // Ако е избран таба с графиката да се предава в урл-то на бутона за принтиране
        $frameTab = Request::get('frameTab');
        if ($frameTab == "chart{$data->rec->containerId}"){
            $printId = plg_Printing::getPrintBtnId($Embedder, $data->rec->id);
            if ($data->toolbar->haveButton($printId)){
                $data->toolbar->setUrlParam($printId, 'frameTab', $frameTab);
            }
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    abstract protected function prepareRecs($rec, &$data = null);
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    abstract protected function detailRecToVerbal($rec, &$dRec);
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    abstract protected function getTableFieldSet($rec, $export = false);
}
