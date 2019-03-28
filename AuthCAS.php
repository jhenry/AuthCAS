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
	public $version = '0.0.1';

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
			AuthCAS::do_exception("CAS authentication is enabled but REMOTE_USER not specified.");
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
		$new_user->email = 'first.last@uvm.edu';

		$new_user->released = true;
		$new_user->duration = true;

		$userService = new UserService();
		$our_user = $userService->create($new_user);

		$userService->approve($our_user,'approve');
		
		$auth_service = new AuthService();
		$auth_service->login( $our_user );

	}
	

	/**
	* Pretty print vars for more convenient debugging.
	*
	* @var object/array to print
	*
	*/
	private function tracer($var) {
		echo " ============================= ";
		echo "<pre>";
		var_dump($var); 
		echo "</pre>";
	}
	
	
	/**
	* Handle and display an error in the proper context.
	*
	* @var string Error message to display.
	*
	*/
	private function do_exception($errorMessage)
	{
		//TODO: Hook into filter for system error view 
		echo "Error:" . $errorMessage;
		exit;
	}
}

