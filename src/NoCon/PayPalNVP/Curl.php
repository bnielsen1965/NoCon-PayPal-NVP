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
 * Curl class provides curl functions to make PayPal NVP calls.
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



    public function __construct() {
        $this->setUserAgent();
        $this->setTimeout();
        $this->setConnectTimeout();
        $this->setFollowLocation();
        $this->setMaxRedirs();
        $this->setReturnTransfer();
    }
    
    
    
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
    
    
    private function processResponse($response) {
        $this->curlHTTPCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->responseBody = substr($response, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));
        return $this->responseBody;
    }
    
    
    public function processHeader($curl, $header) {
        $this->responseHeaders[] = $header;
        return strlen($header);
    }
    
    
    private function processFailure() {
        $this->curlError = curl_error($this->curl);
        $this->curlErrorNo = curl_errno($this->curl);
    }
    
    
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
            CURLOPT_HEADER          => true,
            CURLOPT_HEADERFUNCTION  => array($this, 'processHeader')
        );
        curl_setopt_array($this->curl, $options);
    }
    
    
    public function setUrl($url) {
        $this->url = $url;
    }
    
    
    public function getUrl() {
        return $this->url;
    }
    
    
    public function setUserAgent($userAgent = self::USERAGENT) {
        $this->userAgent = $userAgent;
    }
    
    
    public function getUserAgent() {
        return $this->userAgent;
    }
    
    
    public function setTimeout($timeout = self::TIMEOUT) {
        $this->timeout = $timeout;
    }
    
    
    public function getTimeout() {
        return $this->timeout;
    }

    public function setConnectTimeout($connectTimeout = self::CONNECTTIMEOUT) {
        $this->connectTimeout = $connectTimeout;
    }
    
    
    public function getConnectTimeout() {
        return $this->connectTimeout;
    }
    
    
    public function setFollowLocation($followLocation = self::FOLLOWLOCATION) {
        $this->followLocation = $followLocation;
    }
    
    
    public function getFollowLocation() {
        return $this->followLocation;
    }
    
    
    public function setMaxRedirs($maxRedirs = self::MAXREDIRS) {
        $this->maxRedirs = $maxRedirs;
    }
    
    
    public function getMaxRedirs() {
        return $this->maxRedirs;
    }
    
    
    public function setReturnTransfer($returnTransfer = self::RETURNTRANSFER) {
        $this->returnTransfer = $returnTransfer;
    }
    
    
    public function getReturnTransfer() {
        return $this->returnTransfer;
    }
    
    
    public function getCurlError() {
        return $this->curlError;
    }
    
    
    public function getCurlErrorNo() {
        return $this->curlErrorNo;
    }
    
    
    public function getCurlHTTPCode() {
        return $this->curlHTTPCode;
    }
    
    
    public function getResponseBody() {
        return $this->responseBody;
    }
    
    
    public function getResponseHeaders() {
        return $this->responseHeaders;
    }

}