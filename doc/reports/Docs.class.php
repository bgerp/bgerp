<?php



/**
 * Мениджър на отчети от документи
 *
 * По посочен тип на документа със статус различен от
 * чернова и оттеглено се брои за посочения период,
 * колко документа е създал конкретният потребител
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_reports_Docs extends frame_BaseDriver
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'doc_DocsReport';
    
    
    /**
     * Заглавие
     */
    public $title = 'Документи » Създадени документи';

    
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
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('from', 'date', 'caption=Начало');
        $form->FLD('to', 'date', 'caption=Край');
        $form->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title)', 'caption=Документ,mandatory');
        $form->FLD('user', 'users(rolesForAll = ceo|report, rolesForTeams = manager|ceo|report)', 'caption=Потребител');
    }
      

    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        $cu = core_Users::getCurrent();
        
        if (core_Users::haveRole('ceo', $cu)) {
            $form->setDefault('user', 'all_users');
        } elseif (core_Users::haveRole('manager', $cu)) {
            $teamCu = type_Users::getUserWithFirstTeam($cu);
            $team = strstr($teamCu, '_', true);
            $form->setDefault('user', "{$team} team");
        } else {
            $userFromTeamsArr = type_Users::getUserFromTeams($cu);
            $form->setDefault('user', key($userFromTeamsArr));
        }

        $today = dt::today();
        
        $form->setDefault('from', date('Y-m-01', strtotime('-1 months', dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', dt::addDays(-1, $today));
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
        $data->docCnt = array();
        $fRec = $data->fRec = $this->innerForm;
      
        $query = doc_Containers::getQuery();
        
        if ($fRec->from) {
            $query->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if ($fRec->to) {
            $query->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }
        
        if ($fRec->docClass) {
            $query->where("#docClass = '{$fRec->docClass}'");
        }
        
        if (($fRec->user != 'all_users') && (strpos($fRec->user, '|-1|') === false)) {
            $query->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
       

        while ($rec = $query->fetch()) {
            $data->docCnt[$rec->docClass][$rec->createdBy]++;
        }
 
        // Сортиране на данните
        arsort($data->docCnt);

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
        // Подготвяме страницирането
        $data = $res;
        
        if (!Mode::is('printing')) {
            $pager = cls::get('core_Pager', array('itemsPerPage' => $this->listItemsPerPage));
            $pager->setPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
            $pager->addToUrl = array('#' => $this->EmbedderRec->instance->getHandle($this->EmbedderRec->that));
            
            $pager->itemsCount = count($data->docCnt, COUNT_RECURSIVE);
            
            $pager->calc();
            $data->pager = $pager;
        }
        
        $rows = $mvc->getVerbal($data->docCnt);
        
        if (is_array($rows)) {
            foreach ($rows as $id => $row) {
                if (!Mode::is('printing')) {
                    if (!$pager->isOnPage()) {
                        continue;
                    }
                }
        
                $data->rows[$id] = $row;
            }
        }
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($rec)
    {
        $Class = cls::get('type_Class');
        $Class->params['interface'] = 'doc_DocumentIntf';
        $Class->params['select'] = 'title';
        $Class->params['allowEmpty'] = 'allowEmpty';

        $Int = cls::get('type_Int');
        
        foreach ($rec as $docClass => $userCnt) {
            foreach ($userCnt as $user => $cnt) {
                $row = new stdClass();
                $row->docClass = $Class->toVerbal($docClass);
                $row->cnt = $Int->toVerbal($cnt);
                 
                if (!$user) {
                    $row->createdBy = 'Анонимен';
                } elseif ($user == -1) {
                    $row->createdBy = 'Система';
                } else {
                    $names = core_Users::fetchField($user, 'names');
                    $row->createdBy = $names . ' ' . crm_Profiles::createLink($user);
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('doc/tpl/DocReportLayout.shtml');
         
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
    
        $docClass = cls::get($data->fRec->docClass);
        $tpl->replace($docClass->singleTitle, 'DOCTYPE');
        
        $this->prependStaticForm($tpl, 'FORM');
         
        $tpl->placeObject($data->row);
    
        $f = $this->getFields();

        $table = cls::get('core_TableView', array('mvc' => $f));
        
        $tpl->append($table->get($data->rows, 'docClass=Създадени документи->Тип,
    	                                       createdBy=Създадени документи->Автор,
    	                                       cnt=Създадени документи->Брой'), 'DOCS');
        
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
        $f->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'tdClass=itemClass');
        $f->FLD('createdBy', 'key(mvc=core_Users,select=names)', 'tdClass=itemClass');
        $f->FLD('cnt', 'int', 'tdClass=itemClass,smartCenter');
    
        return $f;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function getExportFields_()
    {
        // Кои полета ще се показват
        $fields = arr::make('docClass=Тип на документа,
    					     createdBy=Автор,
    					     cnt=Създадени документи (бр.)', true);
    
        return $fields;
    }
    
    
    /**
     * Създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function exportCsv()
    {
        $exportFields = $this->getExportFields();
        $fields = $this->getFields();
        $dataRec = array();

        foreach ($this->innerState->docCnt as $docClass => $docCnt) {
            foreach ($docCnt  as $userId => $cnt) {
                $row = new stdClass();
                
                $row->docClass = $docClass;
                $row->cnt = $cnt;
                $row->createdBy = $userId;
                
                $dataRec[] = $row;
            }
        }

        $csv = csv_Lib::createCsv($dataRec, $fields, $exportFields);
         
        return $csv;
    }
     
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        return $this->innerForm->to;
    }
}
