<?php


/**
 * Базов драйвер за вид партида 'Задание за производство'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Задание за производство
 */
class batch_definitions_Job extends batch_definitions_Proto
{
    
    
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
        $Class = cls::get($documentClass);
        expect($dRec = $Class->fetchRec($id));
        
        $res = null;
        if(isset($dRec->originId)){
            $origin = doc_Containers::getDocument($dRec->originId);
            $jobId = null;
            if($origin->isInstanceOf('planning_Jobs')){
                $jobId = $origin->that;
            } elseif($origin->isInstanceOf('planning_Tasks')){
                $jobId = doc_Containers::getDocument($origin->fetchField('originId'))->that;
            }
            
            if(isset($jobId)){
                $res = $this->getDefaultBatchName($jobId);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Дефолтното име на партидата за заданието
     * 
     * @param int $jobId
     * @return string $res
     */
    public function getDefaultBatchName($jobId)
    {
        $jobProductId = planning_Jobs::fetchField($jobId, 'productId');
        $res = "JOB{$jobId}/" . str::removeWhiteSpace(cat_Products::getTitleById($jobProductId, false), ' ');
        
        return $res;
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
        if (!preg_match("/^JOB[0-9]+\\//" , $value)) {
            $msg = "Формата трябва да започва с|* JOB1/";
            
            return false;
        }
        
        return parent::isValid($value, $quantity, $msg);
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        return false;
    }
    
    
    /**
     * Разпределя количество към наличните партиди в даден склад към дадена дата
     *
     * @param array  $quantities - масив с наличните партиди и количества
     * @param string $mvc        - клас на обект, към който да се разпределят
     * @param string $id         - ид на обект, към който да се разпределят
     *
     * @return array $quantities - масив с филтрираните наличните партиди и количества
     */
    public function filterBatches($quantities, $mvc, $id)
    {
        $Detail = cls::get($mvc);
        if($Detail instanceof planning_DirectProductNoteDetails){
            $originId = planning_DirectProductionNote::fetchField($Detail->fetchRec($id, 'noteId')->noteId, 'originId');
            $origin = doc_Containers::getDocument($originId);
            if($origin->isInstanceOf('planning_Tasks')){
                $jobId = $origin->fetchField('originId');
                $originJob = doc_Containers::getDocument($jobId);
                $jobId = $originJob->that;
            } else {
                $jobId = $origin->that;
            }
            
            $batchName = $this->getDefaultBatchName($jobId);
            if(array_key_exists($batchName, $quantities)){
                
                return array($batchName => $quantities[$batchName]);
            }
            
            return array();
        }
        
        return $quantities;
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
        list($jobId,) = explode('/', $value);
        
        $res = array();
        $res[] = (object) array('name' => 'Партида', 'classId' => $this->getClassId(), 'value' => $value);
        $res[] = (object) array('name' => 'Задание', 'classId' => batch_definitions_Varchar::getClassId(), 'value' => $jobId);
        
        return $res;
    }
}