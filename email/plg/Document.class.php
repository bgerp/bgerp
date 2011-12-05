<?php
class email_plg_Document extends core_Plugin
{
	function on_AfterPrepareSingleToolbar($mvc, $res, $data)
	{
		$data->toolbar->addBtn('Изпращане', array('email_Sent', 'add', 'ret_url'=>true, 'containerId'=>$data->rec->containerId));
	}
}