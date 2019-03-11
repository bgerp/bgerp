<?php


/**
 * Драйвер за наблюдение състоянието на сървъра
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Мониторинг на домейн
 */
class sens2_DomainMon extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Мониторинг на домейн';
    
    
    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';
    
    
    public $inputs = array(
        'reachable' => array('caption' => 'Достъпност', 'uom' => ''),
        'certValidity' => array('caption' => 'Валидност на сертификата', 'uom' => 'days'),
        'loadTime' => array('caption' => 'Зареждане', 'uom' => 'sec'),
    );
    
    
    public function prepareConfigForm($form)
    {

        
        $form->FLD('domain', 'varchar', 'caption=Домейн');
  
    }
    
    
    public function checkConfigForm($form)
    {
    }
    
    
    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        if ($inputs['reachable']) {
            $res['reachable'] = $this->getReachable($config);
        }

        if ($inputs['certValidity']) {
            $res['certValidity'] = $this->getCertValidity($config);
        }

        if ($inputs['loadTime']) {
            $res['loadTime'] = $this->getLoadTime($config);
        }
         
        return $res;
    }
    
        
    /**
     * Проверява дали имаме http връзка с даден адрес
     */
    public function getReachable($config)
    {
        list($domain, $port) = explode(':', $config->domain);
        
        if (!$port) {
            $port = '80';
        }
        
        $res = @fsockopen($domain, round($port), $errno, $errstr, 3) ? 1 : 0;
        
        return $res;
    }
    

    /**
     * Проверка за валидност на сертификата
     */
    public function getCertValidity($config)
    {
        try {
            $g = @stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
            if($q === false) {
                return null;
            }

            $r = @stream_socket_client("ssl://" . $config->domain . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
            if($r === false) {
                return null;
            }

            $cont = stream_context_get_params($r);
            $certinfo = openssl_x509_parse($cont["options"]["ssl"]["peer_certificate"]);
            $success = true;
            $validity = $certinfo['validTo_time_t'] - time();
        } catch(Exception $e) {
            return null;
        }

        return round($validity / (24*60*60));
    }


    /**
     * Проверка за валидност на сертификата
     */
    public function getLoadTime($config)
    {
        $timeStart = time();

        $txt = @file_get_contents('http://' . $config->domain);

        if($txt === false) {
            return null;
        }

        return time() - $timeStart;
    }
 
    
    /**
     * Записва стойностите на изходите на контролера
     *
     * @param array $outputs         масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param array $config          конфигурациони параметри
     * @param array $persistentState персистентно състояние на контролера от базата данни
     *
     * @return array Масив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    public function writeOutputs($outputs, $config, &$persistentState)
    {
    }
}
