<?php


/**
 * Тип партидност за Номер + Годен до
 *
 * @category  bgerp
 * @package   batch
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Символи+Срок на годност
 */
class batch_definitions_StringExpiryDate extends batch_definitions_Varchar
{
    /**
     * Позволени формати
     */
    protected $formatSuggestions = 'm/d/y,m.d.y,d.m.Y,m/d/Y,d/m/Y,Ymd,Ydm,Y-m-d,dmY,ymd,ydm,m.d.Y';
    
    
    /**
     * Партида
    */
    protected $featureOrder = array('s' => 'Номер', 
                                    'd' => 'Срок на годност'
                                );


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('delimiter', 'enum(&#x20;=Интервал,.=Точка,&#44;=Запетая,&#47;=Наклонена,&#45;=Тире)', 'caption=Разделител');
        $fieldset->FLD('format', 'varchar(20)', 'caption=Формат,mandatory');
        $fieldset->setOptions('format', array('' => '') + arr::make($this->formatSuggestions, true));
        $fieldset->FLD('time', 'time(suggestions=1 ден|2 дена|1 седмица|1 месец)', 'caption=Срок по подразбиране,unit=след текущата дата');
        $fieldset->FLD('sizeOfBatch', 'int', 'caption=Дължина');
    }


    /**
     * Връща параметрите за инстанциране на типа
     *
     * @return array
     */
    protected function getBatchTypeParams()
    {
        return array(
            'productId'   => $this->rec->productId,
            'format'      => $this->rec->format,
            'defaultTime' => $this->rec->time,
            'delimiter'   => $this->rec->delimiter,
            'sizeOfBatch' => $this->rec->sizeOfBatch,
        );
    }

    /**
     * Проверява дали стойността е невалидна
     *
     * @param mixed $class
     * @param int $objectId
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType($class = null, $objectId = null)
    {
        $params = $this->getBatchTypeParams();
        
        $paramStr = array();
        foreach ($params as $k => $v) {
            $paramStr[] = "{$k}={$v}";
        }
        
        return core_Type::getByName("batch_type_StringExpiryDate(" . implode(',', $paramStr) . ")");
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
        if (batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))) return true;

        $Type = $this->getBatchClassType();
        $value = $Type->toVerbal($value);

        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        if (strpos($value, $delimiter) === false) {
            $msg = 'В партидата трябва да има|* "' . $delimiter . '"';
            
            return false;
        }

        $parts = explode($delimiter, $value);
        $featureKeys = array_keys($this->featureOrder);
        $stringIndex = array_search('s', $featureKeys);
        $dateIndex = array_search('d', $featureKeys);

        $string = ($stringIndex !== false && isset($parts[$stringIndex])) ? $parts[$stringIndex] : '';
        $date = ($dateIndex !== false && isset($parts[$dateIndex])) ? $parts[$dateIndex] : '';
        if (isset($this->rec->sizeOfBatch)) {
            if (mb_strlen($string) > $this->rec->sizeOfBatch) {
                $msg = "|*{$string} |е над допустимата дължина от|* <b>{$this->rec->sizeOfBatch}</b>";
                
                return false;
            }
        }

        if (!dt::checkByMask($date, $this->rec->format)) {
            $f = dt::mysql2verbal(dt::today(), $this->rec->format);
            $msg = "|Срока на годност трябва да е във формата|* <b>{$f}</b>";
            
            return false;
        }

        if(!empty($Type->error)){
            $msg = $Type->error;
            return false;
        }

        return true;
    }


    /**
     * Вербално представяне на партидата (Поддържа 2, 3 или повече компонента)
     */
    public function toVerbal($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        
        // Ако системният запис е с '|', го разделяме по него
        $parts = (strpos($value, '|') !== false) ? explode('|', $value) : explode($delimiter, $value);
        $features = array_values($this->featureOrder);

        foreach ($features as $index => $featureName) {
            if (!isset($parts[$index])) continue;

            if ($featureName == 'Срок на годност') {
                $expiryTime = cat_Products::getParams($this->rec->productId, 'expiryTime');
                $expiryTime = !empty($expiryTime) ? $expiryTime : $this->rec->time;
                $parts[$index] = batch_definitions_ExpirationDate::displayExpiryDate($parts[$index], $this->rec->format, $expiryTime);
            } elseif ($featureName == 'Номер') {
                $parts[$index] = core_Type::getByName('varchar')->toVerbal($parts[$index]);
            }
        }

        $value = implode($delimiter, $parts);
        
        return (!Mode::is('text', 'plain') && $value != strip_tags($value)) ? "<span>{$value}</span>" : $value;
    }


    /**
     * Нормализиране преди запис
     */
    public function normalize($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');

        return str_replace($delimiter, '|', $value);
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param batch_BatchTypeIntf $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if($form->isSubmitted()){
            $exampleDate = dt::mysql2verbal(null, $form->rec->format);
            $delimiter = html_entity_decode($form->rec->delimiter, ENT_COMPAT, 'UTF-8');

            if(strpos($exampleDate, $delimiter) !== false){
                $form->setError('format,delimiter', "Разделителят се съдържа във формата на датата");
            }
        }
    }


    /**
     * Какви са свойствата на партидата (Динамично спрямо дефинирания шаблон)
     *
     * @param string $value - номер на партидата
     * @return array - свойства на партидата
     *
     */
    public function getFeatures($value)
    {
        $parts = explode('|', $value);
        $features = array_values($this->featureOrder);
        $res = array();

        $varcharClassId = batch_definitions_Varchar::getClassId();
        $dateClassId = batch_definitions_ExpirationDate::getClassId();

        // Обхождаме динамично структурата, дефинирана в класа
        foreach ($features as $index => $featureName) {
            $partValue = $parts[$index] ?? '';

            if ($featureName == 'Срок на годност') {
                $partValue = dt::getMysqlFromMask($partValue, $this->rec->format);
                $classId = $dateClassId;
            } else {
                $classId = $varcharClassId;
            }

            $res[] = (object) array(
                'name' => $featureName, 
                'classId' => $classId, 
                'value' => $partValue
            );
        }

        return $res;
    }


    /**
     * Подрежда подадените партиди по дата на годност
    *
    * @param array         $batches - наличните партиди ['batch_name'] => ['quantity']
    * @param int           $storeId
    * @param datetime|NULL $date
    * return void
    *
    */
    public function orderBatchesInStore(&$batches, $storeId, $date = null)
    {
        $batchNames = array_keys($batches);

        $keys = array_keys($this->featureOrder);
        $dateIndex = array_search('d', $keys);

        if ($dateIndex === false) return;

        usort($batchNames, function ($a, $b) use ($dateIndex) {
            $aParts = explode('|', $a);
            $bParts = explode('|', $b);
            
            $aDate = $aParts[$dateIndex] ?? '';
            $bDate = $bParts[$dateIndex] ?? '';
            
            $aTime = strtotime(dt::getMysqlFromMask($aDate, $this->rec->format));
            $bTime = strtotime(dt::getMysqlFromMask($bDate, $this->rec->format));

            if ($aTime === false) $aTime = PHP_INT_MAX;
            if ($bTime === false) $bTime = PHP_INT_MAX;

            if ($aTime == $bTime) return 0;
            return ($aTime < $bTime) ? -1 : 1;
        });

        $sorted = array();
        foreach ($batchNames as $name) {
            $sorted[$name] = $batches[$name];
        }

        $batches = $sorted;
    }
}
