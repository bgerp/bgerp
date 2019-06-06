<?php

require './lib/class.ponvif.php';

$onvif = new Ponvif();
$onvif->setUsername('admin');
$onvif->setPassword('admin');
$onvif->setIPAddress('192.168.25.155');

// In some cases you need to set MediaUrl manually. You can find it in "XAddrs" key (see above).
$onvif->setMediaUri('http://192.168.25.155:8899/onvif/device_service');

try
{
	$onvif->initialize();
	
	$sources = $onvif->getSources();
	$profileToken = $sources[0][1]['profiletoken'];
	$mediaUri = $onvif->media_GetStreamUri($profileToken);
	
	
	echo "\n\n\n --------------------------------------------- \n\n\n";
	
	// List Encoders & resolutions  (summarized)
	echo "Encoders Available: \n";
	$encodersList = $onvif->getCodecEncoders('H264');
	foreach ( $encodersList[0] as $enc ) {
		$avail_fps = implode("-", $enc['FrameRateRange']);
		foreach ( $enc['ResolutionsAvailable'] as $res ) {
			echo "    -> {$res['Width']}x{$res['Height']}  $avail_fps  (Encoder: {$enc['profileToken']})\n";
		}
	}
	
	
	// Delete all OSDs
	$OSDs = $onvif->media_GetOSDs();
	foreach( $OSDs as $osd ) {
		if ( isset($osd['@attributes']['token']) ) {
			$onvif->media_DeleteOSD($osd['@attributes']['token']);
		}
	}	
	
	
	// Show Available options for encoder relative to '$profileToken'
	$VideoEncoderConfigurationOpts = $onvif->media_GetVideoEncoderConfigurationOptions($profileToken);
	print_r($VideoEncoderConfigurationOpts);
	
	
	// Get Atual Encoder Options 
	$VEC = $onvif->media_GetVideoEncoderConfigurations($profileToken);
	print_r($VEC);
	
	// Make Changes
	$VEC['Quality'] = 6;
	$VEC['Resolution']['Width'] = 352;
	$VEC['Resolution']['Height'] = 240;
	$VEC['RateControl']['FrameRateLimit'] = 10;
	$VEC['RateControl']['BitrateLimit'] = 1000;
	$VEC['H264']['H264Profile'] = 'High';
	
	// Save Changes!
	$onvif->media_SetVideoEncoderConfiguration($VEC);
	
	// Now, we can start streaming!
	echo "\n\n    ->  $mediaUri \n\n";
	
}
catch(Exception $e)
{
	echo "erro\n";
// 	print_r($e);
}

?>
