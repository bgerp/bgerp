<?php


/**
 *
 *
 * @category  bgerp
 * @package   tremol
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tremol_FiscPrinterDriver2 extends core_Mvc
{
    public $interfaces = 'peripheral_DeviceIntf, peripheral_FiscPrinter';
    
    public $title = 'FP Tremol';
    
    protected $canCashReceived = 'admin, peripheral';
    
    protected $canCashPaidOut = 'admin, peripheral';
    
    protected $canMakeReport = 'admin, peripheral';
    
    protected $rcpNumPattern = '/^[a-z0-9]{8}-[a-z0-9]{4}-[0-9]{7}$/i';
    
    
    /**
     * Максимална дължина за касовите апарати
     * @var integer
     */
    protected $crLen = 32;
    
    
    /**
     * Максимална дължина за фискалните принтери
     * @var integer
     */
    protected $fpLen = 48;
    
    
    /**
     * Максимална дължина за фискалните принтери - за име на артикул
     * @var integer
     */
    protected $fpPluNameLen = 34;
    
    
    /**
     * "Маргин" при печатане на текст - по един # в началото и в края
     * @var integer
     */
    protected $mLen = 2;
    
    
    /**
     * Дефолтни кодове на начините на плащане
     */
    const DEFAULT_PAYMENT_MAP = array('Брой'       => 0,
                                      'Чек'        => 1,
                                      'Талон'      => 2,
                                      'В.Талон'    => 3,
                                      'Амбалаж'    => 4,
                                      'Обслужване' => 5,
                                      'Повреди'    => 6,
                                      'Карта'      => 7,
                                      'Банка'      => 8);
    
    
    /**
     * Дефолтни кодове на начините на плащане
     */
    const DEFAULT_STORNO_REASONS_MAP = array('Операторска грешка' => 0,
                                             'Връщане/Рекламация' => 1,
                                             'Данъчно облекчение' => 2,);
    
    
    /**
     * Дефолтни кодове за ДДС групите
     */
    const DEFAULT_VAT_GROUPS_MAP = array('A' => 0,
                                         'B' => 1,
                                         'V' => 2,
                                         'G' => 3,);
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('serverIp', 'ip', 'caption=Настройки за връзка със ZFPLAB сървър->IP адрес, mandatory');
        $fieldset->FLD('serverTcpPort', 'int', 'caption=Настройки за връзка със ZFPLAB сървър->TCP порт, mandatory');
        
        $fieldset->FLD('driverVersion', 'enum(19.06.13,19.05.17,19.03.22,19.02.20)', 'caption=Настройки на ФУ->Версия, mandatory, notNull');
        $fieldset->FLD('fpType', 'enum(cashRegister=Касов апарат, fiscalPrinter=Фискален принтер)', 'caption=Настройки на ФУ->Тип, mandatory, notNull');
        $fieldset->FLD('serialNumber', 'varchar(8)', 'caption=Настройки на ФУ->Сериен номер');
        
        $fieldset->FLD('type', 'enum(tcp=TCP връзка, serial=Сериен порт)', 'caption=Настройки за връзка с ФУ->Връзка, mandatory, notNull, removeAndRefreshForm=tcpIp|tcpPort|tcpPass|serialPort|serialSpeed');
        $fieldset->FLD('tcpIp', 'ip', 'caption=Настройки за връзка с ФУ->IP адрес, mandatory');
        $fieldset->FLD('tcpPort', 'int', 'caption=Настройки за връзка с ФУ->Порт, mandatory');
        $fieldset->FLD('tcpPass', 'password', 'caption=Настройки за връзка с ФУ->Парола, mandatory');
        
        $fieldset->FLD('serialPort', 'varchar', 'caption=Настройки за връзка с ФУ->Порт, mandatory');
        $fieldset->FLD('serialSpeed', 'int', 'caption=Настройки за връзка с ФУ->Скорост, mandatory');
        
        if ($fieldset instanceof core_Form) {
            $fieldset->input('type');
            
            if ($fieldset->rec->type != 'serial') {
                $fieldset->setField('serialPort', 'input=none');
                $fieldset->setField('serialSpeed', 'input=none');
            } else {
                $fieldset->setField('tcpIp', 'input=none');
                $fieldset->setField('tcpPort', 'input=none');
                $fieldset->setField('tcpPass', 'input=none');
            }
        }
        
        // Добавяне на полета за поддържани валути и кодове на методите на плащане
        $fieldset->FLD('suppertedCurrencies', 'keylist(mvc=currency_Currencies,select=code,where=#code !\\= \\\'BGN\\\')', 'caption=Настройки на апарата->Валути');
        $fieldset->FLD('vatGroups', 'table(columns=groupId|code,captions=Група|Код)', 'caption=Настройки на апарата->ДДС групи');
        $fieldset->setFieldTypeParams('vatGroups', array('groupId_opt' => array('' => '') + cls::get('acc_VatGroups')->makeArray4Select('title')));
        
        $fieldset->FLD('paymentMap', 'table(columns=paymentId|code,captions=Вид|Код)', 'caption=Настройки на апарата->Плащания');
        $fieldset->setFieldTypeParams('paymentMap', array('paymentId_opt' => array('' => '') + array('-1' => 'Брой') + cls::get('cond_Payments')->makeArray4Select('title')));
        
        // Добавяне на поле за поддържаните основания за сторниране с техните кодове
        $fieldset->FLD('reasonMap', 'table(columns=reason|code,captions=Основание|Код,batch_ro=readonly)', 'caption=Настройки на апарата->Сторно основания');
        $fieldset->setFieldTypeParams('reasonMap', array('reason_opt' => array('' => '') + arr::make(array_keys(self::DEFAULT_STORNO_REASONS_MAP), true)));
        
        $fieldset->FLD('header', 'enum(yes=Да,no=Не)', 'caption=Надпис хедър в касовата бележка->Добавяне, mandatory, notNull, removeAndRefreshForm');
        $fieldset->FLD('headerPos', 'enum(center=Центрирано,left=Ляво,right=Дясно)', 'caption=Надпис хедър в касовата бележка->Позиция, mandatory, notNull');
        $fieldset->FLD('headerText1', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 1');
        $fieldset->FLD('headerText2', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 2');
        $fieldset->FLD('headerText3', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 3');
        $fieldset->FLD('headerText4', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 4');
        $fieldset->FLD('headerText5', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 5');
        $fieldset->FLD('headerText6', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 6');
        $fieldset->FLD('headerText7', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 7');
        if ($fieldset instanceof core_Form) {
            $fieldset->input('header');
            if ($fieldset->rec->header == 'no') {
                $fieldset->setField('headerText1', 'input=none');
                $fieldset->setField('headerText2', 'input=none');
                $fieldset->setField('headerText3', 'input=none');
                $fieldset->setField('headerText4', 'input=none');
                $fieldset->setField('headerText5', 'input=none');
                $fieldset->setField('headerText6', 'input=none');
                $fieldset->setField('headerText7', 'input=none');
                $fieldset->setField('headerPos', 'input=none');
            }
        }
        
        $fieldset->FLD('footer', 'enum(yes=Да, no=Не)', 'caption=Надпис футър в касовата бележка->Добавяне, mandatory, notNull, removeAndRefreshForm');
        $fieldset->FLD('footerPos', 'enum(center=Центрирано,left=Ляво,right=Дясно)', 'caption=Надпис футър в касовата бележка->Позиция, mandatory, notNull');
        $fieldset->FLD('footerText', "varchar({$this->fpLen})", 'caption=Надпис футър в касовата бележка->Текст');
        if ($fieldset instanceof core_Form) {
            $fieldset->input('footer');
            if ($fieldset->rec->footer == 'no') {
                $fieldset->setField('footerText', 'input=none');
                $fieldset->setField('footerPos', 'input=none');
            }
        }
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
    
    
    /**
     * Връща JS функция за отпечатване на ФБ
     *
     * @param stdClass $pRec   - запис от peripheral_Devices
     * @param array    $params - масив с параметри необходими за отпечатване на ФБ
     *
     * // Параметри за отваряне на ФБ
     * OPER_NUM - номер на оператор - от 1 до 20
     * OPER_PASS - парола на оператора
     * IS_DETAILED - дали ФБ да е детайлна
     * IS_PRINT_VAT - дали да се отпечата ДДС информацията - разбивка за сумите по ДДС
     * PRINT_TYPE_STR - начин на отпечатване - stepByStep, postponed, buffered
     * RCP_NUM - уникален номер на бележката - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7}
     *
     * // products - масив с артикулите
     * PLU_NAME - име на артикула
     * PRICE - цена
     * VAT_CLASS - ДДС клас - 0-3 (А-Г)
     * QTY - количество
     * DISC_ADD_P - надбавка/отстъпка в проценти - може и с -
     * DISC_ADD_V - надбавка/отстъпка в стойнонст - може и с -
     * BEFORE_PLU_TEXT - стринг или масив от стрингове с текст, който ще се добавя преди продукта
     * AFTER_PLU_TEXT - стринг или масив от стрингове с текст, който ще се добавя след продукта
     *
     * DATE_TIME - времето за синхронизира във формат 'd-m-Y H:i:s'. Ако е false - няма да се синхронизира
     *
     * SERIAL_NUMBER - серийния номер на принтера за проверка. Ако е false - няма да се проверява. Ако има разминаване - спира процеса.
     * 
     * CHECK_AND_CANCEL_PREV_OPEN_RECEIPT - дали да се проверява и прекратява предишна активна бележка - ако не се подаде нищо, се приема за true
     *
     * SERIAL_KEEP_PORT_OPEN - дали порта да се държи отворен при серийна връзка - докато се приключи
     *
     * BEGIN_TEXT - стринг или масив от стрингове с текст, който ще се добавя в началото на бележката - преди продуктите
     * END_TEXT - стринг или масив от стрингове с текст, който ще се добавя в края на бележката - след продуктите
     *
     * // payments - масив от видовете плащания на бележката - ако не се подаде се приема в брой в лв.
     * PAYMENT_CHANGE - дали да се изчисли рестото - 0 - с ресто, 1 - без
     * PAYMENT_AMOUNT - сума на плащането
     * PAYMENT_CHANGE_TYPE - типа на рестото - 0 - ресто в брой, 1 - същото като плащането, 2 - във валута
     * PAYMENT_TYPE - типа на плащането, което може да е от 0 до 11
     * 0 - В брой лв
     * 1 - Чек
     * 2 - Талон
     * 3 - В.Талон
     * 4 - Амбалаж
     * 5 - Обслужване
     * 6 - Повреди
     * 7 - Карта
     * 8 - Банка
     * 9 - Резерв 1 - валута 1
     * 10 - Резерв 2 - валута 2
     * 11 - Резерв 3 - валута 3
     *
     * PAY_EXACT_SUM_TYPE - лесен начин за плащане на цялата сума в една валута. Параметрите са същити, като PAYMENT_TYPE
     * Може частично да се плати с един или няколко payments, а остатъка с PAY_EXACT_SUM_TYPE
     *
     * Ако няма PAY_EXACT_SUM_TYPE и payments, плащането ще е "В брой лв" (0)
     *
     * // Параметри за сторниране
     * IS_STORNO - дали се създава сторно бележка. По подобен начин на ФБ, само, че в бележката е СТОРНО
     * STORNO_REASON - типа на сторно бележката. 0 - грешка от оператор, 1 - рекламация или връщане, 2 - данъчно облекчение.
     * Само при операторска грешка не се следи за наличност в склада
     * RELATED_TO_RCP_NUM - номер на фискалния бон, който ще се сторнира
     * RELATED_TO_RCP_DATE_TIME - дата и час на фискалния бон, който ще се сторнира
     * FM_NUM - номер на фискалната памет, от която е издаден фактурата
     * RELATED_TO_URN - уникален номер на бележката, която се сторнира - [a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[0-9]{7} - подобно на RCP_NUM
     * Другите параметри са: OPER_NUM, OPER_PASS, IS_DETAILED, IS_PRINT_VAT, PRINT_TYPE_STR - като при издаване на ФБ
     * QR_CODE_DATA - резултата от ReadLastReceiptQRcodeData. Връща се в fpOnSuccess функцията - FM Number*Receipt Number*Receipt Date*Receipt Hour*Receipt Amount
     * Може да се подаде този номер и от там автоматично да се извлече FM_NUM, RELATED_TO_RCP_NUM и RELATED_TO_RCP_DATE_TIME, ако не са подадени.
     * Помощен параметър за определяне на някои стойности
     * 
     * // Параметри за кредитно известие
     * IS_CREDIT_NOTE - дали се създава кредитно известие. По подобен начин на СТОРНО, само, че в бележката е кредитно известие
     * RECIPIENT - 26 символа за получателя на фактурата
     * BUYER - 16 симвла за купувача
     * VAT_NUMBER - 13 символа за VAT номер
     * UIC - 13 символа за UIC номер на клиента
     * ADDRESS - 30 символа за адрес на клиента
     * UIC_TYPE_STR - типа на UIC номера - bulstat, EGN, FN, NRA
     * RELATED_TO_INV_NUM - 10 символа за фактурата, която се сторница
     * RELATED_TO_INV_DATE_TIME - дата и час на фактурата, която ще се сторнира - може да се попълни от QR_CODE_DATA, ако не е попълнено
     * 
     * Другите параметри са: OPER_NUM, OPER_PASS, PRINT_TYPE_STR - като при издаване на ФБ
     * Другите параметри са: STORNO_REASON, RELATED_TO_RCP_NUM, FM_NUM, RELATED_TO_URN, QR_CODE_DATA - като при издаване на СТОРНО
     * 
     * @return string
     *
     * @see peripheral_FiscPrinter
     */
    public function getJs($pRec, $params)
    {
        // Шаблона за JS
        $js = getTplFromFile('/tremol/js/fiscPrintTpl.txt');
        
        $this->addTplFile($js, $pRec->driverVersion);
        
        $this->connectToPrinter($js, $pRec, $params['SERIAL_KEEP_PORT_OPEN']);
        
        // Задаваме параметрите за отваряне на ФБ
        setIfNot($params['OPER_NUM'], 1);
        setIfNot($params['OPER_PASS'], 0);
        if ($params['IS_DETAILED']) {
            $params['IS_DETAILED'] = 'true';
        } else {
            $params['IS_DETAILED'] = 'false';
        }
        if ($params['IS_PRINT_VAT']) {
            $params['IS_PRINT_VAT'] = 'true';
        } else {
            $params['IS_PRINT_VAT'] = 'false';
        }
        setIfNot($params['PRINT_TYPE_STR'], 'buffered');
        
        if (!$params['IS_STORNO'] && !$params['IS_CREDIT_NOTE']) {
            expect($params['RCP_NUM'] && preg_match($this->rcpNumPattern, $params['RCP_NUM']));
            $js->replace(json_encode($params['RCP_NUM']), 'RCP_NUM');
            
            $js->removeBlock('OPEN_STORNO_RECEIPT_1');
            $js->removeBlock('OPEN_STORNO_RECEIPT_2');
            $js->removeBlock('OPEN_CREDIT_NOTE_1');
            $js->removeBlock('OPEN_CREDIT_NOTE_2');
        } else {
            
            // Ако ще се прави сторно или кредитно известие
            
            // Опитваме се да попълним няко от задължителните параметри
            if ($params['QR_CODE_DATA']) {
                list($fmNum, $toRcpNum, $toRcpDate, $toRcpTime) = explode('*', $params['QR_CODE_DATA']);
                
                setIfNot($params['FM_NUM'], $fmNum);
                setIfNot($params['RELATED_TO_RCP_NUM'], (int) $toRcpNum);
                
                $toRcpDateAndTime = $toRcpDate . ' ' . $toRcpTime;
                $toRcpDateAndTime = dt::mysql2verbal($toRcpDateAndTime, 'd-m-Y H:i:s', null, false, false);
                setIfNot($params['RELATED_TO_RCP_DATE_TIME'], $toRcpDateAndTime);
                
                setIfNot($params['RELATED_TO_INV_DATE_TIME'], $toRcpDateAndTime);
            }
            
            if ($params['IS_STORNO']) {
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_RCP_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_RCP_DATE_TIME']));
                
                $js->removeBlock('OPEN_CREDIT_NOTE_1');
                $js->removeBlock('OPEN_CREDIT_NOTE_2');
            } else if ($params['IS_CREDIT_NOTE']) {
                expect($params['RELATED_TO_RCP_NUM'] && $params['RELATED_TO_INV_DATE_TIME'] && $params['FM_NUM']);
                expect(dt::verbal2mysql($params['RELATED_TO_INV_DATE_TIME']));
                
                $js->removeBlock('OPEN_STORNO_RECEIPT_1');
                $js->removeBlock('OPEN_STORNO_RECEIPT_2');
            }
            
            setIfNot($params['STORNO_REASON'], 1);
            expect(($params['STORNO_REASON'] >= 0) && ($params['STORNO_REASON'] <= 2));
            expect(strlen($params['RELATED_TO_RCP_NUM']) <= 6);
            expect(strlen($params['FM_NUM']) == 8);
            
            $js->replace($params['STORNO_REASON'], 'STORNO_REASON');
            $js->replace($params['RELATED_TO_RCP_NUM'], 'RELATED_TO_RCP_NUM');
            $js->replace(json_encode($params['RELATED_TO_RCP_DATE_TIME']), 'RELATED_TO_RCP_DATE_TIME');
            $js->replace(json_encode($params['FM_NUM']), 'FM_NUM');
            
            setIfNot($params['RELATED_TO_URN'], $params['RCP_NUM'], 'null');
            $js->replace(json_encode($params['RELATED_TO_URN']), 'RELATED_TO_URN');
            
            if ($params['RELATED_TO_URN'] != 'null') {
                expect(preg_match($this->rcpNumPattern, $params['RELATED_TO_URN']));
            }
            
            $js->removeBlock('OPEN_FISC_RECEIPT_1');
            $js->removeBlock('OPEN_FISC_RECEIPT_2');
            
            if ($params['IS_CREDIT_NOTE']) {
                $js->replace(json_encode($params['RECIPIENT']), 'RECIPIENT');
                $js->replace(json_encode($params['BUYER']), 'BUYER');
                $js->replace(json_encode($params['VAT_NUMBER']), 'VAT_NUMBER');
                $js->replace(json_encode($params['UIC']), 'UIC');
                $js->replace(json_encode($params['ADDRESS']), 'ADDRESS');
                $js->replace(json_encode($params['UIC_TYPE_STR']), 'UIC_TYPE_STR');
                $js->replace(json_encode($params['RELATED_TO_INV_NUM']), 'RELATED_TO_INV_NUM');
                $js->replace(json_encode($params['RELATED_TO_INV_DATE_TIME']), 'RELATED_TO_INV_DATE_TIME');
            }
        }
        
        expect(($params['OPER_NUM'] >= 1) && ($params['OPER_NUM'] <= 20));
        expect(strlen($params['OPER_PASS']) <= 6);
        expect(($params['PRINT_TYPE_STR'] == 'stepByStep') || ($params['PRINT_TYPE_STR'] == 'postponed') || ($params['PRINT_TYPE_STR'] == 'buffered'));
        
        $js->replace($params['OPER_NUM'], 'OPER_NUM');
        $js->replace(json_encode($params['OPER_PASS']), 'OPER_PASS');
        $js->replace($params['IS_DETAILED'], 'IS_DETAILED');
        $js->replace($params['IS_PRINT_VAT'], 'IS_PRINT_VAT');
        $js->replace(json_encode($params['PRINT_TYPE_STR']), 'PRINT_TYPE_STR');
        
        $maxTextLen = ($pRec->fpType == 'fiscalPrinter') ? $this->fpLen : $this->crLen;
        $maxTextLen -= $this->mLen;
        
        // Добавяме продуктите към бележката
        foreach ($params['products'] as $pArr) {
            setIfNot($pArr['PRICE'], 0);
            setIfNot($pArr['VAT_CLASS'], 1);
            setIfNot($pArr['QTY'], 1);
            setIfNot($pArr['DISC_ADD_P'], '');
            setIfNot($pArr['DISC_ADD_V'], '');
            setIfNot($pArr['PLU_NAME'], '');
            
            expect(($pArr['VAT_CLASS'] >= 0) && ($pArr['VAT_CLASS'] <= 3));
            
            expect(is_numeric($pArr['PRICE']) && is_numeric($pArr['QTY']) && (is_numeric($pArr['DISC_ADD_P']) || $pArr['DISC_ADD_P'] == '') && (is_numeric($pArr['DISC_ADD_V']) || $pArr['DISC_ADD_V'] == ''));
            
            $fpSalePLU = $js->getBlock('fpSalePLU');
            
            $fpSalePLU->replace(json_encode($pArr['PLU_NAME']), 'PLU_NAME');
            $fpSalePLU->replace($pArr['VAT_CLASS'], 'VAT_CLASS');
            $fpSalePLU->replace(json_encode($pArr['PRICE']), 'PRICE');
            $fpSalePLU->replace($pArr['QTY'], 'QTY');
            $fpSalePLU->replace(json_encode($pArr['DISC_ADD_P']), 'DISC_ADD_P');
            $fpSalePLU->replace(json_encode($pArr['DISC_ADD_V']), 'DISC_ADD_V');
            
            if (isset($pArr['BEFORE_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['BEFORE_PLU_TEXT'], $fpSalePLU, 'BEFORE_PLU_TEXT', true, $maxTextLen);
            }
            
            if (isset($pArr['AFTER_PLU_TEXT'])) {
                $this->replaceTextArr($pArr['AFTER_PLU_TEXT'], $fpSalePLU, 'AFTER_PLU_TEXT', true, $maxTextLen);
            }
            
            $fpSalePLU->removeBlocks();
            $fpSalePLU->append2master();
        }
        
        setIfNot($params['DATE_TIME'], false);
        
        if ($params['DATE_TIME'] === true) {
            $params['DATE_TIME'] = date('d-m-Y H:i:s');
        }
        
        if ($params['DATE_TIME'] !== false) {
            expect(dt::verbal2mysql($params['DATE_TIME']));
            $js->replace(json_encode($params['DATE_TIME']), 'DATE_TIME');
        } else {
            $js->removeBlock('DATE_TIME');
        }
        
        // Проверяваме серийния номер
        if ($params['SERIAL_NUMBER'] !== false) {
            setIfNot($params['SERIAL_NUMBER'], $pRec->serialNumber);
            
            if (!$params['SERIAL_NUMBER']) {
                list($params['SERIAL_NUMBER']) = explode('-', $params['RCP_NUM'], 2);
            }
            
            expect($params['SERIAL_NUMBER'] && (strlen($params['SERIAL_NUMBER']) == 8), $pRec, $params);
            $js->replace(json_encode($params['SERIAL_NUMBER']), 'SERIAL_NUMBER');
        } else {
            $js->removeBlock('SERIAL_NUMBER');
        }
        
        // Проверява за отворена бележка и ако има я прекратява преди да започне новата
        setIfNot($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT'], true);
        if ($params['CHECK_AND_CANCEL_PREV_OPEN_RECEIPT'] === true) {
            $js->replace(' ', 'CHECK_AND_CANCEL_PREV_OPEN_RECEIPT');
        } else {
            $js->removeBlock('CHECK_AND_CANCEL_PREV_OPEN_RECEIPT');
        }
        
        if (isset($params['BEGIN_TEXT'])) {
            $this->replaceTextArr($params['BEGIN_TEXT'], $js, 'BEGIN_TEXT', false, $maxTextLen);
        }
        
        if (isset($params['END_TEXT'])) {
            $this->replaceTextArr($params['END_TEXT'], $js, 'END_TEXT', false, $maxTextLen);
        }
        
        // Добавяме начините на плащане
        if ($params['payments']) {
            foreach ($params['payments'] as $paymentArr) {
                $payment = $js->getBlock('PAYMENT');
                
                setIfNot($paymentArr['PAYMENT_TYPE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE'], 0);
                setIfNot($paymentArr['PAYMENT_CHANGE_TYPE'], 0);
                
                expect(($paymentArr['PAYMENT_TYPE'] >= 0) && ($paymentArr['PAYMENT_TYPE'] <= 11));
                expect(($paymentArr['PAYMENT_CHANGE'] == 0) || ($paymentArr['PAYMENT_TYPE'] == 1));
                expect(($paymentArr['PAYMENT_CHANGE_TYPE'] >= 0) && ($paymentArr['PAYMENT_CHANGE_TYPE'] <= 2));
                
                expect($paymentArr['PAYMENT_AMOUNT']);
                
                $payment->replace($paymentArr['PAYMENT_TYPE'], 'PAYMENT_TYPE');
                $payment->replace($paymentArr['PAYMENT_CHANGE'], 'PAYMENT_CHANGE');
                $payment->replace($paymentArr['PAYMENT_CHANGE_TYPE'], 'PAYMENT_CHANGE_TYPE');
                $payment->replace(json_encode($paymentArr['PAYMENT_AMOUNT']), 'PAYMENT_AMOUNT');
                
                $payment->removeBlocks();
                $payment->append2master();
            }
        } else {
            $js->removeBlock('PAYMENT');
        }
        
        if (isset($params['PAY_EXACT_SUM_TYPE'])) {
            $js->replace($params['PAY_EXACT_SUM_TYPE'], 'PAY_EXACT_SUM_TYPE');
            expect(($paymentArr['PAY_EXACT_SUM_TYPE'] >= 0) && ($paymentArr['PAY_EXACT_SUM_TYPE'] <= 11));
        } else {
            $js->removeBlock('PAY_EXACT_SUM_TYPE');
        }
        
        $js = $js->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция, която да провери дали има връзка с устройството
     * При успех вика `fpOnConnectionSuccess`, а при грешка fpOnConnectionErr
     *
     * @param stdClass $pRec - запис от peripheral_Devices
     *
     * @return string
     *
     * @see peripheral_FiscPrinter
     */
    public function getJsIsWorking($pRec)
    {
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpSerialNumber();
                                    fpOnConnectionSuccess();
                                } catch(ex) {
                                    fpOnConnectionErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция, за отпечатване на дубликат
     * При успех вика `fpOnDuplicateSuccess`, а при грешка fpOnDuplicateErr
     *
     * @param stdClass $pRec - запис от peripheral_Devices
     *
     * @return string
     *
     * @see peripheral_FiscPrinter
     */
    public function getJsForDuplicate($pRec)
    {
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fpPrintLastReceiptDuplicate();
                                    fpOnDuplicateSuccess();
                                } catch(ex) {
                                    fpOnDuplicateErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща JS функция за добавяне/изкарване на пари от касата
     *
     * @param stdClass $pRec
     * @param int      $operNum
     * @param string   $operPass
     * @param float    $amount - ако е минус - изкрва пари, а с плюс - вкарва
     * @param boolean  $printAvailability
     * @param string   $text
     *
     * @return string
     *
     * @see peripheral_FiscPrinter
     */
    public function getJsForCashReceivedOrPaidOut($pRec, $operNum, $operPass, $amount, $printAvailability = false, $text = '')
    {
        expect(($operNum >= 1) && ($operNum <= 20));
        $jsTpl = new ET('[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    fp.ReceivedOnAccount_PaidOut([#OPER_NUM#], [#OPER_PASS#], [#AMOUNT#], [#PRINT_AVAILABILITY#], [#TEXT#]);
                                    fpOnCashReceivedOrPaidOut();
                                } catch(ex) {
                                    fpOnCashReceivedOrPaidOutErr(ex.message);
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]');
        
        $this->addTplFile($jsTpl, $pRec->driverVersion);
        $this->connectToPrinter($jsTpl, $pRec, false);
        
        if ($printAvailability) {
            $printAvailability = 1;
        } else {
            $printAvailability = 0;
        }
        
        $jsTpl->replace(json_encode($operNum), 'OPER_NUM');
        $jsTpl->replace(json_encode($operPass), 'OPER_PASS');
        $jsTpl->replace(json_encode($amount), 'AMOUNT');
        $jsTpl->replace(json_encode($printAvailability), 'PRINT_AVAILABILITY');
        $jsTpl->replace(json_encode($text), 'TEXT');
        
        $js = $jsTpl->getContent();
        
        // Минифициране на JS
        $js = minify_Js::process($js);
        
        return $js;
    }
    
    
    /**
     * Връща цената с ддс и приспадната отстъпка, подходяща за касовия апарат
     *
     * @param float      $priceWithoutVat
     * @param float      $vat
     * @param float|null $discountPercent
     *
     * @return float
     *
     * @see peripheral_FiscPrinter
     */
    public function getDisplayPrice($priceWithoutVat, $vat, $discountPercent)
    {
        $displayPrice = $priceWithoutVat * (1 + $vat);
        
        if (!empty($discountPercent)) {
            $discountedPrice = round($displayPrice * $discountPercent, 2);
            $displayPrice = $displayPrice - $discountedPrice;
        }
        
        return $displayPrice;
    }
    
    
    /**
     * Помощна функция за добавяне на необходимите JS файлове
     *
     * @param core_ET $tpl
     * @param string $driverVersion
     */
    protected function addTplFile(&$tpl, $driverVersion)
    {
        if (!$driverVersion) {
            $driverVersion = '19.03.22';
        }
        
        // Добавяме необходимите JS файлове
        $tpl->replace(sbf("tremol/libs/{$driverVersion}/fp_core.js"), 'FP_CORE_JS');
        $tpl->replace(sbf("tremol/libs/$driverVersion/fp.js"), 'FP_JS');
        $tpl->replace(sbf("tremol/js/fiscPrinter.js"), 'FISC_PRINT_JS');
    }
    
    
    /**
     * Помощна фунцкия за връзка с принтер
     *
     * @param core_ET   $tpl
     * @param stdClass  $pRec
     * @param null|bool $serialKeepPortOpen
     */
    protected function connectToPrinter($tpl, $pRec, $serialKeepPortOpen = null)
    {
        // Задаваме настройките за връзка със сървъра
        $tpl->replace(json_encode($pRec->serverIp), 'SERVER_IP');
        $tpl->replace(json_encode($pRec->serverTcpPort), SERVER_TCP_PORT);
        
        // Свързваме се с ФП
        if ($pRec->type == 'tcp') {
            $tpl->replace(json_encode($pRec->tcpIp), 'TCP_IP');
            $tpl->replace($pRec->tcpPort, 'TCP_PORT');
            $tpl->replace(json_encode($pRec->tcpPass), 'TCP_PASS');
            
            $tpl->replace('false', 'SERIAL_PORT');
            $tpl->replace('false', 'SERIAL_BAUD_RATE');
            $tpl->replace('false', 'SERIAL_KEEP_PORT_OPEN');
        } elseif ($pRec->type == 'serial') {
            $tpl->replace('false', 'TCP_IP');
            $tpl->replace('false', 'TCP_PORT');
            $tpl->replace('false', 'TCP_PASS');
            $tpl->replace(json_encode($pRec->serialPort), 'SERIAL_PORT');
            $tpl->replace($pRec->serialSpeed, 'SERIAL_BAUD_RATE');
            
            setIfNot($serialKeepPortOpen, 'true');
            if ($serialKeepPortOpen) {
                $serialKeepPortOpen = 'true';
            } else {
                $serialKeepPortOpen = 'false';
            }
            
            $tpl->replace($serialKeepPortOpen, 'SERIAL_KEEP_PORT_OPEN');
        } else {
            expect(false, $pRec);
        }
    }
    
    
    /**
     * Помощна фунцкия за заместване на плейсхолдерите за текст
     *
     * @param array|string $tArr
     * @param core_ET      $jTpl
     * @param string       $placeName
     * @param boolean       $removeBlock
     * @param integer       $maxLen
     */
    protected function replaceTextArr($tArr, &$jTpl, $placeName, $removeBlock = false, $maxLen = 30)
    {
        $resStrArr = array();
        if (!is_array($tArr)) {
            $tArr = array($tArr);
        }
        
        $minLen = $maxLen - 5;
        
        foreach ($tArr as $tStr) {
            $tStr = hyphen_Plugin::getHyphenWord($tStr, $minLen, $maxLen, '<wbr>');
            
            $resStrArr = array_merge($resStrArr, explode('<wbr>', $tStr));
        }
        
        if (!empty($resStrArr)) {
            foreach ($resStrArr as $str) {
                $bTpl = $jTpl->getBlock($placeName);
                
                $bTpl->replace(json_encode($str), $placeName);
                
                $bTpl->removeBlocks();
                $bTpl->append2master();
            }
            
            if ($removeBlock) {
                unset($jTpl->blocks[$placeName]);
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $Embedder
     * @param stdClass                  $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $form = &$data->form;
        
        if (!isset($form->rec->id)) {
            $form->setDefault('footerText', 'Отпечатано с bgERP');
        }
        
        // Дефолти на начините на плащане
        if(empty($form->rec->paymentMap)){
            
            // Задаване на дефолтните кодове на начините на плащане
            $paymentOptions = array('paymentId' => array(), 'code' => array());
            foreach (self::DEFAULT_PAYMENT_MAP as $paymentName => $code){
                $paymentId = ($paymentName == 'Брой') ? -1 : cond_Payments::fetchField("#title='{$paymentName}'");
                if(!empty($paymentId)){
                    $paymentOptions['paymentId'][] = $paymentId;
                    $paymentOptions['code'][] = $code;
                }
            }
            
            $form->setDefault('paymentMap', $form->getFieldType('paymentMap')->fromVerbal($paymentOptions));
        }
        
        // Дефолти на сторно основанията
        if(empty($form->rec->reasonMap)){
            $reasonOptions = array('reason' => array(), 'code' => array());
            foreach (self::DEFAULT_STORNO_REASONS_MAP as $reason => $code){
                $reasonOptions['reason'][] = $reason;
                $reasonOptions['code'][] = $code;
            }
            
            $form->setDefault('reasonMap', $form->getFieldType('reasonMap')->fromVerbal($reasonOptions));
        }
        
        // Задаване на дефолтните кодове на ДДС групите
        if(empty($form->rec->vatGroups)){
            $groupOptions = array('groupId' => array(), 'code' => array());
            foreach (self::DEFAULT_VAT_GROUPS_MAP as $group => $code){
                $groupOptions['groupId'][] = acc_VatGroups::getIdBySysId($group);
                $groupOptions['code'][] = $code;
            }
            
            $form->setDefault('vatGroups', $form->getFieldType('vatGroups')->fromVerbal($groupOptions));
        }
        
        $form->setDefault('serialSpeed', 115200);
        $form->setDefault('serverIp', '127.0.0.1');
        $form->setDefault('serverTcpPort', 4444);
        $form->setDefault('tcpPort', 8000);
        $form->setDefault('tcpPass', 1234);
        $form->setDefault('footerText', 'Отпечатано с bgERP');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $Embedder
     * @param core_ET                   $tpl
     * @param stdClass                  $data
     */
    protected static function on_AfterRenderSingle($Driver, $Embedder, &$tpl, $data)
    {
        if ($Embedder instanceof peripheral_Devices && $Embedder->haveRightFor('edit', $data->rec->id)) {
            $setSerialUrl = toUrl(array($Driver, 'setSerialNumber', $data->rec->id), 'local');
            $setSerialUrl = urlencode($setSerialUrl);
            
            $jsTpl = new ET("[#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    
                                    try {
                                        getEfae().process({url: '{$setSerialUrl}'}, {serial: fpSerialNumber()});
                                    } catch(ex) {
                                        render_showToast({timeOut: 800, text: '" . tr('Грешка при обновяване на серийния номер') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'notice'});
                                    }

                                    [#OTHER#]
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Грешка при свързване с принтера') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                }
                            [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]");
            
            // След запис, обновяваме хедър и футъра
            if (Request::get('update')) {
                // Сверяваме времето
                $now = json_encode(date('d-m-Y H:i:s'));
                $updateTime = "try {
                                    fpSetDateTime({$now});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr('Не може да се синхронизира времето') . ": ' + ex.message, isSticky: false, stayTime: 12000, type: 'warning'});
                                }";
                $jsTpl->prepend($updateTime, 'OTHER');
                
                // Нулираме другихте хедъри
                $headersTextStr = '';
                
                $maxTextLen = ($data->rec->fpType == 'fiscalPrinter') ? $Driver->fpLen : $Driver->crLen;
                
                if ($data->rec->header == 'yes') {
                    for ($i = 1; $i <= 7; $i++) {
                        $h = headerText . $i;
                        $ht = (string) $data->rec->{$h};
                        $ht = self::formatText($ht, $data->rec->headerPos, $maxTextLen);
                        $ht = json_encode($ht);
                        $headersTextStr .= "fpProgramHeader({$ht}, {$i});";
                    }
                }
                $footerTextStr = '';
                if ($data->rec->footer == 'yes') {
                    $ft = (string) $data->rec->footerText;
                    $ft = self::formatText($ft, $data->rec->footerPos, $maxTextLen);
                    $ft = json_encode($ft);
                    $footerTextStr = "fpProgramFooter({$ft});";
                }
                
                if ($headersTextStr || $footerTextStr) {
                    $headerText = "try {
                                        {$headersTextStr}
                                    } catch(ex) {
                                        render_showToast({timeOut: 800, text: '" . tr('Грешка при програмиране на хедъра на устройството') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                    }
                                    
                                    try {
                                        {$footerTextStr}
                                    } catch(ex) {
                                        render_showToast({timeOut: 800, text: '" . tr('Грешка при програмиране на футъра на устройството') . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'warning'});
                                    }";
                    $jsTpl->append($headerText, 'OTHER');
                }
            }
            
            $Driver->addTplFile($jsTpl, $data->rec->driverVersion);
            $Driver->connectToPrinter($jsTpl, $data->rec, false);
            
            $jsTpl->removePlaces();
            
            jquery_Jquery::run($tpl, $jsTpl);
        }
    }
    
    
    /**
     * Помощна функция за позициониране на текст - добавя интервали в началото
     *
     * @param string $text
     * @param string $pos
     * @param int    $maxLen
     *
     * @return string
     */
    protected static function formatText($text, $pos, $maxLen = 32)
    {
        $text = trim($text);
        
        if ($pos == 'right') {
            $l = mb_strlen($text);
            if ($maxLen > $l) {
                $text = str_repeat(' ', $maxLen - $l) . $text;
            }
        } elseif ($pos == 'center') {
            $l = mb_strlen($text);
            if ($maxLen > $l) {
                $text = str_repeat(' ', (int) (($maxLen - $l) / 2)) . $text;
            }
        }
        
        return $text;
    }
    
    
    /**
     * Екшън за промяна на серийния номер
     *
     * @return array|string
     */
    public function act_SetSerialNumber()
    {
        expect(Request::get('ajax_mode'));
        
        peripheral_Devices::requireRightFor('single');
        
        $serial = Request::get('serial');
        $id = Request::get('id', 'int');
        
        expect($id);
        
        $pRec = peripheral_Devices::fetch($id);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $id);
        peripheral_Devices::requireRightFor('edit', $id);
        
        $res = array();
        
        if ($pRec->serialNumber != $serial) {
            $oldSerial = $pRec->serialNumber;
            $pRec->serialNumber = $serial;
            
            $statusData = array();
            
            if (peripheral_Devices::save($pRec)) {
                if (trim($oldSerial)) {
                    $statusData['text'] = tr('Променен сериен номер от') . " {$oldSerial} " . tr('на') . " {$serial}";
                } else {
                    $statusData['text'] = tr('Добавен сериен номер');
                }
                
                $statusData['type'] = 'notice';
                $statusData['timeOut'] = 700;
                $statusData['isSticky'] = 0;
                $statusData['stayTime'] = 8000;
            } else {
                $statusData['text'] = tr('Грешка при промяна на сериен номер');
                $statusData['type'] = 'error';
                $statusData['timeOut'] = 700;
                $statusData['isSticky'] = 1;
                $statusData['stayTime'] = 15000;
            }
            
            $statusObj = new stdClass();
            $statusObj->func = 'showToast';
            $statusObj->arg = $statusData;
            
            $res[] = $statusObj;
        }
        
        return $res;
    }
    
    
    /**
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $Embedder
     * @param object                    $data
     */
    public static function on_AfterPrepareRetUrl($Driver, $Embedder, &$data)
    {
        if ($data->form->cmd == 'save') {
            $data->retUrl = array($Embedder, 'single', $data->form->rec->id, 'update' => true);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     *
     * @param tremol_FiscPrinterDriver2 $Driver
     * @param peripheral_Devices        $mvc
     * @param object                    $res
     * @param object                    $data
     */
    public static function on_AfterPrepareSingleToolbar($Driver, $mvc, &$res, $data)
    {
        if (haveRole($Driver->canMakeReport)) {
            $data->toolbar->addBtn('Отчети', array($Driver, 'Reports', 'pId' => $data->rec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/report.png, title=Отпечатване на отчети, row=2');
        }
        
        if (haveRole($Driver->canCashReceived) || haveRole($Driver->canCashPaidOut)) {
            $data->toolbar->addBtn('Средства', array($Driver, 'CashReceivedOrPaidOut', 'pId' => $data->rec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/money.png, title=Вкарване или изкарване на пари от касата, row=1');
        }
    }
    
    
    /**
     * Екшън за отпечатване/записване на отчети
     *
     * @return core_ET
     */
    public function act_Reports()
    {
        expect(haveRole($this->canMakeReport));
        
        $submitTitle = 'Отпечатване';
        $closeBtnName = 'Отказ';
        
        $pId = Request::get('pId', 'int');
        
        $pRec = peripheral_Devices::fetch($pId);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $pRec);
        
        $form = cls::get('core_Form');
        
        $form->FLD('report', 'enum(day=Дневен,operator=Операторски (дневен),period=Период,month=Месечен,year=Годишен,klen=КЛЕН,csv=CSV)', 'caption=Отчет->Вид, mandatory, removeAndRefreshForm=zeroing,isDetailed,operNum,fromDate,toDate,flagReports,flagReceipts,csvFormat,printIn,saveType,printType');
        
        $form->input('report');
        
        $form->FLD('zeroing', 'enum(no=Не, yes=Да)', 'caption=Отчет->Нулиране, mandatory');
        $form->FLD('isDetailed', 'enum(no=Не, yes=Да)', 'caption=Отчет->Детайлен, mandatory');
        
        if ($form->rec->report == 'operator') {
            $form->FLD('operNum', 'int(min=0, max=20)', 'caption=Отчет->Оператор, mandatory');
            $form->setField('isDetailed', 'input=none');
            $form->setDefault('operNum', 1);
        } elseif (($form->rec->report == 'period') || ($form->rec->report == 'month') || ($form->rec->report == 'year') || ($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
            $form->FLD('fromDate', 'date', 'caption=Дата->От, mandatory');
            $form->FLD('toDate', 'date', 'caption=Дата->До, mandatory');
            
            if ($form->rec->report == 'period') {
                $form->setDefault('fromDate', date('d-m-Y', strtotime('this week')));
                $form->setDefault('toDate', dt::now(false));
            } elseif ($form->rec->report == 'month') {
                if (date('d') <= 20) {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime('first day of previous month')));
                    $form->setDefault('toDate', date('d-m-Y', strtotime('last day of previous month')));
                } else {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime('first day of this month')));
                    $form->setDefault('toDate', dt::now(false));
                }
            } elseif (($form->rec->report == 'year') || ($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
                $y = date('Y');
                if ((date('n') <= 11) && (($form->rec->report != 'klen') && ($form->rec->report != 'csv'))) {
                    $y--;
                    $form->setDefault('fromDate', date('d-m-Y', strtotime(date('01-01-' . $y))));
                    $form->setDefault('toDate', date('d-m-Y', strtotime(date('31-12-' . $y))));
                } else {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime(date('01-01-' . $y))));
                    $form->setDefault('toDate', dt::now(false));
                }
            } elseif (($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
                $form->FLD('printType', 'enum(print=Отпечатване, save=Запис)', 'caption=Действие, mandatory, removeAndRefreshForm=saveType');
                
                $form->input('printType');
                
                if ($form->rec->printType == 'save') {
                    $form->FLD('saveType', 'enum(sd=SD карта, usb=USB)', 'caption=Запис в, mandatory');
                    
                    $submitTitle = 'Запис';
                } else {
                    $form->FLD('printIn', 'enum(PC=Компютър, FP=Фискално устройство)', 'caption=Отпечатване в, mandatory');
                }
                
                $form->setField('zeroing', 'input=none');
                
                if ($form->rec->report == 'csv') {
                    $form->setField('isDetailed', 'input=none');
                    $form->FLD('csvFormat', 'enum(yes=Да, no=Не)', 'caption=CSV формат, mandatory');
                    
                    $form->FLD('flagReceipts', 'int(min=0, max=7)', 'caption=Флаг->ФБ, mandatory');
                    $form->FLD('flagReports', 'int(min=0, max=7)', 'caption=Флаг->Отчет, mandatory');
                    
                    $form->setDefault('flagReceipts', 1);
                    $form->setDefault('flagReports', 1);
                    
                    if ($form->rec->printType != 'save') {
                        $form->setOptions('printIn', array('PC' => 'Компютър'));
                    }
                }
            }
        }
        
        $form->input();
        
        $rec = $form->rec;
        
        $jsTpl = null;
        
        if ($form->isSubmitted()) {
            if ($rec->zeroing == 'yes') {
                $form->setWarning('report, zeroing', 'Отчетът ще бъде нулиран');
            }
        }
        
        $rand = Request::get('rand');
        
        $hash = md5(serialize($rec));
        
        $randStr = 'tremol_reports_' . $rand;
        
        // Защита от случайно повторно отпечатване
        if (($rVal = Mode::get($randStr)) && ($rVal == $hash)) {
            $form->setWarning('report', 'Този отчет вече е отпечатан');
        }
        
        if ($form->isSubmitted()) {
            Mode::setPermanent($randStr, $hash);
            
            $rVerb = $form->getFieldType('report')->toVerbal($rec->report);
            $rVerb = mb_strtolower($rVerb);
            
            $fnc = '';
            
            $isDetailed = 'false';
            if ($rec->isDetailed == 'yes') {
                $isDetailed = 'true';
            }
            
            $isZeroing = 'false';
            if ($rec->zeroing == 'yes') {
                $isZeroing = 'true';
            }
            
            if ($rec->report == 'day') {
                $fnc = "fpDayReport({$isZeroing},{$isDetailed})";
            }
            
            if ($rec->report == 'operator') {
                $operator = (int) $rec->operNum;
                $fnc = "fpOperatorReport({$isZeroing}, {$operator})";
            }
            
            if (($rec->report == 'period') || ($rec->report == 'month') || ($rec->report == 'year') || ($rec->report == 'klen') || ($rec->report == 'csv')) {
                $fromDate = json_encode(dt::mysql2verbal($rec->fromDate, 'd-m-Y H:i:s'));
                $toDate = json_encode(dt::mysql2verbal($rec->toDate . ' 23:59:59', 'd-m-Y H:i:s'));
                $fnc = "fpPeriodReport({$fromDate}, {$toDate}, {$isDetailed})";
                
                if (($rec->report == 'klen') || ($rec->report == 'csv')) {
                    if ($rec->printType == 'save') {
                        $outType = $rec->saveType;
                    } else {
                        $outType = $rec->printIn;
                    }
                    
                    if (!$outType) {
                        $outType = 'pc';
                    }
                    
                    $outType = strtolower($outType);
                    $outType = json_encode($outType);
                    
                    if ($rec->report == 'csv') {
                        $csfFormat = json_encode($rec->csvFormat);
                        $fnc = "fpOutputCSV({$outType}, {$fromDate}, {$toDate}, {$csfFormat}, {$rec->flagReceipts}, {$rec->flagReports})";
                    } else {
                        $fnc = "fpOutputKLEN({$outType}, {$fromDate}, {$toDate}, {$isDetailed})";
                    }
                }
            }
            
            expect($fnc);
            
            $fnc .= ';';
            
            $jsTpl = new ET("function fpPrintReport() {
                                $('.fullScreenBg').fadeIn();
                                [#/tremol/js/FiscPrinterTplFileImportBegin.txt#]
                                try {
                                    [#/tremol/js/FiscPrinterTplConnect.txt#]
                                    {$fnc}
                                    render_showToast({timeOut: 800, text: '" . tr("Успешно отпечатан {$rVerb} отчет") . "', isSticky: false, stayTime: 8000, type: 'notice'});
                                } catch(ex) {
                                    render_showToast({timeOut: 800, text: '" . tr("Грешка при отпечатване на {$rVerb} отчет") . ": ' + ex.message, isSticky: true, stayTime: 8000, type: 'error'});
                                }
                                [#/tremol/js/FiscPrinterTplFileImportEnd.txt#]
                                $('.fullScreenBg').fadeOut();
                            }
                                                    
                            fpPrintReport();");
            
            $jsTpl->prepend('$(\'body\').append(\'<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 10; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"></div>\');');
            
            $this->addTplFile($jsTpl, $pRec->driverVersion);
            $this->connectToPrinter($jsTpl, $pRec, false);
            
            $closeBtnName = 'Назад';
        } else {
            $form->toolbar->addSbBtn($submitTitle, 'save', 'ef_icon = img/16/print_go.png');
        }
        
        $form->title = 'Генериране на отчет в ФУ|* ' . peripheral_Devices::getLinkToSingle($pRec->id, 'name');
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('peripheral_Devices', 'single', $pId);
        }
        
        $form->toolbar->addBtn($closeBtnName, $retUrl, 'ef_icon = img/16/close-red.png');
        
        $html = $form->renderHtml();
        
        if ($jsTpl) {
            $html->appendOnce($jsTpl, 'SCRIPTS');
        }
        
        $tpl = cls::get('peripheral_Devices')->renderWrapping($html);
        
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Екшън за вкарване/изкараване на пари от касата
     *
     * @return core_ET
     */
    public function act_CashReceivedOrPaidOut()
    {
        $cancelBtn = 'Отказ';
        $canReceived = haveRole($this->canCashReceived);
        $canCashPaidOut = haveRole($this->canCashPaidOut);
        expect($canReceived || $canCashPaidOut);
        
        $pId = Request::get('pId', 'int');
        
        $pRec = peripheral_Devices::fetch($pId);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $pRec);
        
        $form = cls::get('core_Form');
        
        $enumStr = '';
        if ($canReceived) {
            $enumStr .= 'received=Захранване';
        }
        
        if ($canCashPaidOut) {
            $enumStr .= $enumStr ? ',' : '';
            $enumStr .= 'paidOut=Изплащане';
        }
        
        $form->FLD('type', "enum({$enumStr})", 'caption=Действие, mandatory, removeAndRefreshForm');
        
        $len = ($pRec->fpType == 'fiscalPrinter') ? $this->fpLen : $this->crLen;
        
        $len -= $this->mLen;
        
        $form->FLD('amount', 'double(min=0)', 'caption=Сума, mandatory');
        $form->FLD('text', "varchar({$len})", 'caption=Текст');
        $form->FLD('printAvailability', 'enum(yes=Да,no=Не)', 'caption=Отпечатване на->Наличност');
        
        $form->input();
        
        $rec = $form->rec;
        
        $jsTpl = null;
        
        $rand = Request::get('rand');
        
        $hash = md5(serialize($rec));
        
        $randStr = 'tremol_cashRAndP_' . $rand;
        
        // Защита от случайно повторно отпечатване
        if (($rVal = Mode::get($randStr)) && ($rVal == $hash)) {
            $form->setWarning('report', 'Това действие вече е извършено');
        }
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('peripheral_Devices', 'single', $pId);
        }
        
        if ($form->isSubmitted()) {
            Mode::setPermanent($randStr, $hash);
            
            $operator = 1;
            $operPass = '0';
            
            $amount = $rec->amount;
            if ($amount && $rec->type == 'paidOut') {
                $amount *= -1;
            }
            
            $printAvailability = $form->rec->printAvailability == 'yes' ? true : false;
            
            $jsFunc = $this->getJsForCashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $printAvailability, $rec->text);
            
            $actTypeVerb = $form->fields['type']->type->toVerbal($rec->type);
            $actTypeVerb = tr(mb_strtolower($actTypeVerb));
            
            $retUrlDecoded = toUrl($retUrl);
            
            $jsTpl = new ET("function fpOnCashReceivedOrPaidOut() {render_redirect({url: '{$retUrlDecoded}'}); };
                             function fpOnCashReceivedOrPaidOutErr(message) { $('.fullScreenBg').fadeOut(); render_showToast({timeOut: 800, text: '" . tr('Грешка при') . ' ' . tr($actTypeVerb) . ' ' . tr('във ФУ') . ": ' + message, isSticky: true, stayTime: 8000, type: 'error'});}");
            
            $jsTpl->prepend('$(\'body\').append(\'<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 10; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"></div>\'); $(".fullScreenBg").fadeIn();');
            
            $jsTpl->append($jsFunc);
            $cancelBtn = 'Назад';
        }
        
        $submitTitle = 'Захранване';
        $submitIcon = 'img/16/money_add.png';
        if ($rec->type == 'paidOut') {
            $submitTitle = 'Изплащане';
            $submitIcon = 'img/16/money_delete.png';
        }
        
        $form->toolbar->addSbBtn($submitTitle, 'save', "ef_icon = {$submitIcon}");
        
        $form->title = 'Вкарване или изкарване на пари от касата|* ' . peripheral_Devices::getLinkToSingle($pRec->id, 'name');
        
        $form->toolbar->addBtn($cancelBtn, $retUrl, 'ef_icon = img/16/close-red.png');
        
        $html = $form->renderHtml();
        
        if ($jsTpl) {
            $html->appendOnce($jsTpl, 'SCRIPTS');
        }
        
        $tpl = cls::get('peripheral_Devices')->renderWrapping($html);
        
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Дали във ФУ има е нагласена подадената валута
     * 
     * @param stdClass $rec
     * @param string $currencyCode
     * @return boolean
     */
    public function isCurrencySupported($rec, $currencyCode)
    {
        if($currencyCode != 'BGN'){
            $currencyId = currency_Currencies::getIdByCode($currencyCode);
            
            return keylist::isIn($currencyId, $rec->suppertedCurrencies);
        }
        
        return true;
    }
    
    
    /**
     * Какъв е кода на плащането в настройките на апарата
     *
     * @param stdClass $rec
     * @param int $paymentId
     * @return string|null
     */
    public function getPaymentCode($rec, $paymentId)
    {
        $payments = type_Table::toArray($rec->paymentMap);
        
        $found = array_filter($payments, function($a) use ($paymentId) {return $a->paymentId == $paymentId;});
        $found = $found[key($found)];
        
        return is_object($found) ? $found->code : null;
    }
    
    
    /**
     * Какъв е кода на основанието за сторниране
     *
     * @param stdClass $rec - запис
     * @param string $reason   - основание
     * @return string|null  - намерения код или null, ако няма
     */
    public function getStornoReasonCode($rec, $reason)
    {
        $payments = type_Table::toArray($rec->reasonMap);
        
        $found = array_filter($payments, function($a) use ($reason) {return $a->reason == $reason;});
        $found = $found[key($found)];
        
        return is_object($found) ? $found->code : null;
    }
    
    
    /**
     * Какви са разрешените основания за сторниране
     *
     * @param stdClass $rec - запис
     * @return array  - $res
     */
    public function getStornoReasons($rec)
    {
        $res = arr::make(array_keys(self::DEFAULT_STORNO_REASONS_MAP), true);
        
        return $res;
    }
    
    
    /**
     * Какъв е кода отговарящ на ДДС групата на артикула
     *
     * @param int $groupId  - ид на ДДС група
     * @param stdClass $rec - запис
     * @return string|null  - намерения код или null, ако няма
     */
    public function getVatGroupCode($groupId, $rec)
    {
        $payments = type_Table::toArray($rec->vatGroups);
        $found = array_filter($payments, function($a) use ($groupId) {return $a->groupId == $groupId;});
        $found = $found[key($found)];
        
        return is_object($found) ? $found->code : null;
    }
}
