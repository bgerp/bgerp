<?php

defIfNot("SENDER_HOST", "colocation.download.bg");


/**
 *  @todo Чака за документация...
 */
defIfNot("SENDER_EMAIL", "team@extrapack.com");


/**
 * Клас 'drdata_Emails' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    drdata
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class drdata_Emails extends core_BaseClass
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $caller;
    
    
    /**
     * Изглежда ли стринга като валиден имейл или не.
     */
    function isWrongEmail($email)
    {
        $regExp = "/^((\\\"[^\\\"\\f\\n\\r\\t\\b]+\\\")|([A-Za-z0-9_][A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*(\\.[A-Za-z0-9_\\!\\#\\$\\%\\&\\'\\*\\+\\-\\~\\/\\^\\`\\|\\{\\}]*)*))@((\\[(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))\\])|(((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\\.((25[0-5])|(2[0-4][0-9])|([0-1]?[0-9]?[0-9])))|((([A-Za-z0-9])(([A-Za-z0-9\\-])*([A-Za-z0-9]))?(\\.(?=[A-Za-z0-9\\-]))?)+[A-Za-z]+))$/D";
        $regExp = '/^[_A-z0-9-]+((\.|\+|\/)[_A-z0-9-]+)*@[A-z0-9-]+(\.[A-z0-9-]+)*(\.[A-z]{2,4})$/';
        
        if (!preg_match($regExp, $email)) {
            return "Невалиден имейл";
        }
    }
    
    
    /**
     * Нормализиране на стоността
     */
    function normalize($email)
    {
        return trim(strtolower($email));
    }
    
    
    /**
     * Основна функция на класа
     **/
    function validate($email, &$result)
    {
        $email = $this->normalize($email);
        
        $result['value'] = $email;
        
        if (preg_match('/^www\./i', $email)) {
            $result['warning'] = "Наистина ли имейла започва с <B>www.</B> ?";
        }
        
        // Ако визуалната проверка не е вярна връщаме грешката
        if ($result['error'] = $this->isWrongEmail($email)) {
            
            return;
        }
        
        // Проверка на MX записа на домейна
        list($user, $domain) = split('@', $email);
        
        if (($mxhosts = $this->mxRecordsValidate($domain)) === FALSE) {
            $result['error'] = "Сгрешен домейн|* {$user}@<b>{$domain}</b>";
            
            return;
        }
        
        if ($this->isInNeverLogged($domain)) {
            $result['warning'] = "Възможен е проблем със сървъра|* <b>{$domain}</b>";
        }
        
        if ($this->isInAlwaysOK($domain)) {
            
            return;
        }
        
        if (count($mxhosts)) {
            $notOpen = 0;
            $timeOutsCnt = 0;
            
            for ($i = 0; $i < count($mxhosts); $i++) {
                $sock = @fsockopen($mxhosts[$i], 25, $errno, $errstr, 7);
                
                if (is_resource($sock)) { // Проверява се последният MX хост и ако не може да се свърже с него на 25 порт добавя предупреждение
                    
                    stream_set_timeout($sock, 7); // 7 секунди таймаут
                    if ($this->stmpResultCode($sock, "") == 2 && $this->stmpResultCode($sock, "HELO " . SENDER_HOST) == 2 && $this->stmpResultCode($sock, "MAIL FROM: <" . SENDER_EMAIL . ">") == 2) {
                        $code = $this->stmpResultCode($sock, "RCPT TO: <{$email}>");
                        
                        switch ($code) {
                            case 2: // Потребителят съществува - всичко е ОК
                                $this->smtpSend($sock, "QUIT");
                                fclose($sock);
                                
                                return;
                            case 4: // Потребителя не съществува или има временен проблем
                                if (!$code4) {
                                    // $result['warning'] = "С имейла| *<b>{$email}</b> |е възможен проблем";
                                }
                                $code4 = TRUE;
                                break;
                            case 5: // Поребителят не съществува - връща грешка
                                $user = substr($email, 0, strpos($email, '@'));
                                $result['error'] = "Липсваща кутия |*<b>{$user}</b> |на сървъра|* <b>{$domain}</b>";
                                $this->smtpSend($sock, "QUIT");
                                fclose($sock);
                                
                                return;
                            default: // TimeOut
                            $timeOutsCnt++;
                            
                            if ($timeOutsCnt >= 1) {
                                
                                return;
                            }
                        }
                    }
                } else {
                    $notOpen++;
                }
            }
            
            if ($notOpen == count($mxhosts)) {
                $result['warning'] = "Сървъра на|* '<b>{$domain}</b>' |не отговаря. Проверете имейла!";
            } else {
                // До тук се стига само ако всички MX записи връщат 4
                if (is_resource($sock)) {
                    $this->smtpSend($sock, "QUIT");
                    fclose($sock);
                }
            }
        }
    }
    
    
    /**
     * Връща масив с MX записите на домейна, ако няма такива връща FALSE
     **/
    function mxRecordsValidate($domain)
    {
        if ($this->getmxrr($domain, $mxhosts, $mx_weight)) {
            if ($mxhosts === array(0 => '')) {
                return FALSE;
            }
            
            return $mxhosts;
        }
        
        return FALSE;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function smtpSend($sock, $cmd)
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
     *  @todo Чака за документация...
     */
    function stmpResultCode($sock, $cmd)
    {
        if (($r = $this->smtpSend($sock, $cmd)) === FALSE) {
            
            return FALSE;
        }
        
        return intval($r{0});
    }
    
    
    /**
     *
     * Връща TRUE ако домейна фигурира в списъка с домейни на които пощенските
     * сървъри винаги отговарят с ОК на запитване за потребител.
     *
     */
    function isInAlwaysOK($domain)
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
     *
     * Връща TRUE ако домейна фигурира в списъка с домейни от които никога не се е логвал потребител.
     *
     */
    function isInNeverLogged($domain)
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
    
    
    /**
     *  @todo Чака за документация...
     */
    function winGetmxrr($host, &$mx, $weight)
    {
        $OS = cls::get('core_OS');
        $res = implode("\n", $OS->exec( 'nslookup -type=mx ' . escapeshellarg($host) . ' 4.2.2.3', 'getOutput') );
        $res = explode("\n", strstr($res, $host));
        
        if (!isset($res[1])) {
            $mx[] = FALSE;
            
            return FALSE;
        }
        
        foreach ($res as $v) {
            $w = explode(' ', $v);
            $mx[] = $w[7];
            
            if (isset($weight)) {
                $weight[] = $w[3]{0};
            }
        }
        unset($mx[count($mx) - 1]);
        unset($weight[count($weight) - 1]);
        
        return TRUE;
    }
    
    
    /**
     * Define
     */
    function getmxrr($hostname, &$mxhosts, $mxweight)
    {
        cls::load('core_Os');
        
        if (core_Os::isWindows()) {
            return $this->winGetmxrr($hostname, $mxhosts, $mxweight);
        } else {
            return getmxrr($hostname, $mxhosts, $mxweight);
        }
    }
}