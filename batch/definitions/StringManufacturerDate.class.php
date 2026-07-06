<?php


/**
 * Тип партидност за Номер + Производител + Годен до
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Номер + Производител + Годен до
 */
class batch_definitions_StringManufacturerDate extends batch_definitions_StringExpiryDate
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'batch_definitions_StringParamDate';


    /**
     * Проверява дали стойността е невалидна и връща специфичния за Сина клас тип
     *
     * @param mixed $class
     * @param int $objectId
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType($class = null, $objectId = null)
    {
        if(isset($class) && isset($objectId)){
            $Class = cls::get($class);
            if($Class instanceof core_Detail){
                if(cls::haveInterface('doc_DocumentIntf', $Class->Master)){
                    $masterKey = $Class->fetchRec($objectId)->{$Class->masterKey};
                    $this->rec->folderId = $Class->Master->fetchField($masterKey, 'folderId');
                }
            } elseif(cls::haveInterface('doc_DocumentIntf', $Class)){
                $this->rec->folderId = $Class->fetchRec($objectId)->folderId;
            }
        }
        
        // Връщаме съответния тип с 3 полета, който написахме по-рано
        $Type = core_Type::getByName("batch_type_StringManufacturerExpiryDate(productId={$this->rec->productId},format={$this->rec->format},defaultTime={$this->rec->time},folderId={$this->rec->folderId},delimiter={$this->rec->delimiter})");

        return $Type;
    }


    /**
     * Вербално представяне на трикомпонентната партида
     */
    public function toVerbal($value)
    {
        // Експлодираме по права черта от системния запис, за да не се счупи 
        // разделителя, ако в името на производителя има интервал, точка или тире.
        list($string, $manifacture, $date) = explode('|', $value);

        $expiryTime = cat_Products::getParams($this->rec->productId, 'expiryTime');
        $expiryTime = !empty($expiryTime) ? $expiryTime : $this->rec->time;
        $date = batch_definitions_ExpirationDate::displayExpiryDate($date, $this->rec->format, $expiryTime);

        $string = core_Type::getByName('varchar')->toVerbal($string);
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        
        $value = implode($delimiter, array($string, $manifacture, $date));
        if(!Mode::is('text', 'plain') && $value != strip_tags($value)) {
            $value = "<span>{$value}</span>";
        }

        return $value;
    }


    /**
     * Нормализира стойността на партидата в удобен за съхранение вид и записва новия производител
     */
    public function normalize($value)
    {
        $delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
        $string = str_replace($delimiter, '|', $value);

        if(isset($this->rec->folderId) && isset($this->rec->productId)){
            $exploded = explode('|', $string);
            if(!empty($exploded[1])){
                if(!batch_ManufacturersPerProducts::fetch(array("#folderId = {$this->rec->folderId} AND #productId = {$this->rec->productId} AND #string = '[#1#]'", $exploded[1]))){
                    $dRec = (object)array('folderId' => $this->rec->folderId, 'productId' => $this->rec->productId, 'string' => $exploded[1]);
                    batch_ManufacturersPerProducts::save($dRec);
                }
            }
        }

        return $string;
    }


    /**
     * Специфични свойства на партидата (включва и Производител)
     */
    public function getFeatures($value)
    {
        list($string, $manufacturer, $date) = explode('|', $value);

        $varcharClassId = batch_definitions_Varchar::getClassId();
        $dateClassId = batch_definitions_ExpirationDate::getClassId();
        $date = dt::getMysqlFromMask($date, $this->rec->format);

        $res = array();
        $res[] = (object) array('name' => 'Номер', 'classId' => $varcharClassId, 'value' => $string);
        $res[] = (object) array('name' => 'Производител', 'classId' => $varcharClassId, 'value' => $manufacturer);
        $res[] = (object) array('name' => 'Срок на годност', 'classId' => $dateClassId, 'value' => $date);

        return $res;
    }
}