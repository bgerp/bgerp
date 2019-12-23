<?php

require './lib/class.ponvif.php';

$onvif = new Ponvif();

$result = $onvif->discover();

print_r($result);
