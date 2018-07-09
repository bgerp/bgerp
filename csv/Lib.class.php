<?php


/**
 * Клас 'csv_Lib' - Пакет за работа с CSV файлове
 *
 *
 * @category  vendors
 * @package   csv
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class csv_Lib
{
    /**
     * Импортира CSV файл в указания модел
     */
    public static function import($mvc, $file, $fields = array(), $defaults = array(), $format = array(), $isLarge = false)
    {
        // Дефолт стойностите за форматирането по подразбиране
        setIfNot($format['length'], 0);
        
        if (!strlen($format['delimiter'])) {
            $format['delimiter'] = ',';
        }
        
        if (!strlen($format['enclosure'])) {
            $format['enclosure'] = '"';
        }
        
        if (!strlen($format['escape'])) {
            $format['escape'] = '\\';
        }
        
        if (!strlen($format['skip'])) {
            $format['skip'] = '#';
        }
        
        $firstRow = true;
        $res = (object) array('created' => 0, 'updated' => 0, 'skipped' => 0);
        $fields = arr::make($fields);
        
        $fromZero = !$mvc->fetch('1=1');
        
        $path = getFullPath($file);
        
        expect(($handle = fopen($path, 'r')) !== false);
        
        $closeOnce = false;
        
        $pRowCnt = null;
        
        while (($data = fgetcsv($handle, $format['length'], $format['delimiter'], $format['enclosure'], $format['escape'])) !== false) {
            $cRowCnt = count($data);
            
            // Пропускаме празните линии
            if (!$cRowCnt || ($cRowCnt == 1 && trim($data[0]) == '')) {
                continue;
            }
            
            // Пропускаме редовете със знака указан в $skip
            if ($data[0]{0} == $format['skip']) {
                if (strtolower(trim($data[0], ' ' . $format['skip'])) == 'closeonce') {
                    $closeOnce = true;
                }
                
                continue;
            }
            
            // Ако броя на колоните не са коректни
            if (!isset($pRowCnt)) {
                $pRowCnt = $cRowCnt;
            } else {
                if ($cRowCnt != $pRowCnt) {
                    wp($data, $cRowCnt, $pRowCnt, $fields);
                }
            }
            
            // Ако не са указани полетата, вземаме ги от първия ред
            if ($firstRow && !count($fields)) {
                foreach ($data as $f) {
                    $fields[] = $f;
                }
                
                $firstRow = false;
            } else {
                // Вкарваме данните
                if ($defaults) {
                    $rec = (object) $defaults;
                } else {
                    $rec = new stdClass();
                }
                
                foreach ($fields as $i => $f) {
                    $data[$i] = str_replace($format['escape'], '', $data[$i]);
                    
                    $rec->{$f} = $data[$i];
                }
                
                if ($closeOnce) {
                    $rec->state = 'closed';
                    $closeOnce = false;
                }
                
                if ($mvc->invoke('BeforeImportRec', array(&$rec, $data, $fields, $defaults)) === false) {
                    continue ;
                }
                
                // Ако таблицата се попълва от нулата, само се добавят редове
                if ($fromZero && $isLarge) {
                    if (!isset($recs)) {
                        $recs = array();
                    }
                    $recs[] = $rec;
                    $res->created++;
                    if (count($recs) > 2000) {
                        $mvc->saveArray($recs, null, true);
                        $recs = array();
                    }
                    continue;
                }
                
                $conflictFields = array();
                
                if ($rec->id || !$mvc->isUnique($rec, $conflictFields, $exRec)) {
                    if (!$rec->id) {
                        $rec->id = $exRec->id;
                    }
                    $flagUpdate = true;
                } else {
                    $res->created++;
                    $flagUpdate = false;
                }
                
                // По подразбиране записът е добавен от системния потребител
                setIfNot($rec->createdBy, -1);
                
                // Ако нямаме запис с посочените уникални стойности, вкарваме новия
                $mvc->save($rec);
                
                // Генериране на събитие след импортиране на запис
                $mvc->invoke('AfterImportRec', array(&$rec));
                
                if ($flagUpdate) {
                    $res->skipped++;
                    $rec = $mvc->fetch($rec->id);
                    foreach ($fields as $i => $f) {
                        if ($rec->{$f} != $exRec->{$f}) {
                            $res->updated++;
                            $res->skipped--;
                            break;
                        }
                    }
                }
            }
        }
        
        if (count($recs)) {
            $mvc->saveArray($recs, null, true);
        }
        
        fclose($handle);
        
        $res->html = self::cntToVerbal($res, $mvc->className);
        
        return $res;
    }
    
    
    /**
     * Функция, която импортира еднократно даден csv файл в даден модел
     */
    public static function importOnce($mvc, $file, $fields = array(), $defaults = array(), $format = array(), $delete = false, $isLarge = false)
    {
        // Пътя до файла с данните
        $filePath = getFullPath($file);
        
        // Името на променливата, в която се записва хеша на CSV файла
        $param = 'csvFile' . preg_replace('/[^a-z0-9]+/', '_', $file);
        
        // Хеша на CSV данните
        $hash = md5_file($filePath);
        
        list($pack, ) = explode('_', $mvc->className);
        
        // Конфигурация на пакета 'lab'
        $conf = core_Packs::getConfig($pack);
        
        $cntObj = new stdClass();
        
        try {
            $confHash = $conf->{$param};
        } catch (core_exception_Expect $e) {
            $confHash = null;
        }
        
        if (($confHash != $hash) || ($delete === 'everytime')) {
            
            // Изтриваме предишното съдържание на модела, ако е сетнат $delete
            if ($delete) {
                $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
            }
            
            $cntObj = self::import($mvc, $file, $fields, $defaults, $format, $isLarge);
            
            // Записваме в конфигурацията хеша на последния приложен csv файл
            core_Packs::setConfig($pack, array($param => $hash));
        } else {
            $cntObj = (object) array('created' => 0, 'updated' => 0, 'skipped' => 0, 'html' => "\n<li>Пропуснато импортиране в {$mvc->className}, защото няма промяна в CSV файла</li>");
        }
        
        return $cntObj;
    }
    
    
    /**
     * Импортира съдържанието на посочения CSV файл, когато той е променян
     * Преди импортирането изпразва таблицата,
     */
    public static function importOnceFromZero($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {
        return self::importOnce($mvc, $file, $fields, $defaults, $format, true);
    }
    
    
    /**
     * Импортира съдържанието на посочения CSV файл, когато той е променян
     * Преди импортирането изпразва таблицата,
     */
    public static function largeImportOnceFromZero($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {
        return self::importOnce($mvc, $file, $fields, $defaults, $format, true, true);
    }
    
    
    /**
     * Връща html вербално представяне на резултата от ::import(...)
     */
    public static function cntToVerbal($cntObj, $place = null)
    {
        $res = '';
        
        if ($place) {
            $place = " в {$place}";
        }
        
        if ($cntObj->created) {
            $res .= "\n<li style='color:green;'>Създадени са {$cntObj->created} записа{$place}</li>";
        }
        
        if ($cntObj->updated) {
            $res .= "\n<li style='color:#600;'>Обновени са {$cntObj->updated} записа{$place}</li>";
        }
        
        if ($cntObj->skipped) {
            $res .= "\n<li>Пропуснати са {$cntObj->skipped} записа{$place}</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Създава csv
     *
     * @param array         $recs
     * @param core_FieldSet $fieldSet
     * @param string        $listFields
     * @param array         $params
     *
     * @return string
     */
    public static function createCsv($recs, core_FieldSet $fieldSet, $listFields = null, $params = array())
    {
        $params = arr::make($params, true);
        
        // Редиректваме, ако сме надвишили бройката
        setIfNot($exportCnt, $params['maxExportCnt'], core_Setup::get('EF_MAX_EXPORT_CNT', true));
        if (count($recs) > $exportCnt) {
            $retUrl = getRetUrl();
            if (empty($retUrl)) {
                if ($fieldSet instanceof core_Manager) {
                    if ($fieldSet->haveRightFor('list')) {
                        $retUrl = array($fieldSet, 'list');
                    }
                }
            }
            
            if (empty($retUrl)) {
                $retUrl = array('Index');
            }
            
            redirect($retUrl, false, '|Броят на заявените записи за експорт надвишава максимално разрешения|* - ' . $exportCnt, 'error');
        }
        
        if (isset($listFields)) {
            $listFields = arr::make($listFields, true);
        } else {
            $fieldsArr = $fieldSet->selectFields('');
            $listFields = array();
            foreach ($fieldsArr as $name => $fld) {
                $listFields[$fld->name] = tr($fld->caption);
            }
        }
        
        $delimiter = str_replace(array('&comma;', 'semicolon', 'colon', '&vert;', '&Tab;', 'comma', 'vertical'), array(',', ';', ':', '|', "\t", ',', '|'), csv_Setup::get('DELIMITER'));
        
        if (strlen($delimiter) > 1) {
            $delimiter = html_entity_decode($delimiter, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }
        
        setIfNot($csvDelimiter, $params['delimiter'], $delimiter);
        setIfNot($decPoint, $params['decPoint'], html_entity_decode(csv_Setup::get('DEC_POINT'), ENT_COMPAT | ENT_HTML401, 'UTF-8'), html_entity_decode(core_Setup::get('EF_NUMBER_DEC_POINT', true), ENT_COMPAT | ENT_HTML401, 'UTF-8'));
        setIfNot($dateFormat, $params['dateFormat'], csv_Setup::get('DATE_MASK'), core_Setup::get('EF_DATE_FORMAT', true));
        setIfNot($datetimeFormat, $params['datetimeFormat'], csv_Setup::get('DATE_TIME_MASK'), 'd.m.y H:i');
        setIfNot($thousandsSep, $params['thousandsSep'], '');
        setIfNot($enclosure, $params['enclosure'], '"');
        setIfNot($decimals, $params['decimals'], 2);
        
        // Вземаме колоните, ако са зададени
        if ($params['columns'] != 'none') {
            foreach ($listFields as $fld => $caption) {
                if (!$caption) {
                    $listFields[$fld] = $fld;
                }
            }
            
            $csv = self::getCsvLine($listFields, $csvDelimiter, $enclosure);
        }
        
        // Подготвяме редовете
        foreach ($recs as $rec) {
            $rCsvArr = array();
            foreach ($listFields as $name => $caption) {
                if ($fieldSet->fields[$name]) {
                    $type = $fieldSet->fields[$name]->type;
                } else {
                    $type = new stdClass();
                }
                
                Mode::push('text', 'plain');
                Mode::push('text-export', 'csv');
                if (($type instanceof type_Key) || ($type instanceof type_Key2)) {
                    $value = $type->toVerbal($rec->{$name});
                } elseif ($type instanceof type_Keylist) {
                    $value = $type->toVerbal($rec->{$name});
                } elseif ($type instanceof type_Set) {
                    $value = $type->toVerbal($rec->{$name});
                } elseif ($type instanceof type_Double) {
                    $type->params['decPoint'] = $decPoint;
                    $type->params['thousandsSep'] = $thousandsSep;
                    $type->params['decimals'] = $decimals;
                    $value = $type->toVerbal($rec->{$name});
                } elseif ($type instanceof type_Datetime) {
                    if ($rec->{$name}) {
                        $value = dt::mysql2verbal($rec->{$name}, $datetimeFormat);
                        $value = strip_tags($value);
                    }
                } elseif ($type instanceof type_Date) {
                    if ($rec->{$name}) {
                        $value = dt::mysql2verbal($rec->{$name}, $dateFormat);
                        $value = strip_tags($value);
                    }
                } elseif ($type instanceof type_Richtext && !empty($params['text'])) {
                    Mode::push('text', $params['text']);
                    $value = $type->toVerbal($rec->{$name});
                    Mode::pop('text');
                } elseif ($type instanceof fileman_FileType) {
                    $value = toUrl(array('F', 'D', $rec->{$name}), 'absolute');
                } elseif ($type instanceof type_Enum) {
                    $value = $type->toVerbal($rec->{$name});
                } elseif ($type instanceof fileman_FileSize) {
                    $value = $type->toVerbal($rec->{$name});
                } else {
                    $value = $rec->{$name};
                }
                Mode::pop('text-export');
                Mode::pop('text');
                
                $rCsvArr[] = $value;
            }
            
            $csv .= ($csv) ? "\n" : '';
            
            $csv .= self::getCsvLine($rCsvArr, $csvDelimiter, $enclosure);
        }
        
        return $csv;
    }
    
    
    /**
     * Масива го преобразува в ред за CSV
     *
     * @param array  $valsArr
     * @param string $delimiter
     * @param string $enclosure
     *
     * @return string
     */
    public static function getCsvLine($valsArr, $delimiter, $enclosure, $trim = true)
    {
        $csvLine = null;
        foreach ($valsArr as $v) {
            if ($trim) {
                $v = trim($v);
            }
            $v = self::prepareCsvVal($v, $delimiter, $enclosure);
            $csvLine = (isset($csvLine)) ? $csvLine . $delimiter : '';
            $csvLine .= $v;
        }
        
        return $csvLine;
    }
    
    
    /**
     * Подоготвя стойност за CSV
     *
     * @param string $val
     * @param string $delimiter
     * @param string $enclosure
     *
     * @return string
     */
    protected static function prepareCsvVal($val, $delimiter, $enclosure)
    {
        $enclosure = preg_quote($enclosure, '/');
        $delimiter = preg_quote($delimiter, '/');
        
        if (preg_match("/\r|\n|{$delimiter}|{$enclosure}/", $val)) {
            $val = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $val) . $enclosure;
        }
        
        return $val;
    }
    
    
    /**
     * Връща масив с данните от CSV стринга
     *
     * @param string $csvData   - csv данни
     * @param char   $delimiter - разделител
     * @param char   $enclosure - ограждане
     * @param string $firstRow  - първи ред данни или имена на колони
     *
     * @return array $rows - масив с парсирани редовете на csv-то
     */
    public static function getCsvRows($csvData, $delimiter = null, $enclosure = null, $firstRow = 'columnNames')
    {
        $rowsArr = self::getCsvRowsFromFile($csvData, array('delimiter' => $delimiter, 'enclosure' => $enclosure, 'firstRow' => $firstRow));
        
        return $rowsArr['data'];
    }
    
    
    /**
     * Връща имената на колоните от CSV файла
     *
     * @param unknown $csvData
     * @param string  $delimiter
     * @param string  $enclosure
     * @param bool    $firstEmpty
     * @param bool    $checkErr
     *
     * @return array
     */
    public static function getCsvColNames($csvData, $delimiter = null, $enclosure = null, $firstEmpty = false, $checkErr = false)
    {
        $rowsArr = self::getCsvRowsFromFile($csvData, array('delimiter' => $delimiter, 'enclosure' => $enclosure, 'firstRow' => 'columnNames'));
        
        if ($checkErr && $rowsArr['error']) {
            
            return array();
        }
        
        if ($rowsArr['firstRow']) {
            $resArr = (array) $rowsArr['firstRow'];
        } else {
            $resArr = $rowsArr['data'][0];
        }
        
        if ($firstEmpty) {
            $resArr = arr::combine(array(null => ''), $resArr);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща редовете от CSV файла
     *
     * @param string $path
     * @param array  $params
     *
     * @return array
     */
    public static function getCsvRowsFromFile($csvData, $params = array())
    {
        list($handle, $params['delimiter'], $params['enclosure'], $params['firstRow']) = self::analyze($csvData, $params['delimiter'], $params['enclosure']);
        
        if ($params['delimiter'] === null) {
            $params['delimiter'] = chr(0);
        }
        if ($params['enclosure'] === null) {
            $params['enclosure'] = chr(1);
        }
        
        setIfNot($params['length'], 0);
        
        setIfNot($params['escape'], '\\');
        setIfNot($params['firstRow'], 'columnNames');
        setIfNot($params['check'], true);
        setIfNot($params['skip'], '#');
        
        $resArr = array();
        $resArr['firstRow'] = array();
        $resArr['error'] = false;
        $resArr['data'] = array();
        
        $isFirst = true;
        $oldCnt = null;
        
        while (($data = fgetcsv($handle, null, $params['delimiter'], $params['enclosure'], $params['escape'])) !== false) {
            
            // Пропускаме празните линии
            if (!count($data) || (count($data) == 1 && trim($data[0]) == '')) {
                continue;
            }
            
            // Пропускаме редовете със знака указан в $skip
            if ($data[0]{0} == $params['skip']) {
                continue;
            }
            
            if ($params['check']) {
                $cnt = count($data);
                
                if (!$resArr['error'] && isset($oldCnt) && ($cnt != $oldCnt)) {
                    $resArr['error'] = true;
                }
                
                $oldCnt = $cnt;
            }
            
            array_unshift($data, '');
            unset($data[0]);
            
            if (($params['firstRow'] == 'columnNames') && $isFirst) {
                $isFirst = false;
                $resArr['firstRow'] = $data;
            } else {
                $resArr['data'][] = $data;
            }
        }
        
        $resArr['params'] = $params;
        
        return $resArr;
    }
    
    
    public static function getColumnTypes($data)
    {
        $maxRows = 1000;
        $res = array();
        foreach ($data as $row) {
            foreach ($row as $i => $col) {
                $col = trim($col);
                if (strlen($col) == 0) {
                    continue;
                }
                
                // Положително цяло число
                if ($res[$i]['unsigned'] !== false && preg_match('/^[0-9 ]*$/', $col)) {
                    $res[$i]['unsigned'] = true;
                } else {
                    $res[$i]['unsigned'] = false;
                }
                
                // Цяло число
                if ($res[$i]['int'] !== false && preg_match("/^[\+\-]?[0-9 ]*$/", $col)) {
                    $res[$i]['int'] = true;
                } else {
                    $res[$i]['int'] = false;
                }
                
                // Пари
                if ($res[$i]['money'] !== false && preg_match("/^[\+\-]?[0-9 ]*[\,\.][0-9]{2}$/", $col)) {
                    $res[$i]['money'] = true;
                } else {
                    $res[$i]['money'] = false;
                }
                
                // Число
                if ($res[$i]['number'] !== false && preg_match("/^[\+\-]?[0-9 ]*([\,\.][0-9]*|)$/", $col)) {
                    $res[$i]['number'] = true;
                } else {
                    $res[$i]['number'] = false;
                }
                
                // Процент
                if ($res[$i]['percent'] !== false && preg_match("/^[\+\-]?[0-9 ]*[\,\.][0-9]*\%$/", $col)) {
                    $res[$i]['percent'] = true;
                } else {
                    $res[$i]['percent'] = false;
                }
                
                // Телефон
                
                // Код
                if ($res[$i]['code'] !== false && preg_match("/^[0-9A-Z \-\_]{3,16}$/i", $col)) {
                    $res[$i]['code'] = true;
                } else {
                    $res[$i]['code'] = false;
                }
                
                // Ник
                
                // Имейли
                if ($res[$i]['email'] !== false && type_Email::isValidEmail($col)) {
                    $res[$i]['email'] = true;
                } else {
                    $res[$i]['email'] = false;
                }
                
                // Имейли
                if ($res[$i]['emails'] !== false && !count(type_Emails::getInvalidEmails($col))) {
                    $res[$i]['emails'] = true;
                } else {
                    $res[$i]['emails'] = false;
                }
                
                
                // URL
                
                $res[$i]['minLen'] = $res[$i]['minLen'] ? min($res[$i]['minLen'], strlen($col)) : strlen($col);
                $res[$i]['maxLen'] = $res[$i]['maxLen'] ? max($res[$i]['maxLen'], strlen($col)) : strlen($col);
            }
            
            if ($maxRows-- == 0) {
                break;
            }
        }
        
        $res1 = array();
        
        foreach ($res as $i => $arr) {
            if ($maxRows < 999 && $arr['minLen'] == $arr['maxLen']) {
                $res1['fixed_' . $i] = true;
            }
            if (is_array($arr)) {
                foreach ($arr as $type => $bool) {
                    if ($bool) {
                        $res1[$i] = $type;
                        break;
                    }
                }
            }
        }
        
        return $res1;
    }
    
    
    /**
     * Функция, която се опитва да анализира CSV файл
     */
    public static function analyze($csv, $delimiter = null, $enclosure = null)
    {
        // Колко максимално линии да рзглеждаме
        $maxLinesCheck = 100;
        
        // Махаме BOM, ако има
        $bom = pack('H*', 'EFBBBF');
        $csv = preg_replace("/^${bom}/", '', $csv);
        
        // Правим новия ред - \n
        $nl = "\n";
        $csv = str_replace(array("\r\n", "\n\r", "\r"), $nl, $csv);
        
        // Конвертираме към UTF-8
        $csv = i18n_Charset::convertToUtf8($csv, array('UTF-8', 'WIN1251'));
        
        $csv = str_replace(chr(194).chr(160), '', $csv);
        
        // Определяне на формата
        if (strlen($delimiter)) {
            $delimiter = str_replace('tab', "\t", $delimiter);
            $dArr = array($delimiter);
        } else {
            $dArr = array('|', "\t", ',', ';', ' ', ':');
        }
        
        if (strlen($enclosure)) {
            $eArr = array($enclosure);
        } else {
            $eArr = array('"', '\'');
        }
        
        $nlCnt = substr_count($csv, $nl);
        
        // $csvSample = implode($nl, array_slice(explode($nl, $csv, $maxLinesCheck * 10 + 1), 0, $maxLinesCheck * 10));
        
        // Запис на файла в паметта
        $fp = fopen('php://memory', 'r+');
        fputs($fp, $csv);
        $best = null;
        
        foreach ($dArr as $d) {
            foreach ($eArr as $e) {
                if (strpos($csv, $d) === false) {
                    continue;
                }
                
                rewind($fp);
                
                $res = array();
                $lCnt = 0;
                $totalFields = 0;
                
                // Опитваме да парсираме първите 100 реда
                while ((($data = fgetcsv($fp, null, $d, $e)) !== false) && ($lCnt <= $maxLinesCheck)) {
                     
                     // Пропускаме празните линии
                    if (!is_array($data) || !count($data) || (count($data) == 1 && trim($data[0]) == '')) {
                        continue;
                    }
                    
                    $res[] = $data;
                    $totalFields += count($data);
                    $lCnt++;
                }
                
                if (!$lCnt) {
                    continue;
                }
                
                // Оценка: Броя на редовете и елементите във всеки ред, като се броят само редовете,
                // които имат брой полета, равен на средния
                $cellsPerRow = round($totalFields / $lCnt);
                
                $points = 0;
                foreach ($res as $row) {
                    $cnt = count($row);
                    if ($cnt == $cellsPerRow) {
                        ++$points;
                    } else {
                        --$points;
                    }
                }
                
                // Добавка за срещанията на ображдащия символ до разделител или нов ред
                $deCntL = substr_count($csv, $d . $e) + substr_count($csv, $nl . $e);
                $deCntR = substr_count($csv, $e . $d) + substr_count($csv, $e . $nl);
                if ($nlCnt) {
                    $points += 0.4 * (($deCntL > 0) && ($deCntL == $deCntR)) * count($res) + min($deCntL, $deCntR) / $nlCnt;
                }
                $points -= ($deCntL > 0) && ($deCntL != $deCntR) * count($res) ;
                
                // Среща ли се $е самостоятелно
                preg_match_all("/[^\\{$d}\\{$e}]\\{$e}[^\\{$d}\\{$e}]/u", $d . str_replace($nl, $d, $csv) . $d, $matches);
                $soloUse = count($matches[0]);
                $points -= $soloUse;
                $points += 0.6 * ($soloUse == 1);
                
                if (!isset($best) || $best < $points) {
                    $delimiter = $d;
                    $enclosure = $e;
                    $best = $points;
                    $parse = $res;
                }
            }
        }
        
        if ($delimiter === '') {
            $delimiter = null;
        }
        
        if ($enclosure === '') {
            $enclosure = null;
        }
        
        rewind($fp);
        
        $fr = 0;
        
        if (is_array($parse[0])) {
            foreach ($parse[0] as $i => $c0) {
                $c1 = $parse[1][$i];
                $c2 = $parse[2][$i];
                
                if (strlen(trim($c0)) == 0) {
                    $fr += -1;
                } elseif (preg_match('/[0-9]/', $c0)) {
                    $fr += -0.5;
                } elseif (preg_match('/@/', $c0)) {
                    $fr += -1;
                } elseif (preg_match("/[0-9\@]/", $c1)) {
                    ++$fr;
                }
                
                if (strlen($c0)) {
                    if ("{$c0}" === "{$c1}") {
                        $fr += -1;
                    } elseif ("{$c1}" === "{$c2}") {
                        $fr += 0.5;
                    }
                }
            }
        }
        
        return array($fp, $delimiter, $enclosure, $fr > 0 ? 'columnNames' : 'data');
    }
    
    
    /**
     * Определя разделителя на групи
     */
    public static function getDevider($str)
    {
        if (strpos($str, '|')) {
            $d = '|';
        } elseif (strpos($str, ';')) {
            $d = ';';
        } else {
            $d = ',';
        }
        
        return $d;
    }
    
    
    // Определяме 4-ките Д,Е,C и Еск които могат да бъдат във файла
    // Рейтингуваме четворките
    // Последователно се пробваме да извадим редовете макс(1000 или Зададените + Офсета)
    // Там, където успеем, обявяваме това за резултата
    // Гледаме дали първия му ред е колони
    
    // Анализираме типовете на колонките, като се опитваме да открием съвпадение
    
    
    public static function parse($csvString, $papams = array())
    {
    }
}
