<?php



/**
 * Мениджър на отчети от продажбени артикули
 *
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_reports_SalesArticle extends frame_BaseDriver
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'cat_SalesArticleReport';
    
    
    /**
     * Заглавие
     */
    public $title = 'Артикули » Продажбени артикули';

    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    //public $oldClassName = 'doc_SalesArticleReport';
    


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
    public $canSelectSource = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo,sales,purchase';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('from', 'date', 'caption=Начало');
        $form->FLD('to', 'date', 'caption=Край');
        $form->FLD('user', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител');
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
    }

    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
        $data = new stdClass();
        $data->articleCnt = array();
        $fRec = $data->fRec = $this->innerForm;
        
        $this->prepareListFields($data);
      
        $querySales = sales_SalesDetails::getQuery();
        $queryShipment = store_ShipmentOrderDetails::getQuery();
        $queryServices = sales_ServicesDetails::getQuery();


        if ($fRec->from) {
            $querySales->where("#createdOn >= '{$fRec->from} 00:00:00'");
            $queryShipment->where("#createdOn >= '{$fRec->from} 00:00:00'");
            $queryServices->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if ($fRec->to) {
            $querySales->where("#createdOn <= '{$fRec->to} 23:59:59'");
            $queryShipment->where("#createdOn <= '{$fRec->to} 23:59:59'");
            $queryServices->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }

        if (($fRec->user != 'all_users') && (strpos($fRec->user, '|-1|') === false)) {
            $querySales->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
            $queryShipment->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
            $queryServices->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
       

        while ($rec = $querySales->fetch()) {
            $data->articleCnt['sales'][$rec->classId][$rec->productId]++;
        }

        while ($recShipment = $queryShipment->fetch()) {
            $data->articleCnt['shipment'][$recShipment->classId][$recShipment->productId]++;
        }

        while ($recServices = $queryServices->fetch()) {
            $data->articleCnt['services'][$recServices->classId][$recServices->productId]++;
        }
 
        // Сортиране на данните
        arsort($data->articleCnt);

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
            $pager = cls::get('core_Pager', array('itemsPerPage' => $mvc->listItemsPerPage));
            $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
            $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));
    
            $pager->itemsCount = count($data->articleCnt, COUNT_RECURSIVE);
            $pager->calc();
            $data->pager = $pager;
        }
        $rows = $mvc->getVerbal($data->articleCnt);

        if (is_array($rows)) {
            foreach ($rows as $id => $row) {
                $cu = getCurrentUrl();
                if ($cu['Act'] == 'export') {
                    $data->rows[$id] = $row;
                } else {
                    if (!Mode::is('printing')) {
                        if (!$pager->isOnPage()) {
                            continue;
                        }
                    }

                    $data->rows[$id] = $row;
                }
            }
        }
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($rec)
    {
        $Users = cls::get('type_Users');
        $Int = cls::get('type_Int');

        foreach ($rec as $doc => $artCnt) {
            foreach ($artCnt as $artClassId => $productCnt) {
                foreach ($productCnt as $product => $cnt) {
                    $row = new stdClass();
                    $row->article = cat_Products::getTitleById($product);
        
                    if ($doc == 'sales') {
                        $row->salesCnt = $Int->toVerbal($cnt);
                    }
                    if ($doc == 'shipment' || $doc == 'services') {
                        $row->shipmentCnt = $Int->toVerbal($cnt);
                    }
        
        
                    if (!$user) {
                        $row->createdBy = 'Анонимен';
                    } elseif ($user == -1) {
                        $row->createdBy = 'Система';
                    } else {
                        $row->createdBy = $Users->toVerbal($user) . ' ' . crm_Profiles::createLink($user);
                    }
        
                    $rows[] = $row;
                }
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
        $tpl = getTplFromFile('cat/tpl/SalesArticleReportLayout.shtml');
         
        return $tpl;
    }
    
    
    /**
     * Полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function prepareListFields_(&$data)
    {
        // Кои полета ще се показват
        $data->listFields = arr::make('article=Продукт,
    					     salesCnt=Продажба (бр.),
    					     shipmentCnt=Доставка (бр.),
                             createdBy=Създаден от', true);
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
        
        $explodeTitle = explode(' » ', $this->title);
        
        $title = tr("|{$explodeTitle[1]}|*");
         
        $tpl->replace($title, 'TITLE');
         
        $this->prependStaticForm($tpl, 'FORM');
         
        $tpl->placeObject($data->row);
         
        $tableMvc = new core_Mvc;
        $tableMvc->FLD('article', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'tdClass=itemClass');
        $tableMvc->FLD('salesCnt', 'int', 'tdClass=itemClass,smartCenter');
        $tableMvc->FLD('shipmentCnt', 'int', 'tdClass=itemClass,smartCenter');

        $table = cls::get('core_TableView', array('mvc' => $tableMvc));

        $tpl->append($table->get($data->rows, 'article=Продукт,
    					     salesCnt=Продажба (бр.),
    					     shipmentCnt=Доставка (бр.),
                             '), 'CONTENT');

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
        $f->FLD('article', 'varchar');
        $f->FLD('salesCnt', 'int');
        $f->FLD('shipmentCnt', 'int');

    
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
        $fields = arr::make('article=Продукт,
    					     salesCnt=Продажба (бр.),
    					     shipmentCnt=Доставка (бр.)', true);
        
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
    
        $csv = csv_Lib::createCsv($this->prepareEmbeddedData()->rows, $fields, $exportFields);
         
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
