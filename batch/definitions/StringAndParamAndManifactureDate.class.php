<?php


/**
 * Драйвер за партиди от тип `Представка + Параметър + Дата на производство`
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Представка+Параметър+Дата на производство
 */
class batch_definitions_StringAndParamAndManifactureDate extends batch_definitions_StringAndCodeAndDate
{
    /**
     * Разделител от срока на годност
     */
    const SEPARATOR = '|';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('prefix', 'varchar(3)', 'caption=Префикс,mandatory');
        $fieldset->FLD('paramId', 'key(mvc=cat_Params,select=typeExt,forceOpen,maxRadio=1,allowEmpty)', 'caption=Параметър,mandatory');
        $fieldset->FLD('delimiter', 'enum(&#x20;=Интервал,.=Точка,&#44;=Запетая,&#47;=Наклонена,&#45;=Тире)', 'caption=Разделител');
        $fieldset->FLD('format', "enum(dmy=|*221199,dmY=|*22111999,m.Y=|*11.1999,d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)'", 'caption=Маска');
        $fieldset->FLD('strategy', "enum(fifo=Най-старо производство,lifo=Най-ново производство)'", 'caption=Изписване');
        $fieldset->FLD('autoValue', 'enum(yes=Автоматично,no=Без)', 'caption=Генериране');
    }


    /**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     *
     * @param string $value
     *
     * @return string $value
     */
    public function normalize($value)
    {
        return $value;
    }


    /**
     * Връща автоматичния партиден номер според класа
     *
     * @param mixed         $documentClass - класа за който ще връщаме партидата
     * @param int           $id            - ид на документа за който ще връщаме партидата
     * @param int           $storeId       - склад
     * @param datetime|NULL $date          - дата
     *
     * @return mixed $value        - автоматичния партиден номер, ако може да се генерира
     */
    public function getAutoValue($documentClass, $id, $storeId, $date = null)
    {
        $batch = null;

        // Ако ще се генерира автоматична стойност
        if ($this->rec->autoValue == 'yes') {

            // Ще се генерира само ако е протокол за производство
            if($documentClass instanceof planning_DirectProductionNote){
                $paramName = cat_Products::getParams($this->rec->productId, $this->rec->paramId);
                $date = dt::mysql2verbal($date, $this->rec->format);

                $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
                $batch = "{$this->rec->prefix}{$paramName}{$delimiter}{$date}";
            }
        }

        return $batch;
    }


    /**
     * Какви са свойствата на партидата
     *
     * @param string $value - номер на партидара
     *
     * @return array - свойства на партидата
     *               масив с ключ ид на партидна дефиниция и стойност свойството
     */
    public function getFeatures($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        $paramName = cat_Params::getTitleById($this->rec->paramId);

        list($code, $date) = explode($delimiter, $value);
        $code = str_replace($this->rec->prefix, "", $code);
        $varcharClassId = batch_definitions_Varchar::getClassId();
        $dateClassId = batch_definitions_ExpirationDate::getClassId();
        $date = dt::getMysqlFromMask($date, $this->rec->format);

        $res = array();
        $res[] = (object) array('name' => $paramName, 'classId' => $varcharClassId, 'value' => $code);
        $res[] = (object) array('name' => 'Дата на производство', 'classId' => $dateClassId, 'value' => $date);

        return $res;
    }


    /**
     * Връща масив с опции за лист филтъра на партидите
     *
     * @return array - масив с опции
     *               [ключ_на_филтъра] => [име_на_филтъра]
     */
    public function getListFilterOptions()
    {
        return array('manifacture' => 'Дата на производство');
    }


    /**
     * Добавя филтър към заявката към  batch_Items възоснова на избраната опция (@see getListFilterOptions)
     *
     * @param core_Query $query          - заявка към batch_Items
     * @param string     $value          -стойност на филтъра
     * @param string     $featureCaption - Заглавие на колоната на филтъра
     *
     * @return void
     */
    public function filterItemsQuery(core_Query &$query, $value, &$featureCaption)
    {
        expect($query->mvc instanceof batch_Items, 'Невалидна заявка');
        $options = $this->getListFilterOptions();
        expect(array_key_exists($value, $options), "Няма такава опция|* '{$value}'");

        // Ако е избран филтър за срок на годност
        if ($value == 'manifacture') {

            // Намиране на партидите със свойство 'срок на годност'
            $featQuery = batch_Features::getQuery();

            $name = batch_Features::canonize('Дата на производство');
            $featQuery->where("#name = '{$name}'");
            $featQuery->orderBy('value', 'ASC');
            $itemsIds = arr::extractValuesFromArray($featQuery->fetchAll(), 'itemId');
            $query->in('id', $itemsIds);

            // Ако има ще бъдат подредени по стойноста на срока им
            if (is_array($itemsIds) && countR($itemsIds)) {
                $count = 1;
                $case = 'CASE #id WHEN ';
                foreach ($itemsIds as $id) {
                    $when = ($count == 1) ? '' : ' WHEN ';
                    $case .= "{$when}{$id} THEN {$count}";
                    $count++;
                }
                $case .= ' END';
                $query->XPR('orderById', 'int', "({$case})");
                $query->orderBy('orderById');
            } else {
                $query->where('1 = 2');
            }

            $query->EXT('featureId', 'batch_Features', 'externalName=id,remoteKey=itemId');
        }

        $featureCaption = 'Дата на производство';
    }


    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
       return core_Type::getByName('varchar')->toVerbal($value);
    }


    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value    - стойноста, която ще проверяваме
     * @param float  $quantity - количеството
     * @param string &$msg     -текста на грешката ако има
     *
     * @return bool - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
        // Ако артикула вече има партида за този артикул с тази стойност, се приема че е валидна
        $checkValue = $this->normalize($value);
        if (batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $checkValue))) {

            return true;
        }

        $paramVal = cat_Products::getParams($this->rec->productId, $this->rec->paramId);
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');

        // Трябва да започва с "<представка><код на артикула>"
        $haveError = false;
        $prefixWithoutDelimiter = "{$this->rec->prefix}{$paramVal}";
        $shouldStartWith = "{$prefixWithoutDelimiter}{$delimiter}";
        $startString = mb_substr($value, 0, mb_strlen($shouldStartWith));
        if($startString != $shouldStartWith){
            $haveError = true;
        }

        // Останалата част трябва да е валидна дата
        $expectedFormat = dt::mysql2verbal(dt::today(), $this->rec->format);
        $expectedEndString = str_replace($shouldStartWith, '', $value);
        if (!dt::checkByMask($expectedEndString, $this->rec->format)) {
            $haveError = true;
        }

        if($haveError){
            $msg = "Партидата трябва да започва с|*: \"<b>{$prefixWithoutDelimiter}</b>\" |след това|* <b>{$delimiter}</b> |последвано от дата във формата|* <b>{$expectedFormat}</b>";

            return false;
        }

        return batch_definitions_Varchar::isValid($value, $quantity, $msg);
    }


    /**
     * Подрежда подадените партиди
     *
     * @param array         $batches - наличните партиди
     *                               ['batch_name'] => ['quantity']
     * @param datetime|NULL $date
     *                               return void
     */
    public function orderBatchesInStore(&$batches, $storeId, $date = null)
    {
        $dates = array_keys($batches);

        if (!is_array($dates)) return;

        usort($dates, function ($a, $b) {
            list(, $aDate) = explode($this->rec->delimiter, $a);
            list(, $bDate) = explode($this->rec->delimiter, $b);

            $aTime = strtotime(dt::getMysqlFromMask($aDate, $this->rec->format));
            $bTime = strtotime(dt::getMysqlFromMask($bDate, $this->rec->format));

            if($this->rec->strategy == 'lifo') {
                return ($aTime < $bTime) ? 1 : -1;
            } else {
                return ($aTime < $bTime) ? -1 : 1;
            }
        });

        $sorted = array();
        foreach ($dates as $date) {
            $sorted[$date] = $batches[$date];
        }

        $batches = $sorted;
    }
}