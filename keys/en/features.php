<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * 
 *
 * @package local_bioauth
 * @copyright 2013 Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once ($CFG -> dirroot . '/local/bioauth/constants.php');

$allkeys = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,0,1,2,3,4,5,6,7,8,9,comma,period,semicolon,slash,space,backspace,shift,enter';

$letters = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z';
$vowels = 'a,e,i,o,u';
$cons1 = 't,n,s,r,h';
$cons2 = 'l,d,c,p,f';
$cons3 = 'm,w,y,b,g';
$cons4 = 'j,k,q,v,x,z';

$visiblekeys = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,0,1,2,3,4,5,6,7,8,9,comma,period,semicolon,slash';
$invisiblekeys = 'space,backspace,shift,enter';

$lefthand = 'q,w,e,r,t,a,s,d,f,g,z,x,c,v,b,1,2,3,4,5';
$righthand = 'y,u,i,o,p,h,j,k,l,n,m,6,7,8,9,0';

$leftlittle = 'a,z,1,q';
$leftring = 's,x,2,w';
$leftmiddle = 'd,c,4,e';
$leftindex = 'f,b,g,r,4,t,5,v';

$rightlittle = 'semicolon,slash,0,p';
$rightring = 'l,period,9,o';
$rightmiddle = 'k,comma,8,i';
$rightindex = 'h,m,j,y,6,u,7,n';

$leftletters = 'q,w,e,r,t,a,s,d,f,g,z,x,c,v,b';
$rightletters = 'y,u,i,o,p,h,j,k,l,n,m';

$keystrokefeatures = array(

    /* 
     * mean durations
     */
    1 => array(NULL, BIOAUTH_FEATURE_DURATION, $visiblekeys, $visiblekeys, BIOAUTH_MEASURE_MEAN, 0),
    
    2 => array(1, BIOAUTH_FEATURE_DURATION, $lefthand, $lefthand, BIOAUTH_MEASURE_MEAN, 0),
    3 => array(1, BIOAUTH_FEATURE_DURATION, $righthand, $righthand, BIOAUTH_MEASURE_MEAN, 0),
    
    4 => array(2, BIOAUTH_FEATURE_DURATION, $leftlittle, $leftlittle, BIOAUTH_MEASURE_MEAN, 0),
    5 => array(2, BIOAUTH_FEATURE_DURATION, $leftring, $leftring, BIOAUTH_MEASURE_MEAN, 0),
    6 => array(2, BIOAUTH_FEATURE_DURATION, $leftmiddle, $leftmiddle, BIOAUTH_MEASURE_MEAN, 0),
    7 => array(2, BIOAUTH_FEATURE_DURATION, $leftindex, $leftindex, BIOAUTH_MEASURE_MEAN, 0),
    8 => array(3, BIOAUTH_FEATURE_DURATION, $rightlittle, $rightlittle, BIOAUTH_MEASURE_MEAN, 0),
    9 => array(3, BIOAUTH_FEATURE_DURATION, $rightring, $rightring, BIOAUTH_MEASURE_MEAN, 0),
    10 => array(3, BIOAUTH_FEATURE_DURATION, $rightmiddle, $rightmiddle, BIOAUTH_MEASURE_MEAN, 0),
    11 => array(3, BIOAUTH_FEATURE_DURATION, $rightindex, $rightindex, BIOAUTH_MEASURE_MEAN, 0),
    
    // left little
    12 => array(4, BIOAUTH_FEATURE_DURATION, 'a', 'a', BIOAUTH_MEASURE_MEAN, 0),
    13 => array(4, BIOAUTH_FEATURE_DURATION, 'z', 'z', BIOAUTH_MEASURE_MEAN, 0),
    14 => array(4, BIOAUTH_FEATURE_DURATION, '1', '1', BIOAUTH_MEASURE_MEAN, 0),
    15 => array(4, BIOAUTH_FEATURE_DURATION, 'q', 'q', BIOAUTH_MEASURE_MEAN, 0),
    // left ring
    16 => array(5, BIOAUTH_FEATURE_DURATION, 's', 's', BIOAUTH_MEASURE_MEAN, 0),
    17 => array(5, BIOAUTH_FEATURE_DURATION, 'x', 'x', BIOAUTH_MEASURE_MEAN, 0),
    18 => array(5, BIOAUTH_FEATURE_DURATION, '2', '2', BIOAUTH_MEASURE_MEAN, 0),
    19 => array(5, BIOAUTH_FEATURE_DURATION, 'w', 'w', BIOAUTH_MEASURE_MEAN, 0),
    // left middle
    20 => array(6, BIOAUTH_FEATURE_DURATION, 'd', 'd', BIOAUTH_MEASURE_MEAN, 0),
    21 => array(6, BIOAUTH_FEATURE_DURATION, 'c', 'c', BIOAUTH_MEASURE_MEAN, 0),
    22 => array(6, BIOAUTH_FEATURE_DURATION, '3', '3', BIOAUTH_MEASURE_MEAN, 0),
    23 => array(6, BIOAUTH_FEATURE_DURATION, 'e', 'e', BIOAUTH_MEASURE_MEAN, 0),
    // left index
    24 => array(7, BIOAUTH_FEATURE_DURATION, 'f', 'f', BIOAUTH_MEASURE_MEAN, 0),
    25 => array(7, BIOAUTH_FEATURE_DURATION, 'b', 'b', BIOAUTH_MEASURE_MEAN, 0),
    26 => array(7, BIOAUTH_FEATURE_DURATION, 'g', 'g', BIOAUTH_MEASURE_MEAN, 0),
    27 => array(7, BIOAUTH_FEATURE_DURATION, 'r', 'r', BIOAUTH_MEASURE_MEAN, 0),
    28 => array(7, BIOAUTH_FEATURE_DURATION, '4', '4', BIOAUTH_MEASURE_MEAN, 0),
    29 => array(7, BIOAUTH_FEATURE_DURATION, 't', 't', BIOAUTH_MEASURE_MEAN, 0),
    30 => array(7, BIOAUTH_FEATURE_DURATION, '5', '5', BIOAUTH_MEASURE_MEAN, 0),
    31 => array(7, BIOAUTH_FEATURE_DURATION, 'v', 'v', BIOAUTH_MEASURE_MEAN, 0),
    // right index
    32 => array(11, BIOAUTH_FEATURE_DURATION, 'h', 'h', BIOAUTH_MEASURE_MEAN, 0),
    33 => array(11, BIOAUTH_FEATURE_DURATION, 'm', 'm', BIOAUTH_MEASURE_MEAN, 0),
    34 => array(11, BIOAUTH_FEATURE_DURATION, 'j', 'j', BIOAUTH_MEASURE_MEAN, 0),
    35 => array(11, BIOAUTH_FEATURE_DURATION, 'y', 'y', BIOAUTH_MEASURE_MEAN, 0),
    36 => array(11, BIOAUTH_FEATURE_DURATION, '6', '6', BIOAUTH_MEASURE_MEAN, 0),
    37 => array(11, BIOAUTH_FEATURE_DURATION, 'u', 'u', BIOAUTH_MEASURE_MEAN, 0),
    38 => array(11, BIOAUTH_FEATURE_DURATION, '7', '7', BIOAUTH_MEASURE_MEAN, 0),
    39 => array(11, BIOAUTH_FEATURE_DURATION, 'n', 'n', BIOAUTH_MEASURE_MEAN, 0),
    // right middle
    40 => array(10, BIOAUTH_FEATURE_DURATION, 'k', 'k', BIOAUTH_MEASURE_MEAN, 0),
    41 => array(10, BIOAUTH_FEATURE_DURATION, 'comma', 'comma', BIOAUTH_MEASURE_MEAN, 0),
    42 => array(10, BIOAUTH_FEATURE_DURATION, '8', '8', BIOAUTH_MEASURE_MEAN, 0),
    43 => array(10, BIOAUTH_FEATURE_DURATION, 'i', 'i', BIOAUTH_MEASURE_MEAN, 0),
    // right ring
    44 => array(9, BIOAUTH_FEATURE_DURATION, 'l', 'l', BIOAUTH_MEASURE_MEAN, 0),
    45 => array(9, BIOAUTH_FEATURE_DURATION, 'period', 'period', BIOAUTH_MEASURE_MEAN, 0),
    46 => array(9, BIOAUTH_FEATURE_DURATION, '9', '9', BIOAUTH_MEASURE_MEAN, 0),
    47 => array(9, BIOAUTH_FEATURE_DURATION, 'o', 'o', BIOAUTH_MEASURE_MEAN, 0),
    // right little
    48 => array(8, BIOAUTH_FEATURE_DURATION, 'semicolon', 'semicolon', BIOAUTH_MEASURE_MEAN, 0),
    49 => array(8, BIOAUTH_FEATURE_DURATION, 'slash', 'slash', BIOAUTH_MEASURE_MEAN, 0),
    50 => array(8, BIOAUTH_FEATURE_DURATION, '0', '0', BIOAUTH_MEASURE_MEAN, 0),
    51 => array(8, BIOAUTH_FEATURE_DURATION, 'p', 'p', BIOAUTH_MEASURE_MEAN, 0),
    
    /* 
     * stddev durations
     */
    52 => array(NULL, BIOAUTH_FEATURE_DURATION, $visiblekeys, $visiblekeys, BIOAUTH_MEASURE_STDDEV, 0),
    
    53 => array(52, BIOAUTH_FEATURE_DURATION, $lefthand, $lefthand, BIOAUTH_MEASURE_STDDEV, 0),
    54 => array(52, BIOAUTH_FEATURE_DURATION, $righthand, $righthand, BIOAUTH_MEASURE_STDDEV, 0),
    
    55 => array(53, BIOAUTH_FEATURE_DURATION, $leftlittle, $leftlittle, BIOAUTH_MEASURE_STDDEV, 0),
    56 => array(53, BIOAUTH_FEATURE_DURATION, $leftring, $leftring, BIOAUTH_MEASURE_STDDEV, 0),
    57 => array(53, BIOAUTH_FEATURE_DURATION, $leftmiddle, $leftmiddle, BIOAUTH_MEASURE_STDDEV, 0),
    58 => array(53, BIOAUTH_FEATURE_DURATION, $leftindex, $leftindex, BIOAUTH_MEASURE_STDDEV, 0),
    59 => array(54, BIOAUTH_FEATURE_DURATION, $rightlittle, $rightlittle, BIOAUTH_MEASURE_STDDEV, 0),
    60 => array(54, BIOAUTH_FEATURE_DURATION, $rightring, $rightring, BIOAUTH_MEASURE_STDDEV, 0),
    61 => array(54, BIOAUTH_FEATURE_DURATION, $rightmiddle, $rightmiddle, BIOAUTH_MEASURE_STDDEV, 0),
    62 => array(54, BIOAUTH_FEATURE_DURATION, $rightindex, $rightindex, BIOAUTH_MEASURE_STDDEV, 0),
    
    // left little
    63 => array(55, BIOAUTH_FEATURE_DURATION, 'a', 'a', BIOAUTH_MEASURE_STDDEV, 0),
    64 => array(55, BIOAUTH_FEATURE_DURATION, 'z', 'z', BIOAUTH_MEASURE_STDDEV, 0),
    65 => array(55, BIOAUTH_FEATURE_DURATION, '1', '1', BIOAUTH_MEASURE_STDDEV, 0),
    66 => array(55, BIOAUTH_FEATURE_DURATION, 'q', 'q', BIOAUTH_MEASURE_STDDEV, 0),
    // left ring
    67 => array(56, BIOAUTH_FEATURE_DURATION, 's', 's', BIOAUTH_MEASURE_STDDEV, 0),
    68 => array(56, BIOAUTH_FEATURE_DURATION, 'x', 'x', BIOAUTH_MEASURE_STDDEV, 0),
    69 => array(56, BIOAUTH_FEATURE_DURATION, '2', '2', BIOAUTH_MEASURE_STDDEV, 0),
    70 => array(56, BIOAUTH_FEATURE_DURATION, 'w', 'w', BIOAUTH_MEASURE_STDDEV, 0),
    // left middle
    71 => array(57, BIOAUTH_FEATURE_DURATION, 'd', 'd', BIOAUTH_MEASURE_STDDEV, 0),
    72 => array(57, BIOAUTH_FEATURE_DURATION, 'c', 'c', BIOAUTH_MEASURE_STDDEV, 0),
    73 => array(57, BIOAUTH_FEATURE_DURATION, '3', '3', BIOAUTH_MEASURE_STDDEV, 0),
    74 => array(57, BIOAUTH_FEATURE_DURATION, 'e', 'e', BIOAUTH_MEASURE_STDDEV, 0),
    // left index
    75 => array(58, BIOAUTH_FEATURE_DURATION, 'f', 'f', BIOAUTH_MEASURE_STDDEV, 0),
    76 => array(58, BIOAUTH_FEATURE_DURATION, 'b', 'b', BIOAUTH_MEASURE_STDDEV, 0),
    77 => array(58, BIOAUTH_FEATURE_DURATION, 'g', 'g', BIOAUTH_MEASURE_STDDEV, 0),
    78 => array(58, BIOAUTH_FEATURE_DURATION, 'r', 'r', BIOAUTH_MEASURE_STDDEV, 0),
    79 => array(58, BIOAUTH_FEATURE_DURATION, '4', '4', BIOAUTH_MEASURE_STDDEV, 0),
    80 => array(58, BIOAUTH_FEATURE_DURATION, 't', 't', BIOAUTH_MEASURE_STDDEV, 0),
    81 => array(58, BIOAUTH_FEATURE_DURATION, '5', '5', BIOAUTH_MEASURE_STDDEV, 0),
    82 => array(58, BIOAUTH_FEATURE_DURATION, 'v', 'v', BIOAUTH_MEASURE_STDDEV, 0),
    // right index
    83 => array(62, BIOAUTH_FEATURE_DURATION, 'h', 'h', BIOAUTH_MEASURE_STDDEV, 0),
    84 => array(62, BIOAUTH_FEATURE_DURATION, 'm', 'm', BIOAUTH_MEASURE_STDDEV, 0),
    85 => array(62, BIOAUTH_FEATURE_DURATION, 'j', 'j', BIOAUTH_MEASURE_STDDEV, 0),
    86 => array(62, BIOAUTH_FEATURE_DURATION, 'y', 'y', BIOAUTH_MEASURE_STDDEV, 0),
    87 => array(62, BIOAUTH_FEATURE_DURATION, '6', '6', BIOAUTH_MEASURE_STDDEV, 0),
    88 => array(62, BIOAUTH_FEATURE_DURATION, 'u', 'u', BIOAUTH_MEASURE_STDDEV, 0),
    89 => array(62, BIOAUTH_FEATURE_DURATION, '7', '7', BIOAUTH_MEASURE_STDDEV, 0),
    90 => array(62, BIOAUTH_FEATURE_DURATION, 'n', 'n', BIOAUTH_MEASURE_STDDEV, 0),
    // right middle
    91 => array(61, BIOAUTH_FEATURE_DURATION, 'k', 'k', BIOAUTH_MEASURE_STDDEV, 0),
    92 => array(61, BIOAUTH_FEATURE_DURATION, 'comma', 'comma', BIOAUTH_MEASURE_STDDEV, 0),
    93 => array(61, BIOAUTH_FEATURE_DURATION, '8', '8', BIOAUTH_MEASURE_STDDEV, 0),
    94 => array(61, BIOAUTH_FEATURE_DURATION, 'i', 'i', BIOAUTH_MEASURE_STDDEV, 0),
    // right ring
    95 => array(60, BIOAUTH_FEATURE_DURATION, 'l', 'l', BIOAUTH_MEASURE_STDDEV, 0),
    96 => array(60, BIOAUTH_FEATURE_DURATION, 'period', 'period', BIOAUTH_MEASURE_STDDEV, 0),
    97 => array(60, BIOAUTH_FEATURE_DURATION, '9', '9', BIOAUTH_MEASURE_STDDEV, 0),
    98 => array(60, BIOAUTH_FEATURE_DURATION, 'o', 'o', BIOAUTH_MEASURE_STDDEV, 0),
    // right little
    99 => array(59, BIOAUTH_FEATURE_DURATION, 'semicolon', 'semicolon', BIOAUTH_MEASURE_STDDEV, 0),
    100 => array(59, BIOAUTH_FEATURE_DURATION, 'slash', 'slash', BIOAUTH_MEASURE_STDDEV, 0),
    101 => array(59, BIOAUTH_FEATURE_DURATION, '0', '0', BIOAUTH_MEASURE_STDDEV, 0),
    102 => array(59, BIOAUTH_FEATURE_DURATION, 'p', 'p', BIOAUTH_MEASURE_STDDEV, 0),
    
    /*
     * mean type 1 transitions
     */
    103 => array(NULL, BIOAUTH_FEATURE_T1, $letters, $letters, BIOAUTH_MEASURE_MEAN, 1),
    104 => array(103, BIOAUTH_FEATURE_T1, $leftletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    105 => array(103, BIOAUTH_FEATURE_T1, $rightletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    106 => array(103, BIOAUTH_FEATURE_T1, $leftletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    107 => array(103, BIOAUTH_FEATURE_T1, $rightletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    // left/left
    108 => array(104, BIOAUTH_FEATURE_T1, 'e', 'r', BIOAUTH_MEASURE_MEAN, 1),
    109 => array(104, BIOAUTH_FEATURE_T1, 'a', 't', BIOAUTH_MEASURE_MEAN, 1),
    110 => array(104, BIOAUTH_FEATURE_T1, 's', 't', BIOAUTH_MEASURE_MEAN, 1),
    111 => array(104, BIOAUTH_FEATURE_T1, 'r', 'e', BIOAUTH_MEASURE_MEAN, 1),
    112 => array(104, BIOAUTH_FEATURE_T1, 'e', 's', BIOAUTH_MEASURE_MEAN, 1),
    113 => array(104, BIOAUTH_FEATURE_T1, 'e', 'a', BIOAUTH_MEASURE_MEAN, 1),
    // right/right
    114 => array(105, BIOAUTH_FEATURE_T1, 'i', 'n', BIOAUTH_MEASURE_MEAN, 1),
    115 => array(105, BIOAUTH_FEATURE_T1, 'o', 'n', BIOAUTH_MEASURE_MEAN, 1),
    // left/right
    116 => array(106, BIOAUTH_FEATURE_T1, 't', 'h', BIOAUTH_MEASURE_MEAN, 1),
    117 => array(106, BIOAUTH_FEATURE_T1, 'e', 'n', BIOAUTH_MEASURE_MEAN, 1),
    118 => array(106, BIOAUTH_FEATURE_T1, 'a', 'n', BIOAUTH_MEASURE_MEAN, 1),
    119 => array(106, BIOAUTH_FEATURE_T1, 't', 'i', BIOAUTH_MEASURE_MEAN, 1),
    // right/left
    120 => array(107, BIOAUTH_FEATURE_T1, 'n', 'd', BIOAUTH_MEASURE_MEAN, 1),
    121 => array(107, BIOAUTH_FEATURE_T1, 'h', 'e', BIOAUTH_MEASURE_MEAN, 1),
    122 => array(107, BIOAUTH_FEATURE_T1, 'o', 'r', BIOAUTH_MEASURE_MEAN, 1),
    
     /*
     * mean type 1 transitions
     */
    123 => array(NULL, BIOAUTH_FEATURE_T1, $letters, $letters, BIOAUTH_MEASURE_STDDEV, 1),
    124 => array(123, BIOAUTH_FEATURE_T1, $leftletters, $leftletters, BIOAUTH_MEASURE_STDDEV, 1),
    125 => array(123, BIOAUTH_FEATURE_T1, $rightletters, $rightletters, BIOAUTH_MEASURE_STDDEV, 1),
    126 => array(123, BIOAUTH_FEATURE_T1, $leftletters, $rightletters, BIOAUTH_MEASURE_STDDEV, 1),
    127 => array(123, BIOAUTH_FEATURE_T1, $rightletters, $leftletters, BIOAUTH_MEASURE_STDDEV, 1),
    // left/left
    128 => array(124, BIOAUTH_FEATURE_T1, 'e', 'r', BIOAUTH_MEASURE_STDDEV, 1),
    129 => array(124, BIOAUTH_FEATURE_T1, 'a', 't', BIOAUTH_MEASURE_STDDEV, 1),
    130 => array(124, BIOAUTH_FEATURE_T1, 's', 't', BIOAUTH_MEASURE_STDDEV, 1),
    131 => array(124, BIOAUTH_FEATURE_T1, 'r', 'e', BIOAUTH_MEASURE_STDDEV, 1),
    132 => array(124, BIOAUTH_FEATURE_T1, 'e', 's', BIOAUTH_MEASURE_STDDEV, 1),
    133 => array(124, BIOAUTH_FEATURE_T1, 'e', 'a', BIOAUTH_MEASURE_STDDEV, 1),
    // right/right
    134 => array(125, BIOAUTH_FEATURE_T1, 'i', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    135 => array(125, BIOAUTH_FEATURE_T1, 'o', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    // left/right
    136 => array(126, BIOAUTH_FEATURE_T1, 't', 'h', BIOAUTH_MEASURE_STDDEV, 1),
    137 => array(126, BIOAUTH_FEATURE_T1, 'e', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    138 => array(126, BIOAUTH_FEATURE_T1, 'a', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    139 => array(126, BIOAUTH_FEATURE_T1, 't', 'i', BIOAUTH_MEASURE_STDDEV, 1),
    // right/left
    140 => array(127, BIOAUTH_FEATURE_T1, 'n', 'd', BIOAUTH_MEASURE_STDDEV, 1),
    141 => array(127, BIOAUTH_FEATURE_T1, 'h', 'e', BIOAUTH_MEASURE_STDDEV, 1),
    142 => array(127, BIOAUTH_FEATURE_T1, 'o', 'r', BIOAUTH_MEASURE_STDDEV, 1),

        /*
     * mean type 1 transitions
     */
    143 => array(NULL, BIOAUTH_FEATURE_T2, $letters, $letters, BIOAUTH_MEASURE_MEAN, 1),
    144 => array(143, BIOAUTH_FEATURE_T2, $leftletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    145 => array(143, BIOAUTH_FEATURE_T2, $rightletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    146 => array(143, BIOAUTH_FEATURE_T2, $leftletters, $rightletters, BIOAUTH_MEASURE_MEAN, 1),
    147 => array(143, BIOAUTH_FEATURE_T2, $rightletters, $leftletters, BIOAUTH_MEASURE_MEAN, 1),
    // left/left
    148 => array(144, BIOAUTH_FEATURE_T2, 'e', 'r', BIOAUTH_MEASURE_MEAN, 1),
    149 => array(144, BIOAUTH_FEATURE_T2, 'a', 't', BIOAUTH_MEASURE_MEAN, 1),
    150 => array(144, BIOAUTH_FEATURE_T2, 's', 't', BIOAUTH_MEASURE_MEAN, 1),
    151 => array(144, BIOAUTH_FEATURE_T2, 'r', 'e', BIOAUTH_MEASURE_MEAN, 1),
    152 => array(144, BIOAUTH_FEATURE_T2, 'e', 's', BIOAUTH_MEASURE_MEAN, 1),
    153 => array(144, BIOAUTH_FEATURE_T2, 'e', 'a', BIOAUTH_MEASURE_MEAN, 1),
    // right/right
    154 => array(145, BIOAUTH_FEATURE_T2, 'i', 'n', BIOAUTH_MEASURE_MEAN, 1),
    155 => array(145, BIOAUTH_FEATURE_T2, 'o', 'n', BIOAUTH_MEASURE_MEAN, 1),
    // left/right
    156 => array(146, BIOAUTH_FEATURE_T2, 't', 'h', BIOAUTH_MEASURE_MEAN, 1),
    157 => array(146, BIOAUTH_FEATURE_T2, 'e', 'n', BIOAUTH_MEASURE_MEAN, 1),
    158 => array(146, BIOAUTH_FEATURE_T2, 'a', 'n', BIOAUTH_MEASURE_MEAN, 1),
    159 => array(146, BIOAUTH_FEATURE_T2, 't', 'i', BIOAUTH_MEASURE_MEAN, 1),
    // right/left
    160 => array(147, BIOAUTH_FEATURE_T2, 'n', 'd', BIOAUTH_MEASURE_MEAN, 1),
    161 => array(147, BIOAUTH_FEATURE_T2, 'h', 'e', BIOAUTH_MEASURE_MEAN, 1),
    162 => array(147, BIOAUTH_FEATURE_T2, 'o', 'r', BIOAUTH_MEASURE_MEAN, 1),
    
     /*
     * mean type 1 transitions
     */
    163 => array(NULL, BIOAUTH_FEATURE_T2, $letters, $letters, BIOAUTH_MEASURE_STDDEV, 1),
    164 => array(163, BIOAUTH_FEATURE_T2, $leftletters, $leftletters, BIOAUTH_MEASURE_STDDEV, 1),
    165 => array(163, BIOAUTH_FEATURE_T2, $rightletters, $rightletters, BIOAUTH_MEASURE_STDDEV, 1),
    166 => array(163, BIOAUTH_FEATURE_T2, $leftletters, $rightletters, BIOAUTH_MEASURE_STDDEV, 1),
    167 => array(163, BIOAUTH_FEATURE_T2, $rightletters, $leftletters, BIOAUTH_MEASURE_STDDEV, 1),
    // left/left
    168 => array(164, BIOAUTH_FEATURE_T2, 'e', 'r', BIOAUTH_MEASURE_STDDEV, 1),
    169 => array(164, BIOAUTH_FEATURE_T2, 'a', 't', BIOAUTH_MEASURE_STDDEV, 1),
    170 => array(164, BIOAUTH_FEATURE_T2, 's', 't', BIOAUTH_MEASURE_STDDEV, 1),
    171 => array(164, BIOAUTH_FEATURE_T2, 'r', 'e', BIOAUTH_MEASURE_STDDEV, 1),
    172 => array(164, BIOAUTH_FEATURE_T2, 'e', 's', BIOAUTH_MEASURE_STDDEV, 1),
    173 => array(164, BIOAUTH_FEATURE_T2, 'e', 'a', BIOAUTH_MEASURE_STDDEV, 1),
    // right/right
    174 => array(165, BIOAUTH_FEATURE_T2, 'i', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    175 => array(165, BIOAUTH_FEATURE_T2, 'o', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    // left/right
    176 => array(166, BIOAUTH_FEATURE_T2, 't', 'h', BIOAUTH_MEASURE_STDDEV, 1),
    177 => array(166, BIOAUTH_FEATURE_T2, 'e', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    178 => array(166, BIOAUTH_FEATURE_T2, 'a', 'n', BIOAUTH_MEASURE_STDDEV, 1),
    179 => array(166, BIOAUTH_FEATURE_T2, 't', 'i', BIOAUTH_MEASURE_STDDEV, 1),
    // right/left
    180 => array(167, BIOAUTH_FEATURE_T2, 'n', 'd', BIOAUTH_MEASURE_STDDEV, 1),
    181 => array(167, BIOAUTH_FEATURE_T2, 'h', 'e', BIOAUTH_MEASURE_STDDEV, 1),
    182 => array(167, BIOAUTH_FEATURE_T2, 'o', 'r', BIOAUTH_MEASURE_STDDEV, 1),

);
