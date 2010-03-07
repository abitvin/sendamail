<?php

	if( isset( $_POST['sendit'] ) )
	{
		include_once( '../lib/class_sendamail.php' );
		
		$m = new SendAMail();
		$m->eol( "\r\n" );
		$m->from( $_POST['from'] );
		$m->to( $_POST['to'] );
		$m->subject( $_POST['subject'] );
		$m->text( $_POST['text'] );
				
		if( $ret = $m->sendProxy( "http://a.host.net/mailproxy.php" ) ) {
			echo "<p>Okidokie: $ret";
		}
		else {
			echo "<p>Error: $ret, {$m->error}";
		}
	}
	else
	{
?>
		<form method="POST" action="simpleproxy.php" enctype="multipart/form-data">
			<input type="hidden" name="sendit" value="1" />
			
			<p>from: <input type="text" name="from" value="" />
			<p>to: <input type="text" name="to" value="" />
			<p>subject: <input type="text" name="subject" value="Proxy mail test" />
			<p>text: <textarea name="text"></textarea>
			<p><input type="submit" value="send mail" />
		</form>
<?php
	}
?>