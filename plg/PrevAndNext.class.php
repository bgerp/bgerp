<?php



/**
 * Клас 'plg_PrevAndNext' - Добавя бутони за предишен и следващ във форма за редактиране
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
class plg_PrevAndNext extends core_Plugin
{
    
    function on_AfterDescription($mvc)
    {
        $mvc->doWithSelected = arr::make($mvc->doWithSelected, TRUE);
        $mvc->doWithSelected['edit'] = 'Редактиране';
    }

    /**
     * Промяна на бутоните
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareRetUrl($mvc, $data)
    {   
        $selKey = static::getModeKey($mvc);

        if(Mode::is($selKey)) {
            $Cmd = Request::get('Cmd');
            
            if (isset($Cmd['save_n_prev'])) {
                $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->prevId, 'PrevAndNext' => 'on');
            } elseif (isset($Cmd['save_n_next'])) {
                $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->nextId, 'PrevAndNext' => 'on');
            }
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
        $id = $data->form->rec->id;
        if(!$id) return;

        $selKey = static::getModeKey($mvc);
        $selArr = Mode::get($selKey);

        if(!count($selArr)) return;
        $selId = array_search($id, $selArr);
        if($selId === FALSE) return;

        $selNeighbourId = $selId + $dir;

        return $selArr[$selNeighbourId];
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
        $selKey = static::getModeKey($mvc);
        
        $Cmd = Request::get('Cmd');

        if($sel = Request::get('Selected')) {

            // Превръщаме в масив, списъка с избраниуте id-та
            $selArr = arr::make($sel);

            // Записваме масива в сесията, под уникален за модела ключ
            Mode::setPermanent($selKey, $selArr);
            
            // Зареждаме id-то на първия запис за редактиране
            expect(ctype_digit($id = $selArr[0]));
            
            // Извличаме записа
            expect($data->form->rec = $mvc->fetch($id));
            
            $mvc->requireRightFor('edit', $data->form->rec);

        } elseif( !($Cmd['save_n_next'] || $Cmd['save_n_prev'] || Request::get('PrevAndNext'))) {

            // Изтриваме в сесията, ако има избрано множество записи 
            Mode::setPermanent($selKey, NULL);
        }

        $data->buttons = new stdClass();
        $data->buttons->prevId = $this->getNeighbour($mvc, $data, -1);
        $data->buttons->nextId = $this->getNeighbour($mvc, $data, +1);
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
        $selKey = static::getModeKey($mvc);

        if(Mode::is($selKey)) {
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


    /**
     * Връща ключа за кеша, който се определя от сесията и модела
     */
    static function getModeKey($mvc) 
    {
        return $mvc->className . '_PrevAndNext';
    }
}