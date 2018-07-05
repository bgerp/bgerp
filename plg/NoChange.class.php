<?php



/**
 * Клас 'plg_NoChange' - Забранява промяната на маркираните с `nochange` полета, ако записа не може да се изтрива
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_NoChange extends core_Plugin
{
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;

        if ($rec->id && !$mvc->haveRightFor('delete', $rec)) {
            $fields = $mvc->selectFields('#noChange');
            
            foreach ($fields as $name => $field) {
                $form->setReadonly($name);
                $form->fields[$name]->type->params['allowEmpty'] = null;
            }
        }
    }
}
