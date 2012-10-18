<?php



/**
 * Клас 'doc_UnsortedFolders' - Корици на папки с несортирани документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_UnsortedFolders extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search ';
    
    
    /**
     * Заглавие
     */
    var $title = "Проекти";
    
    
    /**
     * var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';
     */
    var $oldClassName = 'email_Unsorted';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'name';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Проект';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/basket.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой може да го види?
     */
    var $canSingle = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Кой има права Rip
     */
    var $canWrite = 'user';
    
    
    /**
     * Кои полета можем да редактираме, ако записът е системен
     */
    var $protectedSystemFields = 'none';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Наименование,width=400px');
        $this->setDbUnique('name');
    }
}