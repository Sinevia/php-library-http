<?php

// ========================================================================= //
// SINEVIA CONFIDENTIAL                                  http://sinevia.com  //
// ------------------------------------------------------------------------- //
// COPYRIGHT (c) 2008-2015 Sinevia Ltd                   All rights reserved //
// ------------------------------------------------------------------------- //
// LICENCE: All information contained herein is, and remains, property of    //
// Sinevia Ltd at all times.  Any intellectual and technical concepts        //
// are proprietary to Sinevia Ltd and may be covered by existing patents,    //
// patents in process, and are protected by trade secret or copyright law.   //
// Dissemination or reproduction of this information is strictly forbidden   //
// unless prior written permission is obtained from Sinevia Ltd per domain.  //
//===========================================================================//

namespace Sinevia;

/**
 * Implements a basic HTTP client with cookie support.
 * <code>
 * $http = new Http("http://localhost/");
 * $http->setPath('/2012__kuikee__4.0/');
 * $http->post(array('user'=>'UN','pass'=>'PW');
 * echo $http->getResponseBody();
 * </code>
 */
class HttpClient {

    private $data = array(
        'scheme' => 'http',
        'host' => false,
        'port' => 80,
        'user' => '',
        'pass' => '',
        'cookies' => array(),
        'response' => false,
        'response_status' => false,
        'response_headers' => false,
        'response_body' => false,
        'redirect' => true,
        'url' => false,
    );
    private $debug = false;

    function __construct($host = false) {
        if ($host !== false) {
            $this->setHost($host);
        }
    }

    function get($data = array()) {
        // START: Cleanup
        $this->data['response'] = false;
        $this->data['response_status'] = false;
        $this->data['response_header'] = false;
        $this->data['response_headers'] = false;
        $this->data['response_body'] = false;
        // END: Cleanup
        // START: Data
        $host = $this->getHost();
        $path = $this->getPath();
        $port = $this->getPort();
        // END: Data
        // START: Prepare the GET string
        $get = array();
        foreach ($data as $field => $value) {
            $get[] = $field . '=' . urlencode(stripslashes($value));
        }
        $get = implode("&", $get);
        if ($this->getQuery() != '') {
            $get = $this->getQuery() . '&' . $get;
        }
        // END: Prepare the GET string
        // START: Prepare the COOKIE string
        $cookies = "";
        if (count($this->data['cookies']) > 0) {
            foreach ($this->data['cookies'] as $field => $value) {
                $cookies .= urlencode($field) . '=' . urlencode(stripslashes($value)) . ";";
            }
        }
        // END: Prepare the COOKIE string
        // START: Prepare header
        if ($get != '')
            $path .= '?' . $get;
        $header = "GET " . $path . " HTTP/1.0\r\n";
        $header .= "Host: " . $host . "\r\n";
        $header.= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0\r\n";
        if ($this->getUser() != '') {
            $header .= 'Authorization: Basic ' . base64_encode($this->getUser() . ':' . $this->getPassword()) . "\r\n";
        }
        if ($cookies != "") {
            $header .= "Cookie: " . $cookies . "\r\n";
        }
        $header .= "Connection: close\r\n\r\n";
        // END: Prepare header
        //var_dump($header);
        // START: Send request
        $fp = fsockopen($host, $port, $err_num, $err_str, 30);
        if ($fp === false) {
            echo "No Connection";
            exit;
        }
        $response = "";
        fputs($fp, $header);
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        // END: Send request
        //var_dump($err_num);var_dump($err_str);

        list($http_header, $http_body) = explode("\r\n\r\n", $response, 2);

        $http_lines = explode("\n", $http_header);
        // START: Process status
        $http_status = array_shift($http_lines);
        $http_status_parts = explode(" ", trim($http_status));
        $http_status = $http_status_parts[1];
        // END: Process status
        // START: Process headers
        $http_header = implode("\n", $http_lines);
        $http_headers = array();
        foreach ($http_lines as $http_line) {
            $http_line = explode(":", $http_line);
            $http_headers[$http_line[0]] = trim($http_line[1]);
        }
        // END: Process headers
        // START: Process cookies
        if (isset($http_headers['Set-Cookie'])) {
            $cookies = explode(';', $http_headers['Set-Cookie']);
            foreach ($cookies as $cookie) {
                $tmp = explode("=", $cookie);
                if ($pos = strpos($tmp[1], ";")) {
                    $tmp[1] = substr($tmp[1], 0, ($pos ? $pos : -1));
                }
                $this->data['cookies'][$tmp[0]] = $tmp[1];
            }
        }

        $this->data['response'] = $response;
        $this->data['response_status'] = $http_status;
        $this->data['response_header'] = $http_header;
        $this->data['response_headers'] = $http_headers;
        $this->data['response_body'] = $http_body;
        return true;
    }

    function getCookies() {
        return $this->data['cookies'];
    }

    function getResponse() {
        return $this->data['response'];
    }

    function getResponseBody() {
        return $this->data['response_body'];
    }

    function getResponseHeaders() {
        return $this->data['response_headers'];
    }

    function getResponseStatus() {
        return $this->data['response_status'];
    }

    function post($data = array()) {
        // START: Cleanup
        $this->data['response'] = false;
        $this->data['response_status'] = false;
        $this->data['response_header'] = false;
        $this->data['response_headers'] = false;
        $this->data['response_body'] = false;
        // END: Cleanup
        // START: Data
        $host = $this->getHost();
        $path = $this->getPath();
        $port = $this->getPort();
        // END: Data
        // START: Prepare the POST string
//        $post = array();
//        foreach ($data as $field => $value) {
//            if (is_array($value)) {
//                foreach ($value as $v) {
//                    $post[] = $field . '[]=' . urlencode(stripslashes($v));
//                }
//            } else {
//                $post[] = $field . '=' . urlencode(stripslashes($value));
//            }
//        }
//        $post = implode("&", $post);
        //Utils::alert($post);
        $post = http_build_query($data);
        // END: Prepare the POST string
        // START: Prepare the COOKIE string
        $cookies = "";
        if (count($this->data['cookies']) > 0) {
            foreach ($this->data['cookies'] as $field => $value) {
                $cookies .= urlencode($field) . '=' . urlencode(stripslashes($value)) . ";";
            }
        }
        // END: Prepare the COOKIE string
        // START: Prepare header
        if ($this->getQuery() != '') {
            $path .= '?' . $this->getQuery();
        }
        $header = "POST " . $path . " HTTP/1.0\r\n";
        $header .= "Host: " . $this->data['host'] . "\r\n";
        $header.= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0\r\n";
        if ($this->data['user'] != '') {
            $header .= 'Authorization: Basic ' . base64_encode($this->data['user'] . ':' . $this->data['pass']) . "\r\n";
        }
        if ($cookies != "") {
            $header .= "Cookie: " . $cookies . "\r\n";
        }
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($post) . "\r\n";
        $header .= "Connection: close\r\n\r\n";
        $header .= $post . "\r\n\r\n";
        // END: Prepare header
        // START: Send request
        $fp = fsockopen($host, $port, $err_num, $err_str, 30);
        if ($fp === false) {
            echo "No Connection";
            exit;
        }
        $response = "";
        fputs($fp, $header);
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        // END: Send request
        //var_dump($err_num);var_dump($err_str);

        list($http_header, $http_body) = explode("\r\n\r\n", $response, 2);

        $http_lines = explode("\n", $http_header);
        // START: Process status
        $http_status = array_shift($http_lines);

        $http_status_parts = explode(" ", trim($http_status));
        $http_status = $http_status_parts[1];
        // END: Process status
        // START: Process headers
        $http_header = implode("\n", $http_lines);
        $http_headers = array();
        foreach ($http_lines as $http_line) {
            $http_line = explode(":", $http_line);
            $key = array_shift($http_line);
            $value = implode(':', $http_line);
            $http_headers[$key] = trim($value);
        }
        // END: Process headers
        // START: Process cookies
        if (isset($http_headers['Set-Cookie'])) {
            $cookies = explode(';', $http_headers['Set-Cookie']);
            foreach ($cookies as $cookie) {
                $tmp = explode("=", $cookie);
                if (isset($tmp[1]) == false) {
                    $tmp[1] = "";
                }
                if ($pos = strpos($tmp[1], ";")) {
                    $tmp[1] = substr($tmp[1], 0, ($pos ? $pos : -1));
                }
                $this->data['cookies'][$tmp[0]] = $tmp[1];
            }
        }
        // END: Process cookies

        if ($http_status == "302") {
            $location = (isset($http_headers['Location']) == false) ? 'false' : $http_headers['Location'];
            if ($this->data['redirect'] == true) {
                $this->setUrl($location);
                return $this->post($data);
            }
        }

        $this->data['response'] = $response;
        $this->data['response_status'] = $http_status;
        $this->data['response_header'] = $http_header;
        $this->data['response_headers'] = $http_headers;
        $this->data['response_body'] = $http_body;
        return true;
    }

    /**
     * Returns the debug string
     * @return string
     */
    function getDebug() {
        return $this->debug;
    }

    function getHost() {
        return $this->data['host'];
    }

    //=====================================================================//
    //  METHOD: get_host                                                   //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: get_path                                                   //
    //=====================================================================//
    function getPath() {
        return $this->data['path'];
    }

    //=====================================================================//
    //  METHOD: get_path                                                   //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: get_password                                               //
    //=====================================================================//
    function getPassword() {
        return $this->data['pass'];
    }

    //=====================================================================//
    //  METHOD: get_password                                               //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: get_port                                                   //
    //=====================================================================//
    function getPort() {
        return $this->data['port'];
    }

    //=====================================================================//
    //  METHOD: get_port                                                   //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: set_scheme                                                 //
    //=====================================================================//
    function getScheme() {
        return $this->data['scheme'];
    }

    //=====================================================================//
    //  METHOD: get_scheme                                                 //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: get_url                                                    //
    //=====================================================================//
    function getUrl() {
        return $this->data['url'];
    }

    //=====================================================================//
    //  METHOD: get_url                                                    //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: get_user                                                   //
    //=====================================================================//
    function getUser() {
        return $this->data['user'];
    }

    //=====================================================================//
    //  METHOD: get_user                                                   //
    //========================== END OF METHOD ============================//
    //========================= START OF METHOD ===========================//
    //  METHOD: set_debug                                                  //
    //=====================================================================//
    function setDebug($is_enabled) {
        $this->debug = $is_enabled;
        return $this;
    }

    /**
     * Sets the host
     * @param string $host
     * @return void
     */
    function setHost($host) {
        if (strpos($host, "://") === false) {
            $this->data['host'] = $host;
            return $this;
        }
        $this->setUrl($host);

        return $this;
    }

    function setPath($path) {
        $this->data['path'] = $path;
        return $this;
    }

    function setPort($port) {
        $this->data['port'] = $port;
        return $this;
    }

    /**
     * Sets the query string
     * <code>
     * $http->setQuery("name=Paul&say=Hello");
     * </code>
     * @param type $query
     */
    function setQuery($query) {
        if (is_array($query)) {
            $query = http_build_query($query);
        }
        $this->data['query'] = $query;
        return $this;
    }

    function getQuery() {
        return (isset($this->data['query'])) ? $this->data['query'] : '';
    }

    function setScheme($scheme) {
        $this->data['scheme'] = $scheme;
        return $this;
    }

    function setPassword($password) {
        $this->data['pass'] = $password;
        return $this;
    }

    /**
     * Sets the url and parses it
     * @param unknown_type $host
     */
    function setUrl($url) {
        $this->data['url'] = $url;

        $url = parse_url($url);

        if (isset($url['scheme'])) {
            $this->setScheme($url['scheme']);
        }
        if (isset($url['host'])) {
            $this->setHost($url['host']);
        }
        if (isset($url['path'])) {
            $this->setPath($url['path']);
        }
        if (isset($url['port'])) {
            $this->setPort($url['port']);
        }
        if (isset($url['pass'])) {
            $this->setPassword($url['pass']);
        }
        if (isset($url['user'])) {
            $this->setUser($url['user']);
        }
        if (isset($url['query'])) {
            $this->setQuery($url['query']);
        }

        return $this;
    }

    /**
     * Sets username
     * @param string $username
     */
    function setUser($username) {
        $this->data['user'] = $username;
        return $this;
    }

}

//===========================================================================//
// CLASS: Http                                                               //
//============================== END OF CLASS ===============================//
