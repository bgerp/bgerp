<?php

use jigarakatidus\Signal;

/**
 * Местоположение на signal-cli
 */
defIfNot('PHPSIGNAL_SIGNAL_BIN_PATH', '/usr/local/bin/signal-cli');
//defIfNot('PHPSIGNAL_SIGNAL_VERSION', '0.9.2');
defIfNot('PHPSIGNAL_SIGNAL_NUMBER', '+359');
defIfNot('PHPSIGNAL_SIGNAL_TEST_NUMBER','+359');



/**
 * Wrapper за php-signal CLI
 *
 * @category  vendors
 * @package   php-signal
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class phpsignal_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Wrapper за CLI клиент за signal';

    
/**
     * Описание на системните действия
     */
    public $systemActions = array(
        array('title' => 'Регистриране', 'url' => array('phpsignal_Client', 'Register', 'ret_url' => true), 'params' => array('title' => 'Регистриране клиент')),
        array('title' => 'Валидиране ключ', 'url' => array('phpsignal_Client', 'ValidateCode', 'ret_url' => true), 'params' => array('title' => 'Валидиране код')),
        array('title' => 'Отписване', 'url' => array('phpsignal_Client', 'UnRegister', 'ret_url' => true), 'params' => array('title' => 'Отрегистриране клиент'))
    );
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PHPSIGNAL_SIGNAL_BIN_PATH' => array('varchar', 'mandatory, caption=Настройки signal-cli->Път изпълним файл'),
//        'PHPSIGNAL_SIGNAL_VERSION' => array('varchar', 'mandatory, caption=Настройки signal-cli->Версия'),
        'PHPSIGNAL_SIGNAL_NUMBER' => array('varchar(20)', 'mandatory, caption=Настройки клиент->Номер'),
        'PHPSIGNAL_SIGNAL_TEST_NUMBER' => array('varchar(20)', 'caption=Номер за тестово съобщение->стойност'),
    );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        // Инсталираме библиотеката чрез композера
        if (core_Composer::isInUse()) {
            // $html .= core_Composer::install('jigarakatidus/php-signal', 'dev-main');
            $html .= core_Composer::install('jigarakatidus/php-signal', '2.1.0');
        } else {
            $html .= "<li class='red'>Не е инсталиран композер!</li>";
        }
        
        // Проверка за наличие на cli signal клиент
        // $binPath = phpsignal_Setup::get('SIGNAL_PATH') . '/signal-cli-' . phpsignal_Setup::get('SIGNAL_VERSION') . '/bin/';
        $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
        // Ако няма CLI - го сваляме
        if (!is_executable($binPath)) {
            $html .= "<li class='debug-error'>{$binPath} не може да се изпълни!</li>";
            
//             $filename = "signal-cli-" . phpsignal_Setup::get('SIGNAL_VERSION') . ".tar.gz";
//             $cmd = "wget -O /tmp/{$filename} https://github.com/AsamK/signal-cli/releases/download/v" . phpsignal_Setup::get('SIGNAL_VERSION') . "/{$filename} -P /tmp/ 2>&1";
//             $outputDwnl = null;
//             exec ($cmd, $outputDwnl);
//             $output = null;
//             $cmd = "tar xf /tmp/{$filename} -C " . phpsignal_Setup::get('SIGNAL_PATH') . " 2>&1";
//             exec ($cmd, $output);
//             if (is_executable($binPath . 'signal-cli')) {
//                 $html .= "<li class='debug-new'>Успешно инсталиран signal-cli</li>";
//             } else {
//                 $html .= "<li class='debug-error'>Проблем с инсталирането на signal-cli</li>";
//                 $html .= "<li class='debug-error'>Резултат сваляне: <pre>" . print_r($outputDwnl, true) . "</pre></li>";
//                 $html .= "<li class='debug-error'>Резултат разархивиране: <pre>" . print_r($output, true) . "</pre></li>";
//             }
        } else {
            $html .= "<li class='debug-info'>Инсталиран от преди това signal-cli</li>";
        }
       
        return $html;
    }

    /**
     * Проверява дали phpsignal е конфигуриран 
     * @var boolean $fullCheck дали да прави пълна проверка 
     * @return NULL|string
     */
    public function checkConfig($fullCheck = false)
    {
        if (!$fullCheck) {
            
            return;
        }
        $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
        // Ако няма CLI - го сваляме
        if (!is_executable($binPath)) {
            $html .= "<li class='debug-error'>{$binPath} не е изпълним!</li>";
        }
        if (core_Composer::isInUse()) {
            $binPath = phpsignal_Setup::get('SIGNAL_BIN_PATH');
            $client = new Signal($binPath, phpsignal_Setup::get('SIGNAL_NUMBER'), Signal::FORMAT_JSON);
        } else {
            return "Не е инсталиран композер!";
        }
        $clientNumber = phpsignal_Setup::get('SIGNAL_NUMBER');
        $res = $client->getUserStatus([$clientNumber]);
        if (empty($res)) {
            return "Не е регистриран signal клиент.";
        }
    }
    
}
