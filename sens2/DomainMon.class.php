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
        $form->FLD('domain', 'varchar', 'caption=Домейн->Основен,mandatory');
        $form->FLD('altDomain', 'varchar', 'caption=Домейн->Алтернативен');
    
    }
    
    
    public function checkConfigForm($form)
    {
    }
    
    
    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();
        
        if ($inputs['reachable']) {
            $res['reachable'] = $this->getReachable($config->domain);
            if(strlen($config->altDomain)) {
                $res['reachable'] = min($res['reachable'], $this->getReachable($config->altDomain));
            }
        }
        
        if ($inputs['certValidity']) {
            $res['certValidity'] = $this->getCertValidity($config->domain);
            if(strlen($config->altDomain)) {
                $res['certValidity'] = min($res['certValidity'], $this->getCertValidity($config->altDomain));
            }
        }
        
        if ($inputs['loadTime']) {
            $res['loadTime'] = $this->getLoadTime($config->domain, $config->altDomain);
        }
        
        return $res;
    }
    
    
    /**
     * Проверява дали имаме http връзка с даден адрес
     */
    public function getReachable($domain)
    {
        $port = '80';
        
        $res = @fsockopen($domain, round($port), $errno, $errstr, 3) ? 1 : 0;
        
        return $res;
    }
    
    
    /**
     * Проверка за валидност на сертификата
     */
    public function getCertValidity($domain)
    {
        try {
            $g = @stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
            if($g === false) {
                
                return 0;
            }
            
            $r = @stream_socket_client("ssl://" . $domain . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
            if($r === false) {
                
                return 0;
            }
            
            $cont = stream_context_get_params($r);
            $certinfo = openssl_x509_parse($cont["options"]["ssl"]["peer_certificate"]);
            $success = true;
            $validity = $certinfo['validTo_time_t'] - time();
        } catch(Exception $e) {
            
            return 0;
        }
        
        return round($validity / (24*60*60));
    }
    
    
    /**
     * Проверка за валидност на сертификата
     */
    public function getLoadTime($domain, $altDomain = null)
    {
        $timeStart = microtime(true);
        
        $txt = @file_get_contents('http://' . $domain);
        if($txt === false) {
            
            return -1;
        }
        $c = 1;
        if(strlen($altDomain)) {
            $txt2 = @file_get_contents('http://' . $altDomain);
            if(!strlen($txt2)) {
                
                return -1;
            }
            $c = 2;
        }
        
        return (microtime (true) - $timeStart)/$c;
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
