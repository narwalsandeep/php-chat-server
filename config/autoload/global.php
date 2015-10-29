<?php
return array (
	'db' => array (
		'driver' => 'Pdo',
		'driver_options' => array (
			1002 => 'SET NAMES \'UTF8\'' 
		),
		'adapters' => array (
			'db' => array () 
		),
	),
	'service_manager' => array (
		'abstract_factories' => array (
			0 => 'Zend\\Db\\Adapter\\AdapterAbstractServiceFactory' 
		),
		'factories' => array (
			'Zend\\Db\\Adapter\\Adapter' => 'Zend\\Db\\Adapter\\AdapterServiceFactory' 
		) 
	) 
);
