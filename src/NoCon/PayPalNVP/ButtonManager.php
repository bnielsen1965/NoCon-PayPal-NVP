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
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class ButtonManager extends PayPalNVP {
    /**
     * @var integer Default start time.
     */
    const STARTTIME = 915148800; // 1999-01-01T00:00:00Z
    
    
    /**
     * Button manager button search.
     * 
     * @return type
     */
    public function bmButtonSearch($startTime = self::STARTTIME, $endTime = null) {
        $startDate = gmdate('Y-m-d\TH:i:s\Z', $startTime);
        $endDate = (empty($endTime) ? null : gmdate('Y-m-d\TH:i:s\Z', $endTime));
        
        $args = array(
            'METHOD'        => 'BMButtonSearch',
            'STARTDATE'     => $startDate
        );
        
        if ( !empty($endDate) ) {
            $args['ENDDATE'] = $endDate;
        }
        
        return $this->nvpCall($args);
    }
    
    

}