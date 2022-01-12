<?php


/**
 * Драйвер за партиди от тип `Представка + Код + Срок на годност`
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Представка+Код+Срок на годност
 */
class batch_definitions_StringAndCodeAndDate extends batch_definitions_Varchar
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
        $fieldset->FLD('format', "enum(dmy=|*221199,dmY=|*22111999,m.Y=|*11.1999,d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)'", 'caption=Маска');
        $fieldset->FLD('autoValue', 'enum(yes=Автоматично,no=Без)', 'caption=Генериране');
        $fieldset->FLD('time', 'time(suggestions=1 ден|2 дена|1 седмица|1 месец|1 година|2 години,nullIfEmpty)', 'caption=Срок по подразбиране,unit=след текущата дата');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param batch_definitions_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(batch_definitions_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        if(isset($rec->id)){
            $bQuery = batch_Items::getQuery();
            $bQuery->EXT('templateId', 'batch_Defs', 'externalName=templateId,remoteKey=productId,externalFieldName=productId');
            $bQuery->where("#templateId = {$rec->id}");
            $bQuery->show('id');

            if ($bQuery->count()) {
                $form->setReadOnly('format');
                $form->setField('format', 'hint=Има вече артикули с този тази партидност');
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param batch_definitions_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $form
     */
    protected static function on_AfterInputEditForm(batch_definitions_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = &$form->rec;
        if (preg_match('/[^a-z_\-0-9]/i', $rec->prefix)) {
            $form->setError('prefix', "Полето може да съдържа само латински букви и цифри|*!");
        }
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
            $productTime = cat_Products::getParams($this->rec->productId, 'expiryTime');
            $time = !empty($productTime) ? $productTime : $this->rec->time;

            $date = isset($date) ? $date : dt::today();
            if (isset($time)) {
                $date = dt::addSecs($time, $date);
                $date = dt::verbal2mysql($date, false);
            }

            // Прави се опит за получаване на следващия свободен номер
            $separator = static::SEPARATOR;
            $date = dt::mysql2verbal($date, $this->rec->format);
            $batch = "{$this->rec->prefix}{$this->rec->productCode}{$separator}{$date}";
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
        list($code, $date) = explode(static::SEPARATOR, $value);
        $varcharClassId = batch_definitions_Varchar::getClassId();
        $dateClassId = batch_definitions_ExpirationDate::getClassId();
        $date = dt::getMysqlFromMask($date, $this->rec->format);

        $res = array();
        $res[] = (object) array('name' => 'Код', 'classId' => $varcharClassId, 'value' => $code);
        $res[] = (object) array('name' => 'Срок на годност', 'classId' => $dateClassId, 'value' => $date);

        return $res;
    }


    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
        $Type = core_Type::getByName('varchar');

        return $Type;
    }


    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
        list($string, $date) = explode(static::SEPARATOR, $value);
        $date = batch_definitions_ExpirationDate::displayExpiryDate($date, $this->rec->format, $this->rec->time);

        $string = core_Type::getByName('varchar')->toVerbal($string);
        $value = "{$string}{$date}";

        if(!Mode::is('text', 'plain') && $value != strip_tags($value)) {
            $value = "<span>{$value}</span>";
        }

        return $value;
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

        // Трябва да започва с "<представка><код на артикула>"
        $shouldStartWith = "{$this->rec->prefix}{$this->rec->productCode}";
        $startString = mb_substr($value, 0, mb_strlen($shouldStartWith));
        if($startString != $shouldStartWith){
            $msg = "Партидата трябва да започва с|*: \"<b>{$shouldStartWith}</b>\"";

            return false;
        }

        // Останалата част трябва да е валидна дата
        $expectedEndString = str_replace($shouldStartWith, '', $value);
        if (!dt::checkByMask($expectedEndString, $this->rec->format)) {
            $expectedFormat = dt::mysql2verbal(dt::today(), $this->rec->format);
            $msg = "|След|* <b>{$shouldStartWith}</b> |трябва да следва дата във формата|* <b>{$expectedFormat}</b>";

            return false;
        }

        return parent::isValid($value, $quantity, $msg);
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
        if(strpos($value, static::SEPARATOR) === false){
            $expectedFormatDate = dt::mysql2verbal(dt::today(), $this->rec->format);
            $dateLen = mb_strlen($expectedFormatDate);

            $startsWith = mb_substr($value, 0, mb_strlen($value) - $dateLen);
            $value = str_replace($startsWith, "{$startsWith}|", $value);
        }

        return ($value == '') ? null : $value;
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

        if (is_array($dates)) {
            usort($dates, function ($a, $b) {
                list(, $aDate) = explode(static::SEPARATOR, $a);
                list(, $bDate) = explode(static::SEPARATOR, $b);

                $aTime = strtotime(dt::getMysqlFromMask($aDate, $this->rec->format));
                $bTime = strtotime(dt::getMysqlFromMask($bDate, $this->rec->format));

                return ($aTime < $bTime) ? -1 : 1;
            });

            $sorted = array();
            foreach ($dates as $date) {
                $sorted[$date] = $batches[$date];
            }

            $batches = $sorted;
        }
    }
}