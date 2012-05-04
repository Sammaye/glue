<?php
Glue::import('Facebook', 'glue/plugins/facebook/facebook.php');

class facebook_session extends GApplicationComponent{

	public $appId;
	public $secret;

	public $facebook;

	public function init(){
		$this->facebook = new Facebook(array(
		  'appId' => $this->appId,
		  'secret' => $this->secret
		));
	}

	function getSession(){
		$this->facebook->getSession();
	}

	public function getCurrentUser(){
		if ($this->facebook->getUser()) {

			try {
				return $this->facebook->api('/me');
			} catch (FacebookApiException $e) {
				return false;
			}

		}else{
			return false;
		}
	}

	function preAuth(){}

	function authorize(){
		//$this->facebook->getSession();
	}

	function remove(){}

	public function getLogoutUrl(){
		return $this->facebook->getLogoutUrl();
	}

	public function getLoginUrl($params = array()){
		return $this->facebook->getLoginUrl($params);
	}

	function update_status($title, $message = null, $link, $description, $picture){
//print var_dump($this->getCurrentUser());
		if($this->getCurrentUser()){
			$attachment = array(
				'message' => $message,
			 	'name' => $title,
			 	'link' => $link,
			 	'description' => $description,
			 	'picture' => $picture
			);

			if(!($sendMessage = $this->facebook->api('/me/feed/','post',$attachment))){
				//
				$errors= error_get_last();
				echo "Facebook publish error: ".$errors['type'];
				echo "<br />\n".$errors['message'];
				exit();
			}
		}
	}

}