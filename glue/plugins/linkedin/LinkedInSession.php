<?php
Glue::import('LinkedIn', 'glue/plugins/linkedin/linkedin.php');

class LinkedInSession extends LinkedIn{

	function __construct($consumer_key = "",
	$consumer_secret = "",
	$oauth_callback = "") {

		if($oauth_callback) {
			$this->oauth_callback = $oauth_callback;
		}

		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, $this->oauth_callback);
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->request_token_path = $this->secure_base_url . "/uas/oauth/requestToken";
		$this->access_token_path = $this->secure_base_url . "/uas/oauth/accessToken";
		$this->authorize_path = $this->secure_base_url . "/uas/oauth/authorize";

	}

	function connect($props = array()){
		# Now we retrieve a request token. It will be set as $linkedin->request_token
		if($props['oauth_callback']){
			$this->oauth_callback = $props['oauth_callback'];
		}

		if($props['access_token']){
			$this->setAccessToken($props['access_token']['oauth_token'], $props['access_token']['oauth_token_secret']);
		}
	}

	function isAuthed(){
		$this->user = $this->getCurrentUser();
		if(!$this->user){
			return false;
		}else{
			return true;
		}
	}

	function preAuth(){
		$token = $this->getRequestToken();
		setcookie("_ca_lnkd", serialize(array(
			"oauth_token"=>$token['oauth_token'],
			"oauth_token_secret"=>$token['oauth_token_secret']
		)), 0, "/");
	}

	function authorize(){

		$cookie_token = unserialize($_COOKIE['_ca_lnkd']);

		if (isset($_REQUEST['oauth_token']) && $cookie_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			setcookie("_ca_lnkd", "", time()-3600, "/");
			return;
		}

		$this->setRequestToken($cookie_token['oauth_token'], $cookie_token['oauth_token_secret']);
		$token = $this->getAccessToken($_GET["oauth_verifier"]); // set the verifier so we can activate the $linkedin object

		setcookie("_ca_lnkd", "", time()-3600, "/");

		return array(
			"oauth_token"=>$token['oauth_token'],
			"oauth_token_secret"=>$token['oauth_token_secret']
		);
	}

	function getCurrentUser(){
		$u = simplexml_load_string($this->getProfile("~:(id,first-name,last-name,headline,picture-url)"));

		if($u->{'error-code'}){
			return false;
		}else{
			return $u;
		}
	}
}