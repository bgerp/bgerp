<?php
// Real fiscal action wrappers with an in-memory printer; never loads a device or SDK.
require __DIR__ . '/redirectFixture.php';
class tremol_FiscPrinterDriverParent {}
class TestTremolOptions { const Zeroing=1; const Without_zeroing=0; }
class_alias('TestTremolOptions','Tremol\\OptionZeroing');
class status_Messages { public static $errors=0; public static function newStatus($msg,$type='notice') { if($type==='error')self::$errors++; } }
require dirname(__DIR__,2) . '/tremol/FiscPrinterDriverIp.class.php';
class RedirectTestPrinter { public function __call($name,$args) { RedirectTestFiscal::$calls[]=$name; } }
class RedirectTestFiscal extends tremol_FiscPrinterDriverIp {
    public static $calls=array(),$errors=0,$fail=false;
    protected static function connectToPrinter($rec,$keepPortOpen=false,$setDeviceSettings=true) { return new RedirectTestPrinter(); }
    public static function handleAndShowException($ex) { self::$errors++; }
    public function cashReceivedOrPaidOut($rec,$operNum,$operPass,$amount,$printAvailability=false,$text='',$defPaymentType=0) {
        if(self::$fail)throw new RuntimeException('Device refused');self::$calls[]='cash';
    }
    public function run($name) {
        $tpl=null;
        if($name==='cash')return $this->getResForCashReceivedOrPaidOut(new stdClass(),1,'test',1,array('Result'));
        if($name==='report')return $this->getResForReport(new stdClass(),(object)array('zeroing'=>'no','isDetailed'=>'no','report'=>'day'),'daily',$tpl,array('Result'));
        return $this->setInvoiceRange(new stdClass(),1,10,$tpl,array('Result'));
    }
}
$checks=0;$driver=new RedirectTestFiscal();
foreach(array('cash','report','range') as $action){
    try{$driver->run($action);throw new RuntimeException('Missing redirect');}
    catch(core_exception_Redirect $e){expect(RedirectTestFiscal::$errors===0 && status_Messages::$errors===0);$checks++;}
}
expect(RedirectTestFiscal::$calls===array('cash','PrintDailyReport','SetInvoiceRange'));$checks++;
RedirectTestFiscal::$fail=true;$driver->run('cash');
expect(RedirectTestFiscal::$errors===1 && status_Messages::$errors===1);$checks++;
echo "OK: {$checks} fiscal redirect checks (stub printer).\n";
