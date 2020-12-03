<?php


/**
 * Клас 'uiext_Setup'
 *
 *
 * @category  bgerp
 * @package   uiext
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class uiext_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'uiext_Labels';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разширения на потребителския интерфейс';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'uiext';
        
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'uiext_Labels',
        'uiext_ObjectLabels',
        'migrate::replaceContainerId',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Добавяне на тагове към редовете на транспортните линии', 'uiext_plg_DetailLabels', 'trans_LineDetails', 'private');
        
        if(core_Packs::isInstalled('rack')){
            $html .= $Plugins->installPlugin('Добавяне на тагове към детайлите на зоните в палетния склад', 'uiext_plg_DetailLabels', 'rack_ZoneDetails', 'private');
        }
        
        return $html;
    }
    
    
    public function replaceContainerId()
    {
       $Class = cls::get('uiext_ObjectLabels');
       $Class->setupMvc();
        
       $update = array();
       $query = $Class->getQuery();
       $db = $query->mvc->db;
       if ($db->isFieldExists($query->mvc->dbTableName, 'containerId')) {
           $query->FLD('containerId', 'key(mvc=doc_Containers)');
           $query->where("#containerId IS NOT NULL");
           while($rec = $query->fetch()){
               try{
                   $Document = doc_Containers::getDocument($rec->containerId);
                   $update[$rec->id] = (object)array('id' => $rec->id, 'classId' => $Document->getClassId(), 'objectId' => $Document->that);
               } catch(core_exception_Expect $e){
                   reportException($e);
               }
           }
           
           if(countR($update)){
               $Class->saveArray($update, 'id,classId,objectId');
           }
       }
    }
}
