<?php

require_once( 'kernel/common/template.php' );

class sendNotificationType  extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = "sendnotification";
    const HTML_CONTENT_TYPE = 'text/html';

    var $site_ini;

    function __construct()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'extension/ow/workflow/event', 'Send Notification' ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after') ) ) );
        $this->site_ini = eZIni::instance('site.ini');
    }

    

    function execute( $process, $event )
    {
    	$parameters = $process->attribute( 'parameter_list' );
    	$content_object = eZContentObject::fetch( $parameters['object_id'] );
		$owFormNotification = new owFormNotification( $content_object );
        return $owFormNotification->send( ) ? eZWorkflowType::STATUS_ACCEPTED : eZWorkflowType::STATUS_REJECTED;
    }

}

eZWorkflowEventType::registerEventType( sendNotificationType::WORKFLOW_TYPE_STRING, 'sendNotificationType' );

?>