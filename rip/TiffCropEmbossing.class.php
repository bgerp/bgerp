<?php

/**
 * Кропване и Ембосиране
 */
class rip_TiffCropEmbossing
{
	var $interfaces = 'rip_FileProcessingIntf'; 
	
	
	/**
 	* Кропване + Ембосиране
 	*/
	function processFile($fileId, $id)
	{
		
		$TiffCrop = cls::get('rip_TiffCrop');
		$TiffCrop->processFile($fileId, $id, 'embossing');
				
	}
}
