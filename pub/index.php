<?php

// BAM!Core API Server

require_once '../src/BAMCore.php';

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
		);
	}
	if (isset($routes[$path])) {
		return $routes[$path];
	}
	return false;
}

function request_path() {
	return trim(preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']), '/');
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
			$result[] = $_GET[$arg];
		}
	}
	return $result;
}

function do_action() {
	$definition = route(request_path());
	if ($definition !== false) {
		// TODO: Check if callback exists at all before calling.
		// TODO: Pass actual parameters, if any.
		$params = array();
		try {
			$params = build_params($definition);
		} catch (Exception $e) {
			return array('error' => array('code' => 400, 'message' => $e->getMessage()));
		}
		$result = call_user_func_array($definition['callback'], $params);
		if (!is_array($result)) {
			return array('error' => array('code' => 500, 'message' => 'No array returned'));
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
	header('Content-Type: application/json');
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
