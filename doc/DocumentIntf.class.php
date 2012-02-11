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
 */
class doc_DocumentIntf
{
    
    
    /**
     * Намира най-подходящите $rec->folderId (папка)
     * и $rec->threadId за дадения документ
     */
    function route($rec)
    {
        $this->class->route($rec);
    }
    
    
    /**
     * Връща манипулатор на документа
     */
    function getHandle($id)
    {
        return $this->class->getHandle($id);
    }
    
    
    /**
     * Връща обект, съдържящ следните вербални стойности
     * - $row->title - Заглавие на документа
     * - $row->authorId - id на автора на документа, ако той е потребител на системата
     * - $row->author - името на автора на документа
     * - $docRow->authorEmail - името на автора на документа
     * - $row->state - състояние на документа
     */
    function getDocumentRow($id)
    {
        return $this->class->getDocumentRow($id);
    }
    
    
    /**
     * Връща визуалното представяне на документа
     */
    function getDocumentBody($id, $mode = 'html')
    {
        return $this->class->getDocumentBody($id, $mode);
    }


    /**
     * Определя състоянието на нишката от документи
     * Външните документи правят нишката в състояние "отворено",
     * а всички останали - в "затворено"
     */
    function getThreadState($id)
    {
        return $this->class->getThreadState($id);
    }
    
    
    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     */
    function getShared($id)
    {
        return $this->class->getShared($id);
    }
}