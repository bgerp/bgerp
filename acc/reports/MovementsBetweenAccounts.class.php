<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на кореспонденция по сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_MovementsBetweenAccounts extends frame_BaseDriver
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_reports_CorespondingImpl2';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Обороти между сметки';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Работен кеш
     */
    public $cache = array();
    
    
    /**
     * Работен кеш
     */
    public $cache2 = array();
    
    
    /**
     * Работен кеш
     */
    public $cache3 = array();
    

    /**
     * Кеш за имената на фичърсите
     */
    private $features = array();


    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        // Добавяме полетата за филтър
        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->FLD('to', 'date', 'caption=До,mandatory');
        $form->FLD('compare', 'enum(no=Няма,old=Предходен период,year=Миналогодишен период)', 'caption=Съпоставка,silent');
        $form->FLD('dAcc', 'acc_type_Account(allowEmpty)', 'mandatory,silent,removeAndRefreshForm=df1|df2|df3',
            array('caption' => "|*<span style='color:#ffccaa'>|Дебит|*</span>->|Сметка"));
        $form->FLD('cAcc', 'acc_type_Account(allowEmpty)', 'mandatory,silent,removeAndRefreshForm=cf1|cf2|cf3',
            array('caption' => "|*<span style='color:#aaccff'>|Кредит|*</span>->|Сметка"));
        $form->FLD('order', 'enum(,DESC=Низходящо,ASC=Възходящо)', 'caption=Сортиране->Вид,silent,formOrder=100');
        
        
        $this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        // Информация, че справката не е готова
        $form->info = "<div style='margin:10px;color:red; background-color:yellow; border: dotted 1px red; padding:5px; font-size:1.3em;'>Тази справка е в процес на разработка</div>";

        // Поставяме удобни опции за избор на период
        $op = acc_Periods::getPeriodOptions();
    
        $form->setSuggestions('from', array('' => '') + $op->fromOptions);
        $form->setSuggestions('to', array('' => '') + $op->toOptions);
        $form->setDefault('orderBy', 'DESC');
        $form->setDefault('compare', 'no');
        
        $form->input(NULL, TRUE);
 
        self::addAccFeats($form->rec->dAcc, 'd', $form);
        self::addAccFeats($form->rec->cAcc, 'c', $form);
         
        // Ако е избрано подреждаме
        if(isset($form->rec->orderBy) && $form->rec->orderBy != ''){
            
            if(isset($form->rec->side)){
                if($form->rec->side == 'credit'){
                    $options = arr::make('creditQuantity=Кредит к-во,creditAmount=Кредит сума');
                } elseif($form->rec->side == 'debit') {
                    $options = arr::make('debitQuantity=Дебит к-во,debitAmount=Дебит сума');
                } else {
                    $options = arr::make('debitQuantity=Дебит к-во,debitAmount=Дебит сума,creditQuantity=Кредит к-во,creditAmount=Кредит сума,blQuantity=Остатък к-во,blAmount=Остатък сума');
                }
            
                $form->setOptions('orderField', $options);
            }
        } else {
            $form->setField('orderField', 'input=none');
        }
    
        $this->invoke('AfterPrepareEmbeddedForm', array($form));
    }

    

    /**
     * Добавя след посоченото поле, полета за избор на свойства
     */
    private static function addAccFeats($accId, $prefix, $form)
    {
        if(!$accId) return;

        $aRec = acc_Accounts::fetch($accId);
        
        $color = ($prefix == 'd') ? "#ffccaa" : "#aaccff";
        
        $afterField = $prefix . 'Acc';

        for($i = 1; $i <= 3; $i++) {
            $groupName = "groupId{$i}";
            $afterField = self::addFeatSection($aRec->{$groupName}, $prefix . 'Feat' . $i, $afterField, $form, $color);
        }
    }


    /**
     * Добавя във формата полета за избор на свойство
     */
    private static function addFeatSection($listId, $field, $afterField, $form, $color)
    {
        if($listId) {
            $lRec = acc_Lists::fetch($listId);
            $caption = $listName = acc_Lists::getVerbal($lRec, 'name');
            $caption = "<span style='color:{$color}'>{$caption}</span>";
            
            $valField = $field . 'Val';

            
            $form->FLD($field, 'varchar', "placeholder=Без показване,class=w100,removeAndRefreshForm={$valField},silent", 
                array('caption' => "|*{$caption}->|Признаци", 'after' => $afterField));
            
            // Намираме перата, които с абили използвани в посочения период от посочената номенклатура
            $items = acc_Items::fetchUsedItems($form->rec->from, $form->rec->to, $listId);
                        
            $features = acc_Features::getFeatureOptions(array_keys($items));
            $features = array('' => '') + array('*' => "[{$listName}]") + $features;
            $form->setOptions($field, $features);


            // Отново въвеждаме новото silent поле
            $form->input(NULL, TRUE);

            if($form->rec->{$field}) {
                if($form->rec->{$field} == '*') {
                    $form->FLD($valField, "keylist(mvc=acc_Items,select=title)", "placeholder=Всички", array('caption' => "|*{$caption}->|Стойности", 'after' => $field));
                    $form->setSuggestions($valField, $items);
                } else {
                    $featureTitleId = acc_FeatureTitles::fetchIdByTitle($form->rec->{$field});
                    $featureValuesOpt = acc_Features::getFeatureValueOptions($featureTitleId);
                    $form->FLD($valField, "keylist(mvc=acc_Features,select=value)", "placeholder=Всички", array('caption' => "|*{$caption}->|Стойности", 'after' => $field));
                    $form->setSuggestions($valField, $featureValuesOpt);
                }
                
                $field = $actField = $field . 'Act';
                $form->FLD($actField, 'enum(group=Групиране по признаци,groupItems=Групиране по пера,filter=Филтриране)', array('caption' => "|*{$caption}->|Действие", 'after' => $valField));
            }


        }
        
        return $field;
    }
    

    /**
     * Рендира вътрешната форма като статична форма в подадения шаблон
     *
     * @param core_ET $tpl - шаблон
     * @param string $placeholder - плейсхолдър
     */
    protected function prependStaticForm(core_ET &$tpl, $placeholder = NULL)
    {
        $form = cls::get('core_Form');
    
        $this->addEmbeddedFields($form);
        $form->rec = $this->innerForm;
        $form->class = 'simpleForm';
    
        $tpl->prepend($form->renderStaticHtml(), $placeholder);
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
        // Проверяваме дали началната и крайната дата са валидни
        if($form->isSubmitted()){
            if($form->rec->to < $form->rec->from){
                $form->setError('to, from', 'Началната дата трябва да е по-малка от крайната');
            }
            
            if($form->rec->orderBy == ''){
                unset($form->rec->orderBy);
            }
        }
    }
        
    
    /**
     * Връща ключ за иникланост
     */
    function getJKey($rec)
    {   
        $p = array();

        foreach(array('debitItem1' => 'dFeat1', 'debitItem2' => 'dFeat2', 'debitItem3' => 'dFeat3', 'creditItem1' => 'cFeat1', 'creditItem2' => 'cFeat2', 'creditItem3' => 'cFeat3') as $partItem => $partFeat) {
            $p[] = $r= self::getPeroKey($rec, $partItem, $partFeat);
            if($r === FALSE) return FALSE;
        }

        $key = implode('|', $p);
        
        return $key;
    }


    /**
     * Връща ключа за посочената част
     */
    function getPeroKey($rec, $partItem, $partFeat)
    {
        $formRec = $this->innerForm;
 
        if(!($itemId = $rec->{$partItem})) return '';

        if(!($featTitle = $formRec->{$partFeat})) return '';
        
        $valPart = $partFeat . 'Val';

        $vArr = keylist::toArray($formRec->{$valPart});

        // Филтриране или групиране
        $actPart = $partFeat . 'Act';
        
        if($featTitle == '*') {
            $res = $rec->{$partItem};
        } else {
            $fTitleRec = acc_FeatureTitles::fetch(array("#title = '[#1#]'", $featTitle));
            expect($fTitleRec, $featTitle);
            $fRec = acc_Features::fetch("#itemId = {$itemId} AND #featureTitleId = {$fTitleRec->id}");

            if($fRec) {
                if(!($fId = $this->features[$fRec->value])) {
                    $fId = $this->features[$fRec->value] = $fRec->id;
                }
                $res = -$fId;

                //expect($res != 0, $features, $features[$fRec->value], $fid);
            } else {
                $res = 0;
            }
        }
 
        if($formRec->{$actPart} == 'group' || $formRec->{$actPart} == 'groupItems') {
                
            if(count($vArr) && !$vArr[abs($res)]) {
                $res = '0';
            }

            if($res < 0 && $formRec->{$actPart} == 'groupItems') {
                $res = $rec->{$partItem};
            }

        } else {
            expect($formRec->{$actPart} == 'filter', $formRec, $actPart);
            
            if(count($vArr) && !$vArr[abs($res)]) {
                $res = FALSE;
            } else {
                $res = '';
            }
        }
        
        return $res;
    }


    /**
     * Превръща във вербален запис перо или фичърс
     */
    function idToVerbal($id)
    {
        if($id < 0) {

            return array_search(-$id, $this->features) ;
        } elseif($id == 0) {
            if($id === '') {

                return '';
            } else {

                return tr("Други");
            }
        } else {

            return acc_Items::fetch($id)->title;
        }
    }


    /**
     * Филтрира заявката
     */
    protected function prepareFilterQuery(&$query, $form)
    {
        acc_JournalDetails::filterQuery($query, $form->from, $form->to);

        $query->where("#debitAccId = {$form->dAcc} AND #creditAccId = {$form->cAcc}");
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     */
    public function prepareInnerState()
    {  
        core_App::setTimeLimit(300);
        $data = new stdClass();
        $data->summary = (object)array('debitQuantity' => 0, 'debitAmount' => 0, 'creditQuantity' => 0, 'creditAmount' => 0, 'blQuantity' => 0, 'blAmount' => 0);
        $data->hasSameAmounts = TRUE;
        $data->rows = $data->recs = array();
        $data->rowsOld = $data->recsOld = array();
        $form = &$this->innerForm;
    

        if($form->from > $form->to) {
            list($form->from, $form->to) = array($form->from, $form->to);
        }
        
        if(!$form->dFeat1) {
            $form->dFeat1Act = '';
            $form->dFeat1Val = '';
        }

        if(!$form->dFeat2) {
            $form->dFeat2Act = '';
            $form->dFeat2Val = '';
        }

        if(!$form->dFeat3) {
            $form->dFeat3Act = '';
            $form->dFeat3Val = '';
        }
        
        if(!$form->cFeat1) {
            $form->cFeat1Act = '';
            $form->cFeat1Val = '';
        }

        if(!$form->cFeat2) {
            $form->cFeat2Act = '';
            $form->cFeat2Val = '';
        }

        if(!$form->cFeat3) {
            $form->cFeat3Act = '';
            $form->cFeat3Val = '';
        }

         
        $jQuery = acc_JournalDetails::getQuery();
        
        // Извличаме записите от журнала за периода, където участват основната и кореспондиращата сметка
        $this->prepareFilterQuery($jQuery, $form);
        
        // За всеки запис добавяме го към намерените резултати
        $all = array();

        while($rec = $jQuery->fetch()) {
            $key = $this->getJKey($rec);
            
            if(!$key) continue;

            if(!isset($all[$key])) {
                $all[$key] = new stdClass();
            }

            $all[$key]->creditQuantity += $rec->creditQuantity;
            $all[$key]->debitQuantity += $rec->debitQuantity;
            $all[$key]->amount += $rec->amount;
            $all[$key]->docs[$rec->docType . ':' . $rec->docId] = $rec->reason;
        }
        
        if($form->order) {
            arr::order($all, 'amount', $form->order);
        }

        $p = array();

        foreach($all as $key => $rec) {
            list($p['d1'], $p['d2'], $p['d3'], $p['c1'], $p['c2'], $p['c3']) = explode('|', $key);

            foreach($p as $part => $id) {
                $rec->{$part} = $this->idToVerbal($id);
            }

            $rec->amount = round($rec->amount, 2);
            $rec->creditQuantity = round($rec->creditQuantity, 2);
            $rec->debitQuantity = round($rec->debitQuantity, 2);

            $data->rows[] = $rec;
        }
       
        $data->form = $form;

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$data)
    {   
        // Ако има намерени записи
        if(count($data->recs)){
            
            // Подготвяме страницирането
            $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
            $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
            $data->Pager = $pager;
            $data->Pager->itemsCount = count($data->recs);
            
            // Ако има избрано поле за сортиране, сортираме по него
            arr::order($data->recs, $mvc->innerForm->orderField, $mvc->innerForm->orderBy);
        
            // За всеки запис
            foreach ($data->recs as &$rec){
                
                // Ако не е за текущата страница не го показваме
                if(!$data->Pager->isOnPage()) continue;
                
                // Вербално представяне на записа
                $data->rows[] = $mvc->getVerbalRec($rec, $data);
            }
        }
  
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('acc/tpl/CorespondingReportLayout.shtml');
         
        return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        if(empty($data)) return;
     

        $tpl = $this->getReportLayout();
        // $tpl->replace($this->getReportTitle(), 'TITLE');
        
        $tpl->placeObject($data->summary);
        $tpl->replace(acc_Periods::getBaseCurrencyCode(), 'baseCurrencyCode');

        $cntItem = array();
        for ($i = 0; $i <= count($data->rows); $i++) {
            foreach (range(1, 6) as $l){
                if(!empty($data->rows[$i]->{"item{$l}"})){
                    $cntItem[$l] = "item{$l}";
                }
            }
        }

        if(count($cntItem) <= 1 && count($data->recs) >= 2 ) {
        
            // toolbar
            $btns = $this->generateBtns($data);
    
            if ($this->innerForm->compare == 'year' || $this->innerForm->compare == 'old') {
                $tpl->replace($btns->buttonList, 'buttonList');
                $tpl->replace($btns->buttonBar, 'buttonBar');
            } else {
                $tpl->replace($btns->buttonList, 'buttonList');
                $tpl->replace($btns->buttonPie, 'buttonPie');
            }
        }

        $curUrl = getCurrentUrl();
        
        $pageVar = core_Pager::getPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        
        $f = cls::get('core_FieldSet');

            
        $formRec = $this->innerForm;
 
        $dAccRec = acc_Accounts::fetch($formRec->dAcc);
        
        if($dAccRec->groupId1 && $formRec->dFeat1Act) {
            if(($caption1d = $formRec->dFeat1) == '*') {
                $caption1d = acc_Lists::fetch($dAccRec->groupId1)->name;
            } 

            $f->FLD('d1', 'varchar', "tdClass=itemClass,caption={$caption1d}");
        }

        if($dAccRec->groupId2 && $formRec->dFeat2Act) {
            if(($caption2d = $formRec->dFeat2) == '*') {
                $caption2d = acc_Lists::fetch($dAccRec->groupId2)->name;
            } 

            $f->FLD('d2', 'varchar', "tdClass=itemClass,caption={$caption2d}");
        }

        if($dAccRec->groupId3 && $formRec->dFeat3Act) {
            if(($caption3d = $formRec->dFeat3) == '*') {
                $caption3d = acc_Lists::fetch($dAccRec->groupId3)->name;
            } 

            $f->FLD('d3', 'varchar', "tdClass=itemClass,caption={$caption3d}");
        }

        $cAccRec = acc_Accounts::fetch($formRec->cAcc);
        
        if($cAccRec->groupId1 && $formRec->cFeat1Act) {
            if(($caption1c = $formRec->cFeat1) == '*') {
                $caption1c = acc_Lists::fetch($cAccRec->groupId1)->name;
            } 

            $f->FLD('c1', 'varchar', "tdClass=itemClass,caption={$caption1c}");
        }

        if($cAccRec->groupId2 && $formRec->cFeat2Act) {
            if(($caption2c = $formRec->cFeat2) == '*') {
                $caption2c = acc_Lists::fetch($cAccRec->groupId2)->name;
            } 

            $f->FLD('c2', 'varchar', "tdClass=itemClass,caption={$caption2c}");
        }

        if($cAccRec->groupId3 && $formRec->cFeat3Act) {
            if(($caption3c = $formRec->cFeat3) == '*') {
                $caption3c = acc_Lists::fetch($cAccRec->groupId3)->name;
            } 

            $f->FLD('c3', 'varchar', "tdClass=itemClass,caption={$caption3c}");
        }


        foreach (array('debitQuantity' => "Количество->Дебит", 'creditQuantity' => "Количество->Кредит", 'amount' => "Сума") as $fld => $caption){
            $f->FLD($fld, 'int', 'tdClass=accCell,caption=' . $caption);
        }
        
        foreach($f->fields as $name => $fRec) {
            $data->listFields[$name] = $fRec->caption;
        }
 
            // Рендираме таблицата
            $table = cls::get('core_TableView', array('mvc' => $f));
            $tableHtml = $table->get($data->rows, $data->listFields);
             
            $tpl->replace($tableHtml, 'CONTENT');
             
            // Рендираме пейджъра, ако го има
            if(isset($data->Pager)){
                $tpl->replace($data->Pager->getHtml(), 'PAGER');
            }
             
            // Показваме данните от формата
            $form = cls::get('core_Form');
            $this->addEmbeddedFields($form);

            $form->rec = $this->innerForm;
            $form->class = 'simpleForm';
     
        $this->prependStaticForm($tpl, 'FORM');
         
        $embedderTpl->append($tpl, 'data');
    }
    
    
    /**
     * Вербално представяне на групираните записи
     *
     * @param stdClass $rec - групиран запис
     * @return stdClass $row - вербален запис
     */
    protected function getVerbalRec($rec, $data)
    {
        $row = new stdClass();
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        $Varchar = cls::get('type_Varchar');
 
        // Вербалното представяне на перата
        foreach (range(1, 6) as $i){
            if(!empty($rec->{"item{$i}"})){
                if($this->innerForm->{"feat{$i}"} == '*'){
                    $row->{"item{$i}"} = acc_Items::getVerbal($rec->{"item{$i}"}, 'titleLink');
                } else {
                    $row->{"item{$i}"} = $Varchar->toVerbal($rec->{"item{$i}"});
                    if($row->{"item{$i}"} == 'others'){
                        $row->{"item{$i}"} = tr("|*<i>|Други|*</i>");
                    }
                }
            }
        }
        
        // Вербално представяне на сумите и к-та
        foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
            if(isset($rec->{$fld})){
                $row->{$fld} = $Double->toVerbal($rec->{$fld});
                if($rec->{$fld} < 0){
                    $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
                }
            }
        }
        
        if (isset($rec->delta)) { 
            $row->delta = $rec->delta;
        }
        
        if (isset($rec->measure)) {
           $row->measure = $rec->measure;
        }

        // Връщаме подготвеното вербално рпедставяне
        return $row;
    }
    
    
    /**
     * Подготвя обобщената информация
     *
     * @param stdClass $data
     */
    private function prepareSummary(&$data)
    {
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
         
        foreach ((array)$data->summary as $index => $fld){
            $f = $data->summary->{$index};
            $data->summary->{$index} = $Double->toVerbal($f);
            if($f < 0){
                $data->summary->{$index} = "<span class='red'>{$data->summary->{$index}}</span>";
            }
        }
    }
    
    
    /**
     * Филтриране на записите по код
     * Подрежда кодовете или свойствата във възходящ ред.
     * Ако първата аналитичност са еднакви, сравнява по кодовете на втората ако и те по тези на третата
     */
    private function sortRecs($a, $b)
    {
        if($a->sortField == $b->sortField) return 0;
    
        return (strnatcasecmp($a->sortField, $b->sortField) < 0) ? -1 : 1;
    }
    
    
    /**
     * Групира записите от журнала по пера
     * 
     * @param int      $baseAccountId - Ид на основната сметка
     * @param stdClass $jRec          - запис от журнала
     * @param array    $recs          - групираните записи
     * @return void
     */
    private function addEntry($baseAccountId, $jRec, &$data, $groupBy, $form, $features, &$recs)
    {
        
        // Обхождаме дебитната и кредитната част, И намираме в какви номенклатури имат сметките
        foreach (array('debit', 'credit') as $type){
            if(!isset($this->cache2[$jRec->{"{$type}AccId"}])){
                $this->cache2[$jRec->{"{$type}AccId"}] = acc_Accounts::fetch($jRec->{"{$type}AccId"}, 'groupId1,groupId2,groupId3');
            }
        }
        
        $debitGroups = $this->cache2[$jRec->debitAccId];
        $creditGroups = $this->cache2[$jRec->creditAccId];
        
        $index = array();
        foreach (range(1, 6) as $i){
            if(!empty($form->{"feat{$i}"})){
                foreach (array('debit', 'credit') as $type){
                    $groups = ${"{$type}Groups"};
                    foreach (range(1, 3) as $j){
                        if($groups->{"groupId{$j}"} == $form->{"list{$i}"}){
                            $key = $jRec->{"{$type}Item{$j}"};
                            
                            if($form->{"feat{$i}"} != '*'){
                                $featValue = $features[$key][$form->{"feat{$i}"}];
                                $key = isset($featValue) ? $featValue : 'others';
                            }
                            
                            $jRec->{"column{$i}"} = $key;
                            $index[$i] = $key;
                        }
                    }
                }
            }
        }
        
        // Ако записите няма обект с такъв индекс, създаваме го
        $index = implode('|', $index);
        if(!array_key_exists($index, $recs)){
            $recs[$index] = new stdClass();
            
            foreach (range(1, 6) as $k){
                if(isset($jRec->{"column{$k}"})){
                    $recs[$index]->{"item{$k}"} = $jRec->{"column{$k}"};
                }
            }
        }
        
        // Сумираме записите
        foreach (array('debit', 'credit') as $type){
            
            // Пропускаме движенията от сметката кореспондент
            if($jRec->{"{$type}AccId"} != $baseAccountId) continue;
            
            // Сумираме дебитния или кредитния оборот
            $quantityFld = "{$type}Quantity";
            $amountFld = "{$type}Amount";
            
            $recs[$index]->{$quantityFld} += $jRec->{"{$type}Quantity"};
            $recs[$index]->{$amountFld} += $jRec->amount;
        }
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
        $form = $this->innerForm;
        $newFields = array();

        // Кои полета ще се показват
        if($this->innerForm->compare != 'no'){
            $fromVerbal = dt::mysql2verbal($form->from, 'd.m.Y');
            $toVerbal = dt::mysql2verbal($form->to, 'd.m.Y');
            
            $prefix = (string) $fromVerbal . " - " . $toVerbal;

            $fields = arr::make("debitQuantity={$prefix}->Дебит->К-во,debitAmount={$prefix}->Дебит->Сума,creditQuantity={$prefix}->Кредит->К-во,creditAmount={$prefix}->Кредит->Сума,blQuantity={$prefix}->Остатък->К-во,blAmount={$prefix}->Остатък->Сума,delta={$prefix}->Дял", TRUE);
            
        } else $fields = arr::make("debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума,delta=Дял", TRUE);

        foreach (range(1, 6) as $i){
            if(!empty($form->{"feat{$i}"})){
                if($form->{"feat{$i}"} == '*'){
                    $newFields["item{$i}"] = acc_Lists::getVerbal($form->{"list{$i}"}, 'name');
                } else {
                    $newFields["item{$i}"] = $form->{"feat{$i}"};
                }
            }
        }
        
        if(count($newFields)){
            $fields = $newFields + $fields;
        }
        
        if($this->innerForm->side){
            if($this->innerForm->side == 'debit'){
                unset($fields['creditQuantity'], $fields['creditAmount'], $fields['blQuantity'], $fields['blAmount']);
            }elseif($this->innerForm->side == 'credit'){
                unset($fields['debitQuantity'], $fields['debitAmount'], $fields['blQuantity'], $fields['blAmount']);
            }
        }
        
        if($this->innerForm->compare != 'no'){
            
            if ($data->fromOld != NULL &&  $data->toOld != NULL) {

                $fromOldVerbal = dt::mysql2verbal($data->fromOld, "d.m.Y");
                $toOldVerbal = dt::mysql2verbal($data->toOld, "d.m.Y");
            
            
                
                $prefixOld = (string) $fromOldVerbal . " - " . $toOldVerbal;

                $fieldsCompare = arr::make("debitQuantityCompare={$prefixOld}->Дебит->К-во,
                                            debitAmountCompare={$prefixOld}->Дебит->Сума,
                                            creditQuantityCompare={$prefixOld}->Кредит->К-во,
                                            creditAmountCompare={$prefixOld}->Кредит->Сума,
                                            blQuantityCompare={$prefixOld}->Остатък->К-во,
                                            blAmountCompare={$prefixOld}->Остатък->Сума,
                                            deltaCompare={$prefixOld}->Дял", TRUE);
    
                
                $fields = $fields + $fieldsCompare;
                
                if($this->innerForm->side){ 
                    if($this->innerForm->side == 'debit'){
                        unset($fields['creditQuantityCompare'], $fields['creditAmountCompare'], $fields['blQuantityCompare'], $fields['blAmountCompare']);
                    } elseif($this->innerForm->side == 'credit'){
                        unset($fields['debitQuantityCompare'], $fields['debitAmountCompare'], $fields['blQuantityCompare'], $fields['blAmountCompare']);
                    }
                }
            } 
        }
        
        // Кои полета ще се показват
        $fields = arr::make("debitQuantity=Дебит->К-во,debitAmount=Дебит->Сума,creditQuantity=Кредит->К-во,creditAmount=Кредит->Сума,blQuantity=Остатък->К-во,blAmount=Остатък->Сума,delta=Дял", TRUE);
        $newFields = array();
        $form = $this->innerForm;
         
        foreach (range(1, 6) as $i){
            if(!empty($form->{"feat{$i}"})){
                if($form->{"feat{$i}"} == '*'){
                    $newFields["item{$i}"] = acc_Lists::getVerbal($form->{"list{$i}"}, 'name');
                } else {
                    $newFields["item{$i}"] = $form->{"feat{$i}"};
                }
            }
        }
         
        if(count($newFields)){
            $fields = $newFields + $fields;
        }
         
        if($this->innerForm->side){
            if($this->innerForm->side == 'debit'){
                unset($fields['creditQuantity'], $fields['creditAmount'], $fields['blQuantity'], $fields['blAmount']);
            }elseif($this->innerForm->side == 'credit'){
                unset($fields['debitQuantity'], $fields['debitAmount'], $fields['blQuantity'], $fields['blAmount']);
            }
        }
        $data->listFields = $fields;
    }

    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
        $innerState = &$this->innerState;
        if(count($innerState->rows)){
            foreach ($innerState->rows as $row){
                foreach (array('debitAmount', 'debitQuantity','creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
                    unset($row->{$fld});
                }
            }
        }
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
     * Ако имаме в url-то export създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * $todo да се замени в кода
     */
    public function exportCsv()
    {

        $conf = core_Packs::getConfig('core');
        
        if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
            redirect(array($this), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
        }
        
        $exportFields = $this->innerState->listFields;
        
        $fields = $this->getFields();
        
        arr::order($this->innerState->recs, $this->innerForm->orderField, $this->innerForm->orderBy);
        
        $rows = $this->prepareEmbeddedData()->rows; 
        
        if (count($this->innerState->recs)) {
            foreach($this->innerState->recs as $id => $rec) {
        
                $dataRecs[] = $this->getVerbalRec($rec, $data);
                foreach (array('debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount', 'plus', 'minus','quantity','sum') as $fld){
                    if(!is_null($rec->{$fld})){
                        $dataRecs[$id]->{$fld} = $rec->{$fld};
                    }
                }
                if(!is_null($rec->delta)){
                    $dataRecs[$id]->delta = str_replace("&nbsp;", '', $rec->delta);
                }
                
                if(!is_null($rec->measure)){
                    $dataRecs[$id]->measure = $rows[$id]->measure;
                }
                
                if(!is_null($rec->valior)){
                    $dataRecs[$id]->valior = $rec->valior;
                }
                
                foreach (array('item1', 'item2', 'item3', 'item4', 'item5', 'item6') as $fld1){
                    if(!is_null($rec->{$fld1})){ 
                        $dataRecs[$id]->{$fld1} = str_replace("&nbsp;", '', $dataRecs[$id]->{$fld1});
                        $dataRecs[$id]->{$fld1} = trim(html_entity_decode(strip_tags($dataRecs[$id]->{$fld1})));
                    } 
                }
            }
    
            foreach($exportFields as $caption => $name) {
                if($caption == 'creditAmount') {
                    unset($exportFields[$caption]);
                    $exportFields['sum'] = 'Сума';
                }
                
                if($caption == 'blAmountCompare') {
                    unset($exportFields[$caption]);
                }
       
            }
    
            $csv = csv_Lib::createCsv($dataRecs, $fields, $exportFields);
            
            return $csv;
        } 
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
        $f->FLD('debitQuantity', 'double');
        $f->FLD('debitAmount', 'double');
        $f->FLD('creditQuantity', 'double');
        $f->FLD('creditAmount', 'double');
        $f->FLD('blQuantity', 'double');
        $f->FLD('blAmount', 'double');
        $f->FLD('quantity', 'double');
        $f->FLD('sum', 'double');
        $f->FLD('valior', 'date');
        $f->FLD('delta', 'varchar');
        $f->FLD('measure', 'varchar');

        return $f;
    }

    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
        $baseSysId = acc_Accounts::fetchField($this->innerForm->baseAccountId, 'systemId');
        $corrSysId = acc_Accounts::fetchField($this->innerForm->corespondentAccountId, 'systemId');
        $title = tr("|Кореспонденция на сметки|* {$baseSysId} / {$corrSysId}");
        
        return $title;
    }

    
    /**
     * Генериране на бутоните за тулбара
     * 
     * @param stdClass $data
     * @return StdClass
     */
    public function generateBtns($data)
    {

         $curUrl = getCurrentUrl();
    
        $pageVar = core_Pager::getPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        
        $pagePie = $pageVar . "_pie";
        $pageBar = $pageVar . "_bar";
       
        if ($curUrl["{$pageVar}_pie"] || $curUrl["{$pageVar}_bar"] ) { 
            unset ($curUrl["{$pageVar}_pie"]);
            unset ($curUrl["{$pageVar}_bar"]);
        }
        $realUrl = $curUrl;
   
        // правим бутони за toolbar
        $btnList = ht::createBtn('Таблица', $realUrl, NULL, NULL,
                'ef_icon = img/16/table.png');
        
        $curUrl[$pagePie] = $this->EmbedderRec->that;
        $urlPie = $curUrl;
        
        $btnPie = ht::createBtn('Графика', $urlPie, NULL, NULL,
                'ef_icon = img/16/chart_pie.png');
        
        $realUrl[$pageBar] = $this->EmbedderRec->that;
        $urlBar = $realUrl;
        
        $btnBar = ht::createBtn('Сравнение', $urlBar, NULL, NULL,
                'ef_icon = img/16/chart_bar.png');
        
        $btns = array();
        
        $btns = (object) array('buttonList' => $btnList, 'buttonPie' => $btnPie, 'buttonBar' => $btnBar);
        
        return $btns;
    }
    
    
    /**
     * Изчертаване на графиката
     * 
     * @param stdClass $data
     * @return core_ET
     */
    protected function generateChartData ($data)
    {
        //arr::order($data->recs, $this->innerForm->orderField, $this->innerForm->orderBy);
    
        $arr = array();
        $dArr = array();

        foreach ($data->recs as $id => $rec) { 

            $value = abs($rec->{$this->innerForm->orderField});
            
            if ($rec->item1 || $rec->item2 || $rec->item3 || $rec->item4 || $rec->item5 || $rec->item6) { 
                // правим масив с всички пера и стойност 
                // сумирано полето което е избрали във формата
                if(!array_key_exists($id, $dArr)){ 
                
                    $dArr[$id] =
                    (object) array ('item1' => $rec->item1,
                            'item2' => $rec->item2,
                            'item3' => $rec->item3,
                            'item4' => $rec->item4,
                            'item5' => $rec->item5,
                            'item6' => $rec->item6,
                            'value' => $value
                
                    );
                // в противен случай го ъпдейтваме
                } else {
                     
                    $obj = &$dArr[$id];
        
                    $obj->item1 = $rec->item1;
                    $obj->item2 = $rec->item2;
                    $obj->item3 = $rec->item3;
                    $obj->item4 = $rec->item4;
                    $obj->item5 = $rec->item5;
                    $obj->item6 = $rec->item6;
                    $obj->value = $value;
                }
            } else {
                if(!array_key_exists($id, $dArr)){
            
                    $dArr[$id] =
                    (object) array ('valior' => $rec->valior,
                            'value' => $rec->sum,
                            'valueNew' => $rec->sumNew
                             
                    );
                } else {
                    $obj = &$dArr[$id];
                     
                    $obj->valior = $rec->valior;

                    $obj->value = $rec->sum;
                    $obj->valueNew = $rec->sumNew;
                }
            }
        }

        
        $value1 = array();
        $value2 = array();

        foreach ($dArr as $id=>$rec){
            
            if ($rec->valior) { 
                $m = date('m', strtotime($rec->valior));
                $y = date('Y', strtotime($rec->valior));

                $labels[] = dt::getMonth($m, 'F', 'bg');
                $value1[] = $rec->value;
                $value2[] = $rec->valueNew;
            }
        }
        
        usort($dArr, function($a, $b) {
             
          return ($a->value > $b->value) ? -1 : 1;
        });
        
        $arr = $this->preparePie($dArr, 12);

        $title = '';
        foreach ($arr as $id => $recSort) {
            //$title = str::limitLen($recSort->title, 19);
            $title = $recSort->title;
            $info["{$title}"] = $recSort->value;
        }

        $pie = array (
                    'legendTitle' => $this->getReportTitle(),
                    'suffix' => "лв.",
                    'info' => $info,
        );

        $yFrom = date('Y', strtotime($data->rec->from));
        $yTo = date('Y', strtotime($data->rec->to));
        
        if($yFrom ==  $yTo) {
            $year = $yFrom;
            $yearPrev = $yFrom -1;
        } else {
            $year = $yFrom . "-" . $yTo;
            $yPrevFrom = $yFrom - 1;
            $yPrevTo= $yTo - 1;
            $yearPrev = $yPrevFrom . "-" . $yPrevTo;
        }

        $bar = array(
                'legendTitle' => $this->getReportTitle(),
                'labels' => $labels,
                'values' => array(
                        $yearPrev => $value2,
                        $year => $value1
                )
        );

        $chartData = array();
        $chartData[] = (object) array('type' => 'pie', 'data' => $pie);
        $chartData[] = (object) array('type' => 'bar', 'data' => $bar);
    
        return $chartData;
    }
    
    
    protected function getChartPie ($data)
    {
        $ch = $this->generateChartData($data);

        $coreConf = core_Packs::getConfig('doc');
        $chartAdapter = $coreConf->DOC_CHART_ADAPTER;
        $chartHtml = cls::get($chartAdapter);

        $chart =  $chartHtml::prepare($ch[0]->data,$ch[0]->type);

        return $chart;
    }
    
    
    protected function getChartBar ($data)
    {
        $ch = $this->generateChartData($data);

        $coreConf = core_Packs::getConfig('doc');
        $chartAdapter = $coreConf->DOC_CHART_ADAPTER;
        $chartHtml = cls::get($chartAdapter);

        $chart =  $chartHtml::prepare($ch[1]->data,$ch[1]->type);
    
        return $chart;
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
        $newArr = array();
        
        foreach ($data as $key => $rec) {
            // Вземаме всички пера като наредени н-орки
            $title = '';
            foreach (range(1, 6) as $i){
                if(!empty($rec->{"item{$i}"})){

                    $title .= $rec->{"item{$i}"} . "|";
                }
            }

            $newArr[$key] =
                    (object) array ('title' => substr($title, 0,strlen($title)-1),
                                    'value' => $rec->value
                
                            );
        }

        // броя на елементите в получения масив
        $cntData = count($data);
    
        // ако, числото което сме определили за новия масив
        // е по-малко от общия брой елементи
        // на подадения масив
        if ($cntData <= $n) {
   
            // връщаме направо масива
            foreach($newArr as $id => $rec) {

                // Вербалното представяне на перата
                $t = explode("|", $rec->title);
                $titleV = '';
                for ($i=0; $i <= count($t) -1; $i++) {
                    $titleV .= acc_Items::getVerbal($t[$i], 'title'). "|";
                }
                $titleV = substr($titleV, 0,strlen($titleV)-1);
            
                $res[] = (object) array ('key' => $k, 'title' => $titleV, 'value' => $rec->value);
            }
    
            return $res;
    
            //в противен случай
        } else {
            // взимаме първите n елемента от сортирания масив
            
            for($k = 0; $k <= $n -1; $k++) {

                // Вербалното представяне на перата
                $t = explode("|", $newArr[$k]->title);
                $titleV = '';
                for ($i=0; $i <= count($t) -1; $i++) {
 
                    $titleV .= acc_Items::getVerbal($t[$i], 'title'). "|";
                }
                $titleV = substr($titleV, 0,strlen($titleV)-1);

                $res[] = (object) array ('key' => $k, 'title' => $titleV, 'value' => $newArr[$k]->value);
            }

            // останалите елементи ги събираме
            for ($i = $n; $i <= $cntData; $i++){

                $sum += $newArr[$i]->value;
            }
    
            // ако имаме изрично зададено име за обобщения елемент
            if ($otherName) {
                // използваме него и го добавяме към получения нов масив с
                // n еленета и сумата на останалите елементи
                $res[] = (object) array ('key' => $n+1, 'title' => $otherName, 'value' => $sum);
                // ако няма, използваме default
            } else {
                $res[] = (object) array ('key' => $n+1,'title' => "Други", 'value' => $sum);
            }
        }

        return $res;
    }
}
