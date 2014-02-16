/**
 * Biometric logging. Registers listeners to
 * capture keystroke and mouse information.
 *
 * @package   theme_bioauth
 * @copyright 2013 onwards Vinnie Monaco
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var BrowserDetect = {
    
    init : function() {
        this.browser = this.searchString(this.dataBrowser) || "unknown";
        this.version = this.searchVersion(navigator.userAgent) || this.searchVersion(navigator.appVersion) || "an unknown version";
        this.OS = this.searchString(this.dataOS) || "an unknown OS";
    },
    
    searchString : function(data) {
        for (var i = 0; i < data.length; i++) {
            var dataString = data[i].string;
            var dataProp = data[i].prop;
            this.versionSearchString = data[i].versionSearch || data[i].identity;
            if (dataString) {
                if (dataString.indexOf(data[i].subString) != -1) {
                    return data[i].identity;
                }
            } else if (dataProp) {
                return data[i].identity;
            }
        }
    },
    
    searchVersion : function(dataString) {
        var index = dataString.indexOf(this.versionSearchString);
        if (index == -1) {
            return;
        }
        return parseFloat(dataString.substring(index + this.versionSearchString.length + 1));
    },
    
    dataBrowser : [{
        string : navigator.userAgent,
        subString : "Chrome",
        identity : "chrome"
    }, {
        string : navigator.userAgent,
        subString : "OmniWeb",
        versionSearch : "OmniWeb/",
        identity : "omniweb"
    }, {
        string : navigator.vendor,
        subString : "Apple",
        identity : "safari",
        versionSearch : "Version"
    }, {
        prop : window.opera,
        identity : "opera",
        versionSearch : "Version"
    }, {
        string : navigator.vendor,
        subString : "iCab",
        identity : "icab"
    }, {
        string : navigator.vendor,
        subString : "KDE",
        identity : "konqueror"
    }, {
        string : navigator.userAgent,
        subString : "Firefox",
        identity : "firefox"
    }, {
        string : navigator.vendor,
        subString : "Camino",
        identity : "camino"
    }, {// for newer Netscapes (6+)
        string : navigator.userAgent,
        subString : "Netscape",
        identity : "netscape"
    }, {
        string : navigator.userAgent,
        subString : "MSIE",
        identity : "MSIE",
        versionSearch : "MSIE"
    }, {
        string : navigator.userAgent,
        subString : "Gecko",
        identity : "mozilla",
        versionSearch : "rv"
    }, {// for older Netscapes (4-)
        string : navigator.userAgent,
        subString : "Mozilla",
        identity : "netscape",
        versionSearch : "Mozilla"
    }],
    
    dataOS : [{
        string : navigator.platform,
        subString : "Win",
        identity : "windows"
    }, {
        string : navigator.platform,
        subString : "Mac",
        identity : "mac"
    }, {
        string : navigator.userAgent,
        subString : "iPhone",
        identity : "iphone"
    }, {
        string : navigator.platform,
        subString : "Linux",
        identity : "linux"
    }]
};

BrowserDetect.init();

var KeyManager = {
    
    "default" : {
        0 : 'unknown',
        8 : 'backspace',
        9 : 'tab',
        13 : 'enter',
        16 : 'shift',
        17 : 'ctrl',
        18 : 'alt',
        19 : 'pause',
        20 : 'caps_lock',
        27 : 'escape',
        32 : 'space',
        33 : 'page_up',
        34 : 'page_down',
        35 : 'end',
        36 : 'home',
        37 : 'left',
        38 : 'up',
        39 : 'right',
        40 : 'down',
        45 : 'insert',
        46 : 'delete',
        48 : '0',
        49 : '1',
        50 : '2',
        51 : '3',
        52 : '4',
        53 : '5',
        54 : '6',
        55 : '7',
        56 : '8',
        57 : '9',
        59 : 'semicolon',
        61 : 'equals',
        65 : 'a',
        66 : 'b',
        67 : 'c',
        68 : 'd',
        69 : 'e',
        70 : 'f',
        71 : 'g',
        72 : 'h',
        73 : 'i',
        74 : 'j',
        75 : 'k',
        76 : 'l',
        77 : 'm',
        78 : 'n',
        79 : 'o',
        80 : 'p',
        81 : 'q',
        82 : 'r',
        83 : 's',
        84 : 't',
        85 : 'u',
        86 : 'v',
        87 : 'w',
        88 : 'x',
        89 : 'y',
        90 : 'z',
        91 : 'left_windows',
        92 : 'right_windows',
        96 : 'numpad_0',
        97 : 'numpad_1',
        98 : 'numpad_2',
        99 : 'numpad_3',
        100 : 'numpad_4',
        101 : 'numpad_5',
        102 : 'numpad_6',
        103 : 'numpad_7',
        104 : 'numpad_8',
        105 : 'numpad_9',
        106 : 'numpad_multiply',
        107 : 'numpad_add',
        109 : 'numpad_subtract',
        110 : 'numpad_decimal',
        111 : 'numpad_divide',
        112 : 'f1',
        113 : 'f2',
        114 : 'f3',
        115 : 'f4',
        116 : 'f5',
        117 : 'f6',
        118 : 'f7',
        119 : 'f8',
        120 : 'f9',
        121 : 'f10',
        122 : 'f11',
        123 : 'f12',
        144 : 'num_lock',
        145 : 'scroll_lock',
        188 : 'comma',
        189 : 'dash',
        190 : 'period',
        191 : 'slash',
        192 : 'back_quote',
        219 : 'open_bracket',
        220 : 'back_slash',
        221 : 'close_bracket',
        222 : 'quote',
        224 : 'left_apple',
    },
    
    "chrome" : {
        186 : 'semicolon',
        187 : 'equals',
        189 : 'dash',
        91 : 'windows',
        93 : 'menu',
    },
    
    "safari" : {
        186 : 'semicolon',
        187 : 'equals',
        189 : 'dash',
        91 : 'windows',
        93 : 'menu',
    },
    
    "firefox" : {
        107 : 'equals',
        109 : 'dash',
        91 : 'windows',
        93 : 'menu',
    },
    
    "native" : {
        10 : 'enter',
        24 : 'quote',
        44 : 'comma',
        45 : 'dash',
        46 : 'period',
        47 : 'slash',
        91 : 'open_bracket',
        92 : 'back_slash',
        93 : 'close_bracket',
        127 : 'delete',
        129 : 'back_quote',
        151 : '8',
        152 : 'quote',
        154 : 'print_screen',
        155 : 'insert',
        156 : 'help',
        157 : 'alt',
        160 : 'comma',
        161 : 'open_bracket',
        162 : 'close_bracket',
        512 : '2',
        513 : 'semicolon',
        515 : '4',
        517 : '1',
        519 : '9',
        520 : '3',
        521 : 'equals',
        522 : '0',
        523 : 'dash',
        524 : 'windows',
        525 : 'menu',
    },
    
    "printable" : {
          "shift" : true,
          'space' : true,
          '0' : true,
          '1' : true,
          '2' : true,
          '3' : true,
          '4' : true,
          '5' : true,
          '6' : true,
          '7' : true,
          '8' : true,
          '9' : true,
          'semicolon' : true,
          'equals' : true,
          'a' : true,
          'b' : true,
          'c' : true,
          'd' : true,
          'e' : true,
          'f' : true,
          'g' : true,
          'h' : true,
          'i' : true,
          'j' : true,
          'k' : true,
          'l' : true,
          'm' : true,
          'n' : true,
          'o' : true,
          'p' : true,
          'q' : true,
          'r' : true,
          's' : true,
          't' : true,
          'u' : true,
          'v' : true,
          'w' : true,
          'x' : true,
          'y' : true,
          'z' : true,
          'numpad_0' : true,
          'numpad_1' : true,
          'numpad_2' : true,
          'numpad_3' : true,
          'numpad_4' : true,
          'numpad_5' : true,
          'numpad_6' : true,
          'numpad_7' : true,
          'numpad_8' : true,
          'numpad_9' : true,
          'numpad_multiply' : true,
          'numpad_add' : true,
          'numpad_subtract' : true,
          'numpad_decimal' : true,
          'numpad_divide' : true,
          'comma' : true,
          'dash' : true,
          'period' : true,
          'slash' : true,
          'back_quote' : true,
          'open_bracket' : true,
          'back_slash' : true,
          'close_bracket' : true,
          'quote' : true,
    },
    
    keyname : function(keycode) {
        if (BrowserDetect.browser in this && keycode in this[BrowserDetect.browser]) {
            return this[BrowserDetect.browser][keycode];
        } else if (keycode in this["default"]) {
            return this["default"][keycode];
        }
        
        console.log("Warning: no agent/keycode combination for agent: " + BrowserDetect.browser + " and keycode: " + keycode);
        return "UNKNOWN";
    },
    
    isPrintableKeycode : function(keycode) {
        return this.keyname(keycode) in this["printable"];
    },
    
    isPrintableKeypress : function(evt) {
        if (typeof evt.which == "undefined") {
            // This is IE, which only fires keypress events for printable keys
            return true;
        } else if (typeof evt.which == "number" && evt.which > 0) {
            // In other browsers except old versions of WebKit, evt.which is
            // only greater than zero if the keypress is a printable key.
            // We need to filter out backspace and ctrl/alt/meta key combinations
            return !evt.ctrlKey && !evt.metaKey && !evt.altKey && evt.which != 8 && evt.which != 13;
        }
        
        return false;
    },
};