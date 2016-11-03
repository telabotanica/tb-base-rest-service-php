<?php

/* 
 * Base class for REST services
 * @author mathias@tela-botanica.org
 * @date 08/2015
 */
class BaseRestServiceTB {

	/** Configuration given at construct time */
	protected $config;

	/** HTTP verb received (GET, POST, PUT, DELETE, OPTIONS) */
	protected $verb;

	/** Resources (URI elements) */
	protected $resources = array();

	/** Request parameters (GET or POST) */
	protected $params = array();

	/** Domain root (to build URIs) */
	protected $domainRoot;

	/** Base URI (to parse resources) */
	protected $baseURI;

	/** First resource separator (to parse resources) */
	protected $firstResourceSeparator;

	public function __construct($config) {
		$this->config = $config;

		// HTTP method
		$this->verb = $_SERVER['REQUEST_METHOD'];

		// server config
		$this->domainRoot = $this->config['domain_root'];
		$this->baseURI = $this->config['base_uri'];
		$this->firstResourceSeparator = "/";
		if (!empty ($this->config['first_resource_separator'])) {
			$this->firstResourceSeparator = $this->config['first_resource_separator'];
		}

		// initialization
		$this->getResources();
		$this->getParams();

		$this->init();
	}

	/** Post-constructor adjustments */
	protected function init() {
	}

	/**
	 * Reads the request and runs the appropriate method; catches library
	 * exceptions and turns them into HTTP errors with message
	 */
	public function run() {
		try {
			switch($this->verb) {
				case "GET":
					$this->get();
					break;
				case "POST":
					$this->post();
					break;
				case "PUT":
					$this->put();
					break;
				case "DELETE":
					$this->delete();
					break;
				case "OPTIONS":
					// @WARNING will break CORS if you implement it
					$this->options();
					break;
				default:
					$this->sendError("unsupported method: $this->verb");
			}
		} catch(Exception $e) {
			// catches lib exceptions and turns them into error 500
			$this->sendError($e->getMessage(), 500);
		}
	}

	/**
	 * Sends a JSON message indicating a success and exits the program
	 * @param type $json the message
	 * @param type $code defaults to 200 (HTTP OK)
	 */
	protected function sendJson($json, $code=200) {
		header('Content-type: application/json');
		http_response_code($code);
		echo json_encode($json, JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Sends a JSON message indicating an error and exits the program
	 * @param type $error a string explaining the reason for this error
	 * @param type $code defaults to 400 (HTTP Bad Request)
	 */
	protected function sendError($error, $code=400) {
		header('Content-type: application/json');
		http_response_code($code);
		echo json_encode(array("error" => $error));
		exit;
	}

	/**
	 * Compares request URI to base URI to extract URI elements (resources)
	 */
	protected function getResources() {
		$uri = $_SERVER['REQUEST_URI'];
		// slicing URI
		$baseURI = $this->baseURI . $this->firstResourceSeparator;
		if ((strlen($uri) > strlen($baseURI)) && (strpos($uri, $baseURI) !== false)) {
			$baseUriLength = strlen($baseURI);
			$posQM = strpos($uri, '?');
			if ($posQM != false) {
				$resourcesString = substr($uri, $baseUriLength, $posQM - $baseUriLength);
			} else {
				$resourcesString = substr($uri, $baseUriLength);
			}
			// decoding special characters
			$resourcesString = urldecode($resourcesString);
			//echo "Resources: $resourcesString" . PHP_EOL;
			$this->resources = explode("/", $resourcesString);
			// in case of a final /, gets rid of the last empty resource
			$nbRessources = count($this->resources);
			if (empty($this->resources[$nbRessources - 1])) {
				unset($this->resources[$nbRessources - 1]);
			}
		}
	}

	/**
	 * Gets the GET or POST request parameters
	 */
	protected function getParams() {
		$this->params = $_REQUEST;
	}

	/**
	 * Searches for parameter $name in $this->params; if defined (even if
	 * empty), returns its value; if undefined, returns $default; if
	 * $collection is a non-empty array, parameters will be searched among
	 * it rather than among $this->params (2-in-1-dirty-mode)
	 */
	protected function getParam($name, $default=null, $collection=null) {
		$arrayToSearch = $this->params;
		if (is_array($collection) && !empty($collection)) {
			$arrayToSearch = $collection;
		}
		if (isset($arrayToSearch[$name])) {
			return $arrayToSearch[$name];
		} else {
			return $default;
		}
	}

	/**
	 * Reads and returns request body contents
	 */
	protected function readRequestBody() {
		// @TODO beware of memory consumption
		$contents = file_get_contents('php://input');
		return $contents;
	}

	protected function sendFile($file, $name, $size, $mimetype='application/octet-stream') {
		if (! file_exists($file)) {
			$this->sendError("file does not exist");
		}
		header('Content-Type: ' . $mimetype);
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . $size);
		// progressive sending
		// http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/
		set_time_limit(0);
		$f = @fopen($file,"rb");
		while(!feof($f)) {
			print(fread($f, 1024*8));
			ob_flush();
			flush();
		}
	}
}
