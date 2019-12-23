<?php


/**
 * Драйвър за експортиране на документи в csv формат
 *
 * Класа трябва да има $exportableCsvFields за да може да се експортират данни от него в CSV формат
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_plg_CsvExport extends core_BaseClass
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Експортиране в Csv';
    
    
    /**
     * Може ли да се добавя към този мениджър
     */
    public function isApplicable($mvc)
    {
        $exportableFields = $this->getCsvFieldSet($mvc)->selectFields();
        
        return empty($exportableFields) ? false : true;
    }
    
    
    /**
     *
     * @param core_Mvc $mvc
     *
     * @return array
     */
    public function getCsvFieldSet($mvc)
    {
        $fieldset = new core_FieldSet();
        
        $exportableFields = arr::make($mvc->exportableCsvFields, true);
        foreach ($exportableFields as $name => $caption) {
            $fieldset->FLD($name, 'varchar', "caption={$caption}");
            if ($mvc->getField($name, false)) {
                $fieldset->fields[$name] = $mvc->getField($name, false);
            }
        }
        
        return $fieldset;
    }
    
    
    /**
     * Подготвя формата за експорт
     *
     * @param core_Form $form
     */
    public function prepareExportForm(core_Form &$form)
    {
        $sets = $selected = array();
        $fields = $this->getCsvFieldSet($this->mvc)->selectFields();
        foreach ($fields as $name => $fld) {
            $sets[] = "{$name}={$fld->caption}";
            $selected[$name] = $name;
        }
        $sets[] = 'ExternalLink=Линк';
        
        $selectedFields = cls::get('type_Set')->fromVerbal($selected);
        
        $sets = implode(',', $sets);
        $form->FNC('showColumnNames', 'enum(yes=Да,no=Не)', 'input,caption=Имена на колони,mandatory');
        $form->FNC('fields', "set(${sets})", 'input,caption=Полета,mandatory');
        $form->setDefault('fields', $selectedFields);
        
        $form->FNC('delimiter', 'varchar(1,size=3)', 'input,caption=Разделител,mandatory');
        $form->FNC('enclosure', 'varchar(1,size=3)', 'input,caption=Ограждане,mandatory');
        $form->FNC('decimalSign', 'varchar(1,size=3)', 'input,caption=Десетичен знак,mandatory');
        $form->FNC('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R))', 'caption=Знаци,input');
        
        $form->setOptions('delimiter', array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
        $form->setOptions('enclosure', array('"' => '"', '\'' => '\''));
        $form->setOptions('decimalSign', array('.' => '.', ',' => ','));
    }
    
    
    /**
     * Проверява импорт формата
     *
     * @param core_Form $form
     */
    public function checkExportForm(core_Form &$form)
    {
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     *
     * @return mixed - експортираните данни
     */
    public function export($filter)
    {
        $cu = core_Users::getCurrent();
        $recs = core_Cache::get($this->mvc->className, "exportRecs{$cu}");
        core_App::setTimeLimit(countR($recs) / 10);
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            if ($this->mvc->haveRightFor('list')) {
                $retUrl = array($this->mvc, 'list');
            } else {
                $retUrl = array($this->mvc);
            }
        }
        
        if (!$recs) {
            redirect($retUrl, false, '|Няма данни за експортиране');
        }
        
        $maxCnt = core_Setup::get('EF_MAX_EXPORT_CNT', true);
        if (countR($recs) > $maxCnt) {
            redirect($retUrl, false, '|Броят на заявените записи за експорт надвишава максимално разрешения|* - ' . $maxCnt, 'error');
        }
        
        $fieldsArr = arr::make($filter->fields, true);
        
        if ($fieldsArr['ExternalLink'] && $recs) {
            $this->prepareExternalLink($recs);
        }
        
        $params = array();
        $fieldSet = $this->getCsvFieldSet($this->mvc);
        
        if ($filter->showColumnNames == 'yes') {
            if ($this->mvc && $this->mvc instanceof core_FieldSet) {
                foreach ($fieldsArr as $field => &$caption) {
                    if ($field != 'ExternalLink') {
                        $value = $fieldSet->getFieldParam($field, 'caption');
                        $valueArr = explode('->', $value);
                        if (countR($valueArr) == 1) {
                            $value = $valueArr[0];
                        } else {
                            $value = $valueArr[1];
                        }
                        foreach ($valueArr as &$v) {
                            $v = transliterate(tr($v));
                        }
                        $caption = implode(':', $valueArr);
                    } else {
                        $caption = transliterate(tr('Връзка'));
                    }
                }
            }
        } else {
            $params['columns'] = 'none';
        }
        
        $params['delimiter'] = $filter->delimiter;
        $params['decPoint'] = $filter->decimalSign;
        $params['enclosure'] = $filter->enclosure;
        $params['text'] = 'plain';
        
        $this->mvc->invoke('BeforeExportCsv', array(&$recs));
        
        $content = csv_Lib::createCsv($recs, $fieldSet, $fieldsArr, $params);
        $content = iconv('utf-8', $filter->encoding . '//TRANSLIT', $content);
        
        return $content;
    }
    
    
    /**
     * Подготвя линковете за виждане от външната част
     *
     * @param array $recs
     */
    protected function prepareExternalLink(&$recs)
    {
        foreach ((array) $recs as $id => $rec) {
            if ($this->mvc->haveRightFor('single', $id) && $rec->containerId) {
                $mid = doclog_Documents::saveAction(
                    array(
                        'action' => doclog_Documents::ACTION_EXPORT,
                        'containerId' => $rec->containerId,
                        'threadId' => $rec->threadId,
                    )
                );
                
                // Флъшваме екшъна за да се запише в модела
                doclog_Documents::flushActions();
                
                $externalLink = bgerp_plg_Blank::getUrlForShow($rec->containerId, $mid);
            } elseif ($id && $this->mvc instanceof core_Master) {
                $externalLink = toUrl(array($this->mvc, 'Single', $id), 'absolute');
            } else {
                $externalLink = toUrl(array($this->mvc), 'absolute');
            }
            
            $recs[$id]->ExternalLink = $externalLink;
        }
    }
    
    
    /**
     * Връща името на експортирания файл
     *
     * @return string $name
     */
    public function getExportedFileName()
    {
        $timestamp = time();
        $name = $this->mvc->className . "Csv{$timestamp}.csv";
        
        return $name;
    }
}
