<?php


/**
 * Клас  'batch_type_StringExpiryDate' - Тип за вевеждане на партидност от типа Номер + Годен до
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class batch_type_StringExpiryDate extends type_Varchar
{
    /**
     * Ред и брой компоненти на партидата (override-ва се в наследници)
     */
    protected $partsOrder = array('s', 'd');


    /**
     * Получава дата от двете входни стойности
     */
    public function fromVerbal($value)
    {
        if (!isset($value) || $value === '' || $value === array()) return;

        $valueArr = $value;
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');
        $size = $this->params['sizeOfBatch'] ?? null;

        if (is_scalar($value)) {
            $value = str_replace($delimiter, '|', $value);
            $valueParsed = explode('|', $value);
            $valueArr = array();
            $valueArr['s'] = isset($valueParsed[0]) ? trim($valueParsed[0]) : '';
            $valueArr['d'] = isset($valueParsed[1]) ? trim($valueParsed[1]) : '';
        }

        if ((!isset($valueArr['s']) || $valueArr['s'] === '') && (!isset($valueArr['d']) || $valueArr['d'] === '')) return;

        $errorArr = array();
        
        if (!isset($valueArr['s']) || $valueArr['s'] === '') {
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if (strpos($valueArr['s'], $delimiter) !== false) {
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
            if (isset($size) && $size !== '' && mb_strlen($valueArr['s']) > (int) $size) {
                $errorArr[] = "Номерът е над допустимата дължина от|* <b>{$size}</b> символа";
            }
        }

        $foundDate = $this->extractDateFromSerial($valueArr['s']);

        $res = $valueArr;
        if (empty($valueArr['d'])) {
            $defaultDate = $this->getDefaultExpirationDate($this->params['productId'], $foundDate);
            $res['d'] = $defaultDate;
            if (empty($defaultDate)) {
                $errorArr[] = 'Липсва дата';
            }
        } else {
            $d = DateTime::createFromFormat($this->params['format'], $valueArr['d']);
            $isValidDate = $d && $d->format($this->params['format']) === $valueArr['d'];

            if (!$isValidDate) {
                $example = dt::mysql2verbal(null, $this->params['format']);
                $errorArr[] = "Годен до трябва да е ВАЛИДНА дата във формата|* <b>{$example}</b>";
            }

            if (strpos($valueArr['d'], $delimiter) !== false) {
                $errorArr[] = "Във формата на датата не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        if (countR($errorArr)) {
            $this->error = (empty($this->error)) ? implode(', ', $errorArr) : $this->error . ', ' . implode(', ', $errorArr);
            return false;
        }

        $res['s'] = trim($res['s']);
        
        $filteredRes = array_filter($res, function($val) {
            return $val !== null && $val !== '';
        });

        return implode('|', $filteredRes);
    }


    /**
     * Извлича и намира най-близката валидна дата, кодирана в серийния номер
     */
    protected function extractDateFromSerial($serial)
    {
        if (empty($serial)) return null;

        $matches = array();
        if (!preg_match_all("/\d+/", $serial, $matches)) return null;
        
        $string = implode($matches[0]);
        if (!is_numeric($string)) return null;

        $strlen = strlen($string);
        $masks = array();
        
        if ($strlen == 6) {
            $masks = array('dmy', 'ymd');
        } elseif ($strlen == 4) {
            $masks = array('ym', 'my');
        } elseif ($strlen == 8) {
            $masks = array('dmY', 'Ymd');
        }

        $parsedDates = array();
        foreach ($masks as $mask) {
            $parsed = date_parse_from_format($mask, $string);
            if (!$parsed['error_count'] && !$parsed['warning_count']) {
                if (!$parsed['day']) {
                    $parsedDate = dt::getLastDayOfMonth("{$parsed['year']}-{$parsed['month']}");
                } else {
                    $parsedDate = "{$parsed['year']}-{$parsed['month']}-{$parsed['day']}";
                }
                $parsedDates[strtotime($parsedDate)] = $parsedDate;
            }
        }

        if (countR($parsedDates)) {
            $diffArr = array();
            $nowTime = strtotime(dt::now());
            array_walk($parsedDates, function($a, $k) use (&$diffArr, $nowTime) {
                $diffArr[abs($nowTime - $k)] = $a;
            });
            ksort($diffArr);
            return $diffArr[key($diffArr)];
        }

        return null;
    }

    
    /**
     * Помощен метод за извличане на въведените стойности, спрямо $this->partsOrder
     */
    protected function prepareInputValue($name, $value)    
    {
        $useValue = $this->formWithErrors ? Request::get($name) : $value;
        // Стриктна проверка за празна стойност
        $useValue = ($value === '' || $value === null) ? $value : $useValue;

        $order = $this->partsOrder;
        $res = array_fill_keys($order, null);

        if ($useValue !== null && $useValue !== '') {
            if (is_array($useValue)) {
                foreach ($order as $key) {
                    $res[$key] = $useValue[$key] ?? null;
                }
            } else {
                $parts = explode('|', $useValue);
                foreach ($order as $idx => $key) {
                    $res[$key] = $parts[$idx] ?? null;
                }
            }
        }
        return $res;
    }
    
    /**
     * Помощна ф-я връщаща дефолтния срок на годност
     *
     * @param int $productId
     * @param null $startDate
     * @return datetime|null $date
     *
     */
    protected function getDefaultExpirationDate($productId, $startDate = null)
    {
        $date = null;
        $startDate = $startDate ?? ($this->params['startDate'] ?? dt::now());
        $productTime = isset($productId) ? cat_Products::getParams($productId, 'expiryTime') : null;

        if (empty($productTime)) {
            $productTime = $this->params['defaultTime'];
        }
        if (!empty($productTime)) {
            $date = dt::addSecs($productTime, $startDate);
            $date = dt::mysql2verbal($date, $this->params['format']);
        }
        return $date;
    }


    /**
    * Динамично генериране на входовете спрямо дефинирания шаблон ($partsOrder)
    */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $productId = $this->params['productId'] ?? null;
        $datePlaceholder = $this->getDefaultExpirationDate($productId);
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');

        $order = $this->partsOrder;

        // Вземаме стойностите правилно разпределени по ключове, спрямо $this->partsOrder
        $val = $this->prepareInputValue($name, $value);
        
        // Разделяме подсказките (suggestions) динамично
        $suggestionsArr = array();
        if (is_array($this->suggestions)) {
            unset($this->suggestions['']);
            foreach ($this->suggestions as $sgt) {
                $sgtOpt = explode($delimiter, $sgt);
                foreach ($order as $idx => $key) {
                    if (isset($sgtOpt[$idx])) {
                        $suggestionsArr[$key]["{$sgtOpt[$idx]}"] = $sgtOpt[$idx];
                    }
                }
            }
        }

        $tpl = new ET('<span style="white-space:nowrap;">[#INPUTS#]</span>');

        foreach ($order as $key) {
            $attrComp = $attr;
            $attrComp['id'] = "batchName" . strtoupper($key) . '_' . uniqid();

            $inputHtml = $this->renderComponentInput($key, $name, $val, $attrComp, $suggestionsArr, $productId, $datePlaceholder);

            $tpl->append($inputHtml, 'INPUTS');
        }

        return $tpl;
    }


    /**
     * Рендира вход за един компонент от партидата. Наследниците могат да разширят
     * с обработка на допълнителни ключове, без бащата да знае за тях.
     */
    protected function renderComponentInput($key, $name, $val, $attrComp, $suggestionsArr, $productId, $datePlaceholder)
    {
        switch ($key) {
            case 's':
                $attrComp['placeholder'] = 'Номер';
                $this->suggestions = countR($suggestionsArr['s'] ?? null) ? array('' => '') + $suggestionsArr['s'] : array();
                return $this->createInput($name . '[s]', $val['s'], $attrComp);

            case 'd':
                $attrComp['placeholder'] = !empty($datePlaceholder) ? $datePlaceholder : 'Годен до';
                $this->suggestions = countR($suggestionsArr['d'] ?? null) ? array('' => '') + $suggestionsArr['d'] : array();
                return $this->createInput($name . '[d]', $val['d'], $attrComp);
        }

        return '';
    }
    

    /**
     * Кой може да избере драйвера
    */
    public function toVerbal($value)
    {
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');

        return str_replace('|', $delimiter, $value);
    }
}
