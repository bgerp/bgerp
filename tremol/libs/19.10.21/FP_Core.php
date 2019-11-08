<?php
namespace Tremol {
    use Exception;
    use DateTime;
    use ReflectionClass;
   
    abstract class BaseResClass {
        public function __construct($properties){
            foreach($properties as $key => $value){
                $this->{$key} = $value;
            }
        }
        public function __get ($name) {
            if($this->$name !== NULL) {
                return $this->$name;
            } else  {
                throw new Exception("Incorrect field ".$name." for class ".get_class($this));
            }
        }
    }

    class EnumType {
        //private static $_constCacheArray = NULL;
        private static function getConstants() {
            //if (self::$_constCacheArray == NULL) {
            //    self::$_constCacheArray = [];
            //}
            $calledClass = get_called_class();
            //if (!array_key_exists($calledClass, self::$_constCacheArray)) {
                $reflect = new ReflectionClass($calledClass);
                //self::$_constCacheArray[$calledClass] = $reflect->getConstants();
            //}
            //return self::$_constCacheArray[$calledClass];
            return $reflect->getConstants();
        }
    
        public static function isValidName($name, $strict = FALSE) {
            $constants = self::getConstants();
            if ($strict) {
                return array_key_exists($name, $constants);
            }
            $keys = array_map('strtolower', array_keys($constants));
            return in_array(strtolower($name), $keys);
        }
        
        public static function isValidValue($value, $strict = TRUE) {
            $values = array_values(self::getConstants());
            return in_array($value, $values, $strict);
        }

        public static function createEnum($className, $value) {
            $nameWns = __NAMESPACE__."\\".$className;
            $res = new $nameWns($value);
            return $res;
        }

        public function __construct($value) {
            if(self::isValidValue($value)) {
                foreach(self::getConstants() as $c => $v) {
                    if($value == $v) {
                        $this->$c = $v;
                        break;
                    }
                }
            }
            else {
                throw new SException("Error while parsing Enum", ServerErrorType::ClientArgValueWrongFormat);
            }
        }
    
        public function __toString() {
            $vars = get_object_vars($this);
            return array_values($vars)[0];
        }
    }

    class ServerErrorType extends EnumType {
        const OK = 0;
        /** The current library version and the fiscal device firmware is not matching */
        const ServMismatchBetweenDefinitionAndFPResult = 9;
        const ServDefMissing = 10;
        const ServArgDefMissing = 11;
        const ServCreateCmdString = 12;
        const ServUndefined = 19;
        /** When the server can not connect to the fiscal device */
        const ServSockConnectionFailed = 30;
        /** Wrong device ТCP password */
        const ServTCPAuth = 31;
        const ServWrongTcpConnSettings = 32;
        const ServWrongSerialPortConnSettings = 33;
        /** Processing of other clients command is taking too long */
        const ServWaitOtherClientCmdProcessingTimeOut = 34;
        const ServDisconnectOtherClientErr = 35;
        const FPException = 40;
        const ClientArgDefMissing = 50;
        const ClientAttrDefMissing = 51;
        const ClientArgValueWrongFormat = 52;
        const ClientSettingsNotInitialized = 53;
        const ClientInvalidGetFormat = 62;
        const ClientInvalidPostFormat = 63;
        const ServerAddressNotSet = 100;
        /** Specify server ServerAddress property */
        const ServerConnectionError = 101;
        /** Connection from this app to the server is not established */
        const ServerResponseMissing = 102;
        const ServerResponseError = 103;
        /** The current library version and server definitions version do not match */
        const ServerDefsMismatch = 104;
        const ClientXMLCanNotParse = 105;
        const PaymentNotSupported = 201;
        const ServerErr = 1000;
    }

    
    class SException extends Exception {
        protected $ste1 = NULL;
        protected $ste2 = NULL;
        protected $is_fp_exception = FALSE;
        
        function __construct($message, $code, $previous = NULL, $in_ste1 = NULL, $in_ste2 = NULL) {
            parent::__construct($message, $code, $previous);
            if($this->code == ServerErrorType::FPException) {
                $this->is_fp_exception = TRUE;
            }
            if($in_ste1 !== NULL) {
                $this->ste1 = $in_ste1;
            }
            if($in_ste2 !== NULL) {
                $this->ste2 = $in_ste2;
            }
        }

        public function isFpException() {
            return $this->is_fp_exception;
        }
        public function getSte1() {
            return $this->ste1;
        }
        public function getSte2() {
            return $this->ste2;
        }
    }

    class FP_DeviceSettings extends BaseResClass{
        protected $IsWorkingOnTcp = TRUE;
        protected $IpAddress = "";
        protected $TcpPort = 0;
        protected $Password = "";
        protected $SerialPort = "";
        protected $BaudRate = 0;
        protected $KeepPortOpen = FALSE;
    }

    class FP_Client {
        public $id;
        public $is_connected = FALSE;
    }

    class FP_Core {
        protected $coreVersion = "1.0.0.2";
        protected $ip = "localhost";
        protected $port = 4444;
        protected $url = "http://localhost:4444/";
        protected $w = FALSE;
        protected $ok = FALSE;
        protected $timeStamp = 0;

        public static function IsOnAndroid() {
            if(array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                return preg_match('/android/i', $_SERVER['HTTP_USER_AGENT']);
            }
            else {
                return FALSE;
            }
        }

        /**
         * Returns the version of the core library
         * @return string Returns the version of the core library
         */
        function GetVersionCore() {
            return $this->coreVersion;
        }

        /**
         * Returns the version of the generated library
         * @return number
         */
        function GetVersionDefinitions() {
            return $this->timeStamp;
        }

        /**
         * Returns TRUE if there is a command which is currently in progress 
         * @return boolean
         */
        function IsWorking () {
            return $this->w;
        }

        /** 
         * Returns TRUE if server definitions and generated code are with the same versions 
         * @return boolean
         */
        function IsCompatible() {
            return $this->ok;
        }

        private function sendPostRequest($u, $postStr){
            $data_len = strlen($postStr);
            $options = array(
                'http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded'.
                                     'Connection: keep-alive;'.
                                     'Content-Length: '.$data_len,
                        'content' => $postStr 
                    )
            );
            $streamContext  = stream_context_create($options);
            $result = file_get_contents($u, FALSE, $streamContext);
            if($result === FALSE){
                $error = error_get_last();
                throw new Exception('POST request failed: ' . $error['message']);
            }
            return $result;
        }

        private function sendGetRequest($u) {
            $result = file_get_contents($u);
            return $result;
        }

        private function sendReq($isPost, $u, $data) {
            try {
                $result = NULL;
                if($isPost)
                {
                    $result = $this->sendPostRequest($u, $data);
                }
                else
                {
                    $result = $this->sendGetRequest($u);
                }
                $resxml = $this->throwOnServerError($result);
                return $resxml;
            }
            catch(SException $sx){
                throw $sx;
            }
            catch(Exception $ex) {
                $msg = "Server connection error ($".$ex->getMessage().")";
                throw new SException($msg, ServerErrorType::ServerConnectionError, $ex);
            }
        }

        private function throwOnServerError($resp) {
            $xml = NULL;
            try {
                $xml = simplexml_load_string($resp);
            }
            catch(Exception $ex) {
                throw new SException($ex->getMessage(), ServerErrorType::ClientXMLCanNotParse, $ex);
            }
            $resCode = (int)$xml->attributes()->Code;
            if ($resCode !== 0) {
                $errNode = $xml->Err;
                /** $source = errNode.getAttribute("Source"); */
                $errMsg = (string)$errNode->Message;
                if($resCode == 40) {
                    $ste1 = hexdec($errNode->attributes()->STE1);
                    $ste2 = hexdec($errNode->attributes()->STE2);
                    throw new SException($errMsg, $resCode, NULL, $ste1, $ste2);
                }
                else {
                    throw new SException($errMsg, $resCode);
                }
            } 
            return $xml;
        }

        private function analyzeResponse($xml) {
            $res = [];//array();
            $name = "";
            $p = 0;            
            $props = $xml->xpath('//Res//Res');
            for(; $p < sizeof($props); $p++) {
                $name = (string)$props[$p]->attributes()->Name;
                $type = $props[$p]->attributes()->Type;
                $value = $props[$p]->attributes()->Value;
                if ($value == "@") {
                    $res[$name] = NULL;
                    break;
                }
                if ($name == "Reserve") {
                    continue; /** SKIP */
                }
                switch((string)$type) {
                    case "Text":
                        $res[$name] = (string)$value;
                        break;
                    case "Number":
                        $res[$name] = (double)$value;
                        break;
                    case "Decimal":
                    case "Decimal_with_format":
                    case "Decimal_plus_80h":
                        $res[$name] = (double)$value;
                        break;
                    case "Option":
                        $res[$name] = EnumType::createEnum((string)$name, (string)$value);
                        break;
                    case "DateTime":
                        $res[$name] = DateTime::createFromFormat('d-m-Y H:i:s', (string)$value);
                        break;
                    case "Reserve":
                    case "OptionHardcoded":
                        continue; /** SKIP */
                    case "Base64":
                        $bytes = unpack("C*", base64_decode((string)$value));
                        $res[$name] = $bytes;
                        break;
                    case "Status":
                        $res[$name] = ($value==1);
                        break;
                    case "NULL":
                        $res[$name] = NULL;
                        break;
                    default: /** unknown type => string */
                        $res[$name] = (string)$value;
                        break;
                }
            }
            $this->w = FALSE;
            if($p == 1) {
                return $res[$name];
            }
            else if($p > 1) {
                return $res;
            }
            return NULL;
        }

        function execute($commandName, ...$args) {
            $this->w = TRUE;
            try {
                $argsize = sizeof($args);
                $xml = simplexml_load_string("<Command Name=\"".$commandName."\"></Command>");
                if($argsize > 0)
                {
                    $xml->addChild("Args");
                    $as = $xml->Args;
                    for ($a = 0; $a < $argsize; $a += 2) 
                    {
                        $an = $as->addChild("Arg");
                        $an->addAttribute("Name", $args[$a]);
                        if(is_string($args[$a+1])) {
                            //$val= strVal($args[$a + 1]);
                            $an->addAttribute("Value", strVal($args[$a + 1]));
                        }
                        elseif($args[$a+1] instanceof DateTime) {
                            //$val = $args[$a + 1]->format('d-m-Y H:i:s');
                            $an->addAttribute("Value", $args[$a + 1]->format('d-m-Y H:i:s'));
                        }
                        elseif(is_array($args[$a+1])) {
                            $b64 = base64_encode(pack("C*", ...$args[$a + 1]));
                            $an->addAttribute("Value", $b64);
                        }
                        elseif($args[$a+1] instanceof EnumType)
                        {
                            $an->addAttribute("Value", strval($args[$a + 1]));
                        }
                        else {
                            //$val= strVal($args[$a + 1]);
                            $an->addAttribute("Value", strVal($args[$a + 1]));
                        }
                    }
                }
                $dom = dom_import_simplexml($xml);
                $x = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
                $response = $this->sendReq(TRUE, $this->url, $x);
                return $this->analyzeResponse($response);
            } catch(SException $sx) {
                throw $sx;
            } catch(Exception $ex) {
                throw new SException($ex->getMessage(), ServerErrorType::ServerErr, $ex);
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Returns the IP and the port of ZfpLabServer
         */
        function ServerGetSettings() {
            return array('ip' => $this->ip, 'port' => $this->port);
        }

        /**
         * Sets the IP and the port of ZfpLabServer
         * @param string $ipaddress Sets IP address
         * @param int $tcpport Sets TCP port
         */
        function ServerSetSettings($ipaddress, $tcpport) {
            $this->ip = $ipaddress;
            $this->port = $tcpport;
            $this->url = "http://".$this->ip;
            if($this->port && $this->port > 0) {
                $this->url = $this->url.":".$this->port;
            }
            $this->url = $this->url."/";
        }

        /**
         * Find device connected on serial port or USB
         * @return FP_DeviceSettings Device settings
         * @throws SException
         */
        function ServerFindDevice($usefound) {
            $this->w = TRUE;
            try {
                $f = "finddevice";
                if($usefound == TRUE) {
                    $f = $f."(usefound=1)";
                }
                $response = $this->sendReq(FALSE, $this->url.$f, NULL);
                $dev = $response->device;
                if($dev !== NULL) {
                    $fps = array(
                        'SerialPort' => (string)$dev->com, 
                        'BaudRate' => (int)$dev->baud, 
                        'IsWorkingOnTcp' => FALSE);
                    return new FP_DeviceSettings($fps);
                }
                return NULL;
            } finally {
                $this->w = FALSE;
            }
        }

        /**
         * Gets the device settings
         * @return FP_DeviceSettings Device settings
         * @throws SException
         */
        function ServerGetDeviceSettings() {
            $this->w = TRUE;
            try {
                $response = $this->sendReq(FALSE, $this->url."settings", NULL);
                $settings = $response->settings;
                $df = $settings->defVer;
                if($df != NULL) {
                    $this->ok = ((int)$df == $this->timeStamp);
                }
                $fps = array('IsWorkingOnTcp' => (bool)$settings->tcp,
                    'IpAddress' => (string)$settings->ip,
                    'TcpPort' =>  (int)$settings->port,
                    'Password' => (string)$settings->password,
                    'SerialPort' => (string)$settings->com,
                    'BaudRate' => (int)$settings->baud,
                    'KeepPortOpen' => (bool)$settings->keepPortOpen);
                return new FP_DeviceSettings($fps);
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Sets Device serial port communication settings
         * This method is also used to set Bluetooth connection if ZFPLabServer is running on Android device.
         * @param string $serialPort The name of the serial port (example: COM1)
         * @param number $baudRate Baud rate (9600, 19200, 38400, 57600, 115200)
         * @param boolean $keepPortOpen Keeps serial port open
         * @throws SException
         */
        function ServerSetDeviceSerialSettings($serialPort, $baudRate, $keepPortOpen) {
            $this->w = TRUE;
            $keepOpen = "0";
            if($keepPortOpen == TRUE){
                $keepOpen = "1";
            }
            try {
                $response = $this->sendReq(FALSE, $this->url."settings(com=".$serialPort.",baud=".strval($baudRate).",keepPortOpen=".$keepOpen.",tcp=0)", NULL);
                $df = $response->settings->defVer;
                if($df != NULL) {
                    $this->ok = ((int)$df == $this->timeStamp);
                }
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Sets Device LAN/WIFI communication settings
         * @param string $ipaddress IP address
         * @param int $tcpport TCP port
         * @param string $password ZFP password
         * @throws SException
         */
        function ServerSetDeviceTcpSettings ($ipaddress, $tcpport, $password) {
            $this->w = TRUE;
            try {
                $pass = "";
                if($password != NULL){
                    $pass = ",password=".$password;
                }
                $response = $this->sendReq(FALSE, $this->url."settings(ip=".$ipaddress.",port=".$tcpport.$pass.",tcp=1)", NULL);
                $df = $response->settings->defVer;
                if($df != NULL) {
                    $this->ok = ((int)$df == $this->timeStamp);
                }
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Gets ZfpLab server connected clients
         * 
         * @return FP_Client[] Array of clients
         * @throws SException
         */
        public function ServerGetClients() {
            $this->w = TRUE;
            try {
                $clients = array();
                $response = $this->sendReq(FALSE, $this->url."clients", NULL);
                foreach($response->Tree->children() as $item) {
                    $cl = new FP_Client();
                    $cl->id =  trim((string)$item->Id);
                    $cl->is_connected = (boolean)$item->PortIsOpen;
                    array_push($clients, $cl);
                }
                return $clients;
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Removes client from the server
         * @param string $id ID of the client
         * @throws SException
         */
        function ServerRemoveClient($id) {
            $this->w = TRUE;
            try {
                $this->sendReq(FALSE, $this->url."clientremove(ip=".$id.")", NULL);
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Closes the connection of the current client
         * @throws SException
         */
        function ServerCloseDeviceConnection() {
            $this->w = TRUE;
            try {
                $this->sendReq(FALSE, $this->url."clientremove(who=me)", NULL);
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Removes all clients from the server
         * @throws SException
         */
        function ServerRemoveAllClients() {
            $this->w = TRUE;
            try {
                $this->sendReq(FALSE, $this->url."clientremove(who=all)", NULL);
            }
            finally {
                $this->w = FALSE;
            }
        }

        /**
         * Enables or disables ZfpLab server log
         * @throws SException
         * @param boolean $enable enable the log
         */
        function ServerSetLog($enable) {
            $this->w = TRUE;
            try {
                $e = "0";
                if($enable == TRUE){
                    $e = "1";
                }
                $u = $this->url."log(on=".$e.")";
                $this->sendReq(FALSE, $u, NULL);
            }
            finally {
                $this->w = FALSE;
            }
        }
    }
}
?>