<?php
return array (
	'router' => array (
		'routes' => array (
			'ch' => array (
				'type' => 'Segment',
				'options' => array (
					'route' => '/ch[/:controller[/:action[/:id]]]',
					'constraints' => array (
						'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*' 
					),
					'defaults' => array (
						'__NAMESPACE__' => 'Chat\Controller',
						'controller' => 'Server',
						'action' => 'index' 
					) 
				) 
			) 
		) 
	),
	'controllers' => array (
		'invokables' => array (
			'Chat\Controller\Server' => 'Chat\Controller\ServerController' 
		) 
	),
	'view_manager' => array (
		'strategies' => array (
			'ViewJsonStrategy' 
		) 
	),
	'console' => array (
		'router' => array (
			'routes' => array (
				'chat-start' => array (
					'options' => array (
						'route' => 'chat start',
						'defaults' => array (
							'controller' => 'Chat\Controller\Server',
							'action' => 'start' 
						) 
					) 
				) 
			) 
		) 
	) 
);
