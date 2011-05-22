<?php

class BAMCore_Object {

	protected $data = array();

	protected $arrayFields = array();

	protected function get() {
		$args = func_get_args();
		$count = count($args);
		if ($count != 1 && $count != 2) {
			throw new Exception('invalid number of arguments');
		}
		$first = !is_array($args[0]);
		if ($count == 2) {
			if (is_array($args[0])) {
				throw new Exception('key may not be an array');
			}
			if (!isset($this->data[$args[0]])) {
				return $args[1];
			}
			$args = array(array($args[0]));
		}
		$results = array();
		foreach ($args[0] as $key) {
			if (isset($this->data[$key])) {
				$results[$key] = $this->data[$key];
			}
		}
		return $first ? $results[0] : $results;
	}

	protected function set() {
		$args = func_get_args();
		$count = count($args);
		if ($count == 2) {
			if (is_array($args[0])) {
				throw new Exception('key may not be an array');
			}
			$args = array(array($args[0], $args[1]));
		}
		if ($count == 1) {
			foreach($args[0] as $key => $value) {
				$this->data[$key] = $value;
			}
		} else {
			throw new Exception('wrong number of arguments');
		}
	}

	public function __call($method, $params) {
		$three = substr($method, 0, 3);
		$field = strtolower(substr($method, 3, 1)) . substr($method, 4);
		switch ($three) {
			case 'get':
				return $this->get($field, count($params) ? $params[0] : null);
				break;
			case 'set':
				return $this->set($field, count($params) ? $params[0] : null);
				break;
			default:
				throw new Exception("method \"$method\" not implemented");
				break;
		}
	}

	public function toArray($recursive = true) {
		$result = $this->get($this->arrayFields);
		if ($recursive) {
			foreach ($result as $k => $v) {
				if ($v instanceof BAMCore_Object) {
					$result[$k] = $v->toArray($recursive);
				}
			}
		}
		return $result;
	}

}
