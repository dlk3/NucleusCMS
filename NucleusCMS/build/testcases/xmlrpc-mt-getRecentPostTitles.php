<?php
/*
	Test call to the nucleus XML-RPC server sending a mt.setPostCategories request
	
	Wouter Demuynck / 2003-09-28
*/

// URL of XML-RPC server
$serverHost = 'localhost';
$serverPort = 8080;
$serverPath = '/nucleus/nucleus/xmlrpc/server.php';
	
include('../../config.php');
include($DIR_LIBS . 'xmlrpc.inc.php');

$f=new xmlrpcmsg(
	'mt.getRecentPostTitles',
	 array(
	 	new xmlrpcval('1', 'string'),			// blogid
	 	new xmlrpcval('example', 'string'),			// username
	 	new xmlrpcval('example', 'string'),		// password
	 	new xmlrpcval(20, 'int')				// amount
	 )
 );
	 

  $c=new xmlrpc_client($serverPath, $serverHost, $serverPort);
  $c->setDebug(1);
  $r=$c->send($f);
  $v=$r->value();


  if (!$r->faultCode()) {
  	echo 'succes!';
  } else {
      print "Fault: ";
      print "Code: " . $r->faultCode() . 
            " Reason '" .$r->faultString()."'<BR>";
  }
	

	
?>