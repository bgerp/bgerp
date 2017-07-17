<?php



/**
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_BaseImporter extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ImportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "CSV импорт";
    
    /*
     * Имплементация на bgerp_ImportIntf
     */
    
    
    /**
     * Инициализиране драйвъра
     */
    function init($params = array())
    {
        $this->mvc = $params['mvc'];
    }
    
    
    /**
     * Функция, връщаща полетата в които ще се вкарват данни
     * в мениджъра-дестинация
     * Не връща полетата които са hidden, input=none,enum,key и keylist
     */
    public function getFields()
    {
        $fields = array();
        $Dfields = $this->mvc->selectFields();
        
        foreach($Dfields as $name => $fld){
            if($fld->input != 'none' && $fld->input != 'hidden' &&
                $fld->kind != 'FNC' && !($fld->type instanceof type_Enum) &&
                !($fld->type instanceof type_Key) && !($fld->type instanceof type_Key2) && !($fld->type instanceof type_KeyList)){
                $fields[$name] = array('caption' => $fld->caption, 'mandatory' => $fld->mandatory);
            }
        }
        
        $this->mvc->invoke('AfterPrepareImportFields', array(&$fields));
        
        return $fields;
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * @param array $rows - масив с обработени csv данни, получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     * полетата от модела array[{поле_oт_модела}] = {колона_от_csv}
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields)
    {
        $html = '';
        $created = $updated = $skipped = $duplicated = 0;
        core_Debug::startTimer('import');
        
        $onExist = Mode::get('onExist');
        
        // Увеличаваме времето, ако е необходимо
        $rCnt = count($rows);
        $time = ceil($rCnt / 10);
        if ($time > ini_get('max_execution_time')) {
            core_App::setTimeLimit($time);
        }
        
        $oFields = $this->getFields();
        
        $errArr = array();
        
        foreach ($rows as $row){
            $rec = new stdClass();
            
            foreach ($fields as $name => $position){
                if ($position != -1){
                    $value = $row[$position];
                    if (isset($oFields[$name]['notColumn'])) {
                        $value = $position;
                    }
                    
                    $rec->{$name} = $value;
                    
                    // Ако ще се добавя файл, правим опит да свалим файла и да го добавим
                    if (isset($rec->{$name})) {
                        if ($this->mvc->getFieldType($name) instanceof fileman_FileType) {
                            $bucketId = fileman_Buckets::fetchByName('import');
                            $rec->{$name} = fileman_Get::getFile((object)array('url' => $rec->{$name}, 'bucketId' => $bucketId));
                        }
                    }
                }
            }
            
            // Ако записа е уникален, създаваме нов, ако не е обновяваме стария
            $fieldsUn = array();
            
            if ($this->mvc->invoke('BeforeImportRec', array(&$rec)) === FALSE) {
                $errArr[] = $row;
                
                continue ;
            }
            
            if(!$this->mvc->isUnique($rec, $fieldsUn, $exRec)){
                $rec->id = $exRec->id;
            }
            
            if ($rec->id) {
                if ($onExist == 'skip') {
                    $skipped++;
                    continue;
                } elseif ($onExist == 'duplicate') {
                    unset($rec->id);
                    $duplicated++;
                } else {
                    $updated++;
                }
            } else {
                $created++;
            }
            
            $this->mvc->save($rec);
        }
        
        // Ако има грешки при импортиране, ги записваме в отделен файл
        if (!empty($errArr)) {
            $nCsv = '';
            foreach ($errArr as $errStr) {
                $nCsv .= ($nCsv) ? "\n" : '';
                $nCsv .= csv_Lib::getCsvLine($errStr, '|', '"');
            }
            
            $fh = fileman::absorbStr($nCsv, 'exportCsv', 'ImportErr.csv');
            
            $errCnt = count($errArr);
            if ($errCnt == 1) {
                $errCntW  = "1 |запис|. |Записан е в|*: ";
            } else {
                $errCntW  = $errCnt . " |записa|. |Записани са в|*: ";
            }
            status_Messages::newStatus('|Грешка в|* ' . $errCntW . fileman::getLinkToSingle($fh));
        }
        
        core_Debug::stopTimer('import');
        
        if ($created) {
            $html .= "|Импортирани|* {$created} |нови записа|*.";
        }
        
        foreach (array('Обновени' => $updated, 'Пропуснати' => $skipped, 'Дублирани' => $duplicated) as $verbName => $cnt) {
            if ($cnt) {
                $html .= ($html) ? '<br />' : '';
                $html .= "|{$verbName}|* {$cnt} |съществуващи записа|*.";
            }
        }
        
        if (isDebug()) {
            $html .= ($html) ? '<br />' : '';
            $html .= "|Общо време|*: " . round(core_Debug::$timers['import']->workingTime, 2);
        }
        
        return $html;
    }
    
    
    /**
     * Драйвъра може да се показва към всички мениджъри
     */
    public function isApplicable($className)
    {
        return TRUE;
    }
}