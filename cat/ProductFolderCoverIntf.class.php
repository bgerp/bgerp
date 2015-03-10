<?php



/**
 * Клас 'cat_ProductFolderCoverIntf' - Интерфейс за корици на папки, 
 * в които могат да се създават документи спецификации
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
     * @param int $id - ид на спецификация папка
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	return $this->class->getDefaultMeta($id);
    }
}