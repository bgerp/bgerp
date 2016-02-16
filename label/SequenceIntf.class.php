<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_SequenceIntf
{
    
    
    /**
     * Връща масив с данни за плейсхолдърите на етикет
     * 
     * @param integer $id
     * @param integer $labelNo
     * 
     * @return array
     */
    public function  getLabelData($id, $labelNo)
    {
        
        return $this->class->getLabelData($id, $labelNo);
    }
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     * 
     * @param integer $id
     * @param string $allowSkip
     * 
     * @return integer
     */
    public function getEstimateCnt($id, &$allowSkip)
    {
        
        return $this->class->getEstimateCnt($id, $allowSkip);
    }
}
