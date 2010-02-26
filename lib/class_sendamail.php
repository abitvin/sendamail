<?php

	class SendAMail
	{
		public $error = 'No errors.';
	
		private $attach = false;
		private $bcc = false;
		private $bounce = false;
		private $cc = false;
		private $embed = false;
		private $eol = "\n"; 
		private $html = false;
		private $subject = false;
		private $text = false;
		
		private $mailHead = '';
		private $mailBody = '';
		private $mailBound = array();
		private $curBound = '';
		
		
		
		private function addFile( &$files, $file )
		{
			$f = false;
			
			if( is_string( $file ) )
			{
				$f = array(
					"file" => $file,
					"name" => basename( $file )
				);
			}
			elseif( is_array( $file ) )
			{
				switch( $file['error'] )
				{
					case 1:	return $this->error( 'The file is bigger than this PHP installation allows' );
					case 2: return $this->error( 'The file is bigger than this form allows' );
					case 3: return $this->error( 'Only part of the file was uploaded' );
					case 4: return $this->error( 'No file was uploaded' );
				}
			
				$f = array(
					"file" => $file['tmp_name'],
					"name" => $file['name']
				);
			}
						
			if( !$f ) {
				return $this->error( 'File is no file.' );
			}
			
			if( !$files ) {
				$files = array( $f );
			}
			else {
				$files[] = $f;
			}
						
			return true;
		}
		
		private function addToArray( &$arr, &$var )
		{
			if( !$arr ) {
				$arr = array();
			}
			
			if( is_string( $var ) ) {
				$arr[] = $var;
			}
			elseif( is_array( $var ) ) {
				$arr = array_merge( $arr, $var );
			}
		}
		
		private function br2nl( &$html )
		{
			return preg_replace( '%<br\s*/?>%', "\n", str_replace( '<p>', "\n\n", $html ) );
		}
		
		private function cleanUp( &$var )
		{
			if( is_string( $var ) )
			{
				$var = trim( $var );
				
				if( $var == '' ) {
					return false;
				}
			}
			elseif( is_array( $var ) )
			{
				$tmp = array();
			
				foreach( $var as &$v )
				{
					if( in_array( $v, $tmp ) ) {
						continue;
					}
					
					$v = trim( $v );
					
					if( $v == '' ) {
						continue;
					}
					
					$tmp[] = $v;
				}
				
				if( count( $var = $tmp ) == 0 ) {
					return false;
				}
			}
			else
			{
				return false;
			}
			
			return $var;
		}
		
		private function getMime( $file = false )
		{
			if( !file_exists( $file ) ) {
				return 'application/octet-stream';
			}
			
			$mime = mime_content_type( $file );
					
			if( !$mime ) {
				$mime = trim( `file -bi '$file'` );
			}
			
			if( !$mime ) {
				$mime = 'application/octet-stream';
			}
			
			return $mime;
		}
		
		private function error( $msg )
		{
			$this->error = $msg;
			return false;
		}
		
		
		
		private function composeAttach()
		{
			if( !$this->attach ) {
				return;
			}
		
			foreach( $this->attach as &$f )
			{
				if( !$data = file_get_contents( $f['file'] ) ) {
					return $this->error( "Could not open attachment file '{$f['file']}'." );
				}
				
				$data = chunk_split( base64_encode( $data ) );
				$type = $this->getMime( $f['file'] );
									
				$this->composeBodyHead( "--{$this->curBound}" );
				$this->composeBodyHead( "Content-Type: $type" );
				$this->composeBodyHead( "Content-Transfer-Encoding: base64" );
				$this->composeBodyHead( "Content-Disposition: attachment; filename=\"{$f['name']}\"" );
				$this->composeBodyData( $data );
			}
		}
		
		private function composeBegin()
		{
			$this->mailHead = '';
			$this->mailBody = '';
			$this->mailBound = array();
			$this->curBound = '';
		}
		
		private function composeBodyHead( $item )
		{
			$this->mailBody .= trim( $item ) . $this->eol;
		}
		
		private function composeBodyData( $data )
		{
			$this->mailBody .= $this->eol . $data . $this->eol . $this->eol;
		}
		
		private function composeBodyMimeMsg( $msg )
		{
			$this->mailBody .= $this->eol . trim( $msg ) . $this->eol . $this->eol;
		}
		
		private function composeEmbed()
		{
			if( !$this->embed ) {
				return;
			}
			
			foreach( $this->embed as &$f )
			{
				if( !$data = file_get_contents( $f['file'] ) ) {
					return $this->error( "Could not open attachment file '{$f['file']}'." );
				}
				
				$data = chunk_split( base64_encode( $data ) );
				$type = $this->getMime( $f['file'] );
									
				$this->composeBodyHead( "--{$this->curBound}" );
				$this->composeBodyHead( "Content-Type: $type" );
				$this->composeBodyHead( "Content-Transfer-Encoding: base64" );
				$this->composeBodyHead( "Content-ID: <{$f['name']}>" );
				$this->composeBodyHead( "Content-Disposition: inline; filename=\"{$f['name']}\"" );
				$this->composeBodyData( $data );
			}
		}
		
		private function composeHead( $item )
		{
			$this->mailHead .= trim( $item ) . $this->eol;
		}
		
		private function composeHTML( $html )
		{
			$this->composeBodyHead( "--{$this->curBound}" );
			$this->composeBodyHead( "Content-Type: text/html; charset=ISO-8859-1;" );
			$this->composeBodyHead( "Content-Transfer-Encoding: 7bit" );
			$this->composeBodyData( $html );
		}
		
		private function composePopBoundary()
		{
			$b = array_pop( $this->mailBound );
			$this->mailBody .= "--$b--{$this->eol}{$this->eol}";
			$this->curBound = $this->mailBound[ count( $this->mailBound ) - 1 ];
		}
		
		private function composePushBoundary( $type )
		{
			$b = md5( time() . rand( 0, 7777777 ) );
			
			if( count( $this->mailBound ) == 0 )
			{
				$this->composeHead( "Content-Type: multipart/$type; boundary=$b" );
				$this->composeBodyMimeMsg( 'This is a multi-part message in MIME format.' );
			}
			else
			{
				$this->composeBodyHead( "--{$this->curBound}" );
				$this->composeBodyHead( "Content-Type: multipart/$type; boundary=$b" );
				$this->composeBodyHead( '' );
			}
			
			$this->curBound = $b;
			$this->mailBound[] = $b;
		}
		
		private function composeText( $text )
		{
			$this->composeBodyHead( "--{$this->curBound}" );
			$this->composeBodyHead( "Content-Type: text/plain; charset=ISO-8859-1; format=flowed" );
			$this->composeBodyHead( "Content-Transfer-Encoding: 7bit" );
			$this->composeBodyData( $text );
		}
		
		
		
		public function attach( $file = false )
		{
			return $this->addFile( $this->attach, $file );
		}
		
		public function bcc( $bcc = false )
		{
			$this->addToArray( $this->bcc, $bcc );
		}
		
		public function bounce( $bounce = false )
		{
			if( is_string( $bounce ) ) {
				$this->bounce = $bounce;
			}
			else {
				$this->bounce = false;
			}
		}
		
		public function cc( $cc = false )
		{
			$this->addToArray( $this->cc, $cc );
		}
		
		public function embed( $file = false )
		{
			return $this->addFile( $this->embed, $file );
		}
		
		public function eol( $eol = "\n" )
		{
			$this->eol = $eol;
		}
		
		public function from( $from = false )
		{
			if( is_string( $from ) ) {
				$this->from = $from;
			}
			else {
				$this->from = false;
			}
		}
		
		public function html( $html = false )
		{
			if( is_string( $html ) ) {
				$this->html = $html;
			}
			else {
				$this->html = false;
			}
		}
		
		public function proxy()
		{
			$this->bounce( $_POST['sendmailproxy_bounce'] );
			$this->from( $_POST['sendmailproxy_from'] );
			$this->html( $_POST['sendmailproxy_html'] );
			$this->subject( $_POST['sendmailproxy_subject'] );
			$this->text( $_POST['sendmailproxy_text'] );
			
			$i = 0;
			
			while( isset( $_POST["sendmailproxy_bcc_$i"] ) ) {
				$this->bcc( $_POST["sendmailproxy_bcc_$i"] ); $i++;
			}
			
			$i = 0;
			
			while( isset( $_POST["sendmailproxy_cc_$i"] ) ) {
				$this->cc( $_POST["sendmailproxy_cc_$i"] ); $i++;
			}
			
			$i = 0;
			
			while( isset( $_POST["sendmailproxy_to_$i"] ) ) {
				$this->to( $_POST["sendmailproxy_to_$i"] ); $i++;
			}
			
			$i = 0;
			
			while( isset( $_FILES["sendmailproxy_attach_$i"] ) ) {
				$this->attach( $_FILES["sendmailproxy_attach_$i"] ); $i++;
			}
			
			$i = 0;
			
			while( isset( $_FILES["sendmailproxy_embed_$i"] ) ) {
				$this->embed( $_FILES["sendmailproxy_embed_$i"] ); $i++;
			}
			
			return $this->sendMail();
		}
		
		public function sendMail()
		{
			if( !$from = $this->cleanUp( $this->from ) ) {
				return $this->error( 'No from address.' );
			}
			
			if( !$subject = $this->cleanUp( $this->subject ) ) {
				return $this->error( 'No subject.' );
			}
			
			if( !$to = $this->to ) {
				return $this->error( 'No recipient.' );
			}
			
			$text = $this->cleanUp( $this->text );
			$html = $this->cleanUp( $this->html );
			
			if( !$text && !$html ) {
				return $this->error( 'No body.' );
			}
			
			if( !$text ) {
				$text = strip_tags( $this->br2nl( $html ) );
			}
			
			
			if( $cc = $this->cleanUp( $this->cc ) ) {
				$cc = implode( ', ', $cc );
			}
						
			if( $bcc = $this->cleanUp( $this->bcc ) ) {
				$bcc = implode( ', ', $bcc );
			}
						
			if( $to = $this->cleanUp( $to ) ) {
				$to = implode( ', ', $to );
			}
			
			
			$this->composeBegin();
			$this->composeHead( "From: $from" );
			$this->composeHead( "Reply-To: $from" );
			$this->composeHead( "Message-ID: <" . time() . "-$from>" );
			$this->composeHead( "X-Mailer: PHP v" . phpversion() );
			if( $cc ) { $this->composeHead( "Cc: $cc" ); }
			if( $bcc ) { $this->composeHead( "Bcc: $bcc" ); }
			$this->composeHead( 'MIME-Version: 1.0' );
			
			
			if( $text && !$html )
			{
				$this->composePushBoundary( 'mixed' );
				$this->composeText( $text );
				$this->composeAttach();
				$this->composePopBoundary();
			}
			elseif( $this->attach && $this->embed )
			{
				$this->composePushBoundary( 'mixed' );
				$this->composePushBoundary( 'alternative' );
				$this->composeText( $text );
				$this->composePushBoundary( 'related' );
				$this->composeHTML( $html );
				$this->composeEmbed();
				$this->composePopBoundary();
				$this->composePopBoundary();
				$this->composeAttach();
				$this->composePopBoundary();
			}
			elseif( $this->attach )
			{
				$this->composePushBoundary( 'mixed' );
				$this->composePushBoundary( 'alternative' );
				$this->composeText( $text );
				$this->composeHTML( $html );
				$this->composePopBoundary();
				$this->composeAttach();
				$this->composePopBoundary();
			}
			elseif( $this->embed )
			{
				$this->composePushBoundary( 'alternative' );
				$this->composeText( $text );
				$this->composePushBoundary( 'related' );
				$this->composeHTML( $html );
				$this->composeEmbed();
				$this->composePopBoundary();
				$this->composePopBoundary();
			}
			else
			{
				$this->composePushBoundary( 'alternative' );
				$this->composeText( $text );
				$this->composeHTML( $html );
				$this->composePopBoundary();
			}
									
			
			if( $this->bounce ) {
				$addi = "-f {$this->bounce}";
			}
			else {
				$addi = '';
			}
			
			
			ini_set( "sendmail_from", $from ); 
			$ok = mail( $to, $subject, $this->mailBody, $this->mailHead, $addi );
			ini_restore( "sendmail_from" );
			
			if( !$ok ) {
				$this->error( 'PHP mail() returned false.' );
			}
			
			if( $f = fopen( 'dump.txt', 'wb' ) )
			{
				fwrite( $f, "To: $to{$this->eol}{$this->mailHead}{$this->mailBody}" );
				fclose( $f );
			}
			
			return $ok;
		}
		
		public function sendMailProxy( $url = false )
		{
			$eol = $this->eol;
			$d = '';
			$b = md5( time() );
			
			$post = array();
			$post['sendmailproxy_bounce'] = $this->bounce;
			$post['sendmailproxy_from'] = $this->from;
			$post['sendmailproxy_html'] = $this->html;
			$post['sendmailproxy_subject'] = $this->subject;
			$post['sendmailproxy_text'] = $this->text;
						
			if( $this->bcc )
			{
				$i = 0;
			
				foreach( $this->bcc as &$v ) {
					$post["sendmailproxy_bcc_$i"] = $v; $i++;
				}
			}
			
			if( $this->cc )
			{
				$i = 0;
				
				foreach( $this->cc as &$v ) {
					$post["sendmailproxy_cc_$i"] = $v; $i++;
				}
			}
			
			if( $this->to )
			{			
				$i = 0;
				
				foreach( $this->to as &$v ) {
					$post["sendmailproxy_to_$i"] = $v; $i++;
				}
			}
			
			foreach( $post as $k => &$v )
			{
				$d .= "--$b$eol";
				$d .= "Content-Disposition: form-data; name=\"$k\"$eol";
				$d .= 'Content-Length: ' . strlen( $v ) . "$eol$eol";
				$d .= "$v$eol$eol";
			}
			
			if( $this->attach )
			{
				$i = 0;
				
				foreach( $this->attach as &$f )
				{
					$file = $f['file'];
					
					if( !$data = file_get_contents( $file ) ) {
						return $this->error( "Could not open attachment file '$file'." );
					}
					
					$name = $f['name'];
					$type = $this->getMime( $file );
					$size = filesize( $file );
					
					$d .= "--$b$eol";
					$d .= "Content-Disposition: form-data; name=\"sendmailproxy_attach_$i\"; filename=\"$name\"$eol";
					$d .= "Content-Type: $type$eol";
					$d .= "Content-Length: $size$eol";
					$d .= "Content-Transfer-Encoding: binary$eol$eol";
					$d .= "$data$eol";
										
					$i++;
				}
			}
			
			if( $this->embed )
			{
				$i = 0;
				
				foreach( $this->embed as &$f )
				{
					$file = $f['file'];
					
					if( !$data = file_get_contents( $file ) ) {
						return $this->error( "Could not open embedded file '$file'." );
					}
					
					$name = $f['name'];
					$type = $this->getMime( $file );
					$size = filesize( $file );
					
					$d .= "--$b$eol";
					$d .= "Content-Disposition: form-data; name=\"sendmailproxy_embed_$i\"; filename=\"$name\"$eol";
					$d .= "Content-Type: $type$eol";
					$d .= "Content-Length: $size$eol";
					$d .= "Content-Transfer-Encoding: binary$eol$eol";
					$d .= "$data$eol";
										
					$i++;
				}
			}
						
			$d .= "--$b--$eol$eol";
			
			
			$parm = array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-Type: multipart/form-data; boundary=$b$eol",
					'content' => $d
				)
			);
			
			$ctx = stream_context_create( $parm );
			$f = fopen( $url, 'rb', false, $ctx );
			
			if( !$f ) {
				return $this->error( "Problem with $url, $php_errormsg" );
			}
		 
			$r = @stream_get_contents( $f );
		   
			if( $r === false ) {
				return $this->error( "Problem reading data from $url, $php_errormsg" );
			}
			
			return $r;
		}
		
		public function subject( $subject = false )
		{
			if( is_string( $subject ) ) {
				$this->subject = $subject;
			}
			else {
				$this->subject = false;
			}
		}
		
		public function text( $text = false )
		{
			if( is_string( $text ) ) {
				$this->text = $text;
			}
			else {
				$this->text = false;
			}
		}
		
		public function to( $to = false )
		{
			$this->addToArray( $this->to, $to );
		}
	}

?>