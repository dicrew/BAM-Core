<?php

class BAMCore_Object {

	protected $data = array();

	protected $arrayFields = array();

	protected function get($key, $default = null) {
		$results = $this->getMultiple(array($key));
		return count($results) ? $results[$key] : $default;
	}

	protected function getMultiple($data) {
		$results = array();
		foreach($data as $key) {
			if (isset($this->data[$key])) {
				$results[$key] = $this->data[$key];
			}
		}
		return $results;
	}

	protected function set($key, $value) {
		$this->setMultiple(array($key => $value));
	}

	protected function setMultiple($data) {
		foreach($data as $key => $value) {
			$this->data[$key] = $value;
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
		$result = $this->getMultiple($this->arrayFields);
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
