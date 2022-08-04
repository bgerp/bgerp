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
                $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
                $client = new Signal($binPath, phpsignal_Setup::get('SIGNAL_NUMBER'), Signal::FORMAT_JSON);
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
            
            if (core_Composer::isInUse()) {
                $signalNumber = phpsignal_Setup::get('SIGNAL_NUMBER');
                // Инстанция на класа
                $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
                $client = new Signal($binPath, $signalNumber, Signal::FORMAT_JSON);
            }
            $msg = "Неуспешна валидация";
            if ($client->verify($form->rec->key)) {
                $msg = 'Валидиран код за signal-cli|*';
            }
            
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
                $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
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
    
    /**
     * Регистрира signal клиента
     */
    public function act_Register()
    {
        requireRole('admin');
        $form = cls::get('core_Form');
        $form->title = 'Регистрация клиент signal';
        $form->FLD('captcha', 'varchar(512)', 'caption=Кепча от: https://signalcaptchas.org/staging/challenge/generate.html->Кепча');
        $form->FLD('validationMethod', 'enum(voice,sms)', 'caption=Получаване на код валидиране->VOICE/SMS');
        
        $form->input('captcha,validationMethod', true);
        
        if ($form->isSubmitted()) {
            $retUrl = getRetUrl();
            
            if (core_Composer::isInUse()) {
                $signalNumber = phpsignal_Setup::get('SIGNAL_NUMBER');
                // Инстанция на класа
                $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
                $client = new Signal($binPath, $signalNumber, Signal::FORMAT_JSON);
            }
            
            $validationMethod = ($form->rec->validationMethod == 'voice') ? true : false;
            $msg = "Неуспешна регистрация";
            if ($client->Register($validationMethod, $form->rec->captcha)) {
                $msg = "Успешно регистриран номер.";
            }
            
            return new Redirect($retUrl, $msg);
            
        }
        $form->toolbar->addSbBtn('Регистрирай', 'save', 'ef_icon = img/16/disk.png, title = Регистрирай');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        
        return $tpl;
    }

    /**
     * Регистрира signal клиента
     */
    public function act_Test()
    {
        requireRole('admin');
        bp(phpsignal_Client::send(['+359887181813'], "Hi hi hi ..."));
    }
}
