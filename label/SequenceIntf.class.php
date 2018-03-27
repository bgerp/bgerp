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
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Връща наименованието на етикета
     * 
     * @param integer $id
     * 
     * @return string
     */
    public function getLabelName($id)
    {
        
        return $this->class->getLabelName($id);
    }
    
    
    /**
     * Връща масив с данните за плейсхолдерите
     * 
     * @param integer|NULL $objId
     * 
     * @return array
     * Ключа е името на плейсхолдера и стойностите са обект:
     * type -> text/picture - тип на данните на плейсхолдъра
     * len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
     * readonly -> (boolean) - данните не могат да се променят от потребителя
     * hidden -> (boolean) - данните не могат да се променят от потребителя
     * importance -> (int|double) - тежест/важност на плейсхолдера
     * example -> (string) - примерна стойност
     */
    public function getLabelPlaceholders($objId = NULL)
    {
        return $this->class->getLabelPlaceholders($objId);
    }
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     * 
     * @param integer $id
     * 
     * @return integer
     */
    public function getLabelEstimatedCnt($id)
    {
        
        return $this->class->getLabelEstimatedCnt($id);
    }
    
    
    /**
     * Връща масив с всички данни за етикетите
     * 
     * @param integer $id
     * @param integer $cnt
     * @param boolean $onlyPreview
     * 
     * @return array - масив от масив с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = FALSE)
    {
        
        return $this->class->getLabelData($id, $cnt, $onlyPreview);
    }
}
