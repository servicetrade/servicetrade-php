<?php
/**
 * Library for interacting with the ServiceTrade API
 * https://api.servicetrade.com/api/docs
 */
class Servicetrade
{
	protected $authId;
	protected $baseUrl    = 'https://api.servicetrade.com/api';
	protected $debug;
	protected $lastError  = '';

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $baseUrl
	 * @return string or null
	 */
	public function __construct($username, $password, $baseUrl=null)
	{
		if ($baseUrl) {
			$this->baseUrl = $baseUrl;
		}
		return $this->authenticate($username, $password);
	}

	/**
	 * Get auth id
	 *
	 * @return string
	 */
	public function getAuthId()
	{
		return $this->authId;
	}

	/**
	 * Get last error
	 *
	 * @return string
	 */
	public function getLastError()
	{
		return $this->lastError;
	}

	/**
	 * Enable debugging to dump out raw API results
	 *
	 */
	public function enableDebugging() {
		$this->debug = true;
	}

	/**
	 * Kill the current session
	 *
	 */
	public function logout() {
		return $this->delete('/auth');
	}

	//////////////////////////////////////////////////////////////////////
	// HTTP CALL METHODS ////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Make a GET request to the API
	 *
	 * @param string $path   API path, excluding the base path
	 * @param array $query   Query params
	 * @return array
	 */
	public function get($path, $query=array())
	{
		return $this->execCurl('get', $path, $query);
	}

	/**
	 * Make a POST request to the API
	 *
	 * @param string $path   API path, excluding the base path
	 * @param array $params  POST Params key/value array
	 * @param array $query   Query params
	 * @return array
	 */
	public function post($path, $params, $query=array())
	{
		$curlOpts = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($params));
		return $this->execCurl('post', $path, $query, $curlOpts);
	}

	/**
	 * Make a PUT request to the API
	 *
	 * @param string $path   API path, excluding the base path
	 * @param array $params  PUT Params key/value array
	 * @param array $query   Query params, optional
	 * @return array
	 */
	public function put($path, $params, $query=array())
	{
		$curlOpts = array(CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => json_encode($params));
		return $this->execCurl('put', $path, $query, $curlOpts);
	}

	/**
	 * Make a DELETE request to the API
	 *
	 * @param string $path   API path, excluding the base path
	 * @param array $query   Query params
	 * @return boolean
	 */
	public function delete($path, $query=array())
	{
		return $this->execCurl('delete', $path, $query, array(CURLOPT_CUSTOMREQUEST => 'DELETE'));
	}

	/**
	 * Make a non-JSON-encoded POST request to the API for sending a file attachment
	 *
	 * @param string $filePath   Full path to file to be uploaded
	 * @param array $params      POST Params key/value array
	 * @return array
	 */
	public function attach($filePath, $params)
	{
		$params['uploadedFile'] = '@' . $filePath;
		$curlOpts = array(CURLOPT_POST => true, CURLOPT_SAFE_UPLOAD => false, CURLOPT_POSTFIELDS => $params);
		return $this->execCurl('post', '/attachment', array(), $curlOpts);
	}

	//////////////////////////////////////////////////////////////////////
	// PROTECTED METHODS ////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////

	/*
	 * Authenticate with API and return a session id
	 *
	 * @param string $user
	 * @param string $password
	 * return string session id
	 */
	protected function authenticate($user, $password)
	{
		$data = array(
			'username' => $user,
			'password' => $password,
		);

		$result = $this->post('/auth', $data);
		$this->authId = isset($result['authToken']) ? $result['authToken'] : null;
	}

	/*
	 * Execute cURL calls to API
	 *
	 * @param string $method
	 * @param string $path
	 * @param array $query       Query params
	 * @param array $curlOpts    cURL options
	 * return array
	 */
	protected function execCurl($method, $path, $query=array(), $curlOpts=array())
	{
		$method = strtolower($method);
		$queryString = empty($query) ? '' : '?' . http_build_query($query);

		$url = $this->baseUrl . $path . $queryString;
		$curl_handle = curl_init($url);
		if ($this->authId) {
			$curlOpts[CURLOPT_COOKIE] = 'PHPSESSID=' . $this->authId;
		}
		$curlOpts[CURLOPT_RETURNTRANSFER] = 1;
		$curlOpts[CURLOPT_SSL_VERIFYPEER] = true;

		curl_setopt_array($curl_handle, $curlOpts);

		$rawResult = curl_exec($curl_handle);

		$results = json_decode($rawResult, true);
		$status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		curl_close($curl_handle);
		if($this->debug == true) {
			print_r($results);
		}

		if ((int)($status / 100) == 4) {
			$this->lastError = $results['messages'];
		}

		if ($method=='delete') {
			if ((int)($status / 100) != 2) {
				$this->lastError = $status;
			}
			return (int)($status / 100) == 2;
		}
		$data = $status == 200 ? $results['data'] : null;
		return $data;
	}
}
