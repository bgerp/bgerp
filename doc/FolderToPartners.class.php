<?php



/**
 * Клас 'doc_FolderToPartners' - Релация между партньори и папки
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_FolderToPartners extends core_Manager
{   


     
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, doc_Wrapper';
    
    
     /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * 
     */
    var $canWrite = 'officer';
    
    
    /**
     * Заглавие
     */
    var $title = "Споделени партньори";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Споделен партньор";
    
        
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key(mvc=doc_Folders,select=title,silent)', 'caption=Папка');
        $this->FLD('contractorId', 'key(mvc=core_Users,select=names)', 'caption=Потребител,notNull');
         
        // Един партньор може да е само в една папка
        $this->setDbUnique('contractorId');
    }


    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {  
        $form = $data->form;
        $form->title = "Добавяне на нов партньор в папка";
    }


    static function preparePartners($data)
    {
        $data->partners = array();
        if(cls::getClassName($data->masterMvc) == 'crm_Companies') {
            $folderId = $data->masterData->rec->folderId;
            if ($folderId) {
                $query = self::getQuery();
                while($rec = $query->fetch("#folderId = {$folderId}")) {
                    $uRec = core_Users::fetch($rec->contractorId);
                    if($uRec->state != 'rejected') {
                        $data->partners[$rec->contractorId] = $rec->contractorId;
                    }
                }
            }
        }
    }


    static function renderPartners($data, $tpl)
    {

        if(count($data->partners)) {
            $table = "<br><table width=100% class='listTable'>
            <tr><th style='background-color:#ddd; color:#666;'>Партньори</th></tr>";
            foreach($data->partners as $userId => $lastLoginTime) {
                $uRec = core_Users::fetch($userId);
                $table .= "<tr><td>" . core_Users::getVerbal($uRec, 'names') . " (" . crm_Profiles::createLink($userId) . ") " . core_Users::getVerbal($uRec, 'lastLoginTime') . "</td></tr>";
            }
            $table .= "</table>";
            $tpl->append($table, 'PARTNERS');
        }
    }
    
}
