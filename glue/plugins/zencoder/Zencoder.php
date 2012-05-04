<?php
require_once('Services/Zencoder.php');

class Zencoder extends GApplicationComponent{
	public $api_key;
	public $model;

	function init(){
		$this->model = new Services_Zencoder($this->api_key);
	}

	function submit($video_id, $file_path){
		try {
			$job = $this->model->jobs->create('
				{
				  "input": "'.$file_path.'",
				  "private": true,
				  "output": [
				    {
				      "label": "mp4",
				      "base_url": "http://s3.amazonaws.com/sx-uploads/",
				      "format": "mp4",
				      "thumbnails": {
				        "number": 1,
				        "aspect_mode": "crop",
				        "width": 800,
				        "height": 600,
				        "base_url": "http://s3.amazonaws.com/sx-uploads/",
				        "prefix": "thmb_'.new MongoId().'",
				        "public": 1,
				        "rrs": true
				      },
				      "public": 1,
				      "rrs": true,
				      "notifications": [
				        {
				          "url": "http://stagex.co.uk/video/process_encoding",
				          "format": "json"
				        }
				      ]
				    },
				    {
				      "label": "ogg",
				      "base_url": "http://s3.amazonaws.com/sx-uploads/",
				      "format": "ogg",
				      "video_codec": "theora",
				      "audio_codec": "vorbis",
				      "thumbnails": {
				        "number": 1,
				        "width": 800,
				        "height": 600,
				        "base_url": "http://s3.amazonaws.com/sx-uploads",
				        "prefix": "thmb_'.new MongoId().'",
				        "public": 1,
				        "rrs": true
				      },
				      "public": 1,
				      "rrs": true,
				      "notifications": [
				        {
				          "url": "http://stagex.co.uk/video/process_encoding",
				          "format": "json"
				        }
				      ]
				    }
				  ]
				}
			');

			if($job->id)
				glue::db()->videos->update(array('_id' => $video_id), array('$set' => array('state' => 'transcoding')));
			else{
				glue::db()->videos->update(array('_id' => $video_id), array('$set' => array('state' => 'pending')));
			}
			return $job->id;
		} catch (Services_Zencoder_Exception $e) {
			glue::db()->videos->update(array('_id' => $video_id), array('$set' => array('state' => 'pending')));
			return false;
		}
	}
}