<html>
<head>
	<title>SendAMail - Simple example</title>
</head>

<body>
<?php

	if( isset( $_POST['sendit'] ) )
	{
		include_once( '../lib/class_sendamail.php' );
		
		$m = new SendAMail();
		$m->from( $_POST['from'] );
		$m->to( $_POST['to'] );
		$m->subject( $_POST['subject'] );
		$m->text( $_POST['text'] );
		
		if( $m->send() ) {
			echo "Your message has been send :)";
		}
		else {
			echo "Whoops! {$m->error}";
		}
	}
	else
	{
?>
		<form method="POST" action="simple.php">
			<input type="hidden" name="sendit" value="1" />
			
			<p>e-mail from: <input type="text" name="from" value="" />
			<p>e-mail to: <input type="text" name="to" value="" />
			<p>subject: <input type="text" name="subject" value="" />
			<p>text: <textarea name="text"></textarea>
			<p><input type="submit" value="send" />
		</form>
<?php
	}
?>
</body>
</html>