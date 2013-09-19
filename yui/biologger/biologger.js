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
 * Biometric logging. Registers listeners with TinyMCE and other elements to
 * capture keystroke and mouse information.
 *
 * This was taken from the autosave script by Tim Hunt.
 *
 * @package   local_bioauth
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

YUI.add('moodle-local_bioauth-biologger', function(Y) {
    var BIOLOGGERNAME = 'biologger';
    var BIOLOGGER = function() {
        BIOLOGGER.superclass.constructor.apply(this, arguments);
    };
    Y.extend(BIOLOGGER, Y.Base, {
        /** Delays and repeat counts. */
        TINYMCE_DETECTION_DELAY : 500,
        TINYMCE_DETECTION_REPEATS : 20,
        WATCH_HIDDEN_DELAY : 1000,

        /** Selectors. */
        SELECTORS : {
            QUIZ_FORM : '#responseform',
            VALUE_CHANGE_ELEMENTS : 'input, textarea',
            CHANGE_ELEMENTS : 'input, select',
            HIDDEN_INPUTS : 'input[type=hidden]'
        },

        /** Script that handles the auto-saves. */
        AUTOSAVE_HANDLER : M.cfg.wwwroot + '/local/bioauth/biologger.ajax.php',

        /** Script that handles the auto-saves. */
        ENROLLMENT_HANDLER : M.cfg.wwwroot + '/local/bioauth/enroll.php',

        /** The delay between a change being made, and it being auto-saved. */
        delay : 120000,

        /** The form we are monitoring. */
        form : null,

        /** Whether the form has been modified since the last save started. */
        dirty : false,

        /** Timer object for the delay between form modifaction and the save starting. */
        delay_timer : null,

        /** Y.io transaction for the save ajax request. */
        save_transaction : null,

        /** Key and mouse event handlers. */
        editor_change_handler : null,
        key_down_handler : null,
        key_up_handler : null,
        mouse_down_handler : null,
        mouse_up_handler : null,
        mouse_move_handler : null,

        keystrokes : null,
        stylometry : null,
        currentkeystrokes : null,
        currentstylometry : null,

        initializer : function(params) {
            Y.log('Initializing biologger.');
            this.form = Y.one(this.SELECTORS.QUIZ_FORM);
            if (!this.form) {
                Y.log('No response form found. Why did you try to initialize the biologger?');
                return;
            }

            this.delay = params.delay * 1000;

            this.keystrokes = Array();
            this.stylometry = Array();
            this.mouse = Array();
            this.currentkeystrokes = Array();
            this.currentstylometry = "";

            this.form.delegate('valuechange', this.value_changed, this.SELECTORS.VALUE_CHANGE_ELEMENTS, this);
            this.form.delegate('change', this.value_changed, this.SELECTORS.CHANGE_ELEMENTS, this);
            this.form.on('submit', this.stop_autosaving, this);

            this.init_tinymce(this.TINYMCE_DETECTION_REPEATS);
        },

        /**
         * @param repeatcount Because TinyMCE might load slowly, after us, we need
         * to keep trying every 10 seconds or so, until we detect TinyMCE is there,
         * or enough time has passed.
         */
        init_tinymce : function(repeatcount) {
            if ( typeof tinyMCE === 'undefined') {
                if (repeatcount > 0) {
                    Y.later(this.TINYMCE_DETECTION_DELAY, this, this.init_tinymce, [repeatcount - 1]);
                } else {
                    Y.log('Gave up looking for TinyMCE.');
                }
                return;
            }

            Y.log('Biologger found TinyMCE.');
            this.editor_change_handler = Y.bind(this.editor_changed, this);
            this.key_down_handler = Y.bind(this.key_pressed, this);
            this.key_up_handler = Y.bind(this.key_released, this);
            this.mouse_down_handler = Y.bind(this.mouse_pressed, this);
            this.mouse_up_handler = Y.bind(this.mouse_released, this);
            this.mouse_move_handler = Y.bind(this.mouse_move, this);
            tinyMCE.onAddEditor.add(Y.bind(this.init_tinymce_editor, this));
        },

        /**
         * @param repeatcount Because TinyMCE might load slowly, after us, we need
         * to keep trying every 10 seconds or so, until we detect TinyMCE is there,
         * or enough time has passed.
         */
        init_tinymce_editor : function(notused, editor) {
            Y.log('Biologger found TinyMCE editor ' + editor.id + '.');

            editor.onChange.add(this.editor_change_handler);
            editor.onKeyDown.add(this.key_down_handler);
            editor.onKeyUp.add(this.key_up_handler);
            editor.onMouseDown.add(this.mouse_down_handler);
            editor.onMouseUp.add(this.mouse_up_handler);
            document.onmousemove = this.mouse_move_handler;
            // TODO: Unable to record motion events within the editor text area.
        },

        value_changed : function(e) {
            if (e.target.get('name') === 'thispage' || e.target.get('name') === 'scrollpos' || e.target.get('name').match(/_:flagged$/)) {
                return;
                // Not interesting.
            }
            Y.log('Detected a value change in element ' + e.target.get('name') + '.');
            this.start_save_timer_if_necessary();
        },

        editor_changed : function(ed) {
            Y.log('Detected a value change in editor ' + ed.id + '.');
            this.start_save_timer_if_necessary();
            stylevent = {
                "time" : (new Date()).getTime(),
                "text"      : ed.getContent()
            };
            this.stylometry.push(stylevent);
        },

        key_pressed : function(ed, e) {
            Y.log('Key pressed: ' + e.keyCode + ", @" + e.timeStamp);

            keycode = e.keyCode;
            timestamp = e.timeStamp;

            // Ignore auto-repeated keystrokes.
            if (keycode in this.currentkeystrokes && this.currentkeystrokes[keycode] != 0) {
                return;
            }
            this.currentkeystrokes[keycode] = timestamp;
        },

        key_released : function(ed, e) {
            Y.log('Key released: ' + e.keyCode + ", @" + e.timeStamp);

            keycode = e.keyCode;
            timestamp = e.timeStamp;

            timepress = this.currentkeystrokes[keycode];
            keystroke = {
                "keycode" : keycode,
                "timepress" : timepress,
                "timerelease" : timestamp
            };
            this.keystrokes.push(keystroke);
            this.currentkeystrokes[keycode] = 0;
        },

        mouse_pressed : function(ed, e) {
            Y.log('Mouse pressed: ' + e.target.nodeName + ", @" + e.timeStamp);
            
            mouseevent = {
                "event" : "press",
                "time" : e.timeStamp,
                "x" : e.screenX,
                "y" : e.screenY,
                "button" : e.button
            };
            
            this.mouse.push(mouseevent);
        },

        mouse_released : function(ed, e) {
            Y.log('Mouse released: ' + e.target.nodeName + ", @" + e.timeStamp);
            
            mouseevent = {
                "event" : "release",
                "time" : e.timeStamp,
                "x" : e.screenX,
                "y" : e.screenY,
                "button" : e.button
            };
            
            this.mouse.push(mouseevent);
        },

        mouse_move : function(e) {
            Y.log('Mouse move: ' + e.screenX + ', ' + e.screenY + ", @" + e.timeStamp + ", dragged: " + e.which);
            
            mouseevent = {
                "event" : "motion",
                "time" : e.timeStamp,
                "x" : e.screenX,
                "y" : e.screenY,
                "dragged" : e.which
            };
            
            this.mouse.push(mouseevent);
        },

        start_save_timer_if_necessary : function() {
            this.dirty = true;

            if (this.delay_timer || this.save_transaction) {
                // Already counting down or daving.
                return;
            }

            this.start_save_timer();
        },

        start_save_timer : function() {
            this.cancel_delay();
            this.delay_timer = Y.later(this.delay, this, this.save_changes);
        },

        cancel_delay : function() {
            if (this.delay_timer && this.delay_timer !== true) {
                this.delay_timer.cancel();
            }
            this.delay_timer = null;
        },

        getData : function() {
            return JSON.stringify({
                "keystrokes"    : this.keystrokes,
                "stylometry"    : this.stylometry,
                "mouse"        : this.mouse
            });
        },

        save_changes : function() {
            this.cancel_delay();
            this.dirty = false;

            if (this.is_time_nearly_over()) {
                Y.log('No more saving, time is nearly over.');
                this.stop_autosaving();
                return;
            }

            Y.log('Doing a save.');
            if ( typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }
            // TODO: Finish autosave ajax script on the server.
            // this.save_transaction = Y.io(this.AUTOSAVE_HANDLER, {
                // method : 'POST',
                // form : {
                    // id : this.form
                // },
                // data : {
                    // biodata : this.getData()
                // },
                // on : {
                    // complete : this.save_done
                // },
                // context : this
            // });
        },

        save_done : function() {
            Y.log('Save completed.');
            this.save_transaction = null;

            if (this.dirty) {
                Y.log('Dirty after save.');
                this.start_save_timer();
            }
        },

        is_time_nearly_over : function() {
            return M.mod_quiz.timer && M.mod_quiz.timer.endtime && (new Date().getTime() + 2 * this.delay) > M.mod_quiz.timer.endtime;
        },

        stop_autosaving : function() {
            this.cancel_delay();
            this.delay_timer = true;
            if (this.save_transaction) {
                this.save_transaction.abort();
            }

            this.submit_biodata();
        },

        submit_biodata : function() {
            Y.io(this.ENROLLMENT_HANDLER, {
                sync : true,
                method : 'POST',
                form : {
                    id : this.form
                },
                data : {
                    source : BrowserDetect.browser,
                    useragent : navigator.userAgent,
                    platform : navigator.platform,
                    biodata : this.getData(),
                    numkeystrokes : this.keystrokes.length,
                    numstylometry : this.stylometry.length,
                    nummouseevents : this.mouse.length
                },
                on : {
                    complete : this.submit_done
                },
                context : this
            });
        },

        submit_done : function() {
            Y.log('Save completed.');

            //alert('Submission complete');
        },
    }, {
        NAME : BIOLOGGERNAME,
        ATTRS : {
            aparam : {}
        }
    });

    M.local_biologger = M.local_biologger || {};
    M.local_biologger.init_biologger = function(config) {
        return new BIOLOGGER(config);
    };

}, '@VERSION@', {
    requires : ["base", "node", "event", "event-valuechange", "node-event-delegate", "io-form"]
});
