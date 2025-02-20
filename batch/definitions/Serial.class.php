<?php


/**
 * Базов драйвер за вид партида 'сериен номер'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Сериен номер
 */
class batch_definitions_Serial extends batch_definitions_Proto
{
    /**
     * Име на полето за партида в документа
     *
     * @param string
     */
    public $fieldCaption = 'SN';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('numbers', 'int(Min=0)', 'caption=Цифри,unit=брой');
        $fieldset->FLD('prefix', 'varchar(10,regexp=/^\p{L}*$/iu)', 'caption=Представка');
        $fieldset->FLD('suffix', 'varchar(10,regexp=/^\p{L}*$/iu)', 'caption=Наставка');
        $fieldset->FLD('prefixHistory', 'blob', 'input=none');
        $fieldset->FLD('suffixHistory', 'blob', 'input=none');
        $fieldset->FLD('length', 'int(Min=0)', 'caption=Дължина,unit=Символа');
        $fieldset->FLD('transferBatchOnProduction', 'enum(no=Не,yes=Да)', 'caption=Пренасяне на партиди от вложения към произвеждания артикул->Избор');
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
        if(isset($this->rec->numbers)){
            $Type = core_Type::getByName('text(rows=3)');
        } else {
            $Type = core_Type::getByName('varchar');
        }
        
        return $Type;
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value    - стойноста, която ще проверяваме
     * @param float  $quantity - количеството
     * @param string &$msg     - текста на грешката ако има
     *
     * @return bool - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
        $serials = $this->normalize($value);
        $serials = $this->makeArray($serials);

        $count = countR($serials);
        
        if ($count != $quantity) {

            // Проверка дали въведените серийни номера отговарят на количеството
            // освен ако к-то не е 0, но е позволено да е 0
            if(!(empty($quantity) && $this->params['allowZero'])){
                $mMsg = ($count != 1) ? 'серийни номера' : 'сериен номер';
                $msg = ($quantity != 1) ? "|Въведени са|* <b>{$count}</b> |{$mMsg}, вместо очакваните|* <b>{$quantity}</b>" : 'Трябва да е въведен само един сериен номер';

                return false;
            }
        }
        
        // Ако артикула вече има партида за този артикул с тази стойност, се приема че е валидна
        if (batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))) {
            
            return true;
        }

        $errMsg = '|Всички номера трябва да отговарят на формата|*: ';
        if (!empty($this->rec->prefix)) {
            $errMsg .= "|да започват с|* <b>{$this->rec->prefix}</b>, ";
        }

        if(isset($this->rec->numbers)){
            $errMsg .= "|да имат точно|* <b>{$this->rec->numbers}</b> |цифри|*";
        }

        if (!empty($this->rec->suffix)) {
            $errMsg .= " |и да завършват на|* <b>{$this->rec->suffix}</b>";
        }

        if (!empty($this->rec->length)) {
            $errMsg .= " |и общата дължина на символите да е точно|* <b>{$this->rec->length}</b>";
        }

        foreach ($serials as $serial) {
            if ($serial === false) {
                $msg = 'Не могат да се генерират серийни номера от зададеният диапазон';
                
                return false;
            }

            $error = false;
            $middleString = $serial;

            // Проверка започва ли с префикса
            if (!empty($this->rec->prefix)) {
                $middleString = mb_substr($middleString, mb_strlen($this->rec->prefix));
                if(strpos($serial, $this->rec->prefix) != 0){
                    $error = true;
                }
            }

            // Проверка завършва ли на суфикса
            if (!empty($this->rec->suffix)) {
                $strlen = mb_strlen($serial);
                $suffixLen = mb_strlen($this->rec->suffix);
                $expectedEndPos = $strlen - $suffixLen;

                $middleString = mb_substr($middleString, 0, mb_strlen($middleString) - $suffixLen);
                if(strpos($serial, $this->rec->suffix) != $expectedEndPos){
                    $error = true;
                }
            }

            // Проверка дължината на символите ако има
            if (!empty($this->rec->numbers)) {
                if (!preg_match('/^[0-9]{' . $this->rec->numbers . '}$/', $middleString)) {
                    $error = true;
                }
            }

            if (!empty($this->rec->length)) {
                if(mb_strlen($value) != $this->rec->length){
                    $error = true;
                }
            }

            if ($error) {
                $msg = $errMsg;
                
                return false;
            }
        }
        
        return parent::isValid($value, $quantity, $msg);
    }
    
    
    /**
     * Генерира серийни номера в интервал
     *
     * @param string $from - начало на диапазона
     * @param string $to   - край на диапазона
     *
     * @return FALSE|array $res - генерираните номера или FALSE ако не може да се генерират
     */
    private function getByRange($from, $to)
    {
        $oldFrom = $from;
        
        $prefix = $this->rec->prefix;
        $suffix = $this->rec->suffix;
        
        $prefixes = (isset($this->rec->prefixHistory)) ? $this->rec->prefixHistory : array("{$prefix}" => "{$prefix}");
        foreach ($prefixes as $pr) {
            $from = ltrim($from, $pr);
            $to = ltrim($to, $pr);
        }
        
        $suffixes = (isset($this->rec->suffixHistory)) ? $this->rec->suffixHistory : array("{$suffix}" => "{$suffix}");
        foreach ($suffixes as $sf) {
            $to = rtrim($to, $sf);
            $from = rtrim($from, $sf);
        }
        
        $res = array();
        $start = $from;
        
        while ($start < $to) {
            $serial = str::increment($start);
            $v = "{$prefix}{$serial}{$suffix}";
            $res[$v] = $v;
            $start = $serial;
        }
        
        if (countR($res)) {
            $res = array($oldFrom => $oldFrom) + $res;
            
            return $res;
        }
        
        return false;
    }
    
    
    /**
     * Разбива партидата в масив
     *
     * @param string $value - партида
     *
     * @return array $array - масив с партидата
     */
    public function makeArray($value)
    {
        $res = array();
        
        $value = explode('|', $value);
        foreach ($value as &$v) {
            if($this->rec->numbers){
                $vArr = explode(':', $v);
                if (countR($vArr) == 2) {
                    $rangeArr = $this->getByRange($vArr[0], $vArr[1]);

                    if (is_array($rangeArr)) {
                        $res = $res + $rangeArr;
                    } else {
                        $res[$v] = false;
                    }
                } else {
                    $res[$vArr[0]] = $vArr[0];
                }
            } else {
                $res[$v] = $v;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param batch_definitions_Proto $Driver
     * @param embed_Manager           $Embedder
     * @param stdClass                $form
     */
    public static function on_AfterInputEditForm(batch_definitions_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = &$form->rec;
        
        // Само артикули с основна мярка в брой, могат да имат серийни номера
        if (isset($rec->productId)) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            if (cat_UoM::fetchBySysId('pcs')->id != $measureId) {
                $form->setError('driverClass', "Само артикули с основна мярка 'брой' могат да имат серийни номера");
            }
        }
        
        
        if (!is_array($rec->prefixHistory)) {
            $rec->prefixHistory = array();
        }
        $rec->prefixHistory[$rec->prefix] = $rec->prefix;
        
        if (!is_array($rec->suffixHistory)) {
            $rec->suffixHistory = array();
        }
        $rec->suffixHistory[$rec->suffix] = $rec->suffix;

        if($form->isSubmitted()){
            if($rec->length && (!empty($rec->prefix) || !empty($rec->suffix))){
                if(mb_strlen("{$rec->prefix}{$rec->suffix}") > $rec->length){
                    $form->setError('length,prefix,suffix', "Представката + надставката е повече от дължината");
                }
            }
        }
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
        $value = preg_replace('!\s+!', "\n", $value);
        $value = explode("\n", trim(str_replace("\r", '', $value)));
        
        $value = implode('|', $value);
        
        return ($value == '') ? null : $value;
    }
    
    
    /**
     * Денормализира партидата
     *
     * @param string $value
     *
     * @return string $value
     */
    public function denormalize($value)
    {
        $value = explode('|', $value);
        $value = implode("\n", $value);
        
        return $value;
    }
    
    
    /**
     * Може ли потребителя да сменя уникалноста на партида/артикул
     *
     * @return bool
     */
    public function canChangeBatchUniquePerProduct()
    {
        return false;
    }
    
    
    /**
     * Какви са свойствата на партидата
     *
     * @param string $value - номер на партидара
     *
     * @return array - свойства на партидата
     *               o name    - заглавие
     *               o classId - клас
     *               o value   - стойност
     */
    public function getFeatures($value)
    {
        $res = array();
        $res[] = (object) array('name' => 'Сериен номер', 'classId' => $this->getClassId(), 'value' => $value);
        
        return $res;
    }
}
