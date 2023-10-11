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

        $errorArr = array();
        if(empty($valueArr['s'])){
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if(strpos($valueArr['s'], $delimiter) !== false){
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        if(empty($valueArr['m'])){
            $errorArr[] = 'Липсва производител';
        } else {
            if(strpos($valueArr['m'], $delimiter) !== false){
                $errorArr[] = "В производителя не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        if(empty($valueArr['d'])){
            $errorArr[] = 'Липсва дата';
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

        return implode('|', $valueArr);
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
            $productId = $this->params['productId'];

            $params = cat_Products::getParams($productId);
            $expiryParamId = cat_Params::fetchIdBySysId('expiryTime');

            $time = $params[$expiryParamId];
            if (empty($time)) {
                $time = $this->params['defaultTime'];
            }
            if (!empty($time)) {
                $valDate = dt::addSecs($time, dt::now());
                $valDate = dt::mysql2verbal($valDate, $this->params['format']);
            }
            $valManifacture = $params[$this->params['manifactureParamId']];
        }

        $manifactureOptions = array();
        $Driver = cat_Params::getDriver($this->params['manifactureParamId']);
        if(cat_Params::haveDriver($this->params['manifactureParamId'], 'cond_type_Enum')){
            $paramRec = cat_Params::fetch($this->params['manifactureParamId']);
            $manifactureOptions = $Driver::text2Options($paramRec->options);
        }

        $attr['title'] = 'Номер';
        $attr['id'] = "batchNameS". rand(1, 100);
        $tpl = $this->createInput($name . '[s]', $valString, $attr);

        $attr['placeholder'] = 'Произв.';
        $attr['id'] = "batchNameM". rand(1, 100);
        if(countR($manifactureOptions)){
            $tpl->append(ht::createCombo($name . '[m]', $valManifacture, $attr, $manifactureOptions));
        } else {
            $tpl->append($this->createInput($name . '[m]', $valManifacture, $attr));
        }

        $attr['placeholder'] = 'Годен до';
        $attr['id'] = "batchNameD". rand(1, 100);
        $tpl->append($this->createInput($name . '[d]', $valDate, $attr));
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
