<?php 


/**
 * Модел за създаване на етикети за печатане
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Labels extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Етикети';
    
    
    /**
     * 
     */
    var $singleTitle = 'Етикети';
    
    
    /**
     * Път към картинка 16x16
     */
//    var $singleIcon = 'img/16/.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'label/tpl/SingleLayoutLabels.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'label, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Роли за мастера на етикетите
     */
    var $canMasterlabel = 'masterLabel, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
//    var $loadList = 'plg_Printing, bgerp_plg_Blank, plg_Search';
    var $loadList = 'label_Wrapper, plg_RowTools, plg_State, plg_Printing, plg_Created, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//    var $listFields = '';
    
    
    /**
     * 
     */
//    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
//    var $rowToolsSingleField = 'id';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//    var $searchFields = '';
    
    
    /**
     * Детайла, на модела
     */
//    var $details = '';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон, silent, input=hidden');
        $this->FLD('params', 'blob(serialize,compress)', 'caption=Параметри, input=none');
        $this->FLD('printedCnt', 'int', 'caption=Отпечатъци, title=Брой отпечатани етикети, input=none');
        
        $this->FLD('fieldUp', 'int', 'caption=Поле->Отгоре, value=0, title=Поле на листа отгоре, unit=mm, notNull');
        $this->FLD('fieldLeft', 'int', 'caption=Поле->Отляво, value=0, title=Поле на листа отляво, unit=mm, notNull');
        
        $this->FLD('columnsCnt', 'int(min=1)', 'caption=Колони в един лист->Брой, value=1, title=Брой колони в един лист, mandatory, notNull');
        $this->FLD('columnsDist', 'int', 'caption=Колони в един лист->Разстояние, value=0, title=Разстояние на колоните в един лист, unit=mm, notNull');
        
        $this->FLD('linesCnt', 'int(min=1)', 'caption=Редове->Брой, value=1, title=Брой редове в един лист, mandatory, notNull');
        $this->FLD('linesDist', 'int', 'caption=Редове->Разстояние, value=0, title=Разстояние на редовете в един лист, unit=mm, notNull');
        
        $this->setDbUnique('title');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако формата не е субмитната и не я редактираме
        if (!$data->form->isSubmitted() && !$data->form->rec->id) {
            
            // id на шаблона
            $templateId = Request::get('templateId', 'int');
            
            // Ако не е избрано id на шаблона
            if (!$templateId) {
                
                // Редиректваме към екшъна за избор на шаблон
                return Redirect(array($mvc, 'selectTemplate'));
            }
            
            // Ако има последен запис
            if ($lastRec = static::getLastRec($templateId)) {
                
                // Използваме данните за полетата от него
                $data->form->rec->fieldUp = $lastRec->fieldUp;
                $data->form->rec->fieldLeft = $lastRec->fieldLeft;
                $data->form->rec->columnsCnt = $lastRec->columnsCnt;
                $data->form->rec->columnsDist = $lastRec->columnsDist;
                $data->form->rec->linesCnt = $lastRec->linesCnt;
                $data->form->rec->linesDist = $lastRec->linesDist;
            }
        }
        
        // Ако няма templateId
        if (!$templateId) {
            
            // Вземаме от записа
            $templateId = $data->form->rec->templateId;
            
            // Очакваме вече да има
            expect($templateId);
        }
        
        // Вземаме масив с полетата
        $fieldsArr = label_TemplateFormats::getFieldArrForTemplate($templateId);
        
        // Показваме полетата
        $data->form->addFncFields($fieldsArr);
        
        // Вземаме данните от предишния запис
        $dataArr = $data->form->rec->params;
        
        // Обхождаме масива
        foreach ((array)$dataArr as $fieldName => $value) {
            
            // Добавяме данните от записите
            $data->form->rec->$fieldName = $value;
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param label_Labels $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
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
            
            // Вземаме масив с полетата
            $fieldsArr = label_TemplateFormats::getFieldArrForTemplate($form->rec->templateId);
            
            // Обхождаме масива
            foreach ((array)$fieldsArr as $fieldName => $dummy) {
                
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
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $row
     * @param unknown_type $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Масив с шаблоните
        static $fieldsArr=array();
        
        // Ако не е сетнат за този шаблон
        if(!$fieldsArr[$rec->templateId]) {
            
            // Вземаме полетата
            $fieldsArr[$rec->templateId] = label_TemplateFormats::getFieldArrForTemplate($rec->templateId);
        }
        
        // Нулираме стойността
        $row->params = '';
        
        // Обхождаме масива с полетата
        foreach((array)$fieldsArr[$rec->templateId] as $name => $otherParams) {
            
            // Името на полето
            $fieldName = $otherParams['name'];
            
            // Ескейпваме
            $fieldName = type_Varchar::escape($fieldName);
            $fieldName = core_Type::escape($fieldName);
            
            // Инстанция на класа
            $inst = cls::get($otherParams['clsType']);
            
            // Вербалната стойност
            $verbalVal = $inst->toVerbal($rec->params[$name]);
            
            // Добавяме в полето
            $row->params .= '<div>' . $fieldName . ': ' . $verbalVal . '</div>';
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

        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Добавяме функционално поле
        $form->FNC('selectTemplateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон');
        
        // Въвеждаме полето
        $form->input('selectTemplateId');
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Редиректваме към екшъна за добавяне
            return new Redirect(array($this, 'add', 'templateId' => $form->rec->selectTemplateId));
        }
        
        // Заглавие на шаблона
        $form->title = "Избор на шаблон";
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'selectTemplateId';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     * 
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('btnPrint');
        
        // URL за избор на брой отпечатвания
	    $url = array(
            $mvc,
            'selectPrintCnt',
            $data->rec->id,
            'ret_url' => TRUE
        );
        
        // Бутон за избор на брой отпечатвания
        $data->toolbar->addBtn('Печат', $url, 'id=btnPrint,target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    }
    
    
    /**
     * Екшън за задаване на брой на отпечатвания
     */
    function act_SelectPrintCnt()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('single');
        
        // id на записа
        $id = Request::get('id', 'int');
        
        // Вземаме записа
        $rec = $this->fetch($id);
        
        // Очакваме да има запис
        expect($rec);
        
        // Очакваме да имаме права към сингъла за този запис
        $this->requireRightFor('single', $rec);
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Добавяме функционално поле
        $form->FNC('printCnt', 'int', 'caption=Брой, mandatory');
        
        // За вкавране на silent записите
        $form->input(NULL, TRUE);
        
        // Въвеждаме полето
        $form->input('printCnt', TRUE);
        
        // URL за редирект
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Увеличаваме броя на отпечатванията в модела
            $rec->printedCnt += $form->rec->printCnt;
            
            // Активираме етикета
            $rec->state = 'active';
            
            // Записваме
            $this->save($rec);
            
            // URL за печат
    	    $printUrl = array(
                $this,
                'print',
                $id,
                'cnt' => $form->rec->printCnt,
                'Printing' => 'yes',
                'ret_url' => $retUrl
            );
            
            // Редиректваме към екшъна за добавяне
            return new Redirect($printUrl);
        }
        
        // Заглавие на шаблона
        $form->title = "Брой отпечатвания";
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'printCnt';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Екшън за принтиране
     */
    function act_Print()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('single');
        
        // id на записа
        $id = Request::get('id', 'int');
        
        // Брой печат едновремменно
        $cnt = Request::get('cnt');
        
        // Записа
        $rec = $this->fetch($id);
        
        // Очакваме да има запис
        expect($rec);
        
        // Очакваме да имаме права за сингъл за записа
        $this->requireRightFor('single', $rec);
        
        // Данни
        $data = new stdClass();
        $data->cnt = $cnt;
        $data->rec = $rec;
        $data->id = $id;
        
        // Подгогвяме етикетите
        $this->prepareLabel($data);
        
        // Рендираме етикетите
        $tpl = $this->renderLabel($data);
        
        return $tpl;
    }
    
    
    /**
     * Подготвяме етикета
     * 
     * @param object $data
     */
    static function prepareLabel(&$data)
    {
        // Ако няма запис
        if (!$data->rec) {
            
            // Вземаме записа
            $data->rec = static::fetch($data->id);
        }
        
        // Ако не е сетната бройката
        setIfNot($data->cnt, 1);
        
        // Подготвяме данните за страниците
        static::preparePageLayout($data);
        
        // Вземаме шаблона
        $data->row->Template = label_Templates::getTemplate($data->rec->templateId);
        
        // Вземема плейсхолдерите в шаблона
        $placesArr = $data->row->Template->getPlaceholders();
        
        // Параметрите
        $params = $data->rec->params;
        
        // Докато достигнем броя на принтиранията
        for ($i = 0; $i < $data->cnt; $i++) {
            
            // Обхождаме масива с шаблоните
            foreach ((array)$placesArr as $place) {
                
                // Вземаме името на плейсхолдера
                $fPlace = label_TemplateFormats::getPlaceholderFieldName($place);
                
                // Вземаме вербалната стойност
                $data->rows[$i]->$place = label_TemplateFormats::getVerbalTemplate($data->rec->templateId, $place, $params[$fPlace], $data->rec->id);
            }
        }
    }
    
    
    /**
     * Подготвя данните необходими за странициране
     * 
     * @param object $data
     */
    static function preparePageLayout(&$data)
    {
        // Ако някоя от необходимите стойности не е сетната
        if (!$data->rec->columnsCnt || !$data->rec->linesCnt || !$data->cnt) return FALSE;
        
        // Колко етикети ще има на страница
        $data->pageLayout->itemsPerPage = $data->rec->columnsCnt * $data->rec->linesCnt;
        
        // Брой страници
        $data->pageLayout->pageCnt = (int)ceil($data->cnt / $data->pageLayout->itemsPerPage);
        
        // Брой записи в поседната страница
        $data->pageLayout->lastPageCnt = (int)($data->cnt % $data->pageLayout->itemsPerPage);
        
        // Брой на колоните
        $data->pageLayout->columnsCnt = $data->rec->columnsCnt;
        
        // Брой на редовете
        $data->pageLayout->linesCnt = $data->rec->linesCnt;
        
        // Ако не са сетнати да са единици
        setIfNot($data->pageLayout->columnsCnt, 1);
        setIfNot($data->pageLayout->linesCnt, 1);
        
        // Отместване на цялата страница
        $data->pageLayout->up = (int) ($data->rec->fieldUp - $data->rec->linesDist) . 'mm';
        $data->pageLayout->left = (int) ($data->rec->fieldLeft - $data->rec->columnsDist) . 'mm';

        // Отместване на колона
        $data->pageLayout->columnsDist = (int) $data->rec->columnsDist . 'mm';
        
        // Отместване на ред 
        $data->pageLayout->linesDist = (int) $data->rec->linesDist . 'mm';
    }
    
    
    /**
     * Рендираме етикете
     * 
     * @param object $data
     * 
     * @return core_Et - Шаблона, който ще връщаме
     */
    static function renderLabel(&$data)
    {
        // Генерираме шаблона
        $allTpl = new core_ET();
        
        // Брой записи на страница
        $itemsPerPage = $data->pageLayout->itemsPerPage;
        
        // Обхождаме резултатите
        foreach ((array)$data->rows as $rowId => $row) {
            
            // Номера на вътрешния шаблон
            $n = $rowId % $itemsPerPage;
            
            // Ако е първа или нямам шаблон
            if ($n === 0 || !$tpl) {
                
                // Рендираме изгледа за една страница
                $tpl = static::renderPageLayout($data);
            }

            // Вземаме шаблона
            $template = clone($data->row->Template);
            
            // Заместваме в шаблона всички данни
            $template->placeArray($row);
            
            // Заместваме шаблона в таблицата на страницата
            $tpl->replace($template, $n);
            
            // Ако сме на последния запис в страницата или изобщо на последния запис
            if (($rowId == ($data->cnt - 1)) || ($n == ($itemsPerPage - 1))) {
                
                // Добавяме към главния шаблон
                $allTpl->append($tpl);
            }
        }
        
        // Премахваме незаместените плейсхолдери
        $allTpl->removePlaces();
        
        return $allTpl;
    }
    
    
    /**
     * Рендираме шаблона за една страница
     * 
     * @param object $data
     */
    static function renderPageLayout(&$data)
    {
        // Брой колоени
        $columns = $data->pageLayout->columnsCnt;
        
        // Брой редове
        $lines = $data->pageLayout->linesCnt;
        
        // Отместване редове
        $linesDist = $data->pageLayout->linesDist;
        
        // Отместване колони
        $columnsDist = $data->pageLayout->columnsDist;
        
        // Брояч
        $cnt = 0;
        
        // Създаваме таблицата
        $t = "<table class='label-table printing-page-break' style='border-collapse: separate; border-spacing: {$columnsDist} {$linesDist}; margin-top: {$data->pageLayout->up}; margin-left: {$data->pageLayout->left};'>";
        
        // Броя на редовете
        for ($i = 0; $i < $lines; $i++) {
            
            // Ако е последен ред
            if ($i == ($lines - 1)) {
                
                // Да няма отместване отдолу
                $bottom = 0;
            }
            
            // Добавям ред
            $t .= '<tr>';
            
            // Броя на колоните
            for ($s = 0; $s < $columns; $s++) {
                
                // Добавяме колона
                $t .= "<td>[#$cnt#]</td>";
                
                // Увеличаваме брояча
                $cnt++;
            }
            
            // Добавяме край на ред
            $t .= "</tr>";
        }
        
        // Добавяме край на таблица
        $t .= '</table>';
        
        return new ET($t);
    }
    
    
    /**
     * Връща последния запис за етикет създаден от същия шаблон и потребител.
     * Ако няма, тогава връща последния етикет създаден от шаблона.
     * 
     * @param integer $templateId - id на шаблона
     * @param integer $userId - id на потребителя
     * 
     * @return object - Запис от модела
     */
    static function getLastRec($templateId, $userId = NULL)
    {
        // Ако не е подадени id на потребител
        if (!$userId) {
            
            // Вземаме на текущия
            $userId = core_Users::getCurrent();
        }
        
        // Вземаме последния етикет създаден от потребителя с този шаблон
        $query = static::getQuery();
        $query->where("#createdBy = {$userId}");
        $query->where("#templateId = {$templateId}");
        $query->where("#state != 'rejected'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);
        
        // Ако има запис, връщаме го
        if ($rec = $query->fetch()) return $rec;
        
        // Вземаме последния етикет създаден от този шаблон
        $query = static::getQuery();
        $query->where("#templateId = {$templateId}");
        $query->where("#state != 'rejected'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);
        
        return $query->fetch();
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareRetUrl($mvc, &$res, &$data)
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
        // Ако състоянието не е чернова
        if ($rec && $rec->state != 'draft' && $action == 'edit') {
            
            // Потреибители, които имат роля за masterLabel могат да редактират
            $requiredRoles = $mvc->getRequiredRoles('Masterlabel');
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        
//        $data->toolbar->removeBtn('btnPrint');
    }
}
