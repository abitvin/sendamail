<?php

	include_once( '../lib/class_sendamail.php' );
	
	
	$m = new SendAMail();
	$m->from( 'bad@usage.net' );
	$m->subject( 'This is a bad example!' );
	$m->text( 'Just don\'t over do it.' );
	
	/* open some BIG, like thousands(!), e-mail addresses list here */
	
	while( /* read entry from list */ ) {
		$m->to( /* entry */ );
	}
	
	/* close list */
	
	
	if( $m->send() ) {
		echo "Your messages have been send...NOT!";
	}
	else {
		echo "Whoops! {$m->error}";
	}

?>