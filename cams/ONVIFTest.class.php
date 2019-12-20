<?php

class cams_ONVIFTest extends core_Manager
{
    public function act_ponvif()
    {
        requireRole('debug');
        
        // require_once ('/home/mitko/git/ponvif/lib/class.ponvif.php');
        require_once (EF_ROOT_PATH . '/' . EF_APP_CODE_NAME . '/cams/ponvif/lib/class.ponvif.php');
        $onvif = new Ponvif(); 
        $onvif->setUsername('user');
        $onvif->setPassword('Admin555');
        $onvif->setIPAddress('11.0.0.110');
        
//         $onvif->setUsername('admin');
//         $onvif->setPassword('admin');
//         $onvif->setIPAddress('10.0.0.101:8999');
        
        // In some cases you need to set MediaUrl manually. You can find it in "XAddrs" key (see above).
        //$onvif->setMediaUri('http://11.0.0.110:8000/onvif/device_service');
        
        try
        {
            $onvif->initialize();
            
            $sources = $onvif->getSources(); 
            $profileToken = $sources[0][0]['profiletoken']; //echo $sources[0][1]['profiletoken']; echo ("<pre>");print_r($sources);die;
             $mediaUri = $onvif->media_GetStreamUri($profileToken);
             $mediaSnapshotUri = $onvif->media_GetSnapshotUri($profileToken);  die($mediaSnapshotUri);
             // $onvif->getPTZUri();
             //$presets = $onvif->ptz_GetPresets($profileToken);  echo ("<pre>");print_r($presets);die;
             //$capabilities = $onvif->core_getCapabilities(); echo ("<pre>");print_r($capabilities);die; // Not implemented
             // $deviceInfo = $onvif->core_GetDeviceInformation(); echo ("<pre>");print_r($deviceInfo);die; // Action Not Implemented
             //$dateTime = $onvif->core_GetSystemDateAndTime(); echo ("<pre>"); print_r($dateTime); die; // Action Not Implemented
              echo ("<pre>"); print_r($onvif->getCapabilities()); die;
             //$onvif->ptz_GotoPreset($profileToken,8,2,2,2);
             //$onvif->ptz_RelativeMoveZoom($profileToken,0.05,10); // $zoom,$speedZoom
             die;
             $onvif->ptz_ContinuousMove($profileToken,0.1,0);
             sleep(10);
             $onvif->ptz_Stop($profileToken,'TRUE','TRUE'); //$profileToken,$pantilt(bool),$zoom(bool)
             sleep(10);
             $onvif->ptz_RelativeMove($profileToken,1,-0.5,0.1,0.1);
             // $translation_pantilt_x,$translation_pantilt_y,$speed_pantilt_x,$speed_pantilt_y
             //echo ("<pre>"); print_r($this->ptzuri);die;
             echo ("<pre>"); print_r($onvif->getPTZUri());
            
             echo "\n\n\n --------------------------------------------- \n\n\n";
            die;
            
             // List Encoders & resolutions  (summarized)
             echo "Encoders Available: \n";
             $encodersList = $onvif->getCodecEncoders('H264');
             foreach ( $encodersList[0] as $enc ) {
                 $avail_fps = implode("-", $enc['FrameRateRange']);
                 foreach ( $enc['ResolutionsAvailable'] as $res ) {
                     echo "    -> {$res['Width']}x{$res['Height']}  $avail_fps  (Encoder: {$enc['profileToken']})\n";
                 }
             }
            
            
//             // Delete all OSDs
//              try {
//                 $OSDs = $onvif->media_GetOSDs();
//                 foreach( $OSDs as $osd ) {
//                     if ( isset($osd['@attributes']['token']) ) {
//                         $onvif->media_DeleteOSD($osd['@attributes']['token']);
//                     }
//                 }
//              } catch (Exception $e) {}
             
            
            
//             // Show Available options for encoder relative to '$profileToken'
             $VideoEncoderConfigurationOpts = $onvif->media_GetVideoEncoderConfigurationOptions($profileToken);
             print_r($VideoEncoderConfigurationOpts);
            
            
//             // Get Atual Encoder Options
             $VEC = $onvif->media_GetVideoEncoderConfigurations($profileToken);
             print_r($VEC);
            
//             // Make Changes
//             $VEC['Quality'] = 6;
//             $VEC['Resolution']['Width'] = 352;
//             $VEC['Resolution']['Height'] = 240;
//             $VEC['RateControl']['FrameRateLimit'] = 10;
//             $VEC['RateControl']['BitrateLimit'] = 1000;
//             $VEC['H264']['H264Profile'] = 'High';
            
//             // Save Changes!
//             $onvif->media_SetVideoEncoderConfiguration($VEC);
            
//             // Now, we can start streaming!
             echo "<br>    ->  $mediaUri <br>-> $mediaSnapshotUri";
            
        }
        catch(Exception $e)
        {
            //echo "erro\n";
            bp($e);
        }
        
        
        
    }
    
    
    public function act_Test()
    {
        requireRole('debug');
        ini_set('default_socket_timeout', 600);
        
        $header_security = array(
            'UsernameToken' => array(
                'Username' => 'user',
                'Password' => 'Admin555',
                'Nonce' => 'NuoP2mZv3E2PpdpkwoIqQyAAAAAAAA==',
                'Created' => gmdate('Y-m-d\TH:i:s\Z')
            ),
        );
        
        $headers = array();
        $headers[] = new SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $header_security, true);
        //$headers[] = new SoapHeader('https://www.onvif.org/onvif/ver10/device/wsdl/devicemgmt.wsdl', 'Device', $header_security, TRUE);
        
        $location = 'http://11.0.0.110:8000/onvif/device_service';
        
        //$location = 'http://10.0.0.101:8999/onvif/media_service';
        
        $wsdlUrl = "https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/devicemgmt.wsdl";
        //$wsdlUrl = 'https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/ptz.wsdl';
        
        //$wsdlUrl = "https://raw.githubusercontent.com/quatanium/python-onvif/master/wsdl/media.wsdl";
        
        $client = new SoapClient($wsdlUrl, array(
            
            // 'location' => $location,
            //'uri'      => 'http://www.onvif.org/ver10/media/wsdl/GetStreamUri',
            'soap_version' => SOAP_1_1,
            'style' => SOAP_RPC,
            'use' => SOAP_ENCODED,
            'encoding' => 'UTF-8',
            'connection_timeout' => 5000,
            'trace' => 1,
        ));
        
        $client->__setSoapHeaders($headers);
        $client->__setLocation($location);
        
        //$result = $client->__soapCall("GetDNS",array());
//             if (!is_soap_fault($client->__getLastRequestHeaders())) {
//                 trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
//             }

        //    bp($client->__getFunctions());
        // $client->__soapCall("GetDNS", array());
         $client->__soapCall("GetDeviceInformation", array());
        //$client->__soapCall("GetVideoSources", array());
       // bp($client->__soapCall("GetProfiles", array())); //egyptStandardTime-2
       //     bp($client->__soapCall("GetServiceCapabilities",array()));
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

/*
        $x = Request::get('x', 'float');
        $y = Request::get('y', 'float');
        $zoom = Request::get('zoom', 'float');
        
        $client->__soapCall(
                'ContinuousMove',
                array(
                    'parameters' => array(
                        'ProfileToken' => 'SubStream',
                    //'ProfileToken' => 'Profile_1',
                        
                        //      'PTZConfigurationToken'=>'PTZCFG_CH0',
                        'Velocity' => array('PanTilt' => array('x' => $x, 'y' => $y), 'Zoom' => array('x' => $zoom))
                    )
                )
            );
        usleep(400000);
        $client->__soapCall(
                'Stop',
                array(
                    'parameters' => array(
                        'ProfileToken' => 'SubStream',
                        
                        //      'PTZConfigurationToken'=>'PTZCFG_CH0',
                        'PanTilt' => true,
                        'Zoom' => true
                    )
                )
            );

*/
//            PanTilt y="0" x="0"/><tt:Zoom x="0"

//             $client->__soapCall("GetStatus",
//                 array('parameters' => array( 'ProfileToken'=>'SubStream')
//                     )
//             );
        
        $response = $client->__getLastResponse();
        
        $xml = simplexml_load_String($response);
        echo htmlspecialchars($xml->asXml()). '<br><br>';
        
        //$xml1 = $xml->children('SOAP-ENV', true)->Body->children('tds', true)->GetDNSResponse->children('tds', true)->DNSInformation->children('tt', true);
        //$xml1 = $xml->children('SOAP-ENV', true)->Body->children('tptz', true);
        die;
        bp($xml1);
    }
    
    public function act_Test1()
    {
        requireRole('debug');
        try {
            $HeaderSecurity = array(
                "UsernameToken" => array(
                    "Username" => 'user',
                    "Password" => 'Admin555',
                    'Nonce'    => '',
                    'Created'  => '',
                ),
            );
            
            $headers = array();
            //$headers[] = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd", "Security", $HeaderSecurity, true);
            
            $location = 'http://11.0.0.110:8000/onvif/device_service';
            
            $client = new SoapClient(null, array(
                'location' => $location,
                'uri'      => $location,
                'soap_version'   => SOAP_1_2,
                //'style'    => SOAP_DOCUMENT,
                //'use'      => SOAP_ENCODED,
                'encoding' => 'UTF-8',
                'trace'    => 1,
            ));
            
            $client->__setSoapHeaders($headers);
            
            $result = $client->__soapCall(
                'GetWsdlUrl',
                array()
                );
            
            var_dump($result);
        } catch (Exception $e) {
            echo '<pre>';
            var_dump($e);
            var_dump($client->__getLastRequestHeaders());
            var_dump($client->__getLastRequest());
            var_dump($client->__getLastResponse());
        }
    }
}
