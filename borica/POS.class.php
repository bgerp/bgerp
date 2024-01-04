<?php


/**
 *
 *
 * @category  bgerp
 * @package   borica
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borica_POS extends peripheral_DeviceDriver
{


    /**ю
     * @var string
     */
    public $interfaces = 'borica_intf_POS';


    /**
     * @var string
     */
    public $title = 'ПОС на Борика';
    
    
    /**
     *
     */
    public $loadList = 'peripheral_DeviceWebPlg';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('comPort', 'varchar(8)', 'caption=Настройки за връзка на POS с PC->Ком порт,mandatory');
        $fieldset->FLD('protocol', 'enum(http,https,tcp)', 'caption=Настройки за връзка с POS->Протокол,mandatory');
        $fieldset->FLD('hostName', 'varchar', 'caption=Настройки за връзка с POS->Хост,mandatory');
        $fieldset->FLD('port', 'int', 'caption=Настройки за връзка с POS->Порт,mandatory');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }


    /** Изпраща сумата към POS
     *
     * @param stdClass $pRec
     * @param double $amount
     * @param string|null $port
     *
     * @return null|string
     */
    public function sendAmount($pRec, $amount, $port = null)
    {
        expect(is_numeric($amount));

        expect(stripos($amount, '.'));

        $pRec = peripheral_Devices::fetchRec($pRec);
        if (!isset($port)) {
            $port = $pRec->comPort;
        }
        $data = new stdClass();
        $data->port = $port;
        $data->amount = $amount;
        $data = base64_encode(serialize($data));
        $url = $pRec->protocol . '://' . $pRec->hostName . ':' . $pRec->port . '/?DATA=' . $data;

        $ctx = stream_context_create(array('http' => array('timeout' => 120)));

        $res = @file_get_contents($url, false, $ctx);

        return $res;
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param peripheral_DeviceDriver    $Driver
     * @param peripheral_Devices          $Embedder
     * @param stdClass                    $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $data->form->setDefault('comPort', 'COM1');
        $data->form->setDefault('protocol', 'http');
        $data->form->setDefault('hostName', 'localhost');
        $data->form->setDefault('port', '8081');
    }
}
