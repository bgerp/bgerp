<?php


/**
 * Клас 'drdata_IpToHosts'
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_IpToHosts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'IP-към-Host';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, debug';
    

    /**
     * Никой не може да добавя и променя записите
     */
    public $canWrite = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('ip', 'varchar(15)', 'mandatory,caption=IP');
        $this->FLD('host', 'varchar(64)', 'mandatory,caption=Host');
        $this->FLD('createdOn', 'datetime', 'mandatory,caption=Създаване');

        $this->setDbunique('ip');
    }


    /**
     * Връща хоста за посоченото IP
     */
    public static function getHostByIp($ip)
    {
        $rec = self::fetch(array("#ip = '[#1#]'", $ip));

        if(!$rec) {
            $rec = new stdClass();
            $rec->ip = $ip;
            $rec->createdOn = dt::now();
            $hostName = @gethostbyaddr($ip);
            if(!$hostName) {
                $hostName = $ip;
            }
            if($hostName != $ip) {
                $domainArr =  array_slice(explode('.', ($hostName)), -3, 3);
                if(count($domainArr) == 3 && preg_match("/[0-9]{1,3}[^0-9]+[0-9]{1,3}[^0-9]+[0-9]{1,3}[^0-9]+[0-9]{1,3}/", $domainArr[0]) ||
                    strlen($domainArr[0]) > 12 && strlen($domainArr[1]) > 3) {
                    unset($domainArr[0]);
                }
                $rec->host = implode('.', $domainArr);
            }

            self::save($rec, null, 'IGNORE');
        }

        return $rec->host;
    }

}
