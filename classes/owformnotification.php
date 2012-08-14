<?php 


class owFormNotification {
	
	var $site_ini;
	var $owform_ini;
	var $object;
	
	const HTML_CONTENT_TYPE = 'text/html';
	const TEXT_CONTENT_TYPE = 'text/plain';
	
	public function __construct( $object )
    {
	    if ( $object instanceof eZContentObject ) {
	        $this->site_ini = eZIni::instance('site.ini');
	        $this->owform_ini = eZIni::instance('owform.ini');
	        $this->object = $object;
	    } else {
	    	$this->error( 'Object is not an eZContentObject instance.' );
	    	return false;
	    }
    }
	
    
    /**
     * Send notifications
     * 
     * @return boolean
     */
	public function send ( )
    {
		$return = true;
        $content_type = $this->getEmailContentType();
		$sender = $this->getEmailSender();
		
        $receivers = $this->getReceivers();
		
        // Generate mail body for each receiver attribute
        foreach ( $receivers as $attributeIdentifier => $mailArray ) {
        	
        	$subject = $this->getEmailSubject( $attributeIdentifier );
        	$body = $this->getEmailBody( $subject, $this->object, $content_type, $attributeIdentifier );
        	
        	// Send mail at each receiver
        	foreach ( $mailArray as $mailAddress ) {
        		if ( $mailAddress ) {
			        $mail = new eZMail();
			        $mail->setContentType( $content_type );
			        $mail->setReceiver( $mailAddress );
			        $mail->setSender( $sender );
			        $mail->setSubject( $subject );
			        $mail->setBody( $body );
			        
			        $result = eZMailTransport::send( $mail );
			        $return = ($return && $result);
			        if ( !$result ) {
			        	$this->error( 'Error when sending notification at address :' . $mailAddress );
			        }
        		}
        	}
    	}
        
        return $return;
    }
    
    /**
     * Get email sender
     *
     * @return string
     */
	protected function getEmailSender()
    {
        if($this->site_ini->hasVariable( 'MailSettings', 'EmailSender' ))
        {
            $email_sender = $this->site_ini->variable( 'MailSettings', 'EmailSender' );
        }
        else
        {
            $email_sender = $this->site_ini->variable( "MailSettings", "AdminEmail" );
        }
        return $email_sender;
    }
	
    /**
     * Get subject for an attribute identifier
     *
     * @param string $attributeIdentifier
     * @return string
     */
    protected function getEmailSubject( $attributeIdentifier='default' )
    {
    	$subjectMap = $this->owform_ini->variable('NotificationSettings', 'Subjects');
        
    	// Get template file name, based on attribute identifier
        if ( $subjectMap[$attributeIdentifier] ) {
        	$subject = $subjectMap[$attributeIdentifier];
        } else if ( $subjectMap['default'] ) {
        	$subject = $subjectMap['default'];
        } else {
        	$this->error('No subject settings.');
        	return false;
        }
        return $subject;
    }
	
    /**
     * Get mail content type
     *
     * @return string
     */
    protected function getEmailContentType()
    {
        return $this->site_ini->hasVariable('MailSettings', 'ContentType') ? $this->site_ini->variable('MailSettings', 'ContentType') : self::HTML_CONTENT_TYPE;
    }

	
    /**
     * Returns an associative array contains all receivers, grouped by attribute identifier
     *
     * @return array
     */
    protected function getReceivers( )
    {
    	$receivers = array();
    	
    	$receiverAttributeIdentifierList = $this->getReceiverAttributeIdentifierList();
    	$dataMap = $this->object->dataMap();
    	
    	foreach ( $receiverAttributeIdentifierList as $receiverAttributeIdentifier ) {
        	$receiverAttribute = $dataMap[$receiverAttributeIdentifier];
        	if ( $receiverAttribute instanceof eZContentObjectAttribute ) {
	        	if ( $receiverAttribute->value() ) {
	        		$receivers[$receiverAttributeIdentifier][] = $receiverAttribute->value();
	        	}
        	}
    	}
    	
    	if ( $this->owform_ini->hasVariable('NotificationReceivers', 'AdditionalReceivers') ) {
    		$receivers['default'] = (array)$this->owform_ini->variable('NotificationReceivers', 'AdditionalReceivers');
    	}
    	
    	return $receivers;
    }
	
    
    /**
     * Get all attribute identifier to fetch, to generate receiver list
     *
     * @return array
     */
    protected function getReceiverAttributeIdentifierList( ) {
    	
    	$classIdentifier = $this->object->contentClassIdentifier();
    	
    	if ( $this->owform_ini->hasVariable( 'NotificationReceivers', $classIdentifier ) ) {
    		$classReceiverAttributeArray = $this->owform_ini->variable('NotificationReceivers', $classIdentifier);
    		if ( count( $classReceiverAttributeArray ) ) {
    			return $classReceiverAttributeArray;
    		}
    	}
    	
    	return $this->owform_ini->variable('NotificationReceivers', 'DefaultAttributes');
    }
    
    
    /**
     * Get content of template, based on attribute identifier settings map
     *
     * @param string $subject
     * @param eZContentObject $content_object
     * @param string $content_type
     * @param string $attributeIdentifier
     * @return string
     */
    protected function getEmailBody( $subject, $content_object, $content_type=self::HTML_CONTENT_TYPE, $attributeIdentifier='default' )
    {

        if ( $content_type == self::HTML_CONTENT_TYPE ) {
        	$templateMap = $this->owform_ini->variable('NotificationSettings', 'HtmlTemplates');
        } else {
        	$templateMap = $this->owform_ini->variable('NotificationSettings', 'TextTemplates');
        }
        
        if ( $templateMap[$attributeIdentifier] ) {
        	$template_file = $templateMap[$attributeIdentifier];
        } else if ( $templateMap['default'] ) {
        	$template_file = $templateMap['default'];
        } else {
        	$this->error('No template settings.');
        	return false;
        }
        
        $tpl = templateInit();
        $tpl->setVariable( 'subject', $subject );
        $tpl->setVariable( 'content_object', $content_object );
        $tpl->setVariable( 'hostname', eZSys::hostname() );
        
        return $tpl->fetch( 'design:' . $template_file );
    }
    
    /**
     * Display error message
     *
     * @param string $msg
     * @return boolean
     */
	protected function error ( $msg ) {
		
		if ($msg) {
			eZDebug::writeError( "[OWForm] : " . $msg );
			return true;
		} else {
			return false;
		}
		
	}
}

?>