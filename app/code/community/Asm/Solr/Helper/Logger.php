<?php

class Asm_Solr_Helper_Logger
{
	protected $logFileName = 'solr.log';


	public function log($message, $level, $data = null)
	{
		if (!is_null($data)) {
			$message .= ' ' . json_encode($data);
		}

		Mage::log($message, $level, 'solr.log');
	}

	public function emergency($message, $data = null)
	{
		$this->log($message, Zend_Log::EMERG, $data);
	}

	public function alert($message, $data = null)
	{
		$this->log($message, Zend_Log::ALERT, $data);
	}

	public function critical($message, $data = null)
	{
		$this->log($message, Zend_Log::CRIT, $data);
	}

	public function error($message, $data = null)
	{
		$this->log($message, Zend_Log::ERR, $data);
	}

	public function warning($message, $data = null)
	{
		$this->log($message, Zend_Log::WARN, $data);
	}

	public function notice($message, $data = null)
	{
		$this->log($message, Zend_Log::NOTICE, $data);
	}

	public function info($message, $data = null)
	{
		$this->log($message, Zend_Log::INFO, $data);
	}

	public function debug($message, $data = null)
	{
		$this->log($message, Zend_Log::DEBUG, $data);
	}
}


?>