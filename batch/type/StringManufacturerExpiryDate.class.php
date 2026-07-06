<?php

/**
 * Клас  'batch_type_StringManufacturerExpiryDate' - Номер + Производител + Годен до
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 *
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class batch_type_StringManufacturerExpiryDate extends batch_type_StringExpiryDate
{
    /**
     * Получава дата от трите входни стойности
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
            $valueArr['m'] = trim($valueParsed[1]);
            $valueArr['d'] = trim($valueParsed[2]);
        }

        if(empty($valueArr['s']) && empty($valueArr['m']) && empty($valueArr['d'])) return;

        $errorArr = array();
        if(empty($valueArr['s'])){
            $errorArr[] = 'Задайте номер на партидата';
        } else {
            if(strpos($valueArr['s'], $delimiter) !== false){
                $errorArr[] = "В номера не трябва да се съдържа|* <b>{$delimiter}</b>";
            }
        }

        // Извикваме наследения метод от Бащата
        $foundDate = $this->extractDateFromSerial($valueArr['s']);

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
     * Генерира поле за въвеждане на дата, състоящо се от
     * селектори за годината, месеца и деня
    */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $productId = $this->params['productId'] ?? null;
        $params = isset($productId) ? cat_Products::getParams($productId) : array();
        
        $datePlaceholder = $this->getDefaultExpirationDate($productId, null, $params);
        $manifactureOptions = batch_ManufacturersPerProducts::getArray($this->params['folderId'], $productId);
        $delimiter = html_entity_decode($this->params['delimiter'], ENT_COMPAT, 'UTF-8');

        $stringSgt = $dateSgt = array();
        if (is_array($this->suggestions)) {
            unset($this->suggestions['']);
            foreach ($this->suggestions as $sgt) {
                $sgtOpt = explode($delimiter, $sgt);
                $stringSgt["{$sgtOpt[0]}"] = $sgtOpt[0];
                $dateSgt["{$sgtOpt[2]}"] = $sgtOpt[2];
            }
        }

        $val = $this->prepareInputValue($name, $value, 3);
        if (empty($value) && !$this->formWithErrors && !$this->params['autohide']) {
            $val['m'] = key($manifactureOptions);
        }

        $this->suggestions = countR($stringSgt) ? array('' => '') + $stringSgt : array();
        $attrString = $attr;
        $attrString['placeholder'] = 'Номер';
        $attrString['id'] = "batchNameS" . rand(1, 100);
        $tpl = $this->createInput($name . '[s]', $val['s'], $attrString);

        $attrMan = $attr;
        $attrMan['placeholder'] = 'Произв.';
        $attrMan['id'] = "batchNameM" . rand(1, 100);
        $this->suggestions = $manifactureOptions;
        $tpl->append($this->createInput($name . '[m]', $val['m'], $attrMan));

        $this->suggestions = countR($dateSgt) ? array('' => '') + $dateSgt : array();
        $attrDate = $attr;
        $attrDate['placeholder'] = !empty($datePlaceholder) ? $datePlaceholder : 'Годен до';
        $attrDate['id'] = "batchNameD" . rand(1, 100);
        $tpl->append($this->createInput($name . '[d]', $val['d'], $attrDate));

        return new ET('<span style="white-space:nowrap;">[#1#]</span>', $tpl);
    }
}