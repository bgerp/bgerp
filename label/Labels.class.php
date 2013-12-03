<?php 


/**
 * 
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
//    var $singleLayoutFile = 'label/tpl/.shtml';
    
    
    /**
     * Полета, които ще се клонират
     */
//    var $cloneFields = '';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'label';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'label';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'label';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'label';
    
    
    /**
     * Плъгини за зареждане
     */
//    var $loadList = 'plg_Printing, bgerp_plg_Blank, plg_Search';
    var $loadList = 'label_Wrapper, plg_RowTools, plg_State, plg_Printing';
    
    
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
        $this->FLD('printedCnt', 'int', 'caption=Отпечатъци, title=Брой отпечатани етикети');
        
        $this->FLD('fieldUp', 'int', 'caption=Поле->Отгоре, title=Поле на листа отгоре');
        $this->FLD('fieldLeft', 'int', 'caption=Поле->Отляво, title=Поле на листа отляво');
        
        $this->FLD('columnsCnt', 'int', 'caption=Колони в един лист->Брой, title=Брой колони в един лист');
        $this->FLD('columnsDist', 'int', 'caption=Колони в един лист->Разстояние, title=Разстояние на колоните в един лист');
        
        $this->FLD('linesCnt', 'int', 'caption=Редове->Брой, title=Брой редове в един лист');
        $this->FLD('linesDist', 'int', 'caption=Редове->Разстояние, title=Разстояние на редовете в един лист');
        
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
        // Ако формата не е субмитната и нея редактираме
        if (!$data->form->isSubmitted() && !$data->form->rec->id) {
            
            // id на шаблона
            $templateId = Request::get('templateId', 'int');
            
            // Ако не е избрано id на шаблона
            if (!$templateId) {
                
                // Редиректваме към екшъна за избор на шаблон
                return Redirect(array($mvc, 'selectTemplate'));
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
     * @param label_TemplateFormats $mvc
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
            
            // TODO increase printedCnt
            
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
        
        // Подгогвяме етикетите
        $this->prepareLabel($data);
        
        // Рендираме етикетите
        $tpl = $this->renderLabel($data);
        
        // URL за редирект
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        return $tpl;
    }
    
    
    /**
     * Подготвяме етикета
     * 
     * @param object $data
     */
    static function prepareLabel(&$data)
    {
        // TODO да не са нули
        
        // Колко етикети ще има на страница
        $data->itemsPerPage = $data->rec->columnsCnt * $data->rec->linesCnt;
        
        // Брой страници
        $data->pageCnt = (int)ceil($data->cnt / $data->itemsPerPage);
        
        // Брой записи в поседната страница
        $data->lastPageCnt = (int)($data->cnt % $data->itemsPerPage);
        
        // Вземаме шаблона
        $template = label_Templates::getTemplate($data->rec->templateId);
        
        // Вземема плейсхолдерите в шаблона
        $placesArr = $template->getPlaceholders();
        
        // Параметрите
        $params = $data->rec->params;
        
        // TODO работи за един запис на страница
        
//        for ($i = 0; $i < $data->rec->columnsCnt; $i++) {

            // Шаблона
            $data->templateRow = clone($template);
            
            // TODO разделяне на рендиране и подготвяне
            
            // Обхождаме плейсхолдерите от масива
            foreach ($placesArr as $place) {
                
                // Вземаме името на плейсхолдера
                $fPlace = label_TemplateFormats::getPlaceholderFieldName($place);
                
                // Вземаме вербалната стойност
                $verbalVal = label_TemplateFormats::getVerbalTemplate($data->rec->templateId, $place, $params[$fPlace]);
                
                // Заместваме в етикете
                $data->templateRow->replace($verbalVal, $place);
            }
//        }
    }
    
    
    /**
     * Рендираме етикете
     * 
     * @param unknown_type $data
     */
    static function renderLabel(&$data)
    {
        // TODO - работи само за един етикет
        
//        $tpl = new ET('<table>[#TABLE#]</table>');
        $tpl = new ET('[#TABLE#]');
        
        $tpl->append($data->templateRow, 'TABLE');
        
        return $tpl;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        
//        $data->toolbar->removeBtn('btnPrint');
    }
}
