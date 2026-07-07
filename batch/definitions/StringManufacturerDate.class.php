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
     * Партида
     */
    protected $featureOrder = array('s' => 'Номер', 
                                    'm' => 'Производител', 
                                    'd' => 'Срок на годност'
                                );


    /**
     * Проверява дали стойността е невалидна и връща специфичния клас тип
     *
     * @param mixed $class
     * @param int $objectId
     * @return core_Type - инстанция на тип
     * 
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
        
        $Type = core_Type::getByName("batch_type_StringManufacturerExpiryDate(productId={$this->rec->productId},format={$this->rec->format},defaultTime={$this->rec->time},folderId={$this->rec->folderId},delimiter={$this->rec->delimiter})");

        return $Type;
    }


    /**
     * Нормализира стойността на партидата в удобен за съхранение вид и записва новия производител
     */
    public function normalize($value)
    {
        $string = parent::normalize($value);

        if(isset($this->rec->folderId) && isset($this->rec->productId)){
            $exploded = explode('|', $string);
            if(!empty($exploded[1])){
                if(!batch_ManufacturersPerProducts::fetch(array("#folderId = [#1#] AND #productId = [#2#] AND #string = '[#3#]'", $this->rec->folderId, $this->rec->productId, $exploded[1]))){
                    $dRec = (object)array('folderId' => $this->rec->folderId, 'productId' => $this->rec->productId, 'string' => $exploded[1]);
                    batch_ManufacturersPerProducts::save($dRec);
                }
            }
        }

        return $string;
    }
}