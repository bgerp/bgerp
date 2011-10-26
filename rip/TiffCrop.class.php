<?php


/**
 * Кропване
 */
class rip_TiffCrop
{
	
	
	var $interfaces = 'rip_FileProcessingIntf'; 
	
	
	/**
	 * Кропване на файл
	 */
	function processFile($fileId, $id, $combined)
	{
		$process = cls::get('rip_Process');
		$fh = $process->getFh($fileId);
		$outName = $process->newName($fh, 'crop');
		ini_set('memory_limit', '2000M');
		$cropOffset = 480;
		
		$script = new fconv_Script();
		$outPath = $script->tempDir . $outName;
		$returnLog = $script->tempDir . 'log.log';
		
		$script->setFile('INPUTF', "{$fh}");
    	$script->setFile('OUTF', "{$outPath}");
    	$script->setFile('RETLOG', $returnLog);
    	
    	$script->setProgram('tiff-crop-static',TIFF_CROP_STATIC);
    	$script->lineExec("tiff-crop-static [#INPUTF#] -p $cropOffset [#OUTF#] > [#RETLOG#]");
    	$script->callBack('rip_Process::copyFiles');
    	$script->outFileName = $outName;
    	$script->inFileName = $process->getFileName($fh);
    	$script->currentDir = rip_Directory::getCurrent();
    	$script->fileId = $fileId;
    	$script->processId = $id;
    	$script->returnLog = $returnLog;
    	$script->combined = $combined;
    	$script->run();
	}
}