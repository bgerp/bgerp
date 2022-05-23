<?php


/**
 * Клас  'planning_type_ProductionRate' - Тип за задаване на производствени норми
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_type_ProductionRate extends type_Varchar
{

    /**
     * Каква е дефолтната опция, която да не се записва
     */
    private static $defaultOption = 'secsPer1';


    /**
     * Разрешените опции
     */
    private static $allowedRates = array('secsPer1' => '|сек. за|* |[#measureId#]|*',
                                         'minPer1' => '|минути за|* |[#measureId#]|*',
                                         'minPer10' => '|минути за|* 10 |[#measureId#]|*',
                                         'minPer100' => '|минути за|* 100 |[#measureId#]|*',
                                         'per1Hour' => '|[#measureId#]|* |за|* 1 |час|*',
                                         'per1Min' => '|[#measureId#]|* |за|* 1 |минута|*',
                                         'per8Hour' => '|[#measureId#]|* |за|* 8 |часа|*',);


    /**
     * Коя е дефолтната мярка, ако не е зададена друга
     */
    private static $defaultMeasureId = 'pcs';


    /**
     * Получава дата от трите входни стойности
     */
    public function fromVerbal($value)
    {
        if(empty($value)) return;

        $valueArr = $value;
        if(!is_array($value)){
            $valueArr = array('cL' => $value, 'cR' => static::$defaultOption);
        }

        // Валидиране на цифровата част
        $Int = core_Type::getByName('int');
        if(!empty($valueArr['cL'])){
            $valueArr['cL'] = $Int->fromVerbal($valueArr['cL']);
            if(empty($valueArr['cL'])){
                $this->error = 'Невалидно число';

                return false;
            } elseif($valueArr['cL'] <= 0) {
                $this->error = "Не е над|* - 0";

                return false;
            }

            if(strpos($valueArr['cR'], 'min') !== false){
                $valueArr['cL'] = $valueArr['cL'] * 60;
            }
        }

        // Обръщане на завписа в нормален вид
        if(empty($valueArr['cL'])) {

            // Ако не е въведена числова част, ще се нулира
            $value = null;
        } else {

            // Ако мярката е секунда за брой, тя няма да се записва
            $value = "{$valueArr['cL']}|{$valueArr['cR']}";
            if($valueArr['cR'] == static::$defaultOption){
                $value = $valueArr['cL'];
            }
        }

        return $value;
    }


    /**
     * Показва датата във вербален формат
     *
     * @param string $value
     * @param string|array
     */
    public function toVerbal($value)
    {
        $options = $this->getRateOptions($value);
        $paredValues = $this->parseValue($value);
        $leftVal = core_Type::getByName('int')->toVerbal($paredValues['left']);

        return "{$leftVal} {$options[$paredValues['right']]}";
    }


    /**
     * Какви са позволените мерки
     *
     * @return array $options
     */
    private function getRateOptions($value)
    {
        $measureId = null;
        setIfNot($measureId, $this->params['measureId'], cat_UoM::fetchBySysId(static::$defaultMeasureId)->id);
        $measureName = cat_UoM::getVerbal($measureId, 'name');

        // Кои са разрешените опции (от константите + избраната вече в посочената стойност)
        $parsedValues = $this->parseValue($value);
        $allowedMeasures = array('secsPer1' => 'secsPer1') + type_Set::toArray(planning_Setup::get('PRODUCTION_RATE_DEFAULT_MEASURE'));
        $allowedMeasures[$parsedValues['right']] = $parsedValues['right'];
        $allowedOptions = array_intersect_key(static::$allowedRates, $allowedMeasures);

        $options = array();
        foreach ($allowedOptions as $aRate => $aCaption){
            $num = in_array($aRate, array('secsPer1', 'minPer1')) ? 1 : 2;
            $pluralOrSingularMeasureName = str::getPlural($num, $measureName, true);
            $aCaption = str_replace('[#measureId#]', $pluralOrSingularMeasureName, $aCaption);
            $options[$aRate] = tr($aCaption);
        }

        return $options;
    }


    /**
     * Парсиране на стойността
     *
     * @param $value
     * @return array
     */
    private function parseValue($value)
    {
        $leftVal = $rightVal = null;
        if ($value) {
            if(is_array($value)){
                $leftVal = $value['cL'];
                $rightVal = $value['cR'];
            } else {
                list($leftVal, $rightVal) = explode('|', $value);
                setIfNot($rightVal, static::$defaultOption);
            }
        }

        if(strpos($rightVal, 'min') !== false){
            $leftVal /= 60;
        }

        return array('left' => $leftVal, 'right' => $rightVal);
    }


    /**
     * Генерира полето за задаване на нормата
     */
    public function renderInput($name, $value = '', &$attr = array())
    {
        // Разбиване на стойноста и извличане на лявата и дясната част
        $parsedValue = $this->parseValue($value);
        $Int = core_Type::getByName('int');

        // Рендиране на числовата част
        $attr['value'] = $parsedValue['left'];
        $inputLeft = $Int->renderInput($name . '[cL]', null, $attr);

        // Рендиране на опциите за нормата
        $rateOptions = $this->getRateOptions($value);
        $inputRight = ' &nbsp;' . ht::createSmartSelect($rateOptions, $name . '[cR]', $parsedValue['right']);

        // Добавяне на дясната част към лявата на полето
        $inputLeft->append($inputRight);

        // Връщане на готовото поле
        return $inputLeft;
    }


    /**
     * Връща нормата за 1-ца спрямо избраната норма и направеното к-во
     *
     * @param $value
     * @param $quantity
     * @return int $secs
     */
    public static function getInSecsByQuantity($value, $quantity)
    {
        $me = cls::get(get_called_class());
        $parseValue = $me->parseValue($value);
        $secs = null;

        switch($parseValue['right']){
            case 'secsPer1':
                $secs = $parseValue['left'] * $quantity;
                break;
            case 'minPer1':
                $secs = round(60 * $parseValue['left'] * $quantity);
                break;
            case 'minPer10':
                $secs = round((60 * $parseValue['left'] / 10) * $quantity);
                break;
            case 'minPer100':
                $secs = (60 * $parseValue['left'] / 100);
                $secs *= $quantity;
                $secs = round($secs);
                break;
            case 'per1Hour':
                $perSec = 3600 / $parseValue['left'];
                $secs = round($perSec * $quantity);
                break;
            case 'per1Min':
                $perSec = 60 / $parseValue['left'];
                $secs = round($perSec * $quantity);
                break;
            case 'per8Hour':
                $perSec = 3600 * 8 / $parseValue['left'];
                $secs = round($perSec * $quantity);
                break;
        }

        return $secs;
    }
}
