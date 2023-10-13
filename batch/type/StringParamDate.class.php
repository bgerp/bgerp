<?php


/**
 * Клас  'batch_type_ManifactureString' - Тип за вевеждане на партидност от типа Номер + Параметър + Годен до
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
class batch_type_StringParamDate extends type_Varchar
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

        $foundDate = null;
        $errorArr = array();
        if(empty($valueArr['s'])){
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if(strpos($valueArr['s'], $delimiter) !== false){
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }

            if(strpos($valueArr['s'], 'L') === 0){
                $parsedDates = array();
                $string = trim($valueArr['s'],'L');
                $string = str_replace('.', '', $string);
                $string = str_replace('/', '', $string);

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
        if(!empty($value)){
            if (is_array($value)) {
                $valString = $value['s'];
                $valManifacture = $value['m'];
                $valDate = $value['d'];
            } else {
                list($valString, $valManifacture, $valDate) = explode('|', $value);
            }
        } else {
            $valString = $valDate = null;
            $valManifacture = $params[$this->params['manifactureParamId']];
        }

        $manifactureOptions = array();
        $Driver = cat_Params::getDriver($this->params['manifactureParamId']);
        if(cat_Params::haveDriver($this->params['manifactureParamId'], 'cond_type_Enum')){
            $paramRec = cat_Params::fetch($this->params['manifactureParamId']);
            $manifactureOptions = $Driver::text2Options($paramRec->options);
        }

        $attrString = $attrMan = $attrDate = $attr;
        $attrString['placeholder'] = 'Номер';
        $attrString['id'] = "batchNameS". rand(1, 100);
        $tpl = $this->createInput($name . '[s]', $valString, $attrString);

        $attrMan['placeholder'] = 'Произв.';
        $attrMan['id'] = "batchNameM". rand(1, 100);
        if(countR($manifactureOptions)){
            $tpl->append(ht::createCombo($name . '[m]', $valManifacture, $attrMan, $manifactureOptions));
        } else {
            $tpl->append($this->createInput($name . '[m]', $valManifacture, $attrMan));
        }

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
