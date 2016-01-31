<?php
	/**
	* 
	*/
	class ChangeOrgClient
	{
		public $lastResponse = null;		
		private $curl;
		public $config = array(
			'api_url' => 'https://api.change.org/v1/'
		);

		function __construct($init_config)
		{
			$this->setConfig($init_config);
		}
		public function setConfig($config){
			$this->config = array_merge($this->config,$config);
		}

		public function get($api_method,$data = array()){
			return $this->api($api_method,$data,'GET');
		}

		public function post($api_method,$data = array()){
			return $this->api($api_method,$data,'POST');
		}

		public function api($api_method, $data = array(), $method = "POST"){
			$endpoint = $this->config['api_url'] . $api_method;
			$required_data = array(
				'api_key' => $this->config['api_key'],
				'timestamp' => gmdate("Y-m-d\TH:i:s\Z"),
				'endpoint' => parse_url($endpoint)['path']
			);

			$data = array_merge($required_data,$data);

			$auth_key = '';
			if(array_key_exists('auth_key',$data)){
				$auth_key = $data['auth_key'];
				unset($data['auth_key']);
			}

			$opts = array(
				CURLOPT_URL => $endpoint,
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
			);

			switch($method){
				case 'PUT':
				case 'POST':
						$data['endpoint'] = parse_url($endpoint)['path'];
						$data['rsig'] = $this->sign($data,$auth_key);
						$opts[CURLOPT_POST] = TRUE;
						$opts[CURLOPT_POSTFIELDS] = http_build_query($data);
					break;

				default:
						$opts[CURLOPT_URL] .= '?'.http_build_query($data);
						$opts[CURLOPT_HTTPGET] = TRUE;
					break;
			}
			unset($this->curl);

			$this->curl = curl_init();
			curl_setopt_array($this->curl,$opts);
			$result = curl_exec($this->curl);
			
			$this->lastResponse = array(
				'error' => curl_error($this->curl),
				'debug' => curl_getinfo($this->curl)
			); 

			curl_close($this->curl);
			return json_decode($result);
		}
		
		private function sign($params,$auth_key){
			$toHash = http_build_query($params) . $this->config['api_secret'] . $auth_key;
			return hash('sha256',$toHash);
		}

	}
?>