<?php

// BAM!Core API Server

require_once '../src/BAMCore.php';

function route($path) {
	static $routes = null;
	if ($routes === null) {
		$routes = array(
			'ping' => array(
				'callback' => 'ping',
			),
		);
	}
	if (isset($routes[$path])) {
		return $routes[$path];
	}
	return false;
}

function request_path() {
	$path = trim($_SERVER['REQUEST_URI'], '/');
	return $path;
}

function do_action() {
	$definition = route(request_path());
	if ($definition !== false) {
		// TODO: Check if callback exists at all before calling.
		// TODO: Pass actual parameters, if any.
		$result = call_user_func_array($definition['callback'], array());
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

function ping() {
	return array(
		'timestamp' => time(),
	);
}

run();
