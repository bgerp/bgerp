<?php



/**
 * Клас 'plg_PrevAndNext' - Добавя бутони за предишен и следващ във форма за редактиране
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
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
            $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->prevId);
        } elseif (isset($Cmd['save_n_next'])) {
            $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->nextId);
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
        
        if($mvc instanceof core_Detail) {
            $mvc->prepareDetailQuery($data);
            $query = clone($data->query);
        }
        
        $query->where("#id {$dir} {$data->form->rec->id}");
        $query->limit(1);
        $query->orderBy('id', $dir == '>' ? 'ASC' : 'DESC');
        
        $rec = $query->fetch();
        
        return $rec->id;
    }
    
    
    /**
     * Подготовка на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->buttons = new stdClass();
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
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (isset($data->buttons->nextId)) {
            $data->form->toolbar->addSbBtn('»', 'save_n_next', 'class=btn-next noicon,order=30');
        } else {
            $data->form->toolbar->addSbBtn('»', 'save_n_next', 'class=btn-next btn-disabled noicon,disabled,order=30');
        }
        
        if (isset($data->buttons->prevId)) {
            $data->form->toolbar->addSbBtn('«', 'save_n_prev', 'class=btn-prev noicon,order=30');
        } else {
            $data->form->toolbar->addSbBtn('«', 'save_n_prev', 'class=btn-prev btn-disabled noicon,disabled,order=30');
        }
    }
}