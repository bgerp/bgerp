<?php


/**
 * Терминал2 за отчитане на производство
 * 
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Terminal2 extends core_Mvc
{
    
    /**
     * Заглавие
     */
    public $title = 'Производствен терминал';
    

    public function act_Default()
    {
        $tpl = new ET(getTplFromFile('planning/tpl/Terminal2.shtml'));
        
        $tpl->append('<link rel="stylesheet" href=' . sbf('/planning/css/Terminal2.css') . '>', 'HEAD');

        $tpl->append('<script src=' . sbf('/planning/js/Terminal2.js') . '></script>', 'HEAD');


        echo $tpl;

        die;
    }

    /**
     * Изпълнява командата
     */
    public function act_Cmd()
    {
        // Проверка за права ТОДО
        
        $cmd = Request::get('cmd');

        $res = array();

        if(strpos($cmd, 'Количество:') === 0) {
            $q = (float) trim(substr($cmd, strlen('Количество:')));
            if(!($q>0)) {
                $q = rand(2000, 3000);
            }
            $res['quantity'] = $q;
        }

        if(strpos($cmd, 'Сигнал:') === 0) {
            $res['menuTitle'] = "Оборудване";
            $res['menuBody'] = "
                <div>Машина 1</div>
                <div>Машина 2</div>
            ";
            $res['menuBackgroundColor'] = "#669";
        }

        if(strpos($cmd, 'Операция:') === 0) {
            $res['menuTitle'] = "Производствени операции";
            $res['menuBody'] = "
                <div>Операция 1</div>
                <div>Операция 2</div>
            ";
            $res['menuBackgroundColor'] = "#696";
        }

        if(strpos($cmd, 'Задание:') === 0) {
            $res['menuTitle'] = "Задание";
            $res['menuBody'] = "
                <div>Инфо ... </div>
            ";
            $res['menuBackgroundColor'] = "#966";
        }

        $res = json_encode($res);

        echo $res;

        die;
    }
 
}