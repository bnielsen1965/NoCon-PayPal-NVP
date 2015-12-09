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
 * PayPalNVP class provides base functions to make PayPal NVP calls.
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class PayPalNVP {
    
    /**
     * @var string The default PayPal NVP API version to use.
     */
    const VERSION                       = '124.0';
    
    /**
     * @var string The default PayPal NVP API live endpoint url when using signature authentication.
     */
    const ENDPOINT_SIGNATURE            = 'https://api-3t.paypal.com/nvp';
    
    /**
     * @var string The default PayPal NVP API sandbox endpoint url when using signature authentication.
     */
    const ENDPOINT_SIGNATURE_SANDBOX    = 'https://api-3t.sandbox.paypal.com/nvp';
    
    /**
     * @var string The default PayPal NVP API live endpoint url when using certificate authentication.
     */
    const ENDPOINT_CERTIFICATE          = 'https://api.paypal.com/nvp';
    
    /**
     * @var string The default PayPal NVP API sandbox endpoint url when using certificate authentication.
     */
    const ENDPOINT_CERTIFICATE_SANDBOX  = 'https://api.sandbox.paypal.com/nvp';
    
    
    /**
     * @var string Live status. When false the sandbox is assumed.
     */
    protected $live;
    
    /**
     * @var string The current user name.
     */
    protected $user;
    
    /**
     * @var string The current user password.
     */
    protected $password;
    
    /**
     * @var string The current user certificate.
     */
    protected $certificate;
    
    /**
     * @var string The current user signature.
     */
    protected $signature;

    /**
     * @var array The set of nvp parameters from the last response.
     */
    protected $lastResponse;
    
    /**
     * @var array The current set of error messages.
     */
    protected $errors;

    /**
     * Construct a User instance connected to the PDO source specified by the
     * passed configuration parameters.
     * 
     * The configuration array is expected to be an associative array with the
     * fields used in the PDO construction...
     * dsn = The dsn string for the PDO connection.
     * username = The username to use in the PDO connection.
     * password = The password to use in the PDO connection.
     * options = An array of PDO connection options.
     * 
     * @param array $config The PDO connection configuration values.
     * @throws \Exception
     */
    public function __construct($config, $live = true) {
        $this->setLive($live);
        $this->setConfiguration($config);
        $this->clearErrors();
    }
    
    
    /**
     * Set the configuration settings for the PayPal NVP API.
     * 
     * @param array $config Configuration settings.
     */
    public function setConfiguration($config) {
        $config = array_merge($this->getDefaults(), $config);
        foreach ($config as $field => $value) {
            switch ($field) {
                case 'USER':
                    $this->setUser($value);
                    break;
                
                case 'PWD':
                    $this->setPassword($value);
                    break;
                
                case 'SIGNATURE':
                    $this->setSignature($value);
                    break;
                
                case 'CERTIFICATE':
                    $this->setCertificate($value);
                    break;
                
                case 'VERSION':
                    $this->setVersion($value);
                    break;
                
                default:
                    // unknown or not set here
                    break;
            }
        }
        
        $this->setUrl($config['URL']);        
    }
    
    
    /**
     * Get the configuration default settings for the PayPal NVP API.
     * 
     * @return array Configuration defaults.
     */
    public function getDefaults() {
        return array(
            'VERSION' => self::VERSION,
            'URL' => null,
        );
    }
    
    
    /**
     * Set the live state for the PayPal NVP API calls. If live is false then
     * it is assumed the sandbox will be used.
     * 
     * @param boolean $live The live state to set.
     */
    public function setLive($live) {
        $this->live = $live;
    }
    
    
    /**
     * Get the current live state for the PayPal NVP API calls.
     * 
     * @return boolean The current live state.
     */
    public function getLive() {
        return $this->live;
    }
    
    
    /**
     * Set the version of the PayPal NVP API that should be used.
     * 
     * @param string $version The PayPal NVP API version to use.
     */
    public function setVersion($version = self::VERSION) {
        $this->version = $version;
    }
    
    
    /**
     * Get the current version of the PayPal NVP API that will be used.
     * 
     * @return string The current version.
     */
    public function getVersion() {
        return $this->version;
    }
    
    
    /**
     * Set the URL to use for PayPal NVP API endpoint. If no URL is provided then
     * a default value is used based on the credentials provided and the live status.
     * 
     * @param string $url Optional URL to use for PayPal NVP API endpoint.
     */
    public function setUrl($url = null) {
        if ( empty($url) ) {
            if ( $this->live ) {
                $url = (!empty($this->certificate) ? self::ENDPOINT_CERTIFICATE : self::ENDPOINT_SIGNATURE);
            }
            else {
                $url = (!empty($this->certificate) ? self::ENDPOINT_CERTIFICATE_SANDBOX : self::ENDPOINT_SIGNATURE_SANDBOX);
            }
        }
        
        $this->url = $url;
    }
    
    
    /**
     * Get the current PayPal NVP API endpoint that will be used.
     * 
     * @return string The PayPal NVP API endpoint URL.
     */
    public function getUrl() {
        return $this->url;
    }


    /**
     * Set the user name to use in the PayPal NVP API calls.
     * 
     * @param string $user The user name.
     */
    public function setUser($user) {
        $this->user = $user;
    }
    
    
    /**
     * Set the user password to use in the PayPal NVP API calls.
     * 
     * @param string $password The user password.
     */
    public function setPassword($password) {
        $this->password = $password;
    }
    
    
    /**
     * Set the user signature to use in the PayPal NVP API calls when using
     * signature based authentication.
     * 
     * @param string $signature The signature string.
     */
    public function setSignature($signature) {
        $this->signature = $signature;
    }
    
    
    /**
     * Set the user certificate to use in the PayPal NVP API calls when using
     * certificate based authentication.
     * 
     * @param string $certificate The certificate string.
     */
    public function setCertificate($certificate) {
        $this->certificate = $certificate;
    }
    
    
    /**
     * Get the name value pair response array for the last PayPal NVP API call.
     * 
     * @return array The last response name value pairs.
     */
    public function getLastResponse() {
        return $this->lastResponse;
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
     * Execute an NVP API call.
     * 
     * @param array $args The arguements for the API call.
     * @return array The response parameters.
     * @throws \Exception
     */
    public function nvpCall($args) {
        $this->lastResponse = null;
        $this->clearErrors();
        
        $callArgs = array(
            'USER'      => $this->user,
            'PWD'       => $this->password,
            'VERSION'   => $this->version,
        );
        
        if ( !empty($this->certificate) ) {
            $callArgs['CERTIFICATE'] = $this->certificate;
        }
        else {
            $callArgs['SIGNATURE'] = $this->signature;
        }
    
        $curl = new \NoCon\PayPalNVP\Curl();

        $url = $this->url . '?' . http_build_query(array_merge($callArgs, $args));

        if ( false === ($response = $curl->execute($url)) ) {
            $this->processFailure($curl);
            throw new \Exception($curl->getCurlError(), $curl->getCurlErrorNo());
        }
        
        return $this->processResponse($response);
    }
    
    
    /**
     * Check if the last call was successful.
     * 
     * @return boolean True if the last call was successful.
     */
    public function callSuccess() {
        return (
            isset($this->lastResponse['ACK']) && in_array($this->lastResponse['ACK'], array('Success', 'SuccessWithWarning')) ?
            true :
            false
        );
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
     * Process the text from the API response into error messages and response parameters.
     * 
     * @param string $response The body of the response from the API.
     * @return array Returns the response parameters from the API if available.
     * @throws \Exception
     */
    private function processResponse($response) {
        $args = array();
        parse_str($response, $args);
        $this->lastResponse = $args;
        $this->processNVPErrors($args);
        
        switch ( $args['ACK'] ) {
            case 'Success':
            case 'SuccessWithWarning':
                break;
            
            case 'Failure':
            case 'FailureWithWarning':
                throw new \Exception('API failure.');
            
            default:
                $this->setError('Unknown ACK: ' . $args['ACK']);
                throw new \Exception('API failure, no ACK.');
        }
        return $args;
    }
    
    
    /**
     * Process the NVP error messages returned in the API response. Errors and warnings.
     * will be added to the class errors.
     * 
     * @param array $response The array of parameters returned in the API response.
     */
    private function processNVPErrors($response) {
        $errorKeys = array_filter(array_keys($response), function ($key) {
            return preg_match('|^L_LONGMESSAGE[0-9]+$|', $key) > 0;
        });
        
        foreach ( $errorKeys as $errorKey ) {
            $index = intval(substr($errorKey, strlen('L_LONGMESSAGE')));
            $this->setError(
                    (isset($response['L_SEVERITYCODE' . $index]) ? $response['L_SEVERITYCODE' . $index] . ' ' : '') .
                    (isset($response['L_ERRORCODE' . $index]) ? $response['L_ERRORCODE' . $index] . ' ' : '') .
                    (isset($response['L_SHORTMESSAGE' . $index]) ? $response['L_SHORTMESSAGE' . $index] . '. ' : '') .
                    (isset($response['L_LONGMESSAGE' . $index]) ? $response['L_LONGMESSAGE' . $index] . '. ' : '')
            );
        }
    }
    
}