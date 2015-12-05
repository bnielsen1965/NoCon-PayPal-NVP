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
     * Button manager button search.
     * 
     * @return array An array of button details.
     */
    public function bmButtonSearch($startDate = self::STARTDATE, $endDate = null) {
        $args = array(
            'METHOD'        => 'BMButtonSearch',
            'STARTDATE'     => $startDate
        );
        
        if ( !empty($endDate) ) {
            $args['ENDDATE'] = $endDate;
        }
        
        $response = $this->nvpCall($args);
        $this->extractButtons($response);
        return $this->buttons;
    }
    
    
    public function bmCreateButton($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMCreateButton',
        ));
        
        $response = $this->nvpCall($args);
        print_r($response);
        return $response;
    }
    
    
    public function bmUpdateButton($parameters) {
        $args = array_merge($parameters, array(
            'METHOD'        => 'BMUpdateButton',
        ));
        
        $response = $this->nvpCall($args);
        print_r($response);
        return $response;
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
            $button = array('id' => $button);
            
            if ( isset($response['L_BUTTONTYPE' . $index]) ) {
                $button['buttonType'] = $response['L_BUTTONTYPE' . $index];
            }
            
            if ( isset($response['L_ITEMNAME' . $index]) ) {
                $button['itemName'] = $response['L_ITEMNAME' . $index];
            }
            
            if ( isset($response['L_MODIFYDATE' . $index]) ) {
                $button['modifyDate'] = $response['L_MODIFYDATE' . $index];
            }
        });
        
        $this->buttons = $buttons;
    }
    
    

}