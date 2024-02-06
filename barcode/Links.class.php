<?php


/**
 * Показване на връзки при търсене
 *
 *
 * @category  bgerp
 * @package   barcode
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class barcode_Links extends core_BaseClass
{
    /**
     * Заглавие
     */
    public $title = 'Линкове';


    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';


    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     * ->title - заглавие на резултата
     * ->url - линк за хипервръзка
     * ->comment - html допълнителна информация
     * ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();

        if (core_Url::isValidUrl2($str)) {

            $resArr[] = (object)array(
                'title' => 'Отвори този линк',
                'comment' => ht::createLink($str, $str, false, array('target' => '_blank')),
                'priority' => 100
            );
        }

        return $resArr;
    }
}
