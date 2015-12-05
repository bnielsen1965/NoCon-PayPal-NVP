<?php
/*
* Copyright (C) 2015 Bryan Nielsen - All Rights Reserved
*
* Author: Bryan Nielsen <bnielsen1965@gmail.com>
*
*
* This file is part of the NoCon PHP application framework.
* NoCon is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* NoCon is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this application.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NoCon\PayPalNVP;

/**
 * Curl class provides curl functions to make API calls.
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class Curl {
    
    const USERAGENT         = 'NoCon PayPalNVP Curl';
    const TIMEOUT           = 30;
    const CONNECTTIMEOUT    = 10;
    const FOLLOWLOCATION    = true;
    const MAXREDIRS         = 10;
    const RETURNTRANSFER    = true;
    const HEADER            = true;
    
    protected $userAgent;
    protected $timeout;
    protected $connectTimeout;
    protected $followLocation;
    protected $maxRedirs;
    protected $returnTransfer;
    
    protected $sslVerifyPeer;
    protected $sslVerifyHost;
    protected $sslVersion;
    protected $httpHeader;
    protected $post;
    protected $postFields;
    protected $header;
    protected $headerFunction;

    protected $url;
    protected $curl;
    protected $responseBody;
    protected $responseHeaders;
    protected $curlHTTPCode;
    protected $curlError;
    protected $curlErrorNo;


    /**
     * Construct class instance and set intial state.
     */
    public function __construct() {
        $this->setUserAgent();
        $this->setTimeout();
        $this->setConnectTimeout();
        $this->setFollowLocation();
        $this->setMaxRedirs();
        $this->setReturnTransfer();
    }
    
    
    /**
     * Execute a curl call.
     * 
     * @param string $url Optional URL to use for the curl call.
     * @return boolean|string Returns false on failure or the body of the response.
     */
    public function execute($url = null) {
        if ( !empty($url) ) {
            $this->setUrl($url);
        }
        
        $this->initCurl();
        
        if ( false === ($response = curl_exec($this->curl)) ) {
            $this->processFailure();
            return false;
        }
        
        return $this->processResponse($response);
    }
    
    
    /**
     * Process the curl response message and exract header if necessary.
     * 
     * @param string $response The raw curl response.
     * @return string The body of the response message.
     */
    private function processResponse($response) {
        if ( $this->header ) {
            $this->curlHTTPCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $this->responseBody = substr($response, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));
        }
        else {
            $this->responseBody = $response;
        }
        
        return $this->responseBody;
    }
    
    
    /**
     * Process a header line.
     * 
     * @param resource $curl The curl resource.
     * @param string $header The current header line.
     * @return integer The length of the header line.
     */
    public function processHeader($curl, $header) {
        if ( strlen($header) ) {
            $this->responseHeaders[] = $header;
        }
        
        return strlen($header);
    }
    
    
    /**
     * Process a failure condition in the curl call.
     */
    private function processFailure() {
        $this->curlError = curl_error($this->curl);
        $this->curlErrorNo = curl_errno($this->curl);
    }
    
    
    /**
     * Create and initialize the curl resource.
     */
    private function initCurl() {
        $this->curl = curl_init();
        $options = array(
            CURLOPT_URL             => $this->url,
            CURLOPT_USERAGENT       => $this->userAgent,
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_CONNECTTIMEOUT  => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION  => $this->followLocation,
            CURLOPT_MAXREDIRS       => $this->maxRedirs,
            CURLOPT_RETURNTRANSFER  => $this->returnTransfer,
            CURLOPT_HEADER          => $this->header,
            CURLOPT_HEADERFUNCTION  => array($this, 'processHeader')
        );
        curl_setopt_array($this->curl, $options);
    }
    
    
    /**
     * Set the URL to use for the curl call.
     * 
     * @param string $url The URL to use for the curl call.
     */
    public function setUrl($url) {
        $this->url = $url;
    }
    
    
    /**
     * Get the current URL used for the curl call.
     * 
     * @return string The current URL.
     */
    public function getUrl() {
        return $this->url;
    }
    
    
    /**
     * Set the user agent string to use for the curl call. If an agent string is
     * not specified then the default value is used.
     * 
     * @param string $userAgent Optional user agent string.
     */
    public function setUserAgent($userAgent = self::USERAGENT) {
        $this->userAgent = $userAgent;
    }
    
    
    /**
     * Get the current user agent string.
     * 
     * @return string The current user agent string.
     */
    public function getUserAgent() {
        return $this->userAgent;
    }
    
    
    /**
     * Set the curl request timeout in seconds. If a timeout is not specified then 
     * the default value is used.
     * 
     * @param integer $timeout Optional curl request timeout in seconds.
     */
    public function setTimeout($timeout = self::TIMEOUT) {
        $this->timeout = $timeout;
    }
    
    
    /**
     * Get the current timeout setting.
     * 
     * @return integer The current timeout setting in seconds.
     */
    public function getTimeout() {
        return $this->timeout;
    }

    
    /**
     * Set the curl connect timeout value. If the timeout is not specified then the 
     * default value is used.
     * 
     * @param integer $connectTimeout Optional connect timeout in seconds.
     */
    public function setConnectTimeout($connectTimeout = self::CONNECTTIMEOUT) {
        $this->connectTimeout = $connectTimeout;
    }
    
    
    /**
     * Get the current curl connect timeout setting.
     * 
     * @return integer The current connect timeout setting.
     */
    public function getConnectTimeout() {
        return $this->connectTimeout;
    }
    
    
    /**
     * Set the curl follow location setting. If not specified then the default value
     * is used.
     * 
     * @param boolean $followLocation Optional flag to specify if location headers are followed.
     */
    public function setFollowLocation($followLocation = self::FOLLOWLOCATION) {
        $this->followLocation = $followLocation;
    }
    
    
    /**
     * Get the current curl follow location setting.
     * 
     * @return boolean The current follow location setting.
     */
    public function getFollowLocation() {
        return $this->followLocation;
    }
    
    
    /**
     * Set the maximum number of redirections to follow. If not specified then the 
     * default value is used.
     * 
     * @param integer $maxRedirs Optional max redirections setting.
     */
    public function setMaxRedirs($maxRedirs = self::MAXREDIRS) {
        $this->maxRedirs = $maxRedirs;
    }
    
    
    /**
     * Get the current maximum number of redirections curl will follow.
     * 
     * @return integer The current max redirections setting.
     */
    public function getMaxRedirs() {
        return $this->maxRedirs;
    }
    
    
    /**
     * Set the curl return transfer setting. If not specified then the default value
     * is used.
     * 
     * @param boolean $returnTransfer Optional flag to specify if the response is returned.
     */
    public function setReturnTransfer($returnTransfer = self::RETURNTRANSFER) {
        $this->returnTransfer = $returnTransfer;
    }
    
    
    /**
     * Get the current curl return transfer setting.
     * 
     * @return boolean The current curl return transfer setting.
     */
    public function getReturnTransfer() {
        return $this->returnTransfer;
    }
    
    
    /**
     * Set the curl header setting. If not specified then the default value is used.
     * 
     * @param boolean $header Optional flag to specify if headers are returned.
     */
    public function setHeader($header = self::HEADER) {
        $this->header = $header;
    }
    
    
    /**
     * Get the current curl header setting.
     * 
     * @return boolean The current curl header setting.
     */
    public function getHeader() {
        return $this->header;
    }
    
    
    /**
     * Get the current curl error string.
     * 
     * @return string The curl error string.
     */
    public function getCurlError() {
        return $this->curlError;
    }
    
    
    /**
     * Get the current curl error number.
     * 
     * @return integer The current curl error number.
     */
    public function getCurlErrorNo() {
        return $this->curlErrorNo;
    }
    
    
    /**
     * Get the current HTTP response code.
     * 
     * @return integer The current curl HTTP response code.
     */
    public function getCurlHTTPCode() {
        return $this->curlHTTPCode;
    }
    
    
    /**
     * Get the current curl response body.
     * 
     * @return string The current curl response body.
     */
    public function getResponseBody() {
        return $this->responseBody;
    }
    
    
    /**
     * Get the current curl response headers.
     * 
     * @return array The current response headers.
     */
    public function getResponseHeaders() {
        return $this->responseHeaders;
    }

}