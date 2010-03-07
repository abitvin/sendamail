<?php
	
	// NOTE: See simpleproxy.php for usage
	
	include_once( '../lib/class_sendamail.php' );
	
	$m = new SendAMail();
	echo $m->proxy();

?>