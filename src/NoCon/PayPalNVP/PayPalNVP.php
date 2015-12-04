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
class PayPalNVP {
    
    const VERSION                       = '124.0';
    const ENDPOINT_SIGNATURE            = 'https://api-3t.paypal.com/nvp';
    const ENDPOINT_SIGNATURE_SANDBOX    = 'https://api-3t.sandbox.paypal.com/nvp';
    const ENDPOINT_CERTIFICATE          = 'https://api.paypal.com/nvp';
    const ENDPOINT_CERTIFICATE_SANDBOX  = 'https://api.sandbox.paypal.com/nvp';
    
    
    protected $live;
    protected $user;
    protected $password;
    protected $certificate;
    protected $signature;

    protected $lastResponse;
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
        $errors = array();
    }
    
    
    public function getDefaults() {
        return array(
            'VERSION' => self::VERSION,
            'URL' => null,
        );
    }
    
    
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
    
    
    public function setLive($live) {
        $this->live = $live;
    }
    
    
    public function getLive() {
        return $this->live;
    }
    
    
    public function setVersion($version = self::VERSION) {
        $this->version = $version;
    }
    
    
    public function getVersion() {
        return $this->version;
    }
    
    
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
    
    
    public function getUrl() {
        return $this->url;
    }


    public function setUser($user) {
        $this->user = $user;
    }
    
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    
    public function setSignature($signature) {
        $this->signature = $signature;
    }
    
    
    public function setCertificate($certificate) {
        $this->certificate = $certificate;
    }
    
    
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    
    private function clearErrors() {
        $this->errors = array();
    }
    
    
    private function setError($error) {
        $this->errors[] = $error;
    }
    
    
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