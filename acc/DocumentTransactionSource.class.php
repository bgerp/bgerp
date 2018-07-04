<?php


/**
 * Помощен клас-баща за източник на транзакция на контировката на документ
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see acc_DocumentTransactionSource
 *
 */
abstract class acc_DocumentTransactionSource
{
    
    
    /**
     *
     * @var core_Mvc
     */
    public $class;
    
    
    /**
     * В какво състояние да е документа след финализирането на транзакцията
     *
     * @var string
     */
    protected $finalizedState = 'active';
    
    
    /**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        // Извличаме записа
        $rec = $this->class->fetchRec($id);
    
        // Промяна на състоянието на документа
        $rec->state = $this->finalizedState;
    
        // Запазване на промененото състояние
        if ($id = $this->class->save($rec)) {
            
            // Ако записа е успешен, нотифицираме документа, че е бил активиран
            $this->class->invoke('AfterActivation', array($rec));
        }
    
        return $id;
    }
}
