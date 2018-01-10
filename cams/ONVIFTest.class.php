<?php
class cams_ONVIFTest extends core_Manager {
    
    function act_Test () {
            $header_security = array(
                'UsernameToken' => array(
                    'Username' => 'admin',
                    'Password' => 'admin',
                    'Nonce'    => 'LKqI6G/AikKCQrN0zqZFlg==',
                    'Created'  => gmdate('Y-m-d\TH:i:s\Z')
                ),
            );
        
            $headers = array();
            $headers[] = new SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $header_security, TRUE);
            //$headers[] = new SoapHeader('https://www.onvif.org/onvif/ver10/device/wsdl/devicemgmt.wsdl', 'Device', $header_security, TRUE);
            
            $location = 'http://10.0.0.101:8999/onvif/device_service';
            //$location = 'http://10.0.0.101:8999/onvif/media_service';
            
            //$wsdlUrl = "https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/devicemgmt.wsdl";
            $wsdlUrl = "https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/ptz.wsdl";
            //$wsdlUrl = "https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/media.wsdl";
            
            $client = new SoapClient($wsdlUrl, array(
              // 'location' => $location,
               //'uri'      => 'http://www.onvif.org/ver10/media/wsdl/GetStreamUri',
               'soap_version' => SOAP_1_2,
               'style'    => SOAP_RPC,
               'use'      => SOAP_ENCODED,
               'encoding' => 'UTF-8',
               'trace'    => 1,
            ));
            
            $client->__setSoapHeaders($headers);
            $client->__setLocation($location);

            //$result = $client->__soapCall("GetDNS",array());
//             if (!is_soap_fault($client->__getLastRequestHeaders())) {
//                 trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
//             }
            
//            bp($client->__getFunctions());
            // $client->__soapCall("GetDNS", array());
            // $client->__soapCall("GetDeviceInformation", array());
            //$client->__soapCall("GetVideoSources", array());
///            $client->__soapCall("GetProfiles", array()); //egyptStandardTime-2
//            $client->__soapCall("SetSystemDateAndTime", array('parameters' => array("DateTimeType" =>'Manual', "DaylightSavings"=>'true', "TimeZone"=>'', "UTCDateTime" => array('Time'=>array('Hour'=>'17', 'Minute'=>'02', 'Second'=>'00'), 'Date'=>array('Year'=>'2017','Month'=>'11','Day'=>'06')))));
//           $client->__soapCall("GetSystemDateAndTime", array());
///            $client->__soapCall("GetStreamUri", array('parameters' => array('StreamSetup'=>array('Stream'=>'RTP-Unicast','Transport'=>array('Protocol'=>'RTSP')), 'ProfileToken'=>'SubStream')));

///            $client->__soapCall("GetSnapshotUri", array('parameters' => array('ProfileToken'=>'MainStream')));
            //$client->__soapCall("GetConfigurations", array('parameters' => array('ProfileToken'=>'SubStream')));
//            $client->__soapCall("GetConfiguration", array('parameters' => array('ProfileToken'=>'SubStream', 'PTZConfigurationToken'=>'PTZCFG_CH0')));

//             $client->__soapCall("RelativeMove",
//                 array(
//                         'parameters' => array(
//                                                 'ProfileToken'=>'SubStream',
//                                                 'PTZConfigurationToken'=>'PTZCFG_CH0',
//                                                 'Translation'=>array("PanTilt"=>array("x"=>1, "y"=>0), "Zoom"=> array("x"=>1)),
//                                                 'Speed'=>array("PanTilt"=>array("x"=>-1, "y"=>1), "Zoom"=> array("x"=>0.5))
//                                              )
//                      )
//                 );
            $x = Request::get('x', 'float');
            $y = Request::get('y', 'float');
            $zoom = Request::get('zoom','float');

            $client->__soapCall("ContinuousMove",
                array(
                    'parameters' => array(
                        'ProfileToken'=>'SubStream',
                  //      'PTZConfigurationToken'=>'PTZCFG_CH0',
                        'Velocity'=>array("PanTilt"=>array("x"=>$x, "y"=>$y), "Zoom"=> array("x"=>$zoom))
                    )
                )
            );
            usleep(400000);
            $client->__soapCall("Stop",
                array(
                    'parameters' => array(
                        'ProfileToken'=>'SubStream',
                        //      'PTZConfigurationToken'=>'PTZCFG_CH0',
                        'PanTilt'=>true,
                        'Zoom'=> true
                    )
                )
            );
            


//            PanTilt y="0" x="0"/><tt:Zoom x="0"
                
//             $client->__soapCall("GetStatus",
//                 array('parameters' => array( 'ProfileToken'=>'SubStream')
//                     )
//             );
            
            $response = $client->__getLastResponse();
            
            $xml = simplexml_load_String($response);
            echo htmlspecialchars($xml->asXml()). "<br><br>";
            
            //$xml1 = $xml->children('SOAP-ENV', true)->Body->children('tds', true)->GetDNSResponse->children('tds', true)->DNSInformation->children('tt', true);
            //$xml1 = $xml->children('SOAP-ENV', true)->Body->children('tptz', true);            

            bp($xml1);
    }
}