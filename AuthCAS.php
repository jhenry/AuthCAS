<?php

class AuthCAS extends PluginAbstract
{
	/**
	* @var string Name of plugin
	*/
	public $name = 'AuthCAS';

	/**
	* @var string Description of plugin
	*/
	public $description = 'Provides authentication integration with CAS services.';

	/**
	* @var string Name of plugin author
	*/
	public $author = 'Justin Henry';

	/**
	* @var string URL to plugin's website
	*/
	public $url = 'https://uvm.edu/~jhenry/';

	/**
	* @var string Current version of plugin
	*/
	public $version = '0.0.2';

	/**
	* Attaches plugin methods to hooks in code base
	*/
	public function load() {
		Plugin::attachEvent ( 'login.start' , array( __CLASS__ , 'verify_cas_login' ) );
	}

	/**
	* Check that a user is logged in via CAS, and create a new account for 
	* them if this is their first login.
	*
	*/
	public function verify_cas_login() {
		
		//confirm we have the server vars we need		
		$cas_user = $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? false;

		if($cas_user) {
			$auth_service = new AuthService();
			$user = $auth_service->validateCredentials( $cas_user, 'dummy_password' );
			if (!$user) {
				AuthCAS::new_cas_user($cas_user);
			}
			else {
				$auth_service->login( $user );

			}
		}	
		else {
			throw new Exception('CAS authentication is enabled but REMOTE_USER not specified.');
		}

	}
	
	/**
	* Create a new user with server vars from CAS auth.
	*
	* @var string user name from cas remote user vars.
	*
	*/
	public function new_cas_user($cas_user) {
		$new_user= new User();
		$new_user->username = $cas_user;
		$new_user->password = 'dummy_password';
		
		// Get directory entry for this user
		$ldap = LDAP::get_entry($cas_user);
		
		// Email is a required field, so if we can't get one, build one.
		$new_user->email = $ldap['mail'] ?? $cas_user . '@uvm.edu';

		$new_user->firstName = $ldap['givenName'] ?? NULL;
		$new_user->lastName = $ldap['sn'] ?? NULL;
		$new_user->website = $ldap['labeledURI'] ?? NULL;
		$new_user->released = true;
		$new_user->duration = true;

		$new_user->homedirectory = $ldap['homeDirectory'];
		
		include 'UserReMapper.php';
		include 'NewUserService.php';
		$userService = new NewUserService();
		$our_user = $userService->create($new_user);

		$userService->approve($our_user,'approve');
		
		$auth_service = new AuthService();
		$auth_service->login( $our_user );

	}
}