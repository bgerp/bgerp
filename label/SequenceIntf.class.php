<?php



/**
 * 
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_SequenceIntf
{
    
    
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща масив с плейсхолдърите, които ще се попълват от getLabelData
     * 
     * @param integer $id
     * @return array
     */
    public function getLabelPlaceholders($id)
    {
    	return $this->class->getLabelPlaceholders($id);
    }
    
    
    /**
     * Връща масив с данни за плейсхолдърите на етикет
     * 
     * @param integer $id
     * @param integer $labelNo
     * 
     * @return array
     */
    public function getLabelData($id, $labelNo)
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
    
    
    /**
     * Може ли шаблона да бъде избран от класа
     *
     * @param int $id         - ид на обект от класа
     * @param int $templateId - ид на шаблон
     * @return boolean
     */
    public function canSelectTemplate($id, $templateId)
    {
    	return $this->class->canSelectTemplate($id, $templateId);
    }
}
