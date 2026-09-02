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
        $folderId = $this->rec->folderId;
        if (isset($class) && isset($objectId)) {
            $Class = cls::get($class);
            if ($Class instanceof core_Detail) {
                if (cls::haveInterface('doc_DocumentIntf', $Class->Master)) {
                    $masterKey = $Class->fetchRec($objectId)->{$Class->masterKey};
                    $folderId = $Class->Master->fetchField($masterKey, 'folderId');
                }
            } elseif (cls::haveInterface('doc_DocumentIntf', $Class)) {
                $folderId = $Class->fetchRec($objectId)->folderId;
            }
        }

        // Вземаме базовите параметри и само ги разширяваме/заменяме
        $params = parent::getBatchTypeParams($class, $objectId);
        $params['folderId'] = $folderId;

        $paramStr = array();
        foreach ($params as $k => $v) {
            $paramStr[] = "{$k}={$v}";
        }

        return core_Type::getByName("batch_type_StringManufacturerExpiryDate(" . implode(',', $paramStr) . ")");
    }


    /**
     * Нормализира стойността на партидата в удобен за съхранение вид и записва новия производител
     */
    public function normalize($value)
    {
        $string = parent::normalize($value);

        if(isset($this->rec->folderId) && isset($this->rec->productId)){
            $exploded = explode('|', $string);

            $keys = array_keys($this->featureOrder);
            $manufacturerIndex = array_search('m', $keys);

            if($manufacturerIndex !== false && isset($exploded[$manufacturerIndex]) && strlen($exploded[$manufacturerIndex]) > 0){
                $manufacturerValue = $exploded[$manufacturerIndex];

                $dRec = (object)array(
                    'folderId'  => $this->rec->folderId,
                    'productId' => $this->rec->productId,
                    'string'    => $manufacturerValue,
                );
                
                $where = array(
                    "#folderId = [#1#] AND #productId = [#2#] AND #string = '[#3#]'", 
                    $this->rec->folderId, 
                    $this->rec->productId, 
                    $manufacturerValue
                );

                $exRec = batch_ManufacturersPerProducts::fetch($where);

                // Ако записът съществува, му подаваме неговото ID, за да предотвратим дублиране (ще направи UPDATE вместо нов INSERT)
                if ($exRec) {
                    $dRec->id = $exRec->id;
                }
                
                batch_ManufacturersPerProducts::save($dRec);
            }
        }

        return $string;
    }
}