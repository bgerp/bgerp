<?php


use jigarakatidus\Signal;

/**
 * Клиент за работа със signal
 *
 * @category  vendors
 * @package   phpsignal
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class phpsignal_Client extends core_Manager
{
    /**
     * Изпраща съобщение до друг клиент
     *
     * @param array $number  - номерa на получатели
     * @param string $message - съобщение
     *
     * @return string - резултат
     */
    public static function send(array $number, $message)
    {
        
        if (core_Composer::isInUse()) {
            try {
                // Инстанция на класа
                $client = new Signal(
                    phpsignal_Setup::get('SIGNAL_BIN_PATH'), // Binary Path
                    phpsignal_Setup::get('SIGNAL_NUMBER'), // Username/Number including Country Code with '+'
                    Signal::FORMAT_JSON // Format
                    );
            } catch (Exception $e) {
                reportException($e);
            }
        }
        $res = $client->send($number, $message);
        
        return $res;
    }

    /**
     * Валидира получения код за signal
     */
    public function act_ValidateCode()
    {
        requireRole('admin');
        
        $form = cls::get('core_Form');
        $form->title = 'Валидиране код за signal';
        $form->FLD('key', 'varchar(6)', 'caption=Код');
        
        $form->input('key', true);
        
        if ($form->isSubmitted()) {
            $retUrl = getRetUrl();
            
            
            
            $msg = 'Регистриран код за signal-cli|*';
            
            return new Redirect($retUrl, $msg);
            
        }
        $form->toolbar->addSbBtn('Валидирай', 'save', 'ef_icon = img/16/disk.png, title = Валидация');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        
        return $tpl;
        
    }

    /**
     * Дерегистрира signal клиента
     */
    public function act_UnRegister()
    {
        requireRole('admin');
        
        if (core_Composer::isInUse()) {
            try { 
                // Инстанция на класа
                $binPath = phpsignal_Setup::get('SIGNAL_PATH') . '/signal-cli-' . phpsignal_Setup::get('SIGNAL_VERSION') . '/bin/signal-cli';
                $client = new Signal($binPath, phpsignal_Setup::get('SIGNAL_NUMBER'), Signal::FORMAT_JSON);
            } catch (Exception $e) {
                reportException($e);
            }
        }
        $signalNumber = phpsignal_Setup::get('SIGNAL_NUMBER');
        if (false !== strpos($client->getUserStatus([$signalNumber]), 'true')) {
            if ($client->UnRegister()) {
                $res = "Успешно дерегистриран номер.";
            }
        }
        if (empty($res)) {
            $res = "Липсваща регистрация.";
        }
        
        $retUrl = getRetUrl();
            
        return new Redirect($retUrl, $res);
            
    }
    

}
