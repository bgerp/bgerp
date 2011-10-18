<?php

/**
 * Кропване и Ембосиране старо
 */
class rip_TiffCropEmbossingOld
{
	var $interfaces = 'rip_FileProcessingIntf'; 
	
	
	/**
 	* Кропване + Ембосиране
 	*/
	function processFile($fileId, $id)
	{
		
		$TiffCrop = cls::get('rip_TiffCrop');
		$TiffCrop->processFile($fileId, $id, 'embossingOld');
				
	}
}
