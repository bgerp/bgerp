<?php



/**
 * Клас 'doc_DocumentIntf' - Интерфейс за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title:    Интерфейс за мениджърите на документи
 */
class doc_DocumentIntf
{
    
    
    /**
     * Намира най-подходящите $rec->folderId (папка)
     * и $rec->threadId за дадения документ
     */
    public function route($rec)
    {
        $this->class->route($rec);
    }
    
    
    /**
     * Връща манипулатор на документа
     */
    public function getHandle($id)
    {
        return $this->class->getHandle($id);
    }
    
    
    /**
     * Връща обект,следните вербални стойности
     * - $docRow->title - Заглавие на документа
     * - $docRow->subTitle - Подзаглавие на документа
     * - $docRow->authorId - id на автора на документа, ако той е потребител на системата
     * - $docRow->author - името на автора на документа
     * - $docRow->authorEmail - името на автора на документа
     * - $docRow->state - състояние на документа
     */
    public function getDocumentRow($id)
    {
        return $this->class->getDocumentRow($id);
    }
    
    
    /**
     * Връща заглавието на документа, като хипервръзка, сочеща към самия документ
     */
    public function getLink($id)
    {
        return $this->class->getLink($id);
    }
    
    
    /**
     * Връща визуалното представяне на документа
     */
    public function getDocumentBody($id, $mode = 'html', $options = null)
    {
        return $this->class->getDocumentBody($id, $mode, $options);
    }
    
    
    /**
     * Определя състоянието на нишката от документи
     * Външните документи правят нишката в състояние "отворено",
     * а всички останали - в "затворено"
     */
    public function getThreadState($id)
    {
        return $this->class->getThreadState($id);
    }
    
    
    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     */
    public function getShared($id)
    {
        return $this->class->getShared($id);
    }
    
    
    public function getContainer($id)
    {
        return $this->class->getContainer($id);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public function canAddToFolder($folderId)
    {
        return $this->class->canAddToFolder($folderId);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param  int     $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public function canAddToThread($threadId)
    {
        return $this->class->canAddToThread($threadId);
    }
    
    
    /**
     * Връща възможните типове за файлови формати, към които може да се конвертира дадения документ
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана
     *               по подразбиране
     */
    public function getPossibleTypeConvertings()
    {
        return $this->class->getPossibleTypeConvertings();
    }
    
    
    /**
     * Връща възможните типове за файлови формати, в зависимост от класа
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана
     *               по подразбиране
     */
    public function getTypeConvertingsByClass()
    {
        return $this->class->getTypeConvertingsByClass();
    }
    
    
    /**
     * Конвертира документа към файл от указания тип и връща манипулатора му
     *
     * @param string $fileName - Името на файла, без разширението
     * @param string $type     - Разширението на файла
     *
     * return array $res - Масив с fileHandler' и на документите
     * @deprecated
     * @see doc_DocumentIntf::convertTo()
     */
    public function convertDocumentAsFile($fileName, $type)
    {
        return $this->class->convertDocumentAsFile($fileName, $type);
    }
    
    
    /**
     * Конвертира документа към файл от указания тип и връща манипулатора му
     *
     * @param string $type     - Разширението на файла
     * @param string $fileName - Името на файла; ако не е зададено се определя автоматично -
     *                         {ABBR}{id}.{type}
     *
     * return array $res - масив с манипулатора на генерирания файл
     */
    public function convertTo($type, $fileName = null)
    {
        return $this->class->convertTo($type, $fileName);
    }
    
    /**
     * Връша прикачените файлове в документите
     *
     * @param mixed $rec - id' то на документа или записа на документа
     *
     * @return array $files - Масив с ключ манипулатора на файла и стойност	името на файла
     */
    public function getAttachments($rec)
    {
        return $this->class->getAttachments($rec);
    }
    
    
    /**
     * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param  int   $id - ид на документ
     * @return param $res - масив с използваните документи
     *                  ['class'] - инстанция на документа
     *                  ['id'] - ид на документа
     */
    public function getUsedDocs($id)
    {
        return $this->class->getUsedDocs($id);
    }
    
    
    /**
     * Метод филтриращ заявка към doc_Folders
     * Добавя условия в заявката, така, че да останат само тези папки,
     * в които може да бъде добавен документ от типа на $mvc
     *
     * @param core_Query $query      Заявка към doc_Folders
     * @param boolean    $viewAccess
     */
    public function restrictQueryOnlyFolderForDocuments($query, $viewAccess = false)
    {
        return $this->class->restrictQueryOnlyFolderForDocuments($query, $viewAccess);
    }
    
    
    /**
     * Записва подадения обект
     *
     * @param object $rec - Обект, който ще се записва
     */
    public function createNew($rec)
    {
        return $this->class->createNew($rec);
    }
    
    
    /**
     * Генерираме ключа за кеша
     *
     * @param object               $rec
     * @param core_ObjectReference $document
     *
     * @return FALSE|string
     */
    public function generateCacheKey($rec, $document)
    {
        return $this->class->generateCacheKey($rec, $document);
    }
    
    
    /**
     * Връща антетката на документа
     *
     * @param stdClass $rec
     * @param stdClass $row
     */
    public function getLetterHead($rec, $row)
    {
        return $this->class->getLetterHead($rec, $row);
    }
    
    
    /**
     * Връща масив с възможните формати
     *
     * @return array
     */
    public function getExportFormats()
    {
        return $this->class->getExportFormats();
    }
    
    
    /**
     * Връща хеша на подадения документ
     *
     * @param integer $id
     *
     * @return string|NULL
     */
    public function getDocContentHash($id)
    {
        return $this->class->getExportFormats($id);
    }
    
    
    /**
     * Връща дефолтни стойности за попълване на река
     *
     * @param stdClass $rec
     * @param array    $otherParams
     *
     * @return array
     */
    public function getDefaultData($rec, $otherParams = array())
    {
        return $this->class->getDefaultData($rec, $otherParams);
    }
    
    
    /**
     * Връща дефолтни стойности за попълване на река за коментара
     *
     * @param stdClass $rec
     * @param array    $otherParams
     *
     * @return array
     */
    public function getDefaultDataForComment($rec, $otherParams = array())
    {
        return $this->class->getDefaultDataForComment($rec, $otherParams);
    }
}
