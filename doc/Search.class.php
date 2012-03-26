<?php

/**
 * Клас 'doc_Search' - Търсене в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class doc_Search extends core_Manager
{
    
    /**
     * Заглавие
     */
    var $title = "Търсене на документи";
    
    var $loadList = 'doc_Wrapper, plg_Search';
    
    /**
     * Роли с права за добавяне.
     * 
     * 'no_one', за да не се показва бутона "Нов запис"
     *
     * @var string
     */
    var $canAdd = 'no_one';
    

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "folderId=Папка,threadId=Тема,docClass,docId";
    
    
    function description()
    {
        $DC = cls::get('doc_Containers');
        
        $this->fields = $DC->fields;
        $this->dbTableName = $DC->dbTableName;
        $this->dbIndexes   = $DC->dbIndexes;
    }
    
    
    /**
     * Филтрира по id на нишка (threadId)
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $filterRec = $data->listFilter->rec; 
        
        if(!$filterRec->search) {
            $data->query->where("0 = 1");
        } else {
            doc_Threads::restrictAccess($data->query);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
    }
    
    
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!$data->listFilter->rec->search) {

            return FALSE;
        } 
        
    }
}