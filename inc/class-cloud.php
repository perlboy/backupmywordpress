<?php
namespace BMWP\BackupMyWordpress;

class BMWP {
	
	/**
	 * Our endpoint
	 */
	const BMWP_ENDPOINT = 'http://localhost:8081/api';

	/**
	 * The cloud connection information
	 *
	 * @var resource
	 */
	protected $connection;

	protected $options;

	public function __construct( $options ) {
		$this->options = $options;
	}

	public function __get( $property ) {
		return $this->$property;
	}

	/**
	 * Uploads a backup to the cloud
	 *
	 * @param     $file_path Full path to local file to upload.
	 * @param     $destination Remote file name.
	 * @param int $size
	 *
	 * @return bool|WP_Error
	 */
	public function upload( $file_path ) {
				
		/**
		 * Setup our json array to post
		 */
		$inputArray = $this->options;
		$inputArray['data'] = base64_encode(fread(fopen($file_path, "r"), filesize($file_path)));
		$inputArray['contenttype'] = 'application/zip';
		$inputJSON = json_encode($inputArray);
				
		/**
		 * Post it to our end point
		 */
		$curlCaller = curl_init(self::BMWP_ENDPOINT . '/backup');
		curl_setopt($curlCaller, CURLOPT_CUSTOMREQUEST, "PUT");                                                                     
		curl_setopt($curlCaller, CURLOPT_POSTFIELDS, $inputJSON);                                                                  
		curl_setopt($curlCaller, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($curlCaller, CURLOPT_HTTPHEADER, array(                                                                          
							'Content-Type: application/json',                                                                                
							'Content-Length: ' . strlen($inputJSON))                                                                       
			);
		
		$responseData = curl_exec($curlCaller);
		
		/**
		 * Curl error
		 */
		if($errno = curl_errno($curlCaller)) {
		   $error_message = curl_strerror($errno);
		   return new \WP_Error('backup-timeout', sprintf('Upload connection error: %s', $error_message));
		}
		
		$outputJSON = json_decode($responseData, true);
		
		if(isset($outputJSON['status']) && (strcmp($outputJSON['status'], 'ok') != 0)) {
			return new \WP_Error('backup-upload-failure', sprintf('Upload Failure: %s', $outputJSON['message']));
		}
		
		return true;
	}


	/**
	 * Registers a new subscription
	 *
	 * @param $options
	 *
	 * @return WP_Error
	 */
	public function register_subscription( $options ) {
		
		/**
		 * Setup our json array to post
		 */
		$inputArray = array(
			"email" => $options['username'],
			"password" => $options['password'],
			"description" => sprintf('Name: %s URL: %s', get_bloginfo(), get_site_url())
		);
		$inputJSON = json_encode($inputArray);
		
		/**
		 * Post it to our end point
		 */
		$curlCaller = curl_init(self::BMWP_ENDPOINT . '/subscription');
		curl_setopt($curlCaller, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($curlCaller, CURLOPT_POSTFIELDS, $inputJSON);                                                                  
		curl_setopt($curlCaller, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($curlCaller, CURLOPT_HTTPHEADER, array(                                                                          
							'Content-Type: application/json',                                                                                
							'Content-Length: ' . strlen($inputJSON))                                                                       
			);
		
		$responseData = curl_exec($curlCaller);
		
		/**
		 * Curl error
		 */
		if($errno = curl_errno($curlCaller)) {
		   $error_message = curl_strerror($errno);
		   return new \WP_Error('unsuccessful-registration-connection', sprintf('Registration connection error: %s', $error_message));
		}
		
		$outputJSON = json_decode($responseData, true);
		
		if(isset($outputJSON['status']) && (strcmp($outputJSON['status'], 'ok') != 0)) {
			return new \WP_Error('unsuccessful-registration', sprintf('Registration error: %s', $outputJSON['message']));
		}
		
		return $outputJSON;
	}

}
