<html>
<head>
	<title>SendAMail - Attachment example</title>
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
				
		if( !$m->attach( $_FILES['attach1'] ) ) {
			exit( "<p>Error: {$m->error}" );
		}
		
		if( !$m->attach( $_FILES['attach2'] ) ) {
			exit( "<p>Error: {$m->error}" );
		}
		
		if( $m->send() ) {
			echo "<p>Okidokie :)";
		}
		else {
			echo "<p>Error: {$m->error}";
		}
	}
	else
	{
?>
		<form method="POST" action="attachment.php" enctype="multipart/form-data">
			<input type="hidden" name="sendit" value="1" />
			
			<p>email from: <input type="text" name="from" value="" />
			<p>email to: <input type="text" name="to" value="" />
			<p>attachment #1: <input type="file" name="attach1" />
			<p>attachment #2: <input type="file" name="attach2" />
			<p>subject: <input type="text" name="subject" value="Cool attachment" />
			<p>text: <textarea name="text">Hi there! Here's a cool attachment :) No it's not a virus! :P</textarea>
			<p><input type="submit" value="send" />
		</form>
<?php
	}
?>
</body>
</html>