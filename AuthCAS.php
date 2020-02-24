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
	public $version = '0.3.0';

	/**
	 * Performs install operations for plugin. Called when user clicks install
	 * plugin in admin panel.
	 *
	 */
	public function install()
	{
		Settings::set('authcas_email_domain', AuthCAS::default_email_domain());
		Settings::set('authcas_create_user', 1);
	}

	/**
	 * Performs uninstall operations for plugin. Called when user clicks
	 * uninstall plugin in admin panel and prior to files being removed.
	 *
	 */
	public function uninstall()
	{
		Settings::remove('authcas_email_domain');
		Settings::remove('authcas_create_user');
	}


	/**
	 * Attaches plugin methods to hooks in code base
	 */
	public function load()
	{
		Plugin::attachEvent('login.start', array(__CLASS__, 'verify_cas_login'));
	}

	/**
	 * Check that a user is logged in via CAS, and create a new account for 
	 * them if this is their first login.
	 *
	 */
	public function verify_cas_login()
	{
		// Get the username of the authenticated user, if available
		$cas_user = $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? false;

		if ($cas_user) {
			$userMapper = new \UserMapper();
			$user = $userMapper->getUserByUsername($cas_user);
			if (!$user) {
				// Valid CAS user, but no local account, so create one
				AuthCAS::new_cas_user($cas_user);
			} else {
				$auth_service = new AuthService();
				$auth_service->login($user);
			}
		} else {
			throw new Exception('CAS authentication is enabled but REMOTE_USER not specified.');
		}
	}

	/**
	 * Create a new user with server vars from CAS auth.
	 *
	 * @var string user name from cas remote user vars.
	 *
	 */
	public function new_cas_user($cas_user)
	{
		$new_user = new User();
		$new_user->username = $cas_user;
		$new_user->email = $cas_user . "@" . Settings::get('authcas_email_domain');
		$new_user->password = AuthCAS::random_str(16);
		$new_user->released = true;

		$userService = new UserService();
		$our_user = $userService->create($new_user);

		if (class_exists('ExtendedUser')) {
			ExtendedUser::save($our_user);
		}

		$userService->approve($our_user, 'approve');

		$auth_service = new AuthService();
		$auth_service->login($our_user);
	}

	/**
	 * Create a default email domain based on part of the host name.
	 */
	private function default_email_domain()
	{

		// Grab the last two parts of the URL 
		$split_host = explode('.', parse_url(BASE_URL, PHP_URL_HOST), 2);
		return $split_host[1];
	}

	/**
	 * Generate a random string, using a cryptographically secure 
	 * pseudorandom number generator (random_int)
	 * 
	 * @param int $length      How many characters do we want?
	 * @param string $keyspace A string of all possible characters
	 *                         to select from
	 * @return string
	 */
	private function random_str(int $length = 64, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
	{
		if ($length < 1) {
			throw new \RangeException("Length must be a positive integer");
		}
		$pieces = [];
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$pieces[] = $keyspace[random_int(0, $max)];
		}
		return implode('', $pieces);
	}
}
