<?php

namespace Chat;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

/**
 *
 * @author Sandeepn
 *        
 */
class Module {
	/**
	 *
	 * @param MvcEvent $e        	
	 */
	public function onBootstrap(MvcEvent $e) {
		$eventManager = $e->getApplication ()->getEventManager ();
		$moduleRouteListener = new ModuleRouteListener ();
		$moduleRouteListener->attach ( $eventManager );
		$eventManager->getSharedManager ()->attach ( __NAMESPACE__, \Zend\Mvc\MvcEvent::EVENT_DISPATCH, array (
			$this,
			'onDispatch' 
		) );
	}
	
	/**
	 */
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	
	/**
	 *
	 * @return multitype:multitype:multitype:string
	 */
	public function getAutoloaderConfig() {
		return array (
			'Zend\Loader\StandardAutoloader' => array (
				'namespaces' => array (
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__ 
				) 
			) 
		);
	}
	
	/**
	 *
	 * @param MvcEvent $e        	
	 */
	public function onDispatch(MvcEvent $e) {
	}
}
