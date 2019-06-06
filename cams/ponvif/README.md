# ponvif

Fork of https://github.com/ltoscano/ponvif with WS-Discovery implementation plus some code fixes.

ONVIF PHP implementation

This software module can control network video devices with ONVIF protocol (HTTP SOAP requests) and scan network for supported devices via UDP multicast.

## Usage

### Discovery

```php
<?php

require 'class.ponvif.php';

$onvif = new Ponvif();
$result = $onvif->discover();

var_dump($result);
```
Example result (Hikvision and Dahua IP cameras)
```php
array (size=2)
  0 => 
    array (size=6)
      'EndpointReference' => 
        array (size=1)
          'Address' => string 'urn:uuid:2925be82-4d50-11b4-82c8-c42f905c18f8' (length=45)
      'Types' => string 'dn:NetworkVideoTransmitter tds:Device' (length=37)
      'Scopes' => string 'onvif://www.onvif.org/type/video_encoder onvif://www.onvif.org/Profile/Streaming onvif://www.onvif.org/type/audio_encoder onvif://www.onvif.org/hardware/RVi-IPC11S onvif://www.onvif.org/name/RVi-IPC11S onvif://www.onvif.org/location/' (length=233)
      'XAddrs' => string 'http://192.168.1.205/onvif/device_service http://[fe80::c62f:90ff:fe5c:18f8]/onvif/device_service' (length=97)
      'MetadataVersion' => string '10' (length=2)
      'IPAddr' => string '192.168.1.205' (length=13)
  1 => 
    array (size=6)
      'EndpointReference' => 
        array (size=1)
          'Address' => string 'uuid:2e15cbab-9b44-4074-836d-0bccd8632b3f' (length=41)
      'Types' => string 'dn:NetworkVideoTransmitter' (length=26)
      'Scopes' => string 'onvif://www.onvif.org/location/country/Russia onvif://www.onvif.org/name/RVi onvif://www.onvif.org/hardware/RVi-IPC33M onvif://www.onvif.org/Profile/Streaming onvif://www.onvif.org/type/Network_Video_Transmitter onvif://www.onvif.org/extension/unique_identifier' (length=261)
      'XAddrs' => string 'http://192.168.1.201/onvif/device_service' (length=41)
      'MetadataVersion' => string '1' (length=1)
      'IPAddr' => string '192.168.1.201' (length=13)
```

### Discovery options
setDiscoveryTimeout(5) - timeout for device response; default "2"

setDiscoveryBindIp('192.168.1.5') - choose ethernet card for discovery request; default "0.0.0.0"

setDiscoveryHideDuplicates(false) - disable duplicate filtering (some devices may send more than one response); default "true"


### Get media streams

```php
<?php

require 'class.ponvif.php';

$onvif = new Ponvif();
$onvif->setUsername('admin');
$onvif->setPassword('password');
$onvif->setIPAddress('192.168.1.108');

// In some cases you need to set MediaUrl manually. You can find it in "XAddrs" key (see above).
// $onvif->setMediaUri('http://192.168.1.108:3388/onvif/device_service');

try
{
	$onvif->initialize();
	
	$sources = $onvif->getSources();
	$profileToken = $sources[0][0]['profiletoken'];
	$mediaUri = $onvif->media_GetStreamUri($profileToken);
	
	var_dump($mediaUri);
}
catch(Exception $e)
{
	
}
```

and more ...

- Get the system date
- Get the system capabilities
- Get the video sources
- Get the existing profiles
- Get the available services
- Get information of the device information
- Get the URI of a stream
- Get the URI to take a snapshot from camera
- Get the available presets
- Get information of a given node
- Go to a given preset
- Remove a given preset
- Set a given preset
- Perform a relative move
- Perform a relative move and zoom
- Perform an absolute move
- Start a continuous move
- Start a continuous move and zoom
- Stop a move
