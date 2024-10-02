<?php


/**
 * Клас 'drdata_Emails' -
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Emails extends core_BaseClass
{
    /**
     * @todo Чака за документация...
     */
    public $caller;
    
    
    /**
     * Изглежда ли стринга като валиден имейл или не.
     */
    public static function isWrongEmail($email)
    {
        $regExp = "/^((\\\"[^\\\"\\f\\n\\r\\t\\b]+\\\")|([A-Za-z0-9_][A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*(\\.[A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*)*))@((\\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9])(([A-Za-z0-9\\-])*([A-Za-z0-9]))?(\\.(?=[A-Za-z0-9\\-]))?)+[A-Za-z]+))$/D";
        $regExp = '/^[_A-z0-9-]+((\.|\+|\/)[_A-z0-9-]+)*@[A-z0-9-]+(\.[A-z0-9-]+)*(\.[A-z]{2,24})$/';
        
        if (!preg_match($regExp, $email)) {
            
            return 'Невалиден имейл';
        }
    }
    
    
    /**
     * Нормализиране на стойността
     */
    public static function normalize($email)
    {
        return trim(strtolower($email));
    }
    
    
    /**
     * Основна функция на класа
     * /
     */
    public static function validate($email, &$result)
    {
        $conf = core_Packs::getConfig('drdata');
        
        $email = self::normalize($email);
        
        $result['value'] = $email;
        
        if (preg_match('/^www\./i', $email)) {
            $result['warning'] = 'Наистина ли имейла започва с|* <B>www.</B> ?';
        }
        
        // Ако визуалната проверка не е вярна връщаме грешката
        if ($result['error'] = self::isWrongEmail($email)) {
            
            return;
        }
        
        // Проверка на MX записа на домейна
        list($user, $domain) = explode('@', $email);

        if ((self::mxAndARecordsValidate($domain)) === false) {
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'CNAME')) {
                $result['warning'] = "Възможен е проблем с домейна|* {$user}@<b>{$domain}</b>";
            }

            return;
        }
        
        if (self::isInNeverLogged($domain)) {
            $result['warning'] = "Възможен е проблем със сървъра|* <b>{$domain}</b>";
        }
        
        if (self::isInAlwaysOK($domain)) {
            
            return;
        }
        
        // Заобикаляне на останалите проверки
    }
    
    /**
     * Връща масив с MX и A записите на домейна, ако няма такива и няма и CNAME връща FALSE
     *
     * @param string $domain
     */
    public static function mxAndARecordsValidate($domain)
    {
        // Проверка за MX и A записи
        $hosts = @dns_get_record($domain, DNS_A + DNS_MX, $audthns, $addtl);
        
        // Ако няма MX и A записи, проверка за CNAME запис
        if (!$hosts) {
            $cnameRecords = @dns_get_record($domain, DNS_CNAME, $audthns, $addtl);
            
            // Ако има CNAME запис, правим запитване за A или MX записи за новия домейн
            if ($cnameRecords) {
                $cnameDomain = $cnameRecords[0]['target'];
                $hosts = @dns_get_record($cnameDomain, DNS_A + DNS_MX, $audthns, $addtl);
            }
        }
        
        if (!$hosts) {
            return false;
        }
        
        return $hosts;
    }
    
    
    /**
     * Връща масив с MX записите на домейна, ако няма такива връща FALSE
     */
    public static function mxRecordsValidate($domain)
    {
        if (getmxrr($domain, $mxhosts, $mx_weight)) {
            if ($mxhosts === array(0 => '')) {
                
                return false;
            }
            
            return $mxhosts;
        }
        
        return false;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function smtpSend($sock, $cmd)
    {
        if ($cmd) {
            if (@fwrite($sock, $cmd . "\r\n") === false) {
                
                return false;
            }
        }
        
        if (($resp = @fgets($sock)) === false) {
            
            return false;
        }
        
        return trim($resp);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function stmpResultCode($sock, $cmd)
    {
        if (($r = self::smtpSend($sock, $cmd)) === false) {
            
            return false;
        }
        
        return intval($r[0]);
    }
    
    
    /**
     * Връща TRUE ако домейна фигурира в списъка с домейни на които пощенските
     * сървъри винаги отговарят с ОК на запитване за потребител.
     */
    public static function isInAlwaysOK($domain)
    {
        $domainsOK = array(
            'yahoo.com',
            'yahoo.co.uk',
            'mail.ru',
            'yahoo.de',
            'yahoo.es',
            'yahoo.fr',
            'yahoo.it',
            'rambler.ru',
            'yahoo.ca',
            'yahoo.gr',
            'escom.bg',
            'aol.com',
            'seznam.cz',
            'inbox.ru',
            'list.ru',
            'excite.com',
            'mbox.bol.bg',
            'bk.ru',
            'tu-sofia.bg',
            'rocketmail.com',
            'netissat.bg',
            'icqmail.com',
            'contact.bg',
            'postbank.bg',
            'unacs.bg',
            'starazagora.net',
            'datacom.bg',
            'internet-bg.net',
            'walla.com',
            'ret.download.bg',
            'gmail.bg',
            'almus.net',
            'mobiltel.bg',
            'ymail.com',
            'aubg.bg',
            't-online.de',
            'online.bg',
            'bol.bg',
            'econ.bg',
            'nbu.bg',
            'icon.bg',
            'yahoo.com.au',
            'inbox.com',
            'nat.bg',
            'btinternet.com',
            'bsbg.net',
            'netscape.net',
            'mb.bia-bg.com',
            'bulinfo.net',
            'gorna.net',
            'digicom.bg',
            'yahoo.com.tr',
            'edasat.com',
            'ru.acad.bg',
            'el-soft.com',
            'optisprint.net',
            'softhome.net',
            'mail.bol.bg',
            'yahoo.com.br',
            'haskovo.com',
            'nsi.bg',
            'ukmz.perm.ru',
            'dzi.bg',
            'op.pl',
            'yahoo.ie',
            'is-bg.net',
            'aidabg.net',
            'cez.bg',
            'adv.bg',
            'students.nbu.bg',
            'avc.bg',
            'sz.inetg.bg',
            'bas.bg',
            'sellinet.net',
            'bnbank.org',
            'mail.gr',
            'gawab.com',
            'cytanet.com.cy',
            'aim.com',
            'parvomai.escom.bg',
            'variant6.bg',
            'del.bg',
            'nug.bg',
            'vali.bg',
            'asenovgrad.net',
            'rogers.com',
            'ims.bas.bg',
            'dskbank.bg',
            'm-real.net',
            'aseur.com',
            'slidellcpa.com',
            'cybcom.net',
            'unicreditgroup.bg',
            'aster.net',
            'aatlas.com.mx',
            'telecoms.bg',
            'email.cz',
            'ep-bags.com',
            'bgb.bg',
            'chem.uni-sofia.bg',
            'schulte-schmale.de',
            'mgu.bg',
            'unibg.org',
            'easy-lan.net',
            'boxbg.com',
            'tradel.net',
            'mee.government.bg',
            'unitednet.bg',
            'petrol.bg',
            'nadlanu.com',
            'unicreditbulbank.bg',
            'cumerio.com',
            'asarel.com',
            'applet-bg.com',
            'go-link.net',
            'walla.co.il',
            'yahoo.co.in',
            'pismo.bg',
            'aria-bg.net',
            'poczta.onet.pl',
            'minfin.bg',
            'tlan.org',
            'mail.net.mk',
            'powernet.bg',
            'bgcell.net',
            'yahoo.no',
            'yahoo.se',
            'btv.bg',
            'beverlybay.com',
            '202.117.111.219.dy.bbexcite.jp',
            'yahoo.com.sg',
            'pc-link.net',
            'bnc.bg',
            'mvr.bg',
            'firstnationalbanks.com',
            'balkanstar.com',
            'zgb.bg',
            'unicoms.net',
            'solo.bg',
            'moew.government.bg',
            'lirex.bg',
            'paraflow.bg',
            'online.de',
            'enemona.com',
            'icpusa.com',
            'mlsp.government.bg',
            'kcm.bg',
            'allianz.bg',
            'kkelectronics.com',
            'kammarton.com',
            'nek.bg',
            'sofia.bg',
            'kazanlak.com',
            'stsbg.com',
            'evn.bg',
            'net.hr',
            'ubb.bg',
            'dmail.com',
            'yahoo.co.nz',
            'procreditbank.bg',
            'mon.bg',
            'bia-bg.com',
            'albena.bg',
            'elnics.com',
            'linkos.bg',
            'npp.bg',
            'openbg.com',
            'vtu.bg',
            'eminem.com',
            'bestnewyear.ru',
            'veltrade.net',
            'fair.bg',
            'campus-bg.com',
            'in.com',
            'home.nl',
            'david.bg',
            'bulatsa.com',
            'haskovo.spnet.net',
            'ellatzite-med.com',
            'live.de',
            'img-bg.net',
            'eta.bg',
            'ruvex.bg',
            'bsnow.net',
            'bulauto.com',
            'unicreditleasing.bg',
            'yahoo.com.mx',
            'hq.office1.bg',
            'sliven.bg',
            'yahoo.dk',
            'plovdiv.bg',
            'bcci.bg',
            'eon-bulgaria.com',
            'alex-k.de',
            'stomana.bg',
            'vp.pl',
            'live.be',
            'sigma-bg.com',
            'agropolychim.bg',
            'essexproperties.com',
            'ficosota.bg',
            'government.bg',
            'hebros.bg',
            'hol.gr',
            'cnsys.bg',
            'itp.bg',
            'me3power.com',
            'crc.bg',
            'zamunda.net',
            'eircom.net',
            'riskeng.bg'
        );
        
        if (in_array($domain, $domainsOK)) {
            
            return true;
        }
    }
    
    
    /**
     * Връща TRUE ако домейна фигурира в списъка с домейни от които никога не се е логвал потребител.
     */
    public static function isInNeverLogged($domain)
    {
        $domainsNeverLogged = array(
            'gmail.bg',
            'bg.com',
            'yahoo.bg',
            'internet-bg.net',
            'ret.download.bg',
            'gmai.com',
            'gswpaper.com',
            'yhoo.com',
            'ukmz.perm.ru',
            'adv.bg',
            'avc.bg',
            'yaho.com',
            'slidellcpa.com',
            'aatlas.com.mx',
            '2rock.de',
            'bgb.bg',
            'gmal.com',
            'schulte-schmale.de',
            'cellularpartners.com',
            '4651.gymmo.shacknet.nu',
            'aawra.org',
            'e-mail.bg',
            'resbuild.co.uk',
            'yahho.com',
            'beverlybay.com',
            '202.117.111.219.dy.bbexcite.jp',
            'firstnationalbanks.com',
            'massagemasters.com',
            'thepinnaclegroup.com',
            'icpusa.com'
        );
        
        if (in_array($domain, $domainsNeverLogged)) {
            
            return true;
        }
        
        return false;
    }

    /**
     * Prowe
     */
    static function checkEmailServer($domain)
    {
        $mx_records = array();
        getmxrr($domain, $mx_records);
       
        if (empty($mx_records)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверява на даден домейн дали има конфигурирани SPF и DMARC
     */
    static function checkSpfDmarc($domain)
    {
        $txtRecords = dns_get_record("$domain", DNS_TXT);
        $hasDmarc = false;
        $hasSpf = false;
        foreach ($txtRecords as $record) {
            if (stripos($record['txt'], "v=spf1") !== false) {
                $hasSpf = true;
            }
        }

        $txtRecords = dns_get_record("_dmarc.{$domain}", DNS_TXT);
 
        foreach ($txtRecords as $record) {  
            if (stripos($record['txt'], "v=DMARC1") !== false) {  
                $hasDmarc = true;
            }
        }
 
        return array($hasSpf, $hasDmarc);
    }
    
    /**
     * Проверява дали да наден домейн има уеб-сайт
     */
    static function checkWebsite($domain)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://$domain");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return true;
        }

        return false;
    }
    
    /**
     * Проверява сертификата за даден домейн
     */
    function checkSslCertificate($domain) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$domain");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, true);
        curl_setopt($ch, CURLOPT_CERTINFO, 1);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ssl_info = curl_getinfo($ch, CURLINFO_CERTINFO);
        $sslVerify = curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT);


        if ($httpCode == 200 && $sslVerify === 0) {
            return true;
        }

        return false;
    }

      
    /**
     * Проверява дали има конфигуриран сайт, сертификат и имейл услуга за даден домейн
     */
    function chackDomain($domain)
    {
        $result = array(
                'email_server' => self::checkEmailServer($domain),
                'spf_dmarc' => self::checkSpfDmarc($domain),
                'website' => self::checkWebsite($domain),
                'ssl_certificate' => self::checkSslCertificate($domain),
                'ssl_val_days' => self::getCertValidity($domain),
            );

        return $result;    
    }
}
