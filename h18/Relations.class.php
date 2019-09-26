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
        $tables = array(
            'cash_pko',
            'cash_rko',
            'cat_products',
            'crm_companies',
            'pos_points',
            'pos_receipts',
            'pos_receiptDetails',
            'pos_stocks',
            'sales_sales',
            'sales_salesDetails',
            'sales_invoices',
            'sales_invoiceDetails',
            'sales_services',
            'core_roles',
            'core_users',
        );
        $res = '';
        $t = array();
        $t = $tables;
        foreach ($tables as $table) {
            $cls = core_Cls::get($table);
            $res .= "<br>";
        // bp($cls->fields);
            foreach($cls->fields as $field) {
                $className = get_class($field->type);
                
                switch ($className) {
                    case 'type_Key':
                        //bp($field->type->params['mvc'], $field->type->params['select']);
                        //$res .= "\"" . $table . '_' . $field->name . "\"" . '->' . "\"" . $field->type->params['mvc'] . '_' . (empty($field->type->params['select'])?'id':$field->type->params['select']) . "\"" . "<br>";
                        $res .= "<b>" . $table . '_' . $field->name . "</b>" . ' е връзка към ' . "<b>" . $field->type->params['mvc'] . '_' . 'id' . "</b>" . "<br>";
                        $t[] = $field->type->params['mvc'];
                        break;
                    case 'type_KeyList':
                        break;
                    case 'type_CustomKey':
                       // bp($field->type->params['mvc'], $field->type->params['key'], $field->type->params['select']);
                        $res .= "<b>" . $table . '_' . $field->name . "</b>" . ' е връзка към ' . "<b>" . $field->type->params['mvc'] . '_' . $field->type->params['key'] ."</b>" .  "<br>";
                        $t[] = $field->type->params['mvc'];
                        break;
                }
            }
        }
        
        die($res);
    }
}