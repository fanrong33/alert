<?php
/**
 * @require PHP>=5.3
 */
require_once __DIR__ . '/EmailApiProtocol.php';
require_once __DIR__ . '/SimpleHttpClient.php';

class EmailApiClient {
	
	const VERSION = '1.0';
	
	// private static $apiEntry = 'https://open.koudaitong.com/api/entry';
	// private static $apiEntry = 'http://email.fanrong.com/api/entry';
	private static $apiEntry = 'http://email.fanrong.com/api.php/api/entry';
	
	private $appKey;
	private $appSecret;
	private $format = 'json';
	private $signMethod = 'md5';
	
	public function __construct($appKey, $appSecret) {
		if ('' == $appKey || '' == $appSecret) throw new Exception('appKey 和 appSecret 不能为空');
		
		$this->appKey    = $appKey;
		$this->appSecret = $appSecret;
	}
	
	public function get($method, $params = array()) {
		return $this->parseResponse(
			SimpleHttpClient::get(self::$apiEntry, $this->buildRequestParams($method, $params))
		);
	}
	
	public function post($method, $params = array(), $files = array()) {
		return $this->parseResponse(
			SimpleHttpClient::post(self::$apiEntry, $this->buildRequestParams($method, $params), $files)
		);
	}
	
	
	
	public function setFormat($format) {
		if (!in_array($format, EmailApiProtocol::allowedFormat()))
			throw new Exception('设置的数据格式错误');
		
		$this->format = $format;
		
		return $this;
	}
	
	public function setSignMethod($method) {
		if (!in_array($method, EmailApiProtocol::allowedSignMethods()))
			throw new Exception('设置的签名方法错误');
		
		$this->signMethod = $method;
		
		return $this;
	}
	
	

	private function parseResponse($responseData) {
		$data = json_decode($responseData, true);
		if (null === $data) throw new Exception('response invalid, data: ' . $responseData);
		return $data;
	}
	
	private function buildRequestParams($method, $apiParams) {
		if (!is_array($apiParams)) $apiParams = array();
		$pairs = $this->getCommonParams($method);
		foreach ($apiParams as $k => $v) {
			if (isset($pairs[$k])) throw new Exception('参数名冲突');
			$pairs[$k] = $v;
		}
		
		$pairs[EmailApiProtocol::SIGN_KEY] = EmailApiProtocol::sign($this->appSecret, $pairs, $this->signMethod);
		return $pairs;
	}
	
	private function getCommonParams($method) {
		$params = array();
		$params[EmailApiProtocol::APP_ID_KEY]      = $this->appKey;
		$params[EmailApiProtocol::METHOD_KEY]      = $method;
		$params[EmailApiProtocol::TIMESTAMP_KEY]   = date('Y-m-d H:i:s');
		$params[EmailApiProtocol::FORMAT_KEY]      = $this->format;
		$params[EmailApiProtocol::SIGN_METHOD_KEY] = $this->signMethod;
		$params[EmailApiProtocol::VERSION_KEY]     = self::VERSION;
		return $params;
	}
}
