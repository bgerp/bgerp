<?php


/**
 *
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class peripheral_Terminal extends peripheral_DeviceDriver
{
    public $interfaces = 'peripheral_TerminalIntf';
    
    public $title = 'Терминал';
    
    protected $nameField = 'name';
    
    protected $clsName;
    
    protected $fieldArr;
    
    
    /**
     *
     * @var string
     */
    public $loadList = 'peripheral_DeviceWebPlg';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * 
     * @param pos_Terminal $Driver
     * @param peripheral_Devices $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $clsName = $Driver->clsName;
        $nameField = $Driver->nameField;
        
        $pIdArr = $Embedder->getDevicesArrObjVal('peripheral_TerminalIntf', $Driver->clsName);
        
        $pQuery = $clsName::getQuery();
        
        if (!empty($pIdArr)) {
            $pQuery->notIn('id', $pIdArr);
        }
        
        $pArr = array('' => '');
        while ($pRec = $pQuery->fetch()) {
            $pArr[$pRec->{$nameField}] = $pRec->{$nameField};
        }
        
        if (!empty($pArr)) {
            $data->form->setSuggestions('name', $pArr);
            
            $data->form->setField('name', array('removeAndRefreshForm' => implode('|', $Driver->fieldArr)));
            
            $data->form->input('name');
            
            if ($data->form->rec->name && $pArr[$data->form->rec->name]) {
                $pRec = $clsName::fetch(array("#{$nameField} = '[#1#]'", $data->form->rec->name));
                
                foreach ($Driver->fieldArr as $fName) {
                    $data->form->setDefault($fName, $pRec->{$fName});
                }
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param pos_Terminal       $Driver
     * @param peripheral_Devices $mvc
     * @param int                $id
     * @param stdClass           $rec
     * @param NULL|array         $saveFileds
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec, $saveFileds = null)
    {
        $nameField = $Driver->nameField;
        $clsName = $Driver->clsName;
        
        if (!$rec->data['clsName'] || !$rec->data['objVal']) {
            $dRec = $clsName::fetch(array("#{$nameField} = '[#1#]'", $rec->{$nameField}));
        } else {
            $dRec = cls::get($rec->data['clsName'])->fetch($rec->data['objVal']);
        }
        
        $msgStr = 'Редактиране на|* ';
        if (!$dRec) {
            $dRec = new stdClass();
            $msgStr = 'Дабавяне на|* ';
        }
        
        foreach ($Driver->fieldArr as $fName) {
            $dRec->{$fName} = $rec->{$fName};
        }
        
        $dRec->{$nameField} = $rec->{$nameField};
        
        if ($clsName::save($dRec)) {
            $msgStr .= $clsName::getLinkForObject($dRec->id);
            status_Messages::newStatus($msgStr);
            
            $rec->data['clsName'] = $Driver->clsName;
            $rec->data['objVal'] = $dRec->id;
            
            $mvc->save_($rec, 'data');
        } else {
            status_Messages::newStatus('Грешка при добавяне на|* ' , '|' . $Driver->title, 'warning');
        }
    }
}
