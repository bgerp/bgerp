<?php



/**
 * Клас 'drdata_IpToCountry' -
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
class drdata_IpToCountry extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Държава-към-IP';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('minIp', 'int', 'unsigned,mandatory,caption=IP->минимално');
        $this->FLD('maxIp', 'int', 'unsigned,mandatory,caption=Ip->максимално');
        $this->FLD('country2', 'varchar(2)', 'mandatory,caption=Код на държава');
        
        $this->load('drdata_Countries,drdata_Wrapper');
        
        $this->dbEngine = 'MYISAM';
    }
    
    
    /**
     * Изпълнява се след установяване на модела
     * Импортира предварително зададени данни
     */
    public static function on_AfterSetupMVC(&$mvc, &$res)
    {
        // Пътя до файла с данни
        $file = 'drdata/data/IpToCountry.csv';
        
        // Мапваме полетата от CSV файла
        $fields = array(
            0 => 'minIp',
            1 => 'maxIp',
            4 => 'country2'
        );

        // Импортираме данните
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);

        $res .= $cntObj->html;
    }
    
    
    /**
     * Връща двубуквения код на страната от която е това $ip
     * Ако не е посочено ip, взема ip-то от заявката на клиента
     */
    public static function get($ip = null)
    {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        $cRec = drdata_IpToCountry::fetch("#minIp <= INET_ATON('{$ip}') AND #maxIp >= INET_ATON('{$ip}')");
        
        return $cRec->country2;
    }
}
