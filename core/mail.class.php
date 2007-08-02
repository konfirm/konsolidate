<?php


	class CoreMail extends Konsolidate
	{
		protected	$from;
		protected	$to;
		protected	$cc;
		protected	$bcc;
		protected	$attachment;
		
		public function __construct( $oParent )
		{
			parent::__construct( $oParent );
			
			$this->import( "mail/exception.class.php" );
			$this->reset();
		}
		
		public function addFrom( $oContact )
		{
			if ( $this->_checkContact( $oContact ) )
			{	
				$this->from = $oContact;
				return true;
			}
			return false;
		}
		
		public function addTo( $oContact )
		{
			if ( $this->_checkContact( $oContact ) )
			{	
				array_push( $this->to, $oContact );
				return true;
			}
			return false;
		}
		
		public function addCC( $oContact )
		{
			if ( $this->_checkContact( $oContact ) )
			{	
				array_push( $this->cc, $oContact );
				return true;
			}
			return false;
		}
		
		public function addBCC( $oContact )
		{
			if ( $this->_checkContact( $oContact ) )
			{	
				array_push( $this->bcc, $oContact );
				return true;
			}
			return false;
		}
		
		public function addAttachment( $sFile )
		{
			if ( file_exists( $sFile ) )
			{
				array_push( $this->attachment, $sFile );
				return true;
			}
			throw new CoreMailException( sprintf( $this->call('/language/get', 'File not found %1' ), $sFile ) );
		}
		
		public function reset()
		{
			$this->from 		= null;
			$this->to			= Array();
			$this->cc			= Array();
			$this->bcc			= Array();
			$this->attachment	= Array();
			return true;
		} 
		
		public function send()
		{
			if ( is_null( $this->from ) )
				throw new CoreMailException( $this->call('/language/get', 'No return address specified' ) );
				
			if ( count( $this->to ) == 0 && count( $this->cc ) == 0 && count( $this->bcc) == 0 )
				throw new CoreMailException( $this->call('/language/get', 'No recipient specified' ) );
		
			if ( !class_exists('phpmailer') )
			{
				if (!defined('MAIL_CLASSFILE') )
				{	
					throw new CoreMailException( $this->call('/language/get', 'php mail path not defined' ) );
					return false;
				}
				
				include_once( MAIL_CLASSFILE );
				
				if ( !class_exists('phpmailer') )
				{
					throw new CoreMailException( $this->call('/language/get', 'phpmailer class not found' ) );
					return false;
				}
			}
			
			$oMail = new phpMailer();
			$oMail->setLanguage( $this->call('/language/getLanguage') , dirname(MAIL_CLASSFILE) . '/smtp/language/');
			
			$oMail = new phpMailer();
			$oMail->setLanguage('en', NICE_BASEPATH . '/smtp/language/');
			$oMail->from = CoreTools::replaceExtendedChars( $this->from->firstname ) . '<' . $this->from->email . '>';
	
			foreach( $this->to as $oContact )
				$oMail->AddAddress( $oContact->email );
	
			foreach( $this->cc as $oContact )
				$oMail->addCC( $oContact->email );
			
			foreach( $this->bcc as $oContact )
				$oMail->addBCC( $oContact );
	
			foreach( $this->attachment as $file )
				$oMail->AddAttachment( $file );
			
			$oMail->Host     = ( !defined('MAIL_SMTP_SERVERS') ? 'localhost' : MAIL_SMTP_SERVERS );
			$oMail->Mailer   = ( !defined('MAIL_METHOD') ? 'mail' : MAIL_METHOD );
	
			$oMail->Body 	 = $this->get('mail/template/html');
			$oMail->AltBody  = $this->get('mail/template/text');
			$oMail->Subject  = $this->get('mail/template/subject');
			
			if(  !$oMail->Send() )
			{
				throw new CoreMailException( $this->call('/language/get', $this->oMail->ErrorInfo ) );
				return false;
			}
			return true;
		}
		
		private function _checkContact( $oContact )
		{
			if ( !$this->call('/language/isEmail', $oContact->email ) )
			{
				throw new CoreMailException( $this->call('/language/get', 'No e-mail address in the contact' ) );
				return false;
			}
				
			return true;
		}
	}
	
?>