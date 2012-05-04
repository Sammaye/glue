<?php
require_once 'sdk.class.php';

class s3_upload extends GApplicationComponent{

	public $key;
	public $secret;
	public $bucket;

	function upload($file_name, $opt = array(), $bucket = null){

		$s3 = new AmazonS3(array(
			'key' => $this->key,
			'secret' => $this->secret
		));

		$to_bucket = $this->bucket;
		if($bucket){
			$to_bucket = $this->bucket;
		}

		$response = $s3->create_object($to_bucket, $file_name, array_merge($opt, array(
			'acl' => AmazonS3::ACL_PUBLIC,
			'storage' => AmazonS3::STORAGE_REDUCED
		)));

		if($response->isOK()){
			return true;
		}else{
			return false;
		}
	}

	function get_file($file_name){

		$s3 = new AmazonS3(array(
			'key' => $this->key,
			'secret' => $this->secret
		));

		$response = '';
		$response = $s3->get_object($this->bucket, $file_name);

		return $response;
	}
}