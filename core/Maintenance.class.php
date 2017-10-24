<?php



/**
 * Клас 'core_Maintenance' - Мениджър за поддръжка на системата
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Maintenance extends core_Manager
{
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Поддръжка на системата';


    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Сервизно действие';


    /**
     * Списък с плъгини
     */
    public $loadList = 'plg_SystemWrapper';


    /**
     *
     */
    public function act_Default()
    {
        requireRole('admin');

        $data = new stdClass();
        $actArr = array();
        $query = core_Packs::getQuery();
        
        $res = '';
        
        while($rec = $query->fetch("#state = 'active'")) {
            $stp = $rec->name . '_Setup';
            if(cls::load($stp, TRUE)) {
                $setups[$rec->name] = cls::get($stp);
                if(is_array($setups[$rec->name]->systemActions)) {
                    $res .= "\n<div style='margin-top:15px; background-color:#66a; color:white; padding:5px;'><strong>" . $rec->name . '</strong> - ' . $setups[$rec->name]->info . "</div>";
                    foreach($setups[$rec->name]->systemActions as $a) {  
                        $res .= "\n<div style='margin-top:5px;'>" . ht::createBtn($a['title'], $a['url'], NULL, FALSE, $a['params']) . $a['params']['title'] . "</div>";
                    }
                }
            }
        }
        
        return $this->renderWrapping("<div style='display:table-cell'>" . $res . "</div>", $data);
    }

   
}