<?php

class DirectAdmin_HTTPSocket extends HTTPSocket {

    // ===============
    // === LOGGING ===
    // ===============

    protected $_logPath;
    protected $_debugMode;
    protected $_loggedResponse = false;

    /**
     * Query the server
     *
     * @param string containing properly formatted server API. See DA API docs and examples. Http:// URLs O.K. too.
     * @param string|array query to pass to url
     * @param int if connection KB/s drops below value here, will drop connection
     */
    public function query($request, $content = '', $doSpeedCheck = 0 )
    {
        parent::query($request, $content, $doSpeedCheck);

        if ($this->_debugMode && !$this->_loggedResponse) {
            $requestUrl = strpos($request, '://') === false ? $this->remote_host.$request : $request;
            $this->logResponse($requestUrl, 'HTTP '.$this->result_status_code."\n".$this->result_body);
            $this->_loggedResponse = true;
        }
    }

    public function setLogPath($path){
        $this->_logPath = $path;
    }

    public function setDebugMode($turnOn){
        $this->_debugMode = (bool)$turnOn;
    }

    public function logResponse($request, $response){
        if (!$this->_logPath || !$this->_debugMode) {
            return false;
        }

        $h = fopen($this->_logPath, 'a');
        // make sure the file permissions are ok
        chmod($this->_logPath, 0600);
        fwrite($h, date('Y-m-d H:i:s') . "\n");
        fwrite($h, "Request ".$request."\n");
        fwrite($h, $response."\n\n");
        fclose($h);
        return true;
    }

}
