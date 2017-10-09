<?php 



/**
 * Модел за създаване на етикети за печатане
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Labels extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Етикети';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Етикет';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/price_tag_label.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'label/tpl/SingleLayoutLabels.shtml';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'label, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $canUselabel = 'label, admin, ceo';
    
    
    /**
     * Роли за мастера на етикетите
     */
    public $canMasterlabel = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools2, plg_State, plg_Created, plg_Rejected, plg_Modified, plg_Search, plg_Clone, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, templateId, printedCnt, Object=Обект, createdOn, createdBy, modifiedOn, modifiedBy';
    

    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, templateId';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%, silent');
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон, silent, input=hidden');
        $this->FLD('params', 'blob(serialize,compress)', 'caption=Параметри, input=none');
        $this->FLD('printedCnt', 'int', 'caption=Отпечатъци, title=Брой отпечатани етикети, input=none');
        $this->FLD('classId', 'class(interface=label_SequenceIntf)', 'caption=Клас, title=Брой отпечатани етикети, silent, input=hidden');
        $this->FLD('objId', 'int', 'caption=Отпечатъци, title=Обект, silent, input=hidden');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Обновява броя на отпечатванията
     * 
     * @param integer $id
     * @param integer $printedCnt
     */
    public static function updatePrintCnt($id, $printedCnt)
    {
        $rec = self::fetch($id);
        $rec->id = $rec->id;
        
        if ($rec->state == 'draft') {
            $rec->state = 'active';
        }
        
        $rec->printedCnt += $printedCnt;
        
        self::save($rec, 'printedCnt, state');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
        $rec = &$form->rec;
    	
        // Вземаме данните от предишния запис
        $readOnlyArr = $dataArr = $rec->params;
        
        // Ако формата не е субмитната и не я редактираме
        if (!$rec->id) {
             // id на шаблона
             $templateId = Request::get('templateId', 'int');
                
             // Ако не е избрано id на шаблона
             if (!$templateId) redirect(array($mvc, 'selectTemplate'));
                
             // Ако се създава етикет от обект, използваме неговите данни
             Request::setProtected('classId, objId');
             $classId = Request::get('classId');
             $objId = Request::get('objId');
             if ($classId && $objId) {
             	  $clsInst = cls::getInterface('label_SequenceIntf', $classId);
             	
                  $arr = (array)$clsInst->getLabelPlaceholders($objId);
                  $readOnlyArr = $dataArr = arr::make($arr, TRUE);
                    
                  $form->setDefault('classId', $objId);
                  $form->setDefault('objId', $classId);
                    
                  $title = cls::get($classId)->getHandle($objId);
                  $title = "#{$title}/" . dt::mysql2verbal(dt::now(), 'd.m.y H:i:s');
                  $form->setDefault('title', $title);
              }
         } else {
             // Полетата, които идват от обекта, да не могат да се редактират
             if ($rec->classId && $rec->objId) {
                  $clsInst = cls::getInterface('label_SequenceIntf', $rec->classId);
                  $readOnlyArr = (array)$clsInst->getLabelPlaceholders($rec->objId);
                  $readOnlyArr = arr::make($readOnlyArr, TRUE);
             }
        }
        
        // Ако няма templateId
        if (!$templateId) {
            
            // Вземаме от записа
            $templateId = $rec->templateId;
            
            // Очакваме вече да има
            expect($templateId);
        }
        
        // Добавяме полетата от детайла на шаблона
        label_TemplateFormats::addFieldForTemplate($data->form, $templateId);
        
        // Обхождаме масива
        foreach ((array)$dataArr as $fieldName => $value) {
            $oFieldName = $fieldName;
            $fieldName = label_TemplateFormats::getPlaceholderFieldName($fieldName);
        	
            // Добавяме данните от записите
            $rec->{$fieldName} = $value;
            
            // Стойностите, които идват от интерфейса не се очаква да ги попълва потребителя
            if ($rec->objId && $rec->classId) {
                if ($form->fields[$fieldName] && isset($readOnlyArr[$oFieldName])) {
                    $form->setField($fieldName, 'input=none');
                }
            }
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$data->form->title = ($data->form->rec->id ? 'Редактиране' : 'Добавяне') . ' на етикет от шаблон|* ';
    	$data->form->title .= '"' . label_Templates::getVerbal($data->form->rec->templateId, 'title') . '"';
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param label_Labels $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        // Инпутваме пак формата, за да може да вкараме silent полетата,
        // които идват от шаблона 
        $form->input(NULL, TRUE);
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Вземаме типа
            $type = $form->rec->type;
            
            // Ако редактираме записа
            if ($form->rec->id) {
                
                // Вземаме записа
                $rec = $mvc->fetch($form->rec->id);
                
                // Вземаме старите стойности
                $oldDataArr = $rec->params;
            }
            
            // Форма за функционалните полета
            $fncForm = cls::get('core_Form');
            
            // Вземаме функционалните полета за типа
            label_TemplateFormats::addFieldForTemplate($fncForm, $form->rec->templateId);
            
            $dataArr = array();
            
            // Обхождаме масива
            foreach ((array)$fncForm->fields as $fieldName => $dummy) {
                
                // Ако има масив за старите данни и новта стойност е NULL
                if ($oldDataArr && ($form->rec->$fieldName === NULL)) {
                    
                    // Използваме старата стойност
                    $dataArr[$fieldName] = $oldDataArr[$fieldName];
                } else {
                    
                    // Добавяме данните от формата
                    $dataArr[$fieldName] = $form->rec->$fieldName;
                }
            }
            
            // Добавяме целия масив към формата
            $form->rec->params = $dataArr;
        }
    }
    
    
    /**
     * След вербалното представяне
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$row->templateId = label_Templates::getHyperlink($rec->templateId, TRUE);
        
        // Показваме линк към обекта, от който е създаден етикета
        if (isset($rec->classId) && isset($rec->objId)) {
        	if(cls::load($rec->classId, TRUE)) {
        		$intfInst = cls::get($rec->classId);
        		if(($intfInst instanceof core_Master) && $intfInst->haveRightFor('single', $rec->objId)) {
        			$row->Object = $intfInst->getLinkToSingle($rec->objId);
        		} else {
        			$row->Object = $intfInst->title;
        		}
        	} else {
        		$row->Object = tr('Проблем при зареждането на класа');
        	}
        }
    }
    
    
    /**
     * Екшън за избор на шаблон
     */
    function act_SelectTemplate()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('add');
        
        // URL за редирект
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this));
        
        Request::setProtected('class, objectId');
        
        // Ако е подаден клас и обект
        $classId = Request::get('class', 'class(interface=label_SequenceIntf)');
        $objId = Request::get('objectId');
        
        $labelDataArr = array();
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        $form->title = "Избор на шаблон";
        
        if ($classId && $objId) {
        	$form->title = 'Избор на шаблон за печат на етикети от|* ' . cls::get($classId)->getFormTitleLink($objId);
        	
            $intfInst = cls::getInterface('label_SequenceIntf', $classId);
            $labelDataArr = (array) $intfInst->getLabelPlaceholders($objId);
            $labelDataArr = arr::make($labelDataArr, TRUE);
        }
       
        // Добавяме функционално поле
        $form->FNC('selectTemplateId', 'key(mvc=label_Templates, select=title, where=#state !\\= \\\'rejected\\\' AND #state !\\= \\\'closed\\\')', 'caption=Шаблон');
        
        $redirect = FALSE;
        $optArr = array();
        
        if (!empty($labelDataArr)) {
        	$templates = label_Templates::getTemplatesByDocument($classId, $objId);
            if (!count($templates)) return new Redirect($retUrl, '|Няма шаблон, който да се използва');
            
            foreach ($templates as $tRec){
                $template = label_Templates::getTemplate($tRec->id);
                $templatePlaceArr = label_Templates::getPlaceHolders($template);
                
                $cnt = 0;
                
                foreach ($templatePlaceArr as $key => $v) {
                    $key = label_TemplateFormats::getPlaceholderFieldName($key);
                   
                    if (isset($labelDataArr[$key])) {
                        $cnt++;
                    }
                }
                
                // Оцветяваме имената на шаблоните, в зависимост от съвпаданието на плейсхолдерите
                $percent = 0;
                $lCnt = count($labelDataArr);
                if ($lCnt) {
                    $percent = ($cnt / $lCnt) * 100;
                }
               
                $dataColor = '#000000';
                if ($percent >= 90) {
                    $dataColor = '#00ff00';
                } elseif ($percent <= 10) {
                    $dataColor = '#999999';
                }

                $opt = new stdClass();
                $opt->attr = array('data-color' => $dataColor);
                $opt->title = label_Templates::getVerbal($tRec, 'title');
                
                $optArr[$tRec->id] = $opt;
            }
            
            $form->setOptions('selectTemplateId', array('' => '') + $optArr);
            
            if (count($optArr) == 1) {
                $redirect = TRUE;
            }
        }
        
        // Въвеждаме полето
        $form->input('selectTemplateId');
        
        // Ако формата е изпратена без грешки
        if ($redirect || $form->isSubmitted()) {
            $templId = $form->rec->selectTemplateId;
            
            // Ако има само една стойност, избираме и редиректваме
            if ($redirect && !$templId) {
                $templId = key($optArr);
            }
            
            $redirectUrl = array($this, 'add', 'templateId' => $templId);
            
            if ($classId && $objId) {
                Request::setProtected('classId, objId');
                $redirectUrl['classId'] = $classId;
                $redirectUrl['objId'] = $objId;
                if($title = Request::get('title', 'varchar')){
                	$redirectUrl['title'] = $title;
                }
            } else {
                foreach ($labelDataArr as $labelName => $val) {
                    $redirectUrl[$labelName] = $val;
                }
            }
            
            if($redirect){
            	$redirectUrl['ret_url'] = $retUrl;
            } else {
            	$redirectUrl['ret_url'] = TRUE;
            }
            
            // Редиректваме към екшъна за добавяне
            return new Redirect($redirectUrl);
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'selectTemplateId';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     * 
     * @param stdClass $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако има права за отпечатване
        if (label_Prints::haveRightFor('print') && label_Labels::haveRightFor('uselabel', $data->rec)) {
            $data->toolbar->addBtn('Отпечатване', array('label_Prints', 'new', 'labelId' => $data->rec->id, 'ret_url' => TRUE), "ef_icon=img/16/print_go.png, order=30, title=Започване на принтиране");
        }
    }
    
    
    /**
     * След подготовка на сингъла
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Данни
        $previewLabelData = new stdClass();
        $previewLabelData->Label = new stdClass();
        $previewLabelData->Label->rec = $data->rec;
        $previewLabelData->Label->id = $data->rec->id;
        $previewLabelData->updateTempData = FALSE;
        
        $previewLabelData->pageLayout = new stdClass();
        $previewLabelData->pageLayout->columnsCnt = 1;
        
        // Подгогвяме етикетите
        $mvc->prepareLabel($previewLabelData);
        
        // Добавяме към данните
        $data->PreviewLabel = $previewLabelData;
    }
    
    
    /**
     * Преди рендиране на сингъла
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    protected static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        // Рендираме етикетите
        $data->row->PreviewLabel = $mvc->renderLabel($data->PreviewLabel);
    }
    
    
    /**
     * Подготвяме етикета
     * 
     * @param object $data
     */
    public static function prepareLabel(&$data)
    {
        $rec = $data->Label->rec;
        
        // Ако няма запис
        if (!$rec) {
            
            // Вземаме записа
            $rec = static::fetch($data->Label->id);
        }
        
        // Ако не е сетната бройката
        setIfNot($data->cnt, 1);
        setIfNot($data->copyCnt, 1);
        
        if (!$data->allCnt) {
            $data->allCnt = $data->cnt * $data->copyCnt;
        }
        
        // Ако няма стойност
        if (!$data->row) {
            
            // Създаваме обект
            $data->row = new stdClass();
        }
        
        // Вземаме шаблона
        $data->row->Template = label_Templates::getTemplate($rec->templateId);
        
        // Вземема плейсхолдерите в шаблона
        $placesArr = label_Templates::getPlaceholders($data->row->Template);
        
        // Параметрите
        $params = $rec->params;
        
        // Плейсхолдери за брой отпечатване и текущ етикет
        $printCntField = label_TemplateFormats::getPlaceholderFieldName('Общо_етикети');
        $currPrintCntField = label_TemplateFormats::getPlaceholderFieldName('Текущ_етикет');
        $currPageCntField = label_TemplateFormats::getPlaceholderFieldName('Страница');
        
        setIfNot($itemsPerPage, $data->pageLayout->itemsPerPage, 1);
        
        // Ако не е зададена стойност за брой отпечатвания
        setIfNot($params[$printCntField], $data->printCnt, $data->cnt, 1);
        
        // Ако не е зададена стойност за текущия отпечатван етикет
        $updatePrintCnt = FALSE;
        if (!$params[$currPrintCntField]) {
            $updatePrintCnt = TRUE;
            $params[$currPrintCntField] = 0;
        }
        
        // Ако не е зададена стойност за текущата страница
        $updatePageCnt = FALSE;
        if (!$params[$currPageCntField]) {
            $updatePageCnt = TRUE;
            $params[$currPageCntField] = 0;
        }
        
        $rowId = 0;
        $perPageCnt = 0;
        $lDataNo = ($data->rec && $data->rec->begin) ? $data->rec->begin : 0;
        
        // Докато достигнем броя на принтиранията
        for ($i = 0; $i < $data->cnt; $i++) {
            
            // Вземаме стойностите на плейсхолдерите от обекта
            if ($rec->objId && $rec->classId) {
                $intfInst = cls::getInterface('label_SequenceIntf', $rec->classId);
                
                $lang = label_Templates::fetchField($rec->templateId, 'lang');
                core_Lg::push($lang);
                $labelDataArr = (array) $intfInst->getLabelData($rec->objId, $lDataNo++);
                core_Lg::pop();
                
                foreach ($labelDataArr as $key => $val) {
                    $key = label_TemplateFormats::getPlaceholderFieldName($key);
                    $params[$key] = $val;
                }
            }
            
            $copyId = 1;
            
            if ($updatePrintCnt) {
                $params[$currPrintCntField]++;
            }
            
            // Ако сме минали на нова страница увеличаваме брояча за страници
            if (($updatePageCnt) && ($perPageCnt % $itemsPerPage == 0)) {
                
                $params[$currPageCntField]++;
            }
            $perPageCnt++;
            
            if (!isset($data->rows[$rowId])) {
                $data->rows[$rowId] = array();
            }
            
            // Обхождаме масива с шаблоните
            foreach ((array)$placesArr as $place) {
                
                // Вземаме името на плейсхолдера
                $fPlace = label_TemplateFormats::getPlaceholderFieldName($place);
                
                // Вземаме вербалната стойност
                $data->rows[$rowId][$place] = label_TemplateFormats::getVerbalTemplate($rec->templateId, $place, $params[$fPlace], $rec->id, $data->updateTempData);
            }
            
            $newCurrPage = FALSE;
            
            // За всяко копие добавяме по едно копие
            for ($copyId; $copyId < $data->copyCnt; $copyId++) {
                $copyField = $rowId + $copyId;
                $data->rows[$copyField] = $data->rows[$rowId];
                
                // При копиятата, ако сме минали на нова страница, да се увеличи брояча за всички следващи копия
                if (($updatePageCnt) && ($perPageCnt % $itemsPerPage == 0)) {
                    
                    $params[$currPageCntField]++;
                    $newCurrPage = label_TemplateFormats::getVerbalTemplate($rec->templateId, $currPageCntField, $params[$currPageCntField], $rec->id, $data->updateTempData);
                }
                
                if ($newCurrPage) {
                    $data->rows[$copyField][$currPageCntField] = $newCurrPage;
                }
                
                $perPageCnt++;
            }
            
            $rowId += $copyId;
        }
    }
    
    
    /**
     * Рендираме етикете
     * 
     * @param object $data
     * @return core_ET - Шаблона, който ще връщаме
     */
    public static function renderLabel(&$data, $labelLayout=NULL)
    {
        // Генерираме шаблона
        $allTpl = new core_ET();
       
        // Брой записи на страница
        setIfNot($itemsPerPage, $data->pageLayout->itemsPerPage, 1);
        
        // Обхождаме резултатите
        foreach ((array)$data->rows as $rowId => $row) {
            
            // Номера на вътрешния шаблон
            $n = $rowId % $itemsPerPage;
            
            // Ако е първа или нямам шаблон
            if ($n === 0 || !$tpl) {
                
                if (is_object($labelLayout)) {
                    // Рендираме изгледа за една страница
                    $tpl = clone $labelLayout;
                } else {
                    $tpl = new ET($labelLayout);
                }
            }
            
            // Заместваме в шаблона всички данни
            $template = label_Templates::placeArray($data->row->Template, $row);
            
            // Вкарваме CSS-a, като инлайн
            $template = label_Templates::addCssToTemplate($data->Label->rec->templateId, $template);
            
            $divStyle = '';
            
            $cCol = $n % $data->pageLayout->columnsCnt;
            
            // За всяка колона без първата и се добавя междината за колоните
            if (isset($data->pageLayout->columnsDist) && ($n !== 0) && ($cCol != 0)) {
                $divStyle =  "margin-left: {$data->pageLayout->columnsDist}; ";
            }
            
            // За всеки ред без първия се добавя междината за редовете
            if (isset($data->pageLayout->linesDist) && $n >= $data->pageLayout->columnsCnt) {
                $divStyle .=  "margin-top: {$data->pageLayout->linesDist};";
            }
            
            if ($divStyle) {
                $divStyle = "style='{$divStyle}'";
            }
            
            $template = "<div {$divStyle}>" . $template . "</div>";
            
            // Заместваме шаблона в таблицата на страницата
            $tpl->replace($template, $n);
            
            // Ако сме на последния запис в страницата или изобщо на последния запис
            if (($rowId == ($data->allCnt - 1)) || ($n == ($itemsPerPage - 1))) {
                
                // Добавяме към главния шаблон
                $allTpl->append($tpl);
            }
        }
        
        // Премахваме незаместените плейсхолдери
        $allTpl->removePlaces();
        
        return $allTpl;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    protected static function on_AfterPrepareRetUrl($mvc, &$res, &$data)
    {
        // Ако е субмитната формата и сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save') {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param label_Labels $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис
        if ($rec) {
            
            // Ако ще добавяме нов
            if ($action == 'add') {
                
                // Вземаме записите
                $templateRec = label_Templates::fetch($rec->templateId);
                
                // Вземаме правата за създаване на етикет
                $requiredRoles = label_Templates::getRequiredRoles('createlabel', $templateRec);
            }
             
            // Ако редактираме
            if ($action == 'edit') {
                
                // Ако е оттеглено
                if ($rec->state == 'rejected') {
                    
                    // Оттеглените да не могат да се редактират
                    $requiredRoles = 'no_one';
                } elseif ($rec->state != 'draft') {
                    
                    // Потреибители, които имат роля за masterLabel могат да редактират
                    $requiredRoles = $mvc->getRequiredRoles('Masterlabel');
                }
            }
            
            // Ако ще се клонира, трябва да има права за добавяне
            if ($action == 'cloneuserdata') {
                if (!$mvc->haveRightFor('add', $rec, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'uselabel') {
                if ($rec->state == 'rejected') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
 	/**
 	 * Изпълнява се след подготовката на формата за филтриране
 	 * 
 	 * @param unknown_type $mvc
 	 * @param unknown_type $data
 	 */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $form->FNC('fState', 'enum(, draft=Чернови, active=Отпечатани)', 'caption=Състояние, allowEmpty,autoFilter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search, fState';
        
        // Инпутваме полетата
        $form->input('fState', 'silent');
        
        // Подреждаме по състояние
        $data->query->orderBy('#state=ASC');
        
        // Подреждаме по дата на модифициране
        $data->query->orderBy('#modifiedOn=DESC');
        
        if ($state = $data->listFilter->rec->fState) {
            $data->query->where(array("#state = '[#1#]'", $state));
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param label_Labels $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!$rec->templateId) {
            expect($rec->id);
            $rec = $mvc->fetch($rec->id);
        }
        // Активираме шаблона
        label_Templates::activateTemplate($rec->templateId);
    }
    
    
    /**
     * Премахваме някои полета преди да клонираме
     * @see plg_Clone
     * 
     * @param label_Labels $mvc
     * @param object $rec
     * @param object $nRec
     */
    protected static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
        unset($nRec->searchKeywords);
        unset($nRec->printedCnt);
        unset($nRec->modifiedOn);
        unset($nRec->modifiedBy);
        unset($nRec->state);
        unset($nRec->exState);
        unset($nRec->lastUsedOn);
        unset($nRec->createdOn);
        unset($nRec->createdBy);
    }
}
