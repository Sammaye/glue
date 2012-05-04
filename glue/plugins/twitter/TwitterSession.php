<?php
Glue::import('TwitterOAuth', 'glue/plugins/twitter/twitteroauth.php');

class TwitterSession extends GApplicationComponent{

	public $consumer_key;
	public $secret_key;
	public $callback;

	public $action;
	public $user;

	protected $model;

	function connect($props){
		$this->model = new TwitterOAuth($this->consumer_key, $this->secret_key);

		if($props['access_token'] && !empty($props['access_token'])){
			//echo "inhere";
			$c= $this->model->getAccessToken($props['access_token']);
		}
	}

	function preAuth(){

		/* Get temporary credentials. */
		$request_token = $this->model->getRequestToken("http://stagex.co.uk/autoshare/auth?network=twt");

		/* Save temporary credentials to session. */
		setcookie("_ca_twt", serialize(array(
				"oauth_token"=>$request_token['oauth_token'],
				"oauth_token_secret"=>$request_token['oauth_token_secret']
		)), 0, "/");
	}

	function connection(){
		return $this->model;
	}

	function authorize(){

		$cookie_token = unserialize($_COOKIE['_ca_twt']);

		/* If the oauth_token is old redirect to the connect page. */
		if (isset($_REQUEST['oauth_token']) && $cookie_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			return;
		}

		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$connection = new TwitterOAuth($this->consumer_key, $this->secret_key, $cookie_token['oauth_token'], $cookie_token['oauth_token_secret']);

		/* Request access tokens from twitter */
		$token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		/* If HTTP response is 200 continue otherwise send to connect page to retry */
		if (200 == $connection->http_code) {
			/* Remove no longer needed request tokens */
			setcookie("_ca_twt", "", time()-3600, "/");

			return array(
					"oauth_token"=>$token['oauth_token'],
					"oauth_token_secret"=>$token['oauth_token_secret']
			);
		}
	}

	function get($url, $parameters = array()){
		return $this->model->get($url, $parameters);
	}

	function post($url, $parameters = array()){
		return $this->model->post($url, $parameters);
	}

	function getCurrentUser(){
		$u = $this->get('account/verify_credentials');

		if(isset($u->error)){
			return false;
		}else{
			return $u;
		}
	}

	function update_status($status){
		if(!$this->getCurrentUser()){ }else{
			$content = $this->model->post('statuses/update', array('status' => $status));
			//var_dump($content);
		}
	}
}