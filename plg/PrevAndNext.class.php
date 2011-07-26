<?php

/**
 * Клас 'plg_PrevAndNext' - Добавя бутони за предишен и следващ във форма за редактиране
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_PrevAndNext extends core_Plugin
{
    
    
    /**
     * Промяна на бутоните
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareRetUrl($mvc, $data)
    {
        $Cmd = Request::get('Cmd');
        
        if (isset($Cmd['save_n_prev'])) {
            $data->retUrl = array($mvc, 'edit', 'id'=>$data->buttons->prevId);
        } elseif (isset($Cmd['save_n_next'])) {
            $data->retUrl = array($mvc, 'edit', 'id'=>$data->buttons->nextId);
        }
    }
    
    
    /**
     * Връща id на съседния запис в зависимост next/prev
     *
     * @param stdClass $data
     * @param string $dir
     */
    private function getNeighbour($mvc, $data, $dir)
    {
        if (!isset($data->form->rec->id)) {
            
            return NULL;
        }
        
        $query = $mvc->getQuery();
        
        $query->where("#id {$dir} {$data->form->rec->id}");
        $query->limit(1);
        $query->orderBy('id', $dir == '>'?'ASC':'DESC');
        
        $rec = $query->fetch();
        
        return $rec->id;
    }
    
    
    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        
        $data->buttons->prevId = $this->getNeighbour($mvc, $data, '<');
        $data->buttons->nextId = $this->getNeighbour($mvc, $data, '>');
    }
    
    
    /**
     * Добавяне на бутони за 'Предишен' и 'Следващ'
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    function on_AfterPrepareEditToolbar($mvc, $res, $data)
    {
        if (isset($data->buttons->nextId)) {
            $data->form->toolbar->addSbBtn('»', 'save_n_next', array('class'=>'btn-next'));
        }
        
        if (isset($data->buttons->prevId)) {
            $data->form->toolbar->addSbBtn('«', 'save_n_prev', array('class'=>'btn-prev'));
        }
    }
}