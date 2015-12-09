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
 * ButtonManager class provides functions to make PayPal NVP ButtonManager calls.
 * 
 * https://developer.paypal.com/docs/classic/api/#pps
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class ButtonManager extends PayPalNVP {
    /**
     * @var string Default start date.
     */
    const STARTDATE = '1999-01-01T00:00:00Z';
    
    
    protected $buttons;
    
    
    /**
     * Create a hosted button.
     * 
     * @param string $name A name for the button item.
     * @param float $amount The price on the button.
     * @param string $type Optional button type.
     * @param string $subType Optional button subtype.
     * @return boolean|array False on failure or the new button details.
     */
    public function createButton($name, $amount, $type = 'BUYNOW', $subType = 'PRODUCTS') {
        $button = $this->bmCreateButton(array(
            'BUTTONTYPE' => $type,
            'BUTTONSUBTYPE' => $subType,
            'L_BUTTONVAR0' => 'item_name=' . $name,
            'L_BUTTONVAR1' => 'amount=' . $amount,
        ));
        
        return ($this->callSuccess() ? $button : false);
    }
    
    
    /**
     * Update the details of a hosted button.
     * 
     * @param string $buttonId The id of the hosted button.
     * @param string $name A name for the button item.
     * @param float $amount The price on the button.
     * @param string $type Optional button type.
     * @param string $subType Optional button subtype.
     * @return boolean|array False on failure or the new button details.
     */
    public function updateButton($buttonId, $name, $amount, $type = 'BUYNOW', $subType = 'PRODUCTS') {
        $button = $this->bmUpdateButton(array(
            'HOSTEDBUTTONID' => $buttonId,
            'BUTTONTYPE' => $type,
            'BUTTONSUBTYPE' => $subType,
            'L_BUTTONVAR0' => 'item_name=' . $name,
            'L_BUTTONVAR1' => 'amount=' . $amount,
        ));
        
        return ($this->callSuccess() ? $button : false);
    }
    
    
    /**
     * Delete a hosted button.
     * 
     * @param string $buttonId The id of the hosted button.
     * @return boolean The delete success status.
     */
    public function deleteButton($buttonId) {
        $parameters = array(
            'HOSTEDBUTTONID'    => $buttonId,
            'BUTTONSTATUS'      => 'DELETE'
        );
        
        $this->bmManageButtonStatus($parameters);
        return $this->callSuccess();
    }
    
    
    /**
     * Search for hosted buttons in the specified date range. If no range is provided 
     * then the default range is used and will try to find all buttons.
     * 
     * @param string $startDate Optional start date in UTC, i.e. 2015-01-04T04:00:00Z
     * @param string $endDate Optional end date in UTC, i.e. 2015-12-04T10:00:00Z
     * @return boolean|array Returns false on failure or an array of button details.
     */
    public function searchButtons($startDate = self::STARTDATE, $endDate = null) {
        $parameters = array(
            'STARTDATE'     => $startDate
        );
        
        if ( !empty($endDate) ) {
            $parameters['ENDDATE'] = $endDate;
        }
        
        $buttons = $this->bmButtonSearch($parameters);
        return ($this->callSuccess() ? $buttons : false);
    }
    
    
    /**
     * Get the details for a specific hosted button.
     * 
     * @param string $buttonId The id of the hosted button.
     * @return boolean|array Returns false on failure or an array with button details.
     */
    public function getButton($buttonId) {
        $parameters = array(
            'HOSTEDBUTTONID'    => $buttonId,
        );
        
        $button = $this->bmGetButtonDetails($parameters);
        return ($this->callSuccess() ? $button : false);
    }
    
    
    
    
    public function bmButtonSearch($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMButtonSearch',
        ));
        
        $response = $this->nvpCall($args);
        $this->extractButtons($response);
        return $this->buttons;
    }
    
    
    public function bmCreateButton($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMCreateButton',
        ));
        
        return $this->nvpCall($args);
    }
    
    
    public function bmUpdateButton($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMUpdateButton',
        ));
        
        $response = $this->nvpCall($args);
        return $response;
    }
    
    
    public function bmManageButtonStatus($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMManageButtonStatus',
        ));
        
        $response = $this->nvpCall($args);
        return $response;
    }
    
    
    public function bmGetButtonDetails($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMGetButtonDetails',
        ));
        
        $response = $this->nvpCall($args);
        return $response;
    }
    
    
    /**
     * Extract the button variables into an associative array.
     * 
     * @param array $button The button details i.e. from a getButton() call.
     * @return array An associative array of variables extracted from button details.
     */
    public static function extractButtonVariables($button) {
        $vars = array();
        foreach ( $button as $var => $val ) {
            if ( preg_match('|^L_BUTTONVAR[0-9]+$|', $var) ) {
                $varArgs = explode('=', trim($val, '"'));
                if ( strlen($varArgs[0]) ) {
                    $vars[$varArgs[0]] = (count($varArgs) > 1 ? $varArgs[1] : '');
                }
            }
        }
        
        return $vars;
    }
    
    
    /**
     * Extract the button details from the response.
     * 
     * @param array $response An array of button details.
     */
    private function extractButtons($response) {
        $match = array();
        $buttons = array();
        foreach ( $response as $name => $value ) {
            if ( preg_match('|^L_HOSTEDBUTTONID([0-9]+)$|', $name, $match) ) {
                $buttons[$match[1]] = $value;
            }
        }
        
        array_walk($buttons, function(&$button, $index) use($response) {
            $button = array('HOSTEDBUTTONID' => $button);
            
            if ( isset($response['L_BUTTONTYPE' . $index]) ) {
                $button['BUTTONTYPE'] = $response['L_BUTTONTYPE' . $index];
            }
            
            if ( isset($response['L_ITEMNAME' . $index]) ) {
                $button['ITEMNAME'] = $response['L_ITEMNAME' . $index];
            }
            
            if ( isset($response['L_MODIFYDATE' . $index]) ) {
                $button['MODIFYDATE'] = $response['L_MODIFYDATE' . $index];
            }
        });
        
        $this->buttons = $buttons;
    }
    
    

}