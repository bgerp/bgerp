<?php

class spas_Test extends core_Mvc
{

    /**
     * Списък с плъгини
     */
    public $loadList = 'plg_SystemWrapper';

    /**
     * Описание на системните действия
     */
    private $systemActions = array(
        array('title' => 'Ping', 'url' => array ('spas_Test', 'ping', 'ret_url' => TRUE), 'params' => array('title' => 'Пингване на Спас')),
        array('title' => 'Тест', 'url' => array ('spas_Test', 'test', 'ret_url' => TRUE), 'params' => array('title' => 'Тестване на имейл')),
        array('title' => 'Обучение', 'url' => array ('spas_Test', 'learn', 'ret_url' => TRUE), 'params' => array('title' => 'Обучение на Спас')),
    );


    /**
     * Показва менюто с действия на пакета
     */
    public function act_Default()
    {
        $html = "<div style=''><strong>" . tr("Интеграция със SpamAssassin") . "</strong></div>";
        
        foreach($this->systemActions as $a) {
            $html .= "\n<div style='margin-top:5px;'>" . ht::createBtn($a['title'], $a['url'], NULL, FALSE, $a['params']) . $a['params']['title'] . "</div>";
        }
        
        $data = new stdClass();
        
        $this->currentTab = 'Код->Пакети';

        return $this->renderWrapping("<div style='display:table-cell'>" . $html . "</div>", $data);
    }


    function act_Test()
    {  
        $form = cls::get('core_Form');
        $form->FLD('message', 'text(1000000)', 'caption=MIME съобщение,mandatory');
        $form->toolbar->addSbBtn('Тест');
        $form->title = 'Тестване на имейл за спам';

        $rec = $form->input();

         
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($form->isSubmitted()) {    

            $sa = $this->getSa();
            
            try {
                $res = $sa->getSpamReport($rec->message);

                if(is_object($res)) {
                    foreach((array) $res as $key => $value) {
                        $html .= "<li><strong>{$key}</strong> =  {$value}</li>";
                    }
                }
            } catch(spas_client_Exception $e) {
                $html .= "<li class='debug-error'><strong>Грешка:</strong>" . $e->getMessage() . "</li>";
            }
            
            $html = str_replace("\n", "<br>", $html);

            $form->info = "<ul>" . $html . "</ul>";
        }
        
        self::addCancelBtn($form);

        $res = $form->renderHtml();

        return $res;

    }
    
    
    
    function act_Learn()
    {  

        $form = cls::get('core_Form');
        $form->FLD('message', 'text(1000000)', 'caption=MIME съобщение,mandatory');
        $form->FLD('type', 'enum(spam=Спам,ham=Салам,forget=Забравяне)', 'caption=Тип,mandatory');

        $form->toolbar->addSbBtn('Научи');
        $form->title = 'Обучение на Спас';

        $rec = $form->input();

         
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($form->isSubmitted()) {    

            $sa = $this->getSa();

            switch($rec->type) {
                case 'spam': $type = spas_Client::LEARN_SPAM;
                    break;
                case 'ham': $type = spas_Client::LEARN_HAM;
                    break;
                case 'forget': $type = spas_Client::LEARN_FORGET;
                    break;

            }
            
            try {


                $res = $sa->learn($rec->message, $type);

                if(is_object($res)) {
                    foreach((array) $res as $key => $value) {
                        $html .= "<li><strong>{$key}</strong> =  {$value}</li>";
                    }
                }
            } catch(spas_client_Exception $e) {
                $html .= "<li class='debug-error'><strong>Грешка:</strong>" . $e->getMessage() . "</li>";
            }
            
            $html = str_replace("\n", "<br>", $html);

            $form->info = "<ul>" . $html . "</ul>";
        }
        
        self::addCancelBtn($form);

        $res = $form->renderHtml();

        return $res;

    }


    /**
     * Пингва връзката със SA
     */
    function act_Ping() 
     {
        $sa = $this->getSa();
        
        $redirectUrl = getRetUrl();

        try {
            $res = $sa->ping();
        } catch(spas_client_Exception $e) {
            redirect($redirectUrl, FALSE, $e->getMessage(), 'error');
        }
        
        $msg = 'Грешка при свързване със Спас';
        $type = 'error';
        
        if($res === TRUE) {
            $msg = 'Установена е връзка със Спас';
            $type = 'notice';
        }
        
        redirect($redirectUrl, FALSE, $msg, $type);
    }

    
    /**
     * Връща инстанция на драйвера за SA
     */
    public static function getSa()
    {
        $params = array(
            'hostname' => spas_Setup::get('HOSTNAME'), 
            'port' => spas_Setup::get('PORT'),
            'user' => spas_Setup::get('USER'));
        $sa = new spas_Client($params);
        
        return $sa;
    }


    static function addCancelBtn($form)
    {
        $form->toolbar->addBtn('Отказ', array('core_Packs', 'config', 'pack' => 'spas'));
    }
 }
