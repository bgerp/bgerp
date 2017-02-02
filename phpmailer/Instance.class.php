<?php



/**
 * Клас от EF, който агрегира PHP Mailer Lite
 *
 *
 * @category  vendors
 * @package   phpmailer
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class phpmailer_Instance extends core_BaseClass
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
    	$conf = core_Packs::getConfig('phpmailer');
    	
    	// Зареждаме phpmailer-а за избраната версия
    	require_once $conf->PML_VERSION . '/PHPMailerAutoload.php';
    	
        // Създаваме инстанция на PHPMailerLite
        $PML = new PHPMailer();
        
        // Да не проверява сертификата на SMTP-то
        $PML->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        //TODO: да се сложи в конфигурацията?
        
        // Задаваме стойностите от конфигурацията
        $PML->Mailer    = $conf->PML_MAILER;
        $PML->CharSet   = $conf->PML_CHARSET;
        
        //      $PML->Encoding  = PML_ENCODING;
        $PML->From      = $conf->PML_FROM_EMAIL;
        $PML->FromName  = $conf->PML_FROM_NAME;
        $PML->Sendmail  = $conf->SENDMAIL_PATH;
        $PML->SingleTo  = $conf->PML_SINGLE_TO;
        $PML->Host      = $conf->PML_HOST;
        $PML->Port      = $conf->PML_PORT;
        $PML->SMTPAuth  = $conf->PML_SMTPAUTH;
        $PML->SMTPSecure = $conf->PML_SMTPSECURE;
        $PML->Username  = $conf->PML_USERNAME;
        $PML->Password  = $conf->PML_PASSWORD;
        
        if (strpos($PML->From, ".") === FALSE) {
            $PML->From .= ".com";
        }
        
        if($params['emailTo']) {
            list($user, $domain) = explode('@', $params['emailTo']);
            if($domain && getmxrr($domain, $mxhosts, $mx_weight)) {
                if(count($mxhosts) && ! $params['Host']) {
                    $params['Host'] = $mxhosts[0];
                    $params['SMTPAuth'] = FALSE;
                    $params['SMTPSecure'] = FALSE;
                    $params['XMailer'] = 'bgERP direct SMTP';
                    $params['Mailer']  = 'smtp';
                }
            }
            unset($params['emailTo']);
        }
        
        // Добавяме динамичните параметри, които могат да 
        // "препокрият" зададените конфигурационни стойности
        if(count($params)) {
            foreach($params as $name => $value) {
                $PML->{$name} = $value;
            }
        }
 
        // Връщаме създадения обект
        return $PML;
    }
}