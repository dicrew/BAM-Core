<?php

function __autoload($class) {
	$file = strtr($class, '_./', '/') . '.php';
	require_once $file;
}
