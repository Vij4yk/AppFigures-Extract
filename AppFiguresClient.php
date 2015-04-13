<?php

/**
 * AppFiguresClient.php
 *
 *  A client class for making GET requests to the AppFigures API.
 *  Includes some helpers for working with nested responses.
 *  Public info() represents state changes after each request.
 *  Method chaining is useful for ETL scripts.
 *
 *  usage ex:
 *
 *   $opts = array(
 *       'group_by' => 'dates,products',
 *       'start_date' => '2015-03-01',
 *       'end_date' => '2015-03-01'
 *   );
 *
 *   $t = new AppFiguresClient();
 *   $results = $t->get('/reports/sales', $opts)->to_flat_array();
 *
 */
class AppFiguresClient {

    CONST API_KEY = '<set your key here or load from file>';
    CONST AUTH_KEY = '<set your auth key here or load from file>';

    // Headers
    private $_base_url = 'https://api.appfigures.com/v2';
    private $_base_headers = array(
        'Content-Type' => 'application/json',
    );
    private $_auth_headers;
    // Last request info. null if no requests made.
    private $_last_response;
    private $_last_path;          // route of last GET request.
    private $_last_opts;          // options set in last request
    private $_last_group_keys;    // group_by options parsed in last request
    private $_last_status_code;   // status code of last request.

    /**
     * Constructor
     */
    public function __construct() {
        $this->_auth_headers = $this->_getAuthHeaders();
    }

    public function __toString() {
        echo json_encode($this->info());
    }

    public function info() {
        return array(
            'url' => $this->_base_url,
            'headers' => $this->_base_headers,
            'route' => $this->_last_path,
            'options' => $this->_last_opts,
            'group_keys' => $this->_last_group_keys,
            'status_code' => $this->_last_status_code,
        );
    }

    public function status_code() {
        return $this->_last_status_code;
    }

    /**
     *  make get request
     *
     *  @param string   $path   API route for request [optional]
     *  @param array    $opts   query params in name => value form [optional]
     *
     *  @return AppFiguresClient  this client.
     */
    public function get($path='/', $opts=[]) {
        $this->_last_opts = $opts;
        $this->_last_group_keys = $this->_parseGroupBy($opts);
        $this->_last_path = $path;
        $url = $this->_makeURL();
        $headers = array_merge($this->_base_headers, $this->_auth_headers);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);
        curl_close($ch);
        $this->_last_response = json_decode($data, true);
        $this->_last_status_code = $this->_get_status();
        return $this;
    }

    /**
     * @return string   response in JSON format
     */
    public function to_json() {
        return json_encode($this->_last_response);
    }

    /**
     * @return array    response
     */
    public function to_array() {
        return $this->_last_response;
    }

    /**
     * @return array   response flattened. depth defined by group_by params.
     */
    public function to_flat_array() {
        if ($this->status_code()!=200) {
            throw new Exception(
                "error: unable to flatten: last appfigures request had error."
            );
        }
        return self::nestedArrayToListOfArrays($this->_last_group_keys, $this->to_array());
    }

    /*
     * @return int  status code of last request.
     */
    private function _get_status() {
        if (array_key_exists('status', $this->_last_response)) {
            return $this->_last_response['status'];
        } else {
            return 200;
        }
    }

    /*
     * attempts to get authorization keys from file.
     *
     * @return array    the list of auth headers
     */
    private function _getAuthHeaders() {
        // alternatively, use this method to laod from file.
        $api_key = self::API_KEY;
        $auth_key = self::AUTH_KEY;
        if (!isset($api_key) or !isset($auth_key)) {
            throw new
                Exception("Error: unable to get AppFigures Client credentials");
        }
        return array(
            "X-Client-Key: {$api_key}",
            "Authorization: Basic {$auth_key}"
        );
    }

    private function _parseGroupBy($opts) {
        if (array_key_exists('group_by', $opts)) {
            $groups = $opts['group_by'];
            if (gettype($groups) != 'string') {
                throw new Exception("group_by args must be ',' delimited string");
            }
            return explode(',', $opts['group_by']);
        } else {
            return null;
        }
    }

    private function _makeURL() {
        $path = isset($this->_last_path) ? $this->_last_path : '';
        $opts = isset($this->_last_opts) ? http_build_query($this->_last_opts) : '';
        return $this->_base_url . $path . '?' . $opts;
    }

    // HELPER FUNCTONS FOR NESTED ARRAYS

    /*
     * returns list of non-nested associative arrays given
     * a nested array, where depth is defined by |group_by params|
     * made to the request. Useful for linking group_by keys to each
     * datum of leaves within response.
     *
     * $param array     the group_by keys
     * $param array     the data to be flattened
     *
     * @return array
     */
    public static function nestedArrayToListOfArrays($keys, $dat) {
        if (!isset($keys)) {
            return $dat;
        }
        $data = array();
        array_push($data, array(
            'data' => $dat //$this->to_array()
        ));
        while (count($keys)>0) {
            $new_data = [];
            $key = array_shift($keys);
            foreach($data as $num => $row) {
                foreach($row['data'] as $k => $v) {
                    $new_row = $row;
                    $new_row[$key] = $k;
                    $new_row['data'] = $v;
                    array_push($new_data, $new_row);
                }
            $data = $new_data;
            }
        }
        return $data;
    }
}
