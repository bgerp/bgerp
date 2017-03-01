<?php



/**
 * Клас 'cat_ProductFolderCoverIntf' - Интерфейс за корици на папки, 
 * в които могат да се създават документи артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ProductFolderCoverIntf extends doc_FolderIntf
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationFolderCoverIntf';
	
	
	/**
     * Връща мета дефолт мета данните на папката
     * 
     * @param int $id - ид на корицата
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	return $this->class->getDefaultMeta($id);
    }
    
    
    /**
     * Връща мета дефолт параметрите със техните дефолт стойностти, които да се добавят във формата на
     * универсален артикул, създаден в папката на корицата
     *
     * @param int $id - ид на корицата
     * @return array $params - масив с дефолтни параметри И техните стойности
     * 				<ид_параметър> => <дефолтна_стойност>
     */
    public function getDefaultProductParams($id)
    {
    	return $this->class->getDefaultProductParams($id);
    }
    
    
    /**
     * Дали артикулът създаден в папката трябва да е публичен (стандартен) или не
     * 
     * @param mixed $id - ид или запис
     * @return public|private|template - Стандартен / Нестандартен / Шаблон
     */
    public function getProductType($id)
    {
    	return $this->class->getProductType($id);
    }
}
