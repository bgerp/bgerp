<?php


/**
 * 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_Print extends core_Manager
{
    
    
    /**
     * Разделител на стринга за id-то
     */
    protected static $agentParamDelimiter = '_';
    
    
    /**
     * Заглавие
     */
    public $title = 'Отпечатване на документи в мобилен принтер';


    /**
     * Дали id-тата на този модел да са защитени?
     */
    var $protectId = FALSE;
    
    
    /**
     * 
     */
    public $canAdd = 'no_one';
    
    
    /**
     * 
     */
    public $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $canEdit = 'no_one';
    
    
    /**
     *
     */
    public $canList = 'no_one';
    
    
    /**
     * Масив с `id` от приложението и драйвер, на който отговарят
     */
    public static $drvMapArr = array(1 => 'escpos_driver_Ddp250', 2 => 'escpos_driver_P300');
    
    
    /**
     * Екшън за отпечатване с escpos
     */
    function act_Print()
    {
        $idFullStr = Request::get('id');
        
        $paramsArr = $this->parseParamStr($idFullStr);
        
        $id = $paramsArr['id'];
        $clsInst = $paramsArr['clsInst'];
        
        expect($id && $clsInst);
        
        $clsInst->logRead('Мобилно отпечатване', $id);
        
        $drvId = Request::get('drv');
        
        $drvName = self::$drvMapArr[$drvId];
        
        if (!$drvName) {
//             $drvName = 'escpos_driver_Html';
            $drvName = 'escpos_driver_Ddp250';
        }
        
        // За да не се кешира
        header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Указваме, че ще се връща XML
        header('Content-Type: application/xml');
        
        $res = escpos_Helper::getContentXml($clsInst, $id, $drvName, $paramsArr['userId']);
        
        echo $res;
        
        // Прекратяваме процеса
        shutdown();
    }
    
    
    /**
     * Подготвя URL за печатане чрез
     * 
     * @param core_Master $mvc
     * @param int $id
     * 
     * @return string
     */
    public static function prepareUrlIdForAgent($clsInst, $id)
    {
        $pId = $clsInst->protectId($id);
        $clsId = $clsInst->getClassId();
        
        $cu = core_Users::getCurrent();
        
        $hash = self::getHash($clsId, $pId, $cu);
        
        $res = $clsId . self::$agentParamDelimiter . $pId . self::$agentParamDelimiter . $cu . self::$agentParamDelimiter . $hash;
        
        return $res;
    }
    
    
    /**
     * Връща хеша за стринг
     * 
     * @param string $clsId
     * @param string $pId
     * @param integer $cu
     * 
     * @return string
     */
    protected static function getHash($clsId, $pId, $cu)
    {
        $str = $clsId . self::$agentParamDelimiter . $pId . self::$agentParamDelimiter . $cu;
        
        $res = md5($str . '|' . escpos_Setup::get('SALT'));
        
        $res = substr($res, 0, escpos_Setup::get('HASH_LEN'));
        
        return $res;
    }
    
    
    /**
     * Парсира стринга и връща маси в id и инстанция на класа
     * 
     * @param string $str
     * 
     * @return array
     */
    protected static function parseParamStr($str)
    {
        list($clsId, $pId, $userId, $hash) = explode(self::$agentParamDelimiter, $str);
        
        expect($clsId);
        
        $hashGen = self::getHash($clsId, $pId, $userId);
        
        expect($hashGen == $hash);
        
        $inst = cls::get($clsId);
        
        $id = $inst->unprotectId($pId);
        
        expect($id !== FALSE);
        
        $res = array();
        $res['id'] = $id;
        $res['clsInst'] = $inst;
        $res['userId'] = $userId;
        
        return $res;
    }
    
    
    
    /**
     * Тестов екшън
     */
    public function act_Test()
    {
        $idFullStr = Request::get('id');
		
        $drvName = 'escpos_driver_Ddp250';
        
        if (Request::get('html')) {
            $drvName = 'escpos_driver_Html';
        }
        
        if ($idFullStr) {
            $paramsArr = self::parseParamStr($idFullStr);
            
            expect($paramsArr);
            
            $dataContent = escpos_Helper::preparePrintView($paramsArr['clsInst'], $paramsArr['id'], $paramsArr['userId']);
            
            echo escpos_Convert::process($dataContent, $drvName);
            
            shutdown();
        } else {
            $res = escpos_Helper::getTpl();
            
            $res->replace("Тестово отпечатване", 'title');
            
            $test = "<c F b>Фактура №123/28.02.17" .
                            "<p><r32 =>" .
                            "<p b>1.<l3 b>Кисело мляко" .
                            "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                            "<p b>2.<l3 b>Хляб \"Добруджа\"" . "<l f> | годност: 03.03" .
                            "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                            "<p b>3.<l3 b>Минерална вода" .
                            "<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
                            "<p><r32 =>" .
                            "<p><r29 F b>Общо: 34.23 лв.";
            $dataContent = escpos_Convert::process($test, 'escpos_driver_Ddp250');
            
            $res->replace(base64_encode($dataContent), 'data');
            
            // За да не се кешира
            header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            
            // Указваме, че ще се връща XML
            header('Content-Type: application/xml');
            
            echo $res;
            
            shutdown();
        }
    }
}
