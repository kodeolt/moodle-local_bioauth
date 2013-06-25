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
 * @package    local_bioauth
 * @copyright  Vinnie Monaco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
global $keymap;
global $defaultkeycodes;
global $agentkeycodes;
 
$keymap = array(
'A',
'B',
'C',
'D',
'E',
'F',
'G',
'H',
'I',
'J',
'K',
'L',
'M',
'N',
'O',
'P',
'Q',
'R',
'S',
'T',
'U',
'V',
'W',
'X',
'Y',
'Z',

'ENTER',
'SPACE',
'TAB',
'ESCAPE',
'BACKSPACE',

'SHIFT',
'CTRL',
'ALT',
'CAPS_LOCK',
'NUM_LOCK',

'0',
'1',
'2',
'3',
'4',
'5',
'6',
'7',
'8',
'9',

'SEMICOLON',
'EQUALS',
'COMMA',
'DASH',
'PERIOD',
'SLASH',
'BACK_QUOTE',
'OPEN_BRACKET',
'BACK_SLASH',
'CLOSE_BRACKET',
'QUOTE',

'LEFT',
'UP',
'RIGHT',
'DOWN',

'INSERT',
'DELETE',
'HOME',
'END',
'PAGE_UP',
'PAGE_DOWN',

'F1',
'F2',
'F3',
'F4',
'F5',
'F6',
'F7',
'F8',
'F9',
'F10',
'F11',
'F12',

'NUMPAD_0',
'NUMPAD_1',
'NUMPAD_2',
'NUMPAD_3',
'NUMPAD_4',
'NUMPAD_5',
'NUMPAD_6',
'NUMPAD_7',
'NUMPAD_8',
'NUMPAD_9',
'NUMPAD_MULTIPLY',
'NUMPAD_ADD',
'NUMPAD_SUBTRACT',
'NUMPAD_DECIMAL',
'NUMPAD_DIVIDE',

'LEFT_WINDOWS',
'RIGHT_WINDOWS',
'LEFT_APPLE',
);


$defaultkeycodes = array();
$defaultkeycodes[65] = 'A';
$defaultkeycodes[66] = 'B';
$defaultkeycodes[67] = 'C';
$defaultkeycodes[68] = 'D';
$defaultkeycodes[69] = 'E';
$defaultkeycodes[70] = 'F';
$defaultkeycodes[71] = 'G';
$defaultkeycodes[72] = 'H';
$defaultkeycodes[73] = 'I';
$defaultkeycodes[74] = 'J';
$defaultkeycodes[75] = 'K';
$defaultkeycodes[76] = 'L';
$defaultkeycodes[77] = 'M';
$defaultkeycodes[78] = 'N';
$defaultkeycodes[79] = 'O';
$defaultkeycodes[80] = 'P';
$defaultkeycodes[81] = 'Q';
$defaultkeycodes[82] = 'R';
$defaultkeycodes[83] = 'S';
$defaultkeycodes[84] = 'T';
$defaultkeycodes[85] = 'U';
$defaultkeycodes[86] = 'V';
$defaultkeycodes[87] = 'W';
$defaultkeycodes[88] = 'X';
$defaultkeycodes[89] = 'Y';
$defaultkeycodes[90] = 'Z';

$defaultkeycodes[13] = 'ENTER';
$defaultkeycodes[32] = 'SPACE';
$defaultkeycodes[9] = 'TAB';
$defaultkeycodes[27] = 'ESCAPE';
$defaultkeycodes[8] = 'BACKSPACE';

$defaultkeycodes[16] = 'SHIFT';
$defaultkeycodes[17] = 'CTRL';
$defaultkeycodes[18] = 'ALT';
$defaultkeycodes[20] = 'CAPS_LOCK';
$defaultkeycodes[144] = 'NUM_LOCK';

$defaultkeycodes[48] = '0';
$defaultkeycodes[49] = '1';
$defaultkeycodes[50] = '2';
$defaultkeycodes[51] = '3';
$defaultkeycodes[52] = '4';
$defaultkeycodes[53] = '5';
$defaultkeycodes[54] = '6';
$defaultkeycodes[55] = '7';
$defaultkeycodes[56] = '8';
$defaultkeycodes[57] = '9';

$defaultkeycodes[59] = 'SEMICOLON';
$defaultkeycodes[61] = 'EQUALS';
$defaultkeycodes[188] = 'COMMA';
$defaultkeycodes[189] = 'DASH';
$defaultkeycodes[190] = 'PERIOD';
$defaultkeycodes[191] = 'SLASH';
$defaultkeycodes[192] = 'BACK_QUOTE';
$defaultkeycodes[219] = 'OPEN_BRACKET';
$defaultkeycodes[220] = 'BACK_SLASH';
$defaultkeycodes[221] = 'CLOSE_BRACKET';
$defaultkeycodes[222] = 'QUOTE';

$defaultkeycodes[37] = 'LEFT';
$defaultkeycodes[38] = 'UP';
$defaultkeycodes[39] = 'RIGHT';
$defaultkeycodes[40] = 'DOWN';

$defaultkeycodes[45] = 'INSERT';
$defaultkeycodes[46] = 'DELETE';
$defaultkeycodes[36] = 'HOME';
$defaultkeycodes[35] = 'END';
$defaultkeycodes[33] = 'PAGE_UP';
$defaultkeycodes[34] = 'PAGE_DOWN';

$defaultkeycodes[112] = 'F1';
$defaultkeycodes[113] = 'F2';
$defaultkeycodes[114] = 'F3';
$defaultkeycodes[115] = 'F4';
$defaultkeycodes[116] = 'F5';
$defaultkeycodes[117] = 'F6';
$defaultkeycodes[118] = 'F7';
$defaultkeycodes[119] = 'F8';
$defaultkeycodes[120] = 'F9';
$defaultkeycodes[121] = 'F10';
$defaultkeycodes[122] = 'F11';
$defaultkeycodes[123] = 'F12';

$defaultkeycodes[96] = 'NUMPAD_0';
$defaultkeycodes[97] = 'NUMPAD_1';
$defaultkeycodes[98] = 'NUMPAD_2';
$defaultkeycodes[99] = 'NUMPAD_3';
$defaultkeycodes[100] = 'NUMPAD_4';
$defaultkeycodes[101] = 'NUMPAD_5';
$defaultkeycodes[102] = 'NUMPAD_6';
$defaultkeycodes[103] = 'NUMPAD_7';
$defaultkeycodes[104] = 'NUMPAD_8';
$defaultkeycodes[105] = 'NUMPAD_9';
$defaultkeycodes[106] = 'NUMPAD_MULTIPLY';
$defaultkeycodes[107] = 'NUMPAD_ADD';
$defaultkeycodes[109] = 'NUMPAD_SUBTRACT';
$defaultkeycodes[110] = 'NUMPAD_DECIMAL';
$defaultkeycodes[111] = 'NUMPAD_DIVIDE';

$defaultkeycodes[91] = 'LEFT_WINDOWS';
$defaultkeycodes[92] = 'RIGHT_WINDOWS';
$defaultkeycodes[224] = 'LEFT_APPLE';


$agentkeycodes = array(
'native' => array(
0 => 'UNKNOWN',
127 => '',
),

'mozilla' => array(
80 => 'A',
),


);
