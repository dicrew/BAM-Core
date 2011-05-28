<?php

// BAM!Core API Server

require_once '../src/BAMCore.php';

define('TIMESTAMP_DIFF', 3600);

function route($path) {
	static $routes = null;
	if ($routes === null) {
		$routes = array(
			'ping' => array(
				'callback' => 'ping',
				'args' => array(
					'message' => array(
						'optional' => true,
					),
				),
			),
			'player' => array(
				'callback' => array('BAMCore_Player', 'load'),
				'args' => array(
					'id' => array(),
				),
			),
			'players' => array(
				'callback' => array('BAMCore_Player', 'loadMultiple'),
				'args' => array(
					'ids' => array(
						'optional' => true,
						'explode' => ',',
					),
				),
			),
		);
	}
	if (isset($routes[$path])) {
		return $routes[$path];
	}
	return false;
}

function request_path() {
	$me = '/index.php';
	if (isset($_SERVER['PHP_SELF'])) {
		$me = $_SERVER['PHP_SELF'];
	} elseif (isset($_SERVER['SCRIPT_NAME'])) {
		$me = $_SERVER['SCRIPT_NAME'];
	}
	$basepath = dirname($me);
	$requri = $_SERVER['REQUEST_URI'];
	if (substr($requri, 0, strlen($basepath)) == $basepath) {
		$requri = substr($requri, strlen($basepath));
	}
	return trim(preg_replace('/\?.*$/', '', $requri), '/');
}

function build_params($def) {
	$args = isset($def['args']) ? $def['args'] : array();
	$result = array();
	foreach ($args as $arg => $cfg) {
		if (!isset($_GET[$arg])) {
			if (!isset($cfg['optional']) || !$cfg['optional']) {
				throw new Exception("Missing required parameter: $arg");
			}
		} else {
			// TODO: validation
			$value = $_GET[$arg];
			if (isset($cfg['explode'])) {
				$value = explode($cfg['explode'], $value);
			}
			$result[] = $value;
		}
	}
	return $result;
}

function auth($def) {
	// No auth required.
	if (empty($def['auth']) && empty($_GET['user'])) {
		return true;
	}
	$args = $_GET;
	$time = time();
	if (empty($args['timestamp']) || empty($args['salt']) || empty($args['hash'])) {
		throw new Exception("Missing correct auth.");
	}
	if ($args['timestamp'] < $time - TIMESTAMP_DIFF || $args['timestamp'] > $time + TIMESTAMP_DIFF) {
		throw new Exception("Invalid timestamp.");
	}
	// TODO: check for existing timestamps and salts
	if (empty($args['salt'])) {
		throw new Exception("No replay.");
	}
	$hash = isset($args['hash']) ? $args['hash'] : '';
	unset($args['hash']);
	ksort($args);
	$querystring = http_build_query($args) . '#' . sharedSecret($args['user']);
	if ($hash != hash('sha256', $querystring)) {
		throw new Exception("Invalid hash.");
	}
}

function sharedSecret($user) {
	switch ($user) {
		case 'asdfxxx':
			return '';
		case 'asdf':
			return 'foobar';
		case 'foo':
			return '1234';
		case 'bar':
			return 'abc';
	}
	throw new Exception("Unknown user.");
}

function do_action() {
	$definition = route(request_path());
	if ($definition !== false) {
		try {
			auth($definition);
		} catch (Exception $e) {
			return array('error' => array('code' => 403, 'message' => $e->getMessage()));
		}
		$params = array();
		try {
			$params = build_params($definition);
		} catch (Exception $e) {
			return array('error' => array('code' => 400, 'message' => $e->getMessage()));
		}
		// TODO: Check if callback exists at all before calling.
		$result = call_user_func_array($definition['callback'], $params);
		if ($result instanceof BAMCore_Object) {
			$result = $result->toArray();
		} elseif (!is_array($result)) {
			return array('error' => array('code' => 500, 'message' => 'No array returned'));
		}
		foreach ($result as $k => $v) {
			if ($v instanceof BAMCore_Object) {
				$result[$k] = $v->toArray();
			}
		}
		return $result;
	}
	return array('error' => array('code' => 404, 'message' => 'No matching route'));
}

function run() {
	$result = do_action();
	if (isset($result['error'])) {
		http_status($result['error']['code'], $result['error']['message']);
		echo json_encode(array('error' => $result['error']));
	} else {
		http_status(200, 'OK');
		echo json_encode($result);
	}
}

function http_status($code, $message) {
	$proto = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
	header("$proto $code $message");
	header('Content-Type: application/json; charset=UTF-8');
}

function ping($message = null) {
	$result = array(
		'timestamp' => time(),
	);
	if ($message !== null) {
		$result['message'] = $message;
	}
	return $result;
}

run();
