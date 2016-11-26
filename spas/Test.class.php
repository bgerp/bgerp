<?php

class spas_Test extends core_Mvc
{
    function act_Test()
    {  
        $form = cls::get('core_Form');
        $form->FLD('message', 'text', 'caption=MIME съобщение,mandatory');
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
        $form->FLD('message', 'text', 'caption=MIME съобщение,mandatory');
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
        
        if($res === TRUE) {
            redirect($redirectUrl, FALSE, 'Установена е връзка със Спас', 'notice');
        }
    }

    
    /**
     * Връща инстанция на драйвера за SA
     */
    public static function getSa()
    {
        $params = array(
            'host' => spas_Setup::get('HOSTNAME'), 
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
