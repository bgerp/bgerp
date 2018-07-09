<?php 

/**
 * Медии за отпечатване
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_Prints extends core_Master
{
    /**
     * Заглавие на модела
     */
    public $title = 'Серии за отпечатване';
    
    
    public $singleTitle = 'Отпечатък';
    
    
    /**
     * Кой има право да чете?
     */
    public $canChangestate = 'label, admin, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'label, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'seeLabel, label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    public $canReject = 'seeLabel, label, admin, ceo';
    
    
    /**
     * Кой има право да принтира етикети
     */
    public $canPrint = 'label, admin, ceo';
    
    
    /**
     * Кой има право да регенерира?
     */
    public $canRegenerate = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_Created, plg_Modified, plg_State, plg_RefreshRows, plg_Search, plg_Sorting, plg_rowTools2, plg_Clone, plg_Rejected, plg_LastUsedKeys';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'templateId';
    
    
    /**
     * Кои полета да не се клонират
     */
    public $fieldsNotToClone = 'searchKeywords,printedCnt,modifiedOn,modifiedBy,state,exState,lastUsedOn,createdOn,createdBy, rows';
    
    
    /**
     * Стойност по подразбиране на състоянието
     *
     * @see plg_State
     */
    public $defaultState = 'active';
    
    
    /**
     *
     * @see plg_RefreshRows
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, mediaId=Медия, source=Източник, templateId, labelsCnt=Брой->Етикети, copiesCnt=Брой->Копия, printedCnt=Брой->Отпечатвания, createdOn, createdBy';
    
    
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'mediaId, templateId, title, labelsCnt, classId';
    
    
    public $singleLayoutFile = 'label/tpl/SingleLayoutPrints.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title, where=#state !\\= \\\'rejected\\\' AND #state !\\= \\\'closed\\\',allowEmpty)', 'caption=Шаблон, mandatory, silent, removeAndRefreshForm');
        $this->FLD('mediaId', 'key(mvc=label_Media, select=title)', 'caption=Медия, silent, mandatory, notNull, removeAndRefreshForm=labelsCnt');
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%, silent, input');
        
        $this->FLD('labelsCnt', 'int(min=1, max=10000)', 'caption=Брой етикети, mandatory, silent');
        $this->FLD('copiesCnt', 'int(min=1, max=1000)', 'caption=Брой копия, value=1, mandatory, silent');
        
        $this->FLD('printedCnt', 'int', 'caption=Брой отпечатвания, mandatory, notNull, input=none');
        
        $this->FLD('state', 'enum(, active=Активно, closed=Отпечатано, rejected=Оттеглено)', 'caption=Състояние, input=none, notNull, refreshForm, allowEmpty');
        
        $this->FLD('classId', 'class(interface=label_SequenceIntf)', 'caption=Клас, silent, input=hidden');
        $this->FLD('objectId', 'int', 'caption=Обект, title=Обект, silent, input=hidden');
        
        $this->FLD('params', 'blob(serialize,compress)', 'caption=Параметри, input=none');
        
        $this->FLD('rows', 'blob(1000000,serialize,compress)', 'caption=Кеш, input=none');
        
        $this->setDbIndex('createdOn');
        $this->setDbIndex('templateId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if (!$rec->id) {
            $form->title = 'Създаване на етикет';
        }
        
        if ($rec->classId && $rec->objectId) {
            $form->title = 'Създаване на етикет към|* ' . cls::get($rec->classId)->getLabelSourceLink($rec->objectId);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        Request::setProtected(array('classId, objectId'));
        
        $form = $data->form;
        $rec = $form->rec;
        
        // Ако е подаден клас и обект
        $classId = $rec->classId;
        $objId = $rec->objectId;
        $templateId = $rec->templateId;
        
        $labelDataArr = array();
        
        $oLang = core_Lg::getCurrent();
        
        if (isset($classId, $objId)) {
            $intfInst = cls::getInterface('label_SequenceIntf', $classId);
            
            $lang = '';
            if ($rec->templateId) {
                $lang = label_Templates::fetchField($rec->templateId, 'lang');
            }
            core_Mode::push('prepareLabel', true);
            if ($lang) {
                core_Lg::push($lang);
                $oLang = $lang;
            }
            $labelDataArr = $intfInst->getLabelPlaceholders($objId);
            if ($lang) {
                core_Lg::pop();
            }
            core_Mode::pop('prepareLabel');
        }
        
        // Определяме най-добрия шаблон
        if (!empty($labelDataArr)) {
            $templatesArr = label_Templates::getTemplatesByDocument($classId, $objId);
            if (!count($templatesArr)) {
                
                return followRetUrl(null, '|Няма шаблон, който да се използва', 'error');
            }
            
            foreach ($templatesArr as $tRec) {
                $template = label_Templates::getTemplate($tRec->id);
                $templatePlaceArr = label_Templates::getPlaceHolders($template);
                
                // Игнорират се системните плейсхолдъри, те ще са винаги удовлетворени
                $templatePlaceArr = array_diff($templatePlaceArr, label_Templates::$systemPlaceholders);
                
                $cnt = 0;
                foreach ($labelDataArr as $key => $v) {
                    if (isset($templatePlaceArr[$key])) {
                        if (isset($v->importance) && ($v->importance >= 0)) {
                            $cnt += $v->importance;
                        } else {
                            $cnt++;
                        }
                    } else {
                        if ($v->importance) {
                            if ($v->importance < 0) {
                                $cnt += $v->importance;
                            } else {
                                $cnt -= $v->importance;
                            }
                        }
                    }
                }
                
                // Оцветяваме имената на шаблоните, в зависимост от съвпаданието на плейсхолдерите
                $percent = 0;
                $lCnt = count($templatePlaceArr);
                if ($lCnt) {
                    $percent = ($cnt / $lCnt) * 100;
                }
                
                $dataColor = '#f2c167';
                if ($percent >= 90) {
                    $dataColor = '#a0f58d';
                } elseif ($percent <= 10) {
                    $dataColor = '#f35c5c';
                }
                
                $opt = new stdClass();
                $opt->attr = array('data-color' => $dataColor);
                $opt->title = label_Templates::getVerbal($tRec, 'title');
                
                $optArr[$tRec->id] = $opt;
            }
            
            // Сортиране по цвят
            uasort($optArr, function ($a, $b) {
                
                return strcmp($a->attr['data-color'], $b->attr['data-color']);
            });
            
            $form->setOptions('templateId', array('' => '') + $optArr);
            
            $defOptKey = $mvc->getDefaultTemplateId($optArr, $rec->classId);
            
            $form->setDefault('templateId', $defOptKey);
        }
        
        $className = '';
        if (Mode::is('screenMode', 'wide')) {
            $className = 'floatedElement ';
            $form->class .= " {$className}";
        }
        
        // Показваме допълнителните полета за плейсхолдерите
        if ($rec->templateId) {
            $lang = label_Templates::fetchField($rec->templateId, 'lang');
            
            core_Lg::push($lang);
            
            // Ако е променен езика, вземаме данните пак
            if ($lang != $oLang) {
                if ($classId && $objId) {
                    $labelDataArr = $intfInst->getLabelPlaceholders($objId);
                }
            }
            
            // При редакция да се попълват стойностите
            if ($rec->id) {
                foreach ((array) $rec->params as $fieldName => $val) {
                    if (!$labelDataArr[$fieldName]) {
                        $fieldName = label_TemplateFormats::getPlaceholderFieldName($fieldName);
                        if (!$labelDataArr[$fieldName]) {
                            $labelDataArr[$fieldName] = new stdClass;
                        }
                    }
                    $labelDataArr[$fieldName]->example = $val;
                }
            }
            
            core_Lg::pop();
            
            // Добавяме полетата от детайла на шаблона
            label_TemplateFormats::addFieldForTemplate($form, $rec->templateId);
            
            // Обхождаме масива
            foreach ((array) $labelDataArr as $fieldName => $v) {
                $fieldName = label_TemplateFormats::getPlaceholderFieldName($fieldName);
                
                if (!$form->fields[$fieldName]) {
                    continue;
                }
                
                if (!$form->cmd || $form->cmd == 'refresh') {
                    // Добавяме данните от записите
                    $rec->{$fieldName} = $v->example;
                    Request::push(array($fieldName => $v->example));
                }
                
                if ($v->hidden) {
                    $form->setField($fieldName, 'input=hidden');
                } elseif ($v->readonly) {
                    $form->setReadonly($fieldName);
                }
            }
            
            $form->input(null, true);
        }
        
        if ($rec->templateId) {
            // Трябва да има зададена медия за шаблона
            $mediaArr = label_Templates::getMediaForTemplate($rec->templateId);
            
            $form->setOptions('mediaId', $mediaArr);
            
            if (empty($mediaArr)) {
                $form->setError('templateId', 'Няма добавена медия за шаблона');
            } else {
                $form->setDefault('mediaId', key($mediaArr));
            }
        }
        
        $estCnt = null;
        
        if ($classId && $objId) {
            $mvc->requireRightFor('add', (object) array('classId' => $classId, 'objectId' => $objId));
            
            $lName = $intfInst->getLabelName($objId);
            if ($lName) {
                $form->setDefault('title', $lName);
            }
            
            $estCnt = $intfInst->getLabelEstimatedCnt($objId);
        }
        
        if (!$estCnt && $rec->mediaId) {
            $estCnt = label_Media::getCountInPage($rec->mediaId);
        }
        
        setIfNot($estCnt, 1);
        
        $form->setDefault('labelsCnt', $estCnt);
        $form->setDefault('copiesCnt', 1);
    }
    
    
    /**
     * Намира най-добрият шаблон за използване и връща id-то му
     *
     * @param array    $optArr
     * @param NULL|int $classId
     *
     * @return int
     */
    protected static function getDefaultTemplateId($optArr, $classId = null)
    {
        $qLimit = 5;
        
        $query = self::getQuery();
        if ($classId) {
            $query->where(array("#classId = '[#1#]'", $classId));
        }
        
        if (!empty($optArr)) {
            $optKeysArr = array_keys($optArr);
            $optKeysArr = arr::make($optKeysArr, true);
            
            $query->in('templateId', $optKeysArr);
        }
        
        $query->where(array("#createdBy = '[#1#]'", core_Users::getCurrent()));
        
        $query->orderBy('createdOn', 'DESC');
        
        $query->limit($qLimit);
        
        $tArr = array();
        while ($rec = $query->fetch()) {
            $tArr[$rec->templateId] += 1 + ($qLimit-- * 0.1);
        }
        
        if (empty($tArr)) {
            reset($optArr);
            $defOptKey = key($optArr);
        } else {
            if (count($tArr) > 1) {
                arsort($tArr);
            }
            reset($tArr);
            $defOptKey = key($tArr);
        }
        
        return $defOptKey;
    }
    
    
    /**
     * Извиква се селед подготвяне на тулбара
     *
     * @param label_Prints $mvc
     * @param core_Form    $form
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $form = $data->form;
        $toolbar = $form->toolbar;
        
        if (!$form->cmd || $form->cmd = 'refresh') {
            $toolbar->removeBtn('save');
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Записване на данните, order=1');
        $form->toolbar->addSbBtn('Печат', 'print', 'ef_icon = img/16/printer.png, title = Запис и отпечатване на данните, order=2');
        $form->toolbar->addSbBtn('Изглед', 'view', 'ef_icon = img/16/view.png, title = Преглед на данните, order=3');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param label_Prints $mvc
     * @param core_Form    $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        $refreshForm = array();
        
        // Попълваме стойностите на плейсхолдерите
        
        if ($rec->templateId) {
            $oldDataArr = array();
            
            // Ако редактираме записа
            if ($rec->id) {
                
                // Вземаме записа
                $oRec = $mvc->fetch($rec->id);
                
                // Вземаме старите стойности, ако не сме променили шаблона
                if ($oRec->templateId == $rec->templateId) {
                    $oldDataArr = $oRec->params;
                }
            }
            
            // Форма за функционалните полета
            $fncForm = cls::get('core_Form');
            
            // Вземаме функционалните полета за типа
            label_TemplateFormats::addFieldForTemplate($fncForm, $rec->templateId);
            
            $dataArr = array();
            
            // Обхождаме масива
            foreach ((array) $fncForm->fields as $fieldName => $dummy) {
                
                // Ако има масив за старите данни и новта стойност е NULL
                if (!empty($oldDataArr) && ($rec->{$fieldName} === null)) {
                    
                    // Използваме старата стойност
                    $dataArr[$fieldName] = $oldDataArr[$fieldName];
                } else {
                    
                    // Добавяме данните от формата
                    $dataArr[$fieldName] = $rec->$fieldName;
                }
                
                $refreshForm[$fieldName] = $fieldName;
            }
            
            // Добавяме целия масив към формата
            $rec->params = $dataArr;
        }
        
        // Показваме предупреждение, ако ше има празни пространства в една страница на медията
        if ($form->isSubmitted()) {
            $labelsCnt = label_Media::getCountInPage($rec->mediaId);
            
            $allPirntsCnt = $rec->labelsCnt * $rec->copiesCnt;
            
            if ($allPirntsCnt % $labelsCnt) {
                $form->setWarning('labelsCnt, copiesCnt', "Броят на етикетите не се дели на|* {$labelsCnt}. |Ще има неизползвана част от медията|*.");
            }
        }
        
        if ($form->isSubmitted()) {
            if ($rec->classId && $rec->objectId) {
                $intfInst = cls::getInterface('label_SequenceIntf', $rec->classId);
                
                $estCnt = $intfInst->getLabelEstimatedCnt($rec->objectId);
                
                // Ако излезем над разрешената стойност
                if (isset($estCnt) && $rec->labelsCnt > $estCnt) {
                    $form->setWarning('labelsCnt', "Надвишавате допустимата бройка|* - {$estCnt}");
                }
            }
        }
        
        if ($form->isSubmitted()) {
            $rec->rows = null;
        }
        
        // Рендираме изглед, ако има параметри
        if ($rec->templateId) {
            $renderView = false;
            
            if ($rec->id) {
                $renderView = true;
            }
            
            if (!$renderView && ($form->isSubmitted() || $form->cmd == 'refresh')) {
                $renderView = true;
            }
            
            if (!$renderView) {
                foreach ($rec->params as $pVal) {
                    if (isset($pVal)) {
                        $renderView = true;
                        break;
                    }
                }
            }
            
            if (!$renderView) {
                $placeArr = label_TemplateFormats::getAddededPlaceHolders($rec->templateId);
                if (empty($placeArr)) {
                    $renderView = true;
                }
            }
            
            if ($renderView) {
                $form->layout = $form->renderLayout();
                
                $tpl = new ET("<div class='preview-holder floatedElement' style='display: inline-block; min-width: 0;'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Етикет') . "</b></div><div class='preview-label'>[#LABEL_PREVIEW#]</div></div><div class='clearfix21'></div>");
                
                $pData = $mvc->getLabelDataFromRec($rec, true);
                
                $labelPreview = $mvc->renderLabel($pData);
                
                $tpl->replace($labelPreview, 'LABEL_PREVIEW');
                $form->layout->append($tpl, 'AFTER_MAIN_TABLE');
            }
        }
        
        // Ако само ще се преглежда етикета
        if ($form->isSubmitted() && $form->cmd == 'view') {
            $form->cmd = 'refresh';
        }
        
        // Ако е записан или отпечатан
        if ($form->isSubmitted() && ($form->cmd == 'save' || $form->cmd == 'print')) {
            $pData = $mvc->getLabelDataFromRec($rec);
            
            $rec->rows = $pData->rows;
        }
        
        // Да се махат стойността от параметрите при рефрешване
        if (empty($refreshForm)) {
            if ($rec->templateId) {
                $fncForm = cls::get('core_Form');
                
                // Вземаме функционалните полета за типа
                label_TemplateFormats::addFieldForTemplate($fncForm, $rec->templateId);
                
                foreach ((array) $fncForm->fields as $fieldName => $dummy) {
                    $refreshForm[$fieldName] = $fieldName;
                }
            }
            
            if (!empty($refreshForm)) {
                $form->setField('templateId', 'removeAndRefreshForm=' . implode('|', $refreshForm));
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        label_Templates::activateTemplate($rec->templateId);
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     *
     * @param label_Prints $mvc
     * @param object       $res
     * @param object       $data
     */
    protected static function on_AfterPrepareRetUrl($mvc, &$res, &$data)
    {
        // Ако е субмитната формата и сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && ($data->form->cmd == 'save' || $data->form->cmd == 'print')) {
            if ($data->form->cmd == 'print') {
                $data->retUrl = toUrl(array($mvc, 'print', $data->form->rec->id, 'from' => 1, 'to' => count($data->form->rec->rows)));
            } else {
                $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
            }
        }
    }
    
    
    /**
     * След подготовка на сингъла
     *
     * @param label_Prints $mvc
     * @param object       $res
     * @param object       $data
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако данните не са кеширани, тогава ги генерираме
        if (!isset($data->rec->rows)) {
            try {
                $pData = $mvc->getLabelDataFromRec($data->rec);
                $data->rec->rows = $pData->rows;
                
                $mvc->save($data->rec, 'rows');
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
        
        $data->PreviewLabel = $mvc->getLabelDataFromRec($data->rec, true);
    }
    
    
    /**
     * Преди рендиране на сингъла
     *
     * @param label_Prints $mvc
     * @param object       $res
     * @param object       $data
     */
    protected static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        // Рендираме етикетите
        $data->row->PreviewLabel = $mvc->renderLabel($data->PreviewLabel);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('print', $data->rec)) {
            $warning = '';
            
            // Ако съсотоянието е затворено показваме предупреждение
            if ($data->rec->printedCnt) {
                $modifiedDate = dt::mysql2verbal($data->rec->modifiedOn);
                $warning = "warning=Този етикет е бил отпечатван на|* ${modifiedDate}. |Искате ли да го отпечатате още веднъж|*?";
            }
            
            $data->toolbar->addBtn('Печат', array($mvc, 'print', $data->rec->id), $warning, 'ef_icon=img/16/printer.png, title = Отпечатване');
        }
        
        if ($mvc->haveRightFor('regenerate', $data->rec)) {
            $data->toolbar->addBtn('Регенериране', array($mvc, 'regenerate', $data->rec->id, 'ret_url' => true), null, 'ef_icon=img/16/printer.png, title = Отпечатване, row=2');
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param label_Prints $mvc
     * @param object       $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // По подразбиране да се показват черновите записи най-отпред
        $data->query->orderBy('createdOn', 'DESC');
        
        $data->listFilter->FNC('author', 'users(rolesForAll=labelMaster|ceo|admin, rolesForTeams=label|ceo|admin)', 'caption=От, refreshForm');
        
        $data->listFilter->showFields = 'author, search';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->input('author', true);
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Ако не е избран потребител по подразбиране
        if (!$data->listFilter->rec->author) {
            
            // Да е текущия
            $data->listFilter->rec->author = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if ($filter = $data->listFilter->rec) {
            
            // Ако се търси по всички
            if (strpos($filter->author, '|-1|') !== false) {
                if (!haveRole('labelMaster, ceo, admin')) {
                    $data->query->where('1=2');
                }
            } else {
                
                // Масив с потребителите
                $usersArr = type_Keylist::toArray($filter->author);
                
                $data->query->orWhereArr('createdBy', $usersArr);
                $data->query->orWhereArr('modifiedBy', $usersArr, true);
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($data->toolbar->buttons['btnAdd']) {
            $data->toolbar->buttons['btnAdd']->title = 'Нов етикет';
            $data->toolbar->buttons['btnAdd']->attr['ef_icon'] = 'img/16/price_tag_label.png';
            $data->toolbar->buttons['btnAdd']->attr['title'] = 'Създаване на етикет';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!$fields['-single'] && $mvc->haveRightFor('print', $rec)) {
            $warning = false;
            
            // Ако съсотоянието е затворено показваме предупреждение
            if ($rec->printedCnt) {
                $modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y H:i');
                $warning = "Този етикет е бил отпечатван на|* ${modifiedDate}. |Искате ли да го отпечатате още веднъж|*?";
            }
            
            $btnAttr = arr::make('ef_icon=img/16/printer.png, title=Отпечатване, class=fleft');
            if (isset($rec->classId)) {
                if (!cls::haveInterface('label_SequenceIntf', $rec->classId)) {
                    $btnAttr['error'] = 'Проблем при разпечатването на етикета|*!';
                    $btnAttr['ef_icon'] = 'img/16/error.png';
                }
            }
            
            $row->printedCnt = ht::createBtn('Печат', array($mvc, 'print', $rec->id), $warning, '_blank', $btnAttr) . "<span class='fright' style='display: inline-block; margin-top: 4px;'>" . $row->printedCnt . '</span>';
        }
        
        if ($rec->objectId && $rec->classId) {
            if (cls::load($rec->classId, true)) {
                $clsInst = cls::get($rec->classId);
                
                if (!cls::haveInterface('label_SequenceIntf', $rec->classId)) {
                    $row->title = $mvc->getVerbal($rec, 'title');
                    $row->title = "<span class ='red'>{$row->title}</span>";
                    $row->title = ht::createHint($row->title, 'Проблем при показването', 'error', false);
                }
                
                if ($clsInst instanceof core_Detail) {
                    if ($oMasterId = $clsInst->fetchField($rec->objectId, $clsInst->masterKey)) {
                        $row->source = $clsInst->Master->getHyperlink($oMasterId);
                    } else {
                        $row->source = "<span class='red'>" . tr('Проблем с показването') . '</span>';
                    }
                } elseif (cls::haveInterface('doc_DocumentIntf', $clsInst)) {
                    $row->source = $clsInst->getLink($rec->objectId, 0);
                } else {
                    $row->source = $clsInst->getHyperlink($rec->objectId, true);
                }
            }
        }
        
        if ($rec->templateId) {
            if (label_Templates::haveRightFor('single', $rec->templateId)) {
                $row->templateId = label_Templates::getLinkToSingle($rec->templateId, 'title');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'print') {
            if (!$mvc->haveRightFor('single', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add' && $rec && $requiredRoles != 'no_one') {
            if ($rec->classId && $rec->objectId) {
                if (!label_Templates::getTemplatesByDocument($rec->classId, $rec->objectId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'edit' && $rec && $requiredRoles != 'no_one') {
            if ($rec->createdBy != $userId) {
                $requiredRoles = 'labelMaster, admin, ceo';
            }
            
            if ($rec->objectId && $rec->classId) {
                if (cls::load($rec->classId, true)) {
                    if (!cls::haveInterface('label_SequenceIntf', $rec->classId)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготвя данните за етикета
     *
     * @param stdClass $rec
     * @param bool     $preview
     *
     * @return stdClass
     */
    protected function getLabelDataFromRec($rec, $preview = false)
    {
        $pData = new stdClass();
        
        $pData->Label = new stdClass();
        $pData->Label->rec = $rec;
        $pData->updateTempData = !$preview;
        
        $pData->Media = new stdClass();
        if ($rec->mediaId) {
            $pData->Media->rec = label_Media::fetch($rec->mediaId);
        }
        
        if ($preview) {
            $pData->pageLayout = new stdClass();
            $pData->pageLayout->columnsCnt = 1;
            
            $pData->cnt = 1;
            $pData->printCnt = $rec->labelsCnt;
            $pData->copyCnt = 1;
        } else {
            $pData->cnt = $rec->labelsCnt;
            $pData->copyCnt = $rec->copiesCnt;
            $pData->allCnt = $rec->labelsCnt * $rec->copiesCnt;
            
            // Подготвяме медията
            label_Media::prepareMediaPageLayout($pData);
        }
        
        if (!$pData->pageLayout) {
            $pData->pageLayout = new stdClass();
        }
        
        if (!$pData->pageLayout->columnsCnt) {
            $pData->pageLayout->columnsCnt = 1;
        }
        
        // Ако няма стойност
        if (!$pData->row) {
            
            // Създаваме обект
            $pData->row = new stdClass();
        }
        
        // Вземаме шаблона
        $pData->row->Template = label_Templates::getTemplate($rec->templateId);
        
        if (!$rec->rows) {
            $pData->Label->params = array();
            
            $params = $rec->params;
            if ($rec->objectId && $rec->classId) {
                $intfInst = cls::getInterface('label_SequenceIntf', $rec->classId);
                
                $lang = label_Templates::fetchField($rec->templateId, 'lang');
                
                core_Mode::push('prepareLabel', true);
                core_Lg::push($lang);
                $labelDataArr = (array) $intfInst->getLabelData($rec->objectId, $pData->cnt, $preview);
                $placeArr = (array) $intfInst->getLabelPlaceholders($rec->objectId);
                core_Lg::pop();
                core_Mode::pop('prepareLabel');
                
                foreach ($labelDataArr as $id => $lArr) {
                    foreach ((array) $lArr as $key => $val) {
                        $keyNormalized = label_TemplateFormats::getPlaceholderFieldName($key);
                        
                        if ($placeArr[$key]->hidden || $placeArr[$key]->readonly || !array_key_exists($keyNormalized, $params)) {
                            $params[$keyNormalized] = $val;
                        }
                    }
                    
                    $pData->Label->params[$id] = $params;
                }
            } else {
                for ($id = 0; $id < $pData->cnt; $id++) {
                    $pData->Label->params[$id] = $params;
                }
            }
            
            // Подготвяме данните за етикета
            $this->prepareLabel($pData);
        } else {
            if ($preview) {
                $key = key($rec->rows);
                $rows = array($key => $rec->rows[$key]);
            } else {
                $rows = $rec->rows;
            }
            
            $pData->rows = $rows;
        }
        
        return $pData;
    }
    
    
    /**
     * Подготвяме етикета
     *
     * @param object $data
     */
    protected function prepareLabel(&$data)
    {
        $rec = $data->Label->rec;
        
        // Ако няма запис
        if (!$rec) {
            expect($data->Label->id);
            
            $rec = $this->fetch($data->Label->id);
        }
        
        // Ако не е сетната бройката
        setIfNot($data->cnt, 1);
        setIfNot($data->copyCnt, 1);
        
        if (!$data->allCnt) {
            $data->allCnt = $data->cnt * $data->copyCnt;
        }
        
        // Вземема плейсхолдерите в шаблона
        $placesArr = label_Templates::getPlaceholders($data->row->Template);
        
        // Плейсхолдери за брой отпечатване и текущ етикет
        $printCntField = label_TemplateFormats::getPlaceholderFieldName('Общо_етикети');
        $currPrintCntField = label_TemplateFormats::getPlaceholderFieldName('Текущ_етикет');
        $currPageCntField = label_TemplateFormats::getPlaceholderFieldName('Страница');
        
        setIfNot($itemsPerPage, $data->pageLayout->itemsPerPage, 1);
        
        $rowId = 0;
        $perPageCnt = 1;
        
        $currPageCnt = 1;
        $currPrintCnt = 1;
        
        if (!isset($data->rows)) {
            $data->rows = array();
        }
        
        foreach ($data->Label->params as $params) {
            
            // Ако не е зададена стойност за текущия отпечатван етикет
            $updatePrintCnt = false;
            if (!$params[$currPrintCntField]) {
                $updatePrintCnt = true;
                $params[$currPrintCntField] = $currPrintCnt;
            }
            
            // Ако не е зададена стойност за текущата страница
            $updatePageCnt = false;
            if (!$params[$currPageCntField]) {
                $updatePageCnt = true;
                $params[$currPageCntField] = $currPageCnt;
                setIfNot($placesArr[$currPageCntField], $currPageCntField);
            }
            
            // Ако не е зададена стойност за брой отпечатвания
            setIfNot($params[$printCntField], $data->printCnt, $data->cnt, 1);
            
            if ($updatePrintCnt) {
                $params[$currPrintCntField] = $currPrintCnt++;
            }
            
            // Ако сме минали на нова страница увеличаваме брояча за страници
            if (($updatePageCnt) && ($perPageCnt % $itemsPerPage == 0)) {
                $params[$currPageCntField] = $currPageCnt++;
            }
            $perPageCnt++;
            
            if (!isset($data->rows[$rowId])) {
                $data->rows[$rowId] = array();
            }
            
            // Обхождаме масива с шаблоните
            foreach ((array) $placesArr as $place) {
                
                // Вземаме името на плейсхолдера
                $fPlace = label_TemplateFormats::getPlaceholderFieldName($place);
                
                try {
                    // Вземаме вербалната стойност
                    $data->rows[$rowId][$place] = label_TemplateFormats::getVerbalTemplate($rec->templateId, $place, $params[$fPlace], $rec->id, $data->updateTempData);
                } catch (core_exception_Expect $e) {
                    $data->rows[$rowId][$place] = "<span style='color: #c00;'>" . tr('Грешка при показване на данните') . '!!!</span>';
                    $this->logWarning('Грешка при показване на данните: ' . $e->getMessage(), $rec->id);
                }
            }
            
            $newCurrPage = false;
            
            // За всяко копие добавяме по едно копие
            for ($copyId = 1; $copyId < $data->copyCnt; $copyId++) {
                $copyField = $rowId + $copyId;
                $data->rows[$copyField] = $data->rows[$rowId];
                
                $params[$currPageCntField] = $currPageCnt;
                
                // При копиятата, ако сме минали на нова страница, да се увеличи брояча за всички следващи копия
                if (($updatePageCnt) && ($perPageCnt % $itemsPerPage == 0)) {
                    $params[$currPageCntField] = $currPageCnt++;
                }
                
                $newCurrPage = label_TemplateFormats::getVerbalTemplate($rec->templateId, $currPageCntField, $params[$currPageCntField], $rec->id, $data->updateTempData);
                
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
     *
     * @return core_ET - Шаблона, който ще връщаме
     */
    protected function renderLabel(&$data, $labelLayout = null)
    {
        // Генерираме шаблона
        $allTpl = new core_ET();
        
        // Брой записи на страница
        setIfNot($itemsPerPage, $data->pageLayout->itemsPerPage, 1);
        
        // Обхождаме резултатите
        foreach ((array) $data->rows as $rowId => $row) {
            
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
                $divStyle = "margin-left: {$data->pageLayout->columnsDist}; ";
            }
            
            // За всеки ред без първия се добавя междината за редовете
            if (isset($data->pageLayout->linesDist) && $n >= $data->pageLayout->columnsCnt) {
                $divStyle .= "margin-top: {$data->pageLayout->linesDist};";
            }
            
            if ($divStyle) {
                $divStyle = "style='{$divStyle}'";
            }
            
            $template = "<div {$divStyle}>" . $template . '</div>';
            
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
     * Регенериране на данните на етикета
     *
     * @return Redirect
     */
    public function act_Regenerate()
    {
        $this->requireRightFor('regenerate');
        
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        expect($rec);
        $this->requireRightFor('print', $rec);
        
        $rec->rows = null;
        
        $pData = $this->getLabelDataFromRec($rec);
        
        $this->save($rec);
        
        $this->logWrite('Регенерирани данни', $rec);
        
        return new Redirect(getRetUrl(), '|Регенерирахте данните на етикета');
    }
    
    
    /**
     * Рендира етикетите за отпечатване
     *
     * @see core_Master::act_Single()
     */
    public function act_Print()
    {
        // Трябва да има запис и да има права за записа
        $this->requireRightFor('print');
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        expect($rec);
        $this->requireRightFor('print', $rec);
        
        $form = cls::get('core_Form');
        
        $form->title = 'Отпечатване на етикети';
        
        if (!isset($rec->rows)) {
            $pData = $this->getLabelDataFromRec($rec);
            $to = count($pData->rows);
        } else {
            $to = count($rec->rows);
        }
        
        $to = max($to, 1);
        
        $form->FNC('from', 'int(min=1)', 'caption=От, input, mandatory, silent');
        $form->FNC('to', "int(max={$to})", 'caption=До, input, mandatory, silent');
        
        $form->setDefault('from', 1);
        $form->setDefault('to', $to);
        
        $form->input();
        
        if ($form->rec->from > $form->rec->to) {
            $form->setError('from, to', '"От" трябва да е по-малко от "До"');
        }
        
        if ($form->isSubmitted()) {
            $labelsCnt = label_Media::getCountInPage($rec->mediaId);
            
            $allPirntsCnt = $form->rec->to - $form->rec->from + 1;
            
            if ($allPirntsCnt % $labelsCnt) {
                $form->setWarning('from, to', "|Броят на страниците не се дели на|* {$labelsCnt}. |Ще има неизползвана част от медията|*.");
            }
        }
        
        // Ако не са зададени страниците, показваме форма за избора им
        if (($to > 1) && ($form->gotErrors() || !Request::get('from') || !Request::get('to'))) {
            $retUrl = getRetUrl();
            if (empty($retUrl)) {
                $retUrl = array($this, 'single', $id);
            }
            
            $form->toolbar->addSbBtn('Печат', 'print', 'ef_icon = img/16/printer.png, title=Отпечатване на данните');
            $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            $formTpl = $this->renderWrapping($form->renderHtml());
            core_Form::preventDoubleSubmission($formTpl, $form);
            
            return $formTpl;
        }
        
        // Ще се принтира
        Mode::set('wrapper', 'page_Print');
        Mode::set('printing');
        
        $data = new stdClass();
        
        $pData = $this->getLabelDataFromRec($rec);
        
        // Ако са зададени страниците, които да се отпечата, подготвяме само тях
        if (($form->rec->from != 1) || ($form->rec->to != $to)) {
            $pData->rows = array_slice((array) $pData->rows, $form->rec->from - 1, $form->rec->to - $form->rec->from + 1);
        }
        
        $pData->allCnt = count($pData->rows);
        
        // Рендираме медията
        $pageLayout = label_Media::renderMediaPageLayout($pData);
        
        $tpl = $this->renderLabel($pData, $pageLayout);
        
        // Маркираме медията, като използване
        label_Media::markMediaAsUsed($rec->mediaId);
        
        // Обновяваме броя на отпечатванията и за текущия отпечатък
        $rec->printedCnt += $pData->allCnt;
        
        $this->save($rec, 'printedCnt');
        
        $this->logRead('Отпечатване', $rec->id);
        
        return $tpl;
    }
}
