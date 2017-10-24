<?php


/**
 * Клас 'drdata_Emails' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Emails extends core_BaseClass
{
    
    
    /**
     * @todo Чака за документация...
     */
    var $caller;
    
    
    /**
     * Изглежда ли стринга като валиден имейл или не.
     */
    static function isWrongEmail($email)
    {
        $regExp = "/^((\\\"[^\\\"\\f\\n\\r\\t\\b]+\\\")|([A-Za-z0-9_][A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*(\\.[A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*)*))@((\\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9])(([A-Za-z0-9\\-])*([A-Za-z0-9]))?(\\.(?=[A-Za-z0-9\\-]))?)+[A-Za-z]+))$/D";
        $regExp = '/^[_A-z0-9-]+((\.|\+|\/)[_A-z0-9-]+)*@[A-z0-9-]+(\.[A-z0-9-]+)*(\.[A-z]{2,4})$/';
        
        if (!preg_match($regExp, $email)) {
            return "Невалиден имейл";
        }
    }
    
    
    /**
     * Нормализиране на стойността
     */
    static function normalize($email)
    {
        return trim(strtolower($email));
    }
    
    
    /**
     * Основна функция на класа
     * /
     */
    static function validate($email, &$result)
    {
    	$conf = core_Packs::getConfig('drdata');
    	
        $email = self::normalize($email);
        
        $result['value'] = $email;
        
        if (preg_match('/^www\./i', $email)) {
            $result['warning'] = "Наистина ли имейла започва с|* <B>www.</B> ?";
        }
        
        // Ако визуалната проверка не е вярна връщаме грешката
        if ($result['error'] = self::isWrongEmail($email)) {
            
            return;
        }
        
        // Проверка на MX записа на домейна
        list($user, $domain) = explode('@', $email);
     
        if (($mxhosts = self::mxAndARecordsValidate($domain)) === FALSE) {
            $result['warning'] = "Възможен е проблем с домейна|* {$user}@<b>{$domain}</b>";
         
            return;
        }
        
        if (self::isInNeverLogged($domain)) {
            $result['warning'] = "Възможен е проблем със сървъра|* <b>{$domain}</b>";
        }
        
        if (self::isInAlwaysOK($domain)) {
            
            return;
        }
        
        // Заобикаляне на останалите проверки
        return;
    }
    
    
    /**
     * Връща масив с MX и A записите на домейна, ако няма такива връща FALSE
     * 
     * @param string $domain
     */
    public static function mxAndARecordsValidate($domain)
    {
        $hosts = @dns_get_record($domain, DNS_A + DNS_MX, $audthns, $addtl);
  
        if (!$hosts) {
            
            return FALSE;
        }
        
        return $hosts;
    }
    
    
    /**
     * Връща масив с MX записите на домейна, ако няма такива връща FALSE
     */
    static function mxRecordsValidate($domain)
    {
        if (getmxrr($domain, $mxhosts, $mx_weight)) {
            if ($mxhosts === array(0 => '')) {
                return FALSE;
            }
            
            return $mxhosts;
        }
        
        return FALSE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function smtpSend($sock, $cmd)
    {
        if ($cmd) {
            if (@fwrite($sock, $cmd . "\r\n") === FALSE) {
                
                return FALSE;
            }
        }
        
        if (($resp = @fgets($sock)) === FALSE) {
            
            return FALSE;
        }
        
        return trim($resp);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function stmpResultCode($sock, $cmd)
    {
        if (($r = self::smtpSend($sock, $cmd)) === FALSE) {
            
            return FALSE;
        }
        
        return intval($r{0});
    }
    
    
    /**
     * Връща TRUE ако домейна фигурира в списъка с домейни на които пощенските
     * сървъри винаги отговарят с ОК на запитване за потребител.
     */
    static function isInAlwaysOK($domain)
    {
        $domainsOK = array(
            "yahoo.com",
            "yahoo.co.uk",
            "mail.ru",
            "yahoo.de",
            "yahoo.es",
            "yahoo.fr",
            "yahoo.it",
            "rambler.ru",
            "yahoo.ca",
            "yahoo.gr",
            "escom.bg",
            "aol.com",
            "seznam.cz",
            "inbox.ru",
            "list.ru",
            "excite.com",
            "mbox.bol.bg",
            "bk.ru",
            "tu-sofia.bg",
            "rocketmail.com",
            "netissat.bg",
            "icqmail.com",
            "contact.bg",
            "postbank.bg",
            "unacs.bg",
            "starazagora.net",
            "datacom.bg",
            "internet-bg.net",
            "walla.com",
            "ret.download.bg",
            "gmail.bg",
            "almus.net",
            "mobiltel.bg",
            "ymail.com",
            "aubg.bg",
            "t-online.de",
            "online.bg",
            "bol.bg",
            "econ.bg",
            "nbu.bg",
            "icon.bg",
            "yahoo.com.au",
            "inbox.com",
            "nat.bg",
            "btinternet.com",
            "bsbg.net",
            "netscape.net",
            "mb.bia-bg.com",
            "bulinfo.net",
            "gorna.net",
            "digicom.bg",
            "yahoo.com.tr",
            "edasat.com",
            "ru.acad.bg",
            "el-soft.com",
            "optisprint.net",
            "softhome.net",
            "mail.bol.bg",
            "yahoo.com.br",
            "haskovo.com",
            "nsi.bg",
            "ukmz.perm.ru",
            "dzi.bg",
            "op.pl",
            "yahoo.ie",
            "is-bg.net",
            "aidabg.net",
            "cez.bg",
            "adv.bg",
            "students.nbu.bg",
            "avc.bg",
            "sz.inetg.bg",
            "bas.bg",
            "sellinet.net",
            "bnbank.org",
            "mail.gr",
            "gawab.com",
            "cytanet.com.cy",
            "aim.com",
            "parvomai.escom.bg",
            "variant6.bg",
            "del.bg",
            "nug.bg",
            "vali.bg",
            "asenovgrad.net",
            "rogers.com",
            "ims.bas.bg",
            "dskbank.bg",
            "m-real.net",
            "aseur.com",
            "slidellcpa.com",
            "cybcom.net",
            "unicreditgroup.bg",
            "aster.net",
            "aatlas.com.mx",
            "telecoms.bg",
            "email.cz",
            "ep-bags.com",
            "bgb.bg",
            "chem.uni-sofia.bg",
            "schulte-schmale.de",
            "mgu.bg",
            "unibg.org",
            "easy-lan.net",
            "boxbg.com",
            "tradel.net",
            "mee.government.bg",
            "unitednet.bg",
            "petrol.bg",
            "nadlanu.com",
            "unicreditbulbank.bg",
            "cumerio.com",
            "asarel.com",
            "applet-bg.com",
            "go-link.net",
            "walla.co.il",
            "yahoo.co.in",
            "pismo.bg",
            "aria-bg.net",
            "poczta.onet.pl",
            "minfin.bg",
            "tlan.org",
            "mail.net.mk",
            "powernet.bg",
            "bgcell.net",
            "yahoo.no",
            "yahoo.se",
            "btv.bg",
            "beverlybay.com",
            "202.117.111.219.dy.bbexcite.jp",
            "yahoo.com.sg",
            "pc-link.net",
            "bnc.bg",
            "mvr.bg",
            "firstnationalbanks.com",
            "balkanstar.com",
            "zgb.bg",
            "unicoms.net",
            "solo.bg",
            "moew.government.bg",
            "lirex.bg",
            "paraflow.bg",
            "online.de",
            "enemona.com",
            "icpusa.com",
            "mlsp.government.bg",
            "kcm.bg",
            "allianz.bg",
            "kkelectronics.com",
            "kammarton.com",
            "nek.bg",
            "sofia.bg",
            "kazanlak.com",
            "stsbg.com",
            "evn.bg",
            "net.hr",
            "ubb.bg",
            "dmail.com",
            "yahoo.co.nz",
            "procreditbank.bg",
            "mon.bg",
            "bia-bg.com",
            "albena.bg",
            "elnics.com",
            "linkos.bg",
            "npp.bg",
            "openbg.com",
            "vtu.bg",
            "eminem.com",
            "bestnewyear.ru",
            "veltrade.net",
            "fair.bg",
            "campus-bg.com",
            "in.com",
            "home.nl",
            "david.bg",
            "bulatsa.com",
            "haskovo.spnet.net",
            "ellatzite-med.com",
            "live.de",
            "img-bg.net",
            "eta.bg",
            "ruvex.bg",
            "bsnow.net",
            "bulauto.com",
            "unicreditleasing.bg",
            "yahoo.com.mx",
            "hq.office1.bg",
            "sliven.bg",
            "yahoo.dk",
            "plovdiv.bg",
            "bcci.bg",
            "eon-bulgaria.com",
            "alex-k.de",
            "stomana.bg",
            "vp.pl",
            "live.be",
            "sigma-bg.com",
            "agropolychim.bg",
            "essexproperties.com",
            "ficosota.bg",
            "government.bg",
            "hebros.bg",
            "hol.gr",
            "cnsys.bg",
            "itp.bg",
            "me3power.com",
            "crc.bg",
            "zamunda.net",
            "eircom.net",
            "riskeng.bg"
        );
        
        if (in_array($domain, $domainsOK)) {
            return TRUE;
        }
    }
    
    
    /**
     * Връща TRUE ако домейна фигурира в списъка с домейни от които никога не се е логвал потребител.
     */
    static function isInNeverLogged($domain)
    {
        $domainsNeverLogged = array(
            "gmail.bg",
            "bg.com",
            "yahoo.bg",
            "internet-bg.net",
            "ret.download.bg",
            "gmai.com",
            "gswpaper.com",
            "yhoo.com",
            "ukmz.perm.ru",
            "adv.bg",
            "avc.bg",
            "yaho.com",
            "slidellcpa.com",
            "aatlas.com.mx",
            "2rock.de",
            "bgb.bg",
            "gmal.com",
            "schulte-schmale.de",
            "cellularpartners.com",
            "4651.gymmo.shacknet.nu",
            "aawra.org",
            "e-mail.bg",
            "resbuild.co.uk",
            "yahho.com",
            "beverlybay.com",
            "202.117.111.219.dy.bbexcite.jp",
            "firstnationalbanks.com",
            "massagemasters.com",
            "thepinnaclegroup.com",
            "icpusa.com"
        );
        
        if (in_array($domain, $domainsNeverLogged)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
}
