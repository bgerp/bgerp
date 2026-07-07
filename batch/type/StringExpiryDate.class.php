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
     * Получава дата от двете входни стойности
     */
    public function fromVerbal($value)
    {
        if(empty($value)) return;

        $valueArr = $value;
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');
        if(is_scalar($value)){
            $value = str_replace($delimiter, '|', $value);
            $valueParsed = explode('|', $value);
            $valueArr = array();
            $valueArr['s'] = trim($valueParsed[0]);
            $valueArr['d'] = trim($valueParsed[1]);
        }

        if(empty($valueArr['s']) && empty($valueArr['d'])) return;

        $errorArr = array();
        if(empty($valueArr['s'])){
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if(strpos($valueArr['s'], $delimiter) !== false){
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        $foundDate = $this->extractDateFromSerial($valueArr['s']);

        $res = $valueArr;
        if(empty($valueArr['d'])){
            $defaultDate = $this->getDefaultExpirationDate($this->params['productId'], $foundDate);
            $res['d'] = $defaultDate;
            if(empty($defaultDate)){
                $errorArr[] = 'Липсва дата';
            }
        } else {
            $d = DateTime::createFromFormat($this->params['format'], $valueArr['d']);
            $isValidDate = $d && $d->format($this->params['format']) === $valueArr['d'];

            if (!$isValidDate) {
                $example = dt::mysql2verbal(null, $this->params['format']);
                $errorArr[] = "Годен до трябва да е ВАЛИДНА дата във формата|* <b>{$example}</b>";
            }

            if(strpos($valueArr['d'], $delimiter) !== false){
                $errorArr[] = "Във формата на датата не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        if(countR($errorArr)){
            $this->error = implode(', ', $errorArr);
            return false;
        }

        $res['s'] = trim($res['s']);
        return implode('|', $res);
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
     * Помощен метод за извличане на въведените стойности
     */
    protected function prepareInputValue($name, $value, $partsCount)
    {
        $useValue = $this->formWithErrors ? Request::get($name) : $value;
        $useValue = empty($value) ? $value : $useValue;
        
        $res = array_fill_keys(array('s', 'm', 'd'), null);

        if (!empty($useValue)) {
            if (is_array($useValue)) {
                $res['s'] = $useValue['s'] ?? null;
                $res['m'] = $useValue['m'] ?? null;
                $res['d'] = $useValue['d'] ?? null;
            } else {
                $parts = explode('|', $useValue);
                $res['s'] = $parts[0] ?? null;
                if ($partsCount == 3) {
                    $res['m'] = $parts[1] ?? null;
                    $res['d'] = $parts[2] ?? null;
                } else {
                    $res['d'] = $parts[1] ?? null;
                }
            }
        }
        return $res;
    }

    
    /**
     * Помощна ф-я връщаща дефолтния срок на годност
     *
     * @param $productId
     * @param null $startDate
     * @param null $params
     * @return datetime|null $date
     */
    protected function getDefaultExpirationDate($productId, $startDate = null, $params = null)
    {
        $date = null;
        $params = is_array($params) ? $params : cat_Products::getParams($productId);
        $startDate = $startDate ?? dt::now();

        $expiryParamId = cat_Params::fetchIdBySysId('expiryTime');
        $time = $params[$expiryParamId];
        if (empty($time)) {
            $time = $this->params['defaultTime'];
        }
        if (!empty($time)) {
            $date = dt::addSecs($time, $startDate);
            $date = dt::mysql2verbal($date, $this->params['format']);
        }
        return $date;
    }


    /**
     * Генерира поле за въвеждане на дата, състоящо се от
     * селектори за годината, месеца и деня
    */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $params = isset($this->params['productId']) ? cat_Products::getParams($this->params['productId']) : array();
        $datePlaceholder = $this->getDefaultExpirationDate($this->params['productId'], null, $params);
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');

        $stringSgt = $dateSgt = array();
        if (is_array($this->suggestions)) {
            unset($this->suggestions['']);
            foreach ($this->suggestions as $sgt) {
                $sgtOpt = explode($delimiter, $sgt);
                $stringSgt["{$sgtOpt[0]}"] = $sgtOpt[0];
                $dateSgt["{$sgtOpt[1]}"] = $sgtOpt[1];
            }
        }

        $val = $this->prepareInputValue($name, $value, 2);

        $this->suggestions = countR($stringSgt) ? array('' => '') + $stringSgt : array();
        $attrString = $attr;
        $attrString['placeholder'] = 'Номер';
        $attrString['id'] = "batchNameS" . rand(1, 100);
        $tpl = $this->createInput($name . '[s]', $val['s'], $attrString);

        $this->suggestions = countR($dateSgt) ? array('' => '') + $dateSgt : array();
        $attrDate = $attr;
        $attrDate['placeholder'] = !empty($datePlaceholder) ? $datePlaceholder : 'Годен до';
        $attrDate['id'] = "batchNameD" . rand(1, 100);
        $tpl->append($this->createInput($name . '[d]', $val['d'], $attrDate));

        return new ET('<span style="white-space:nowrap;">[#1#]</span>', $tpl);
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
