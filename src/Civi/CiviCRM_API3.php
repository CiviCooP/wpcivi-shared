<?php

/**
 * Class civicrm_api3.
 * This is the default CiviCRM APIv3 PHP class. Not namespaced because CiviCRM couldn't handle that.
 * It is included here for use from remote servers that do not have CiviCRM installed. Local installations
 *
 * Retrieved 16-08-2016 from master / v4.7. See the original and the wiki for documentation.
 * @see https://github.com/civicrm/civicrm-core/blob/master/api/class.api.php
 */
class civicrm_api3 {

    /**
     * Class constructor.
     *
     * @param array $config API configuration.
     */
    public function __construct($config = NULL) {
        $this->local      = TRUE;
        $this->input      = array();
        $this->lastResult = array();
        if (isset($config) && isset($config['server'])) {
            // we are calling a remote server via REST
            $this->local = FALSE;
            $this->uri = $config['server'];
            if (isset($config['path'])) {
                $this->uri .= "/" . $config['path'];
            }
            else {
                $this->uri .= '/sites/all/modules/civicrm/extern/rest.php';
            }
            if (isset($config['key'])) {
                $this->key = $config['key'];
            }
            else {
                die("\nFATAL:param['key] missing\n");
            }
            if (isset($config['api_key'])) {
                $this->api_key = $config['api_key'];
            }
            else {
                die("\nFATAL:param['api_key] missing\n");
            }
            return;
        }
        if (isset($config) && isset($config['conf_path'])) {
            if (!defined('CIVICRM_SETTINGS_PATH')) {
                define('CIVICRM_SETTINGS_PATH', $config['conf_path'] . '/civicrm.settings.php');
            }
            require_once CIVICRM_SETTINGS_PATH;
            require_once 'CRM/Core/ClassLoader.php';
            require_once 'api/api.php';
            require_once "api/v3/utils.php";
            CRM_Core_ClassLoader::singleton()->register();
            $this->cfg = CRM_Core_Config::singxleton();
            $this->init();
        }
        else {
            $this->cfg = CRM_Core_Config::singleton();
        }
    }

    /**
     * Convert to string.
     *
     * @return string
     */
    public function __toString() {
        return json_encode($this->lastResult);
    }

    /**
     * Perform action.
     *
     * @param $action
     * @param $params
     *
     * @return bool
     */
    public function __call($action, $params) {
        // @TODO Check if it's a valid action.
        if (isset($params[0])) {
            return $this->call($this->currentEntity, $action, $params[0]);
        }
        else {
            return $this->call($this->currentEntity, $action, $this->input);
        }
    }

    /**
     * Call via rest.
     *
     * @param $entity
     * @param $action
     * @param array $params
     *
     * @return \stdClass
     */
    private function remoteCall($entity, $action, $params = array()) {
        $query = $this->uri . "?entity=$entity&action=$action";
        $fields = http_build_query(array(
            'key' => $this->key,
            'api_key' => $this->api_key,
            'json' => json_encode($params),
        ));

        if (function_exists('curl_init')) {
            // To facilitate debugging without leaking info, entity & action
            // are GET, other data is POST.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $result = curl_exec($ch);
            // CiviCRM expects to get back a CiviCRM error object.
            if (curl_errno($ch)) {
                $res = new stdClass();
                $res->is_error = 1;
                $res->error_message = curl_error($ch);
                $res->level = "cURL";
                $res->error = array('cURL error' => curl_error($ch));
                return $res;
            }
            curl_close($ch);
        }
        else {
            // Should be discouraged, because the API credentials and data
            // are submitted as GET data, increasing chance of exposure..
            $result = file_get_contents($query . '&' . $fields);
        }
        if (!$res = json_decode($result)) {
            $res = new stdClass();
            $res->is_error = 1;
            $res->error_message = 'Unable to parse returned JSON';
            $res->level = 'json_decode';
            $res->error = array('Unable to parse returned JSON' => $result);
            $res->row_result = $result;
        }
        return $res;
    }

    /**
     * Call api function.
     *
     * @param $entity
     * @param string $action
     * @param array $params
     *
     * @return bool
     */
    private function call($entity, $action = 'Get', $params = array()) {
        if (is_int($params)) {
            $params = array('id' => $params);
        }
        elseif (is_string($params)) {
            $params = json_decode($params);
        }

        if (!isset($params['version'])) {
            $params['version'] = 3;
        }
        if (!isset($params['sequential'])) {
            $params['sequential'] = 1;
        }

        if (!$this->local) {
            $this->lastResult = $this->remoteCall($entity, $action, $params);
        }
        else {
            // Converts a multi-dimentional array into an object.
            $this->lastResult = json_decode(json_encode(civicrm_api($entity, $action, $params)));
        }
        // Reset the input to be ready for a new call.
        $this->input = array();
        if (property_exists($this->lastResult, 'is_error')) {
            return !$this->lastResult->is_error;
        }
        // getsingle doesn't have is_error.
        return TRUE;
    }

    /**
     * Helper method for long running programs (eg bots).
     */
    public function ping() {
        global $_DB_DATAOBJECT;
        foreach ($_DB_DATAOBJECT['CONNECTIONS'] as & $c) {
            if (!$c->connection->ping()) {
                $c->connect($this->cfg->dsn);
                if (!$c->connection->ping()) {
                    die("we couldn't connect");
                }
            }
        }
    }

    /**
     * Return the last error message.
     * @return string
     */
    public function errorMsg() {
        return $this->lastResult->error_message;
    }

    /**
     * Initialize.
     */
    public function init() {
        CRM_Core_DAO::init($this->cfg->dsn);
    }

    /**
     * Get attribute.
     *
     * @param $name
     * @param null $value
     *
     * @return $this
     */
    public function attr($name, $value = NULL) {
        if ($value === NULL) {
            if (property_exists($this->lastResult, $name)) {
                return $this->lastResult->$name;
            }
        }
        else {
            $this->input[$name] = $value;
        }
        return $this;
    }

    /**
     * Is this an error.
     *
     * @return bool
     */
    public function is_error() {
        return (property_exists($this->lastResult, 'is_error') && $this->lastResult->is_error);
    }

    /**
     * Check if var is set.
     *
     * @param string $name
     *
     * @return bool
     */
    public function is_set($name) {
        return (isset($this->lastResult->$name));
    }

    /**
     * Get object.
     *
     * @param string $name
     *
     * @return $this
     */
    public function __get($name) {
        // @TODO Test if valid entity.
        if (strtolower($name) !== $name) {
            // Cheap and dumb test to differentiate call to
            // $api->Entity->Action & value retrieval.
            $this->currentEntity = $name;
            return $this;
        }
        if ($name === 'result') {
            return $this->lastResult;
        }
        if ($name === 'values') {
            return $this->lastResult->values;
        }
        if (property_exists($this->lastResult, $name)) {
            return $this->lastResult->$name;
        }
        $this->currentEntity = $name;
        return $this;
    }

    /**
     * Or use $api->value.
     * @return array
     */
    public function values() {
        if (is_array($this->lastResult)) {
            return $this->lastResult['values'];
        }
        else {
            return $this->lastResult->values;
        }
    }

    /**
     * Or use $api->result.
     * @return array
     */
    public function result() {
        return $this->lastResult;
    }

}
