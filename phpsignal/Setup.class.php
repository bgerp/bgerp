<?php


/**
 * Местоположение на signal-cli
 */
defIfNot('PHPSIGNAL_SIGNAL_PATH', posix_getpwuid(posix_getuid())['dir']);
defIfNot('PHPSIGNAL_SIGNAL_VERSION', '0.9.2');
defIfNot('PHPSIGNAL_SIGNAL_NUMBER', '+359');

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
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PHPSIGNAL_SIGNAL_PATH' => array('varchar', 'mandatory, caption=Настройки->Път'),
        'PHPSIGNAL_SIGNAL_VERSION' => array('varchar', 'mandatory, caption=Настройки->Версия'),
        'PHPSIGNAL_SIGNAL_NUMBER' => array('varchar(18)', 'mandatory, caption=Настройки->Номер')
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
            $html .= core_Composer::install('jigarakatidus/php-signal', '1.1.0');
        } else {
            $html .= "<li class='red'>Не е инсталиран композер!</li>";
        }
        
        // Проверка за наличие на cli signal клиент
        $binPath = phpsignal_Setup::get('SIGNAL_PATH') . '/signal-cli-' . phpsignal_Setup::get('SIGNAL_VERSION') . '/bin/';
        // Ако няма CLI - го сваляме
        if (!is_executable($binPath . 'signal-cli')) {
            $filename = "signal-cli-" . phpsignal_Setup::get('SIGNAL_VERSION') . ".tar.gz";
            $cmd = "wget -O {$filename} https://github.com/AsamK/signal-cli/releases/download/v" . phpsignal_Setup::get('SIGNAL_VERSION') . "/{$filename} -P /tmp/ 2>&1";
            $outputDwnl = null;
            exec ($cmd, $outputDwnl);
            $output = null;
            $cmd = "tar xf /tmp/{$filename} -C " . phpsignal_Setup::get('SIGNAL_PATH') . " 2>&1";
            exec ($cmd, $output);
            if (is_executable($binPath . 'signal-cli')) {
                $html .= "<li class='debug-new'>Успешно инсталиран signal-cli</li>";
            } else {
                $html .= "<li class='debug-error'>Проблем с инсталирането на signal-cli</li>";
                $html .= "<li class='debug-error'>Резултат сваляне: <pre>" . print_r($outputDwnl, true) . "</pre></li>";
                $html .= "<li class='debug-error'>Резултат разархивиране: <pre>" . print_r($output, true) . "</pre></li>";
            }
        } else {
            $html .= "<li class='debug-info'>Инсталиран от преди това signal-cli</li>";
        }
        // Регистрира зададения номер ...
        
        
        return $html;
    }
}
