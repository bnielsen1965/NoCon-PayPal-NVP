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
 * PayPalIPN class provides base functions to process PayPal IPN requests.
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class PayPalIPN {
    
    /**
     * @var string The default PayPal IPN live endpoint url.
     */
    const ENDPOINT            = 'https://www.paypal.com/cgi-bin/webscr';
    
    /**
     * @var string The default PayPal IPN sandbox endpoint url.
     */
    const ENDPOINT_SANDBOX    = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    
    
    /**
     * @var boolean Live status. When false the sandbox is assumed.
     */
    protected $live;

    /**
     * @var string The last IPN response.
     */
    protected $lastResponse;
    
    /**
     * @var boolean Flag that notes if the last IPN transaction was validated.
     */
    protected $validated;


    /**
     * @var array The current set of error messages.
     */
    protected $errors;

    
    /**
     * Create an initialized instance of PayPalIPN.
     * 
     * The endpoint URL used is based on the live setting if it is not specified
     * in the configuration array.
     * 
     * @param array $config Optional, configuration parameters to use.
     * @param boolean $live Optional, specify if live connection is used.
     */
    public function __construct($config = array(), $live = true) {
        $this->setLive($live);
        $this->setConfiguration($config);
        $this->clearErrors();
    }
    
    
    /**
     * Set the configuration settings for the PayPal IPN requests.
     * 
     * @param array $config Configuration settings.
     */
    public function setConfiguration($config) {
        $config = array_merge($this->getDefaults(), $config);
        foreach ($config as $field => $value) {
            switch ($field) {
                
                default:
                    // unknown or not set here
                    break;
            }
        }
        
        $this->setUrl($config['URL']);        
    }
    
    
    /**
     * Get the configuration default settings for the PayPal IPN requests.
     * 
     * @return array Configuration defaults.
     */
    public function getDefaults() {
        return array(
            'URL' => null,
        );
    }
    
    
    /**
     * Set the live state. If live is false then it is assumed the sandbox will be used.
     * 
     * @param boolean $live The live state to set.
     */
    public function setLive($live) {
        $this->live = $live;
    }
    
    
    /**
     * Get the current live state.
     * 
     * @return boolean The current live state.
     */
    public function getLive() {
        return $this->live;
    }
    
    
    /**
     * Set the URL to use for PayPal IPN endpoint. If no URL is provided then
     * a default value is used based on the live status.
     * 
     * @param string $url Optional URL to use for PayPal IPN endpoint.
     */
    public function setUrl($url = null) {
        if ( empty($url) ) {
            if ( $this->live ) {
                $url = self::ENDPOINT;
            }
            else {
                $url = self::ENDPOINT_SANDBOX;
            }
        }
        
        $this->url = $url;
    }
    
    
    /**
     * Get the current PayPal IPN endpoint that will be used.
     * 
     * @return string The PayPal IPN endpoint URL.
     */
    public function getUrl() {
        return $this->url;
    }


    /**
     * Get the last response from the PayPal IPN call.
     * 
     * @return array The last response name value pairs.
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    
    /**
     * Get the validated status for the last IPN validation request.
     * 
     * @return boolean The validated status.
     */
    public function getValidated() {
        return $this->validated;
    }
    
    
    /**
     * Clear all errors.
     */
    private function clearErrors() {
        $this->errors = array();
    }
    
    
    /**
     * Set a new error message in the errors array.
     * 
     * @param string $error An error message.
     */
    private function setError($error) {
        $this->errors[] = $error;
    }
    
    
    /**
     * Get the current set of error messages.
     * 
     * @return array An array of error messages.
     */
    public function getErrors() {
        return $this->errors;
    }
    
    
    /**
     * Validate a transaction recieved from an IPN notification.
     * 
     * @param array $args The array of parameters from the PayPal IPN notification.
     * @return string The validation response.
     */
    public function validateTransaction($args) {
        $response = $this->ipnCall(array_merge(array('cmd' => '_notify-validate'), $args));
        return $this->processResponse($response);
    }
    
    
    /**
     * Execute an IPN call.
     * 
     * @param array $args The arguements for the API call.
     * @return array The response parameters.
     * @throws \Exception
     */
    public function ipnCall($args) {
        $this->lastResponse = null;
        $this->clearErrors();
        
        $curl = new \NoCon\PayPalNVP\Curl();

        $url = $this->url . '?' . http_build_query($args);

        if ( false === ($response = $curl->execute($url)) ) {
            $this->processFailure($curl);
            throw new \Exception($curl->getCurlError(), $curl->getCurlErrorNo());
        }
        
        return $response;
    }
    
    
    /**
     * Process curl failure during an API call.
     * 
     * @param resource $curl The curl resource.
     */
    private function processFailure($curl) {
        $this->setError($curl->getCurlError());
    }
    
    
    /**
     * Process the text from the API response. A check is made to see if the response
     * contains the VERIFIED message.
     * 
     * @param string $response The body of the response from the API.
     * @return string Returns the response.
     * @throws \Exception
     */
    private function processResponse($response) {
        $this->lastResponse = $response;
        $message = trim(preg_replace('/\s+/', ' ', $response));
        switch ( $message ) {
            case 'VERIFIED':
                $this->validated = true;
                break;
            
            default:
                $this->setError('IPN Validation Failed: ' . $message);
                break;
        }
        
        return $message;
    }
    
    
}