<?php

	if( isset( $_POST['sendit'] ) )
	{
		include_once( '../lib/class_sendamail.php' );
		
		$m = new SendAMail();
		$m->eol( "\r\n" );
		$m->from( $_POST['from'] );
		$m->to( $_POST['to'] );
		$m->subject( 'Blender is cool!' );
		$m->html( file_get_contents( 'template/template.html' ) );
		
		if( !$m->embed( 'template/sintel-wallpaper-dragon.jpg' ) ) {
			exit( "<p>Error: {$m->error}" );
		}
		
		if( !$m->embed( 'template/sintel-wallpaper-ishtar.jpg' ) ) {
			exit( "<p>Error: {$m->error}" );
		}
		
		if( !$m->attach( 'template/doc.txt' ) ) {
			exit( "<p>Error: {$m->error}" );
		}
				
		if( $r = $m->send() ) {
			echo "<p>Okidokie :)";
		}
		else {
			echo "<p>Error: {$m->error}";
		}
	}
	else
	{
?>
		<form method="POST" action="htmltemplate.php" enctype="multipart/form-data">
			<input type="hidden" name="sendit" value="1" />
			
			<p>e-mail from: <input type="text" name="from" value="" />
			<p>e-mail to: <input type="text" name="to" value="" />
			<p><input type="submit" value="send" />
		</form>
<?php
	}
?>