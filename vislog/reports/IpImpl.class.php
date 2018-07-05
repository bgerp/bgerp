<?php



/**
 * Мениджър на отчети от посещения по IP
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vislog_reports_IpImpl extends frame_BaseDriver
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'vislog_IpReports';
    
    
    /**
     * Заглавие
     */
    public $title = 'Сайт » Посещения по IP';

    
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
    public $canSelectSource = 'ceo, admin, cms';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'ceo, admin, cms';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, admin, cms';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('from', 'date', 'caption=Начало');
        $form->FLD('to', 'date', 'caption=Край');
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
        $data->ipCnt = array();
        $fRec = $data->fRec = $this->innerForm;
        
        $query = vislog_History::getQuery();

        if ($fRec->from) {
            $query->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if ($fRec->to) {
            $query->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }

        while ($rec = $query->fetch()) {
            $data->ipCnt[$rec->ip]++;
        }

        // Сортиране на данните
        arsort($data->ipCnt);
     
        return $data;
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($rec)
    {
        $Ip = cls::get('type_Ip');
        $Int = cls::get('type_Int');

        foreach ($rec as $ip => $createdCnt) {
            $row = new stdClass();

            if ($this->innerState->fRec->to) {
                $row->ip = $Ip->decorateIp($ip, $this->innerState->fRec->to, true, true);
            } else {
                $row->ip = $Ip->decorateIp($ip, $this->innerState->fRec->createdOn, true, true);
            }
        
            $row->cnt = $Int->toVerbal($createdCnt);
        
            $rows[] = $row;
        }
    
        return $rows;
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
            
            $pager->itemsCount = count($data->ipCnt, COUNT_RECURSIVE);
            $pager->calc();
            $data->pager = $pager;
        }
        $rows = $mvc->getVerbal($data->ipCnt);
        
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
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        if (empty($data)) {
            return;
        }
        
        $tpl = new ET('
            <h1>Отчет за посещенията по IP</h1>
            [#FORM#]
    		[#PAGER#]
            [#VISITS#]
    		[#PAGER#]');
         
        $explodeTitle = explode(' » ', $this->title);
         
        $title = tr("|{$explodeTitle[1]}|*");
        
        $tpl->replace($title, 'TITLE');
        
        $this->prependStaticForm($tpl, 'FORM');
        
        $tpl->placeObject($data->row);
        
        $tableMvc = new core_Mvc;
        $tableMvc->FLD('ip', 'ip(15)', 'tdClass=itemClass');
        $tableMvc->FLD('cnt', 'int', 'tdClass=itemClass,smartCenter');
        
        $table = cls::get('core_TableView', array('mvc' => $tableMvc));
        $fields = 'ip=Посещения->Ip,cnt=Посещения->Брой';

        $tpl->append($table->get($data->rows, $fields), 'VISITS');
        
        if ($data->pager) {
            $tpl->append($data->pager->getHtml(), 'PAGER');
        }
        
        $embedderTpl->append($tpl, 'data');
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
        $f->FLD('ip', 'ip(15)');
        $f->FLD('cnt', 'int');
        $f->FLD('createdBy', 'varchar');
    
    
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
        $fields = arr::make('ip=Посещения->Ip,
                             cnt=Посещения->Брой=Продукт', true);
    
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
        
        foreach ($this->prepareEmbeddedData()->rows as $id => $rec) {
            $rec->ip = html_entity_decode(strip_tags($rec->ip));
            $data[$id] = $rec;
        }

        $csv = csv_Lib::createCsv($data, $fields, $exportFields);
         
        return $csv;
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        return $this->innerForm->to;
    }
}
