<?php
/**
 * @projectName@
 * 
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id: whmcsautoauth.php 4 2012-02-25 16:00:45Z steven_gohigher $ )
 * @author     @buildAuthor@
 * @since      1.0.0
 * 
 * @desc		Once a user is authenticate, this user redirects to an install
 * 				of WHMCS using the AutoAuth feature found in v4.41 and above of
 * 				WHMCS.
 */

// Check to ensure this file is included in Joomla!
defined ( '_JEXEC' ) or die ( 'Restricted access' );

// Import library dependencies
jimport ( 'joomla.plugin.plugin' );
jimport ( 'joomla.html.parameter' );

/**
 * User - WHMCSAutoauth
 * - User event handler to log user into WHMCS via AutoAuth method
 * - For Joomla 1.5 / 2.5
 * 
 * @version @fileVers@
 * @version 1.0.0 - Initial Release supporting Joomla 1.5 only
 *         
 * @since 1.0.0
 * @author Steven
 */
class plgUserWhmcsautoauth extends JPlugin {
	/**
	 * Stores the version of the plugin for internal use
	 * 
	 * @var integer
	 */
	var $version = '@fileVers@';
	
	/**
	 * Stores the authkey setup in WHMCS for authentication purposes
	 * 
	 * @var string
	 */
	var $authkey = null;
	
	/**
	 * Stores the destination URL to send the user to upon log in
	 * 
	 * @var string
	 */
	var $gotourl = null;
	
	/**
	 * Stores the WHMCS URL
	 * 
	 * @var string
	 */
	var $whmcsurl = null;
	
	/**
	 * Indicates the user should log out when passed through here
	 * 
	 * @var boolean
	 */
	var $canlogout = true;
	
	/**
	 * Constructor method
	 * 
	 * @version @fileVers@ - Support for Joomla 2.5 added
	 * @version 1.0.0 - Support for Joomla 1.5 only
	 * @param unknown_type $subject        	
	 * @param unknown_type $config        	
	 * @since 1.0.0
	 */
	function plgUserWhmcsautoauth(& $subject, $config) {
		$params = new JParameter ( $config ['params'] );
		
		$this->version = $params->get ( "version" );
		$this->authkey = trim ( $params->get ( "authkey" ) );
		$this->gotourl = $params->get ( "gotourl" );
		$this->whmcsurl = $params->get ( "whmcsurl" );
		$this->canlogout = $params->get ( "logoutenable" );
		
		if (version_compare ( JVERSION, '1.6.0', 'ge' )) {
			$this->loadLanguage ();
		}
		
		parent::__construct ( $subject, $config );
	}
	
	/**
	 * Event handler for user login
	 * 
	 * @version @fileVers@
	 *         
	 * @param
	 *        	JUser object	- $user: current user
	 * @param
	 *        	array			- $options: option array passed
	 *        	
	 * @since 2.0.0
	 */
	function onUserLogin($user, $options) {
		// 0: Initialize variables
		$app = & JFactory::getApplication ();
		
		if ($app->isAdmin ())
			return; // Dont run in admin
		
		$email = trim ( $user ['email'] );
		$time = time ();
		$hash = sha1 ( $email . $time . $this->authkey );
		
		$gotourl = trim ( $this->gotourl );
		
		if (! empty ( $gotourl ))
			$goto = $gotourl;
		else
			$goto = false;
		
		$uri = & JURI::getInstance ( $this->whmcsurl );
		$uri->setPath ( rtrim ( $uri->getPath (), "/" ) . '/dologin.php' );
		$uri->setVar ( 'email', $email );
		$uri->setVar ( 'timestamp', $time );
		$uri->setVar ( 'hash', $hash );
		
		if ($goto !== false)
			$uri->setVar ( 'goto', $goto );
		
		$url = $uri->toString ();
		$app->redirect ( $url );
	}
	
	/**
	 * Event handler for user log out routine
	 * 
	 * @version @fileVers@
	 * @param
	 *        	JUser object	- $user: current user
	 * @param
	 *        	array			- $options: option array passed
	 *        	
	 * @since 2.0.0
	 */
	function onUserLogout($user, $options) {
		$app = & JFactory::getApplication ();
		
		if ($app->isAdmin ())
			return; // Dont run in admin
		
		if (! $this->canlogout)
			return; // Don't wanna run logout routine
		
		$uri = & JURI::getInstance ( $this->whmcsurl );
		$uri->setPath ( rtrim ( $uri->getPath (), "/" ) . "/logout.php" );
		
		$url = $uri->toString ();
		$app->redirect ( $url );
	}
	
	/**
	 * Event handler for user log in routine (J!1.5)
	 * 
	 * @version @fileVers@ - Updated to redirect to new routine
	 * @version 1.0.0 - Initial release supported J!1.5 only
	 * @param
	 *        	JUser object	- $user: current user
	 * @param
	 *        	array			- $options: option array passed
	 *        	
	 * @return null
	 * @since 1.0.0
	 */
	function onLoginUser($user, $options) {
		$result = $this->onUserLogin ( $user, $options );
		return $result;
	}
	
	/**
	 * Event handler for user log out routine (J!1.5)
	 * 
	 * @version @fileVers@ - Updated to redirect to new routine
	 * @version 1.0.0 - Initial release supported J!1.5 only
	 * @param
	 *        	JUser object	- $user: current user
	 * @param
	 *        	array			- $options: option array passed
	 *        	
	 * @return null
	 * @since 1.0.0
	 */
	function onLogoutUser($user, $options = array()) {
		$result = $this->onUserLogout ( $user, $options );
		return $result;
	}
}