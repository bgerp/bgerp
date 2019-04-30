<?php



/**
 * Определя релациите между таблиците
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Релации в базата bgERP
 */
class h18_Relations extends core_Manager
{
    
    public $loadList = '';

    /**
     * Заглавие
     */
    public $title = 'Релации в базата bgERP';
    
    public function act_Show () {
        
        $cls = core_Cls::get('cash_Pko');
        // bp($cls->fields);
        foreach($cls->fields as $field) {
            $className = get_class($field->type);
            
            switch ($className) {
                case 'type_Key':
                    bp($field->type->params['mvc'], $field->type->params['select']);
                    break;
                case 'type_KeyList':
                    break;
                case 'type_CustomKey':
                    break;
            }
                    
            
           // $field->type['params']['select'];
        }
    }
}