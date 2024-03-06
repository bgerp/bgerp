<?php


/**
 * Клас  'batch_type_ManifactureString' - Тип за вевеждане на партидност от типа Номер + Производител + Годен до
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class batch_type_StringManufacturerExpiryDate extends type_Varchar
{
    /**
     * Получава дата от трите входни стойности
     */
    public function fromVerbal($value)
    {
        if(empty($value)) return;

        // Ако стойността е mysql-ска дата, да се обърне към масив
        $valueArr = $value;
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');
        if(is_scalar($value)){
            $value = str_replace($delimiter, '|', $value);
            $valueParsed = explode('|', $value);
            $valueArr = array();
            $valueArr['s'] = trim($valueParsed[0]);
            $valueArr['m'] = trim($valueParsed[1]);
            $valueArr['d'] = trim($valueParsed[2]);
        }

        // Ако нищо не е въведено, значи няма да се изсикват други полета
        if(empty($valueArr['s']) && empty($valueArr['m']) && empty($valueArr['d'])) return;

        $foundDate = null;
        $errorArr = array();
        if(empty($valueArr['s'])){
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if(strpos($valueArr['s'], $delimiter) !== false){
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }

            $matches = array();
            if(preg_match_all("/\d+/", $valueArr['s'], $matches)){
                $string = implode($matches[0]);

                if(is_numeric($string)){
                    $strlen = strlen($string);
                    $masks = array();
                    if($strlen == 6){
                        $masks = array('dmy', 'ymd');
                    } elseif($strlen == 4){
                        $masks = array('ym', 'my');
                    } elseif($strlen == 8){
                        $masks = array('dmY', 'Ymd');
                    }

                    $parsedDates = array();
                    foreach ($masks as $mask){
                        $parsed = date_parse_from_format($mask, $string);
                        if(!$parsed['error_count'] && !$parsed['warning_count']){
                            if(!$parsed['day']){
                                $parsedDate = dt::getLastDayOfMonth("{$parsed['year']}-{$parsed['month']}");
                            } else {
                                $parsedDate = "{$parsed['year']}-{$parsed['month']}-{$parsed['day']}";
                            }
                            $parsedDates[strtotime($parsedDate)] = $parsedDate;
                        }
                    }

                    if(countR($parsedDates)){
                        $diffArr = array();
                        $nowTime = strtotime(dt::now());
                        array_walk($parsedDates, function($a, $k) use (&$diffArr, $nowTime){
                            $diffArr[abs($nowTime - $k)] = $a;
                        });
                        ksort($diffArr);
                        $foundDate = $diffArr[key($diffArr)];
                    }
                }
            }
        }

        if(empty($valueArr['m'])){
            $errorArr[] = 'Липсва производител';
        } else {
            if(strpos($valueArr['m'], $delimiter) !== false){
                $errorArr[] = "В производителя не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        $res = $valueArr;
        if(empty($valueArr['d'])){
            $defaultDate = $this->getDefaultExpirationDate($this->params['productId'], $foundDate);
            $res['d'] = $defaultDate;
            if(empty($defaultDate)){
                $errorArr[] = 'Липсва дата';
            }
        } else {
            if(!dt::checkByMask($valueArr['d'], $this->params['format'])){
                $example = dt::mysql2verbal(null, $this->params['format']);
                $errorArr[] = "Годен до трябва да е във формата|* <b>{$example}</b>";
            }

            if(strpos($valueArr['d'], $delimiter) !== false){
                $errorArr[] = "В формата на датата не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        if(countR($errorArr)){
            $this->error = implode(', ', $errorArr);

            return false;
        }

        $res['s'] = trim($res['s']);
        $res['m'] = trim($res['m']);

        return implode('|', $res);
    }


    /**
     * Помощна ф-я връщаща дефолтния срок на годност
     *
     * @param $productId
     * @param null $startDate
     * @param null $params
     * @return datetime|null $date
     */
    private function getDefaultExpirationDate($productId, $startDate = null, $params = null)
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
        $params = array();
        if(isset($productId)) {
            $params = cat_Products::getParams($productId);
        }

        $datePlaceholder = $this->getDefaultExpirationDate($this->params['productId'], null, $params);
        $manifactureOptions = batch_ManufacturersPerProducts::getArray($this->params['folderId'], $this->params['productId']);

        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');
        $stringSgt = $dateSgt = array();
        if(is_array($this->suggestions)){
            unset($this->suggestions['']);
            foreach ($this->suggestions as $sgt){
                $sgtOpt = explode($delimiter, $sgt);
                $stringSgt[] = $sgtOpt[0];
                $dateSgt[] = $sgtOpt[2];
            }
        }
        $this->suggestions = countR($stringSgt) ? array('' => '') + $stringSgt : array();

        // Ако има грешка във формата се взимат данните от рекуеста а е не от $value
        $useValue = $this->formWithErrors ? Request::get($name) : $value;
        $useValue = empty($value) ? $value : $useValue;
        if(!empty($useValue)){
            if (is_array($useValue)) {
                $valString = $useValue['s'];
                $valManifacture = $useValue['m'];
                $valDate = $useValue['d'];
            } else {
                list($valString, $valManifacture, $valDate) = explode('|', $useValue);
            }
        } else {
            $valString = $valDate = null;
            if(!$this->params['autohide']){
                $valManifacture = key($manifactureOptions);
            }
        }

        $attrString = $attrMan = $attrDate = $attr;
        $attrString['placeholder'] = 'Номер';
        $attrString['id'] = "batchNameS". rand(1, 100);
        $tpl = $this->createInput($name . '[s]', $valString, $attrString);

        $attrMan['placeholder'] = 'Произв.';
        $attrMan['id'] = "batchNameM". rand(1, 100);
        $this->suggestions = $manifactureOptions;
        $tpl->append($this->createInput($name . '[m]', $valManifacture, $attrMan));

        $this->suggestions = countR($dateSgt) ? array('' => '') + $dateSgt : array();
        $attrDate['placeholder'] = !empty($datePlaceholder) ? $datePlaceholder : 'Годен до';
        $attrDate['id'] = "batchNameD". rand(1, 100);
        $tpl->append($this->createInput($name . '[d]', $valDate, $attrDate));
        $tpl = new ET('<span style="white-space:nowrap;">[#1#]</span>', $tpl);

        return $tpl;
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
