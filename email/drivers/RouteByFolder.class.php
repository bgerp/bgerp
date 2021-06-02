<?php


/**
 * Драйвер за рутиране по папка
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Рутиране по папка
 */
class email_drivers_RouteByFolder extends core_BaseClass
{
    
    
    /**
     * Инрерфейси
     */
    public $interfaces = 'email_ServiceRulesIntf';


    /**
     * Добавяне на полета към наследниците
     */
    public static function addFields(&$mvc)
    {
        $mvc->FLD('folderId', 'key2(mvc=doc_Folders, allowEmpty, restrictViewAccess=no)', 'caption=Папка, before=note, class=w100 clearSelect');
    }


    /**
     *
     *
     * @param email_Mime  $mime
     * @param stdClass  $serviceRec
     *
     * @return string|null
     *
     * @see email_ServiceRulesIntf
     */
    public function process($mime, $serviceRec)
    {
        $resArr = array();

        $resArr['preroute']['folderId'] = $serviceRec->folderId;

        return $resArr;
    }
}
