/**
 * Biometric logging. Registers listeners to
 * capture keystroke and mouse information.
 *
 * @package   theme_bioauth
 * @copyright 2013 onwards Vinnie Monaco
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random()*16|0, v = c === 'x' ? r : (r&0x3|0x8);
        return v.toString(16);
    });
};

function Biologger(params, userid, sesskey, enrollURL, flushDelay) {
    console.log("Biologger init");
    
    var that = this; // Used in several closures below
    
    // Default parameters
    this.flushDelay = flushDelay ? flushDelay : 1000;
    this.userid = userid ? userid : 0;
    this.sesskey = sesskey ? sesskey : 0;
    this.enrollURL = enrollURL ? enrollURL : "http://moodle.vmonaco.com/local/bioauth/enroll.ajax.php";
    
    // Create a session uuid for this logging session
    // This is different than the user's browsing session
    this.session = uuid();
    
    // Key/button states
    this.keysdown = [];
    this.buttonsdown = [];
    this.dragging = 0;
    this.stylometry = "";
    this.stylometryBegin = 0;

    this.buffer = {
        keystroke : [],
        stylometry : [],
        mousemotion : [],
        mouseclick : [],
        mousescroll: [],
        touch : [],
    };
    
    // return an object containing the buffers encoded as json strings 
    this.jsonData = function() {
        var bd = {};
        for (b in this.buffer) {
            if (this.buffer[b].length > 0) {
                bd[b] = this.buffer[b];
            }
        };
        
        return {
            userid: this.userid,
            sesskey: this.sesskey,
            session: this.session,
            task: document.URL,
            tags: document.title,
            useragent: navigator.userAgent,
            appversion: navigator.appVersion,
            biodata: JSON.stringify(bd),
        };
    };
    
    this.startLogging = function() {
        console.log("Start logging");
        
        for (e in this.events) {
            $(document).unbind(e).on(e, function(e) { return function(evtData) { that.events[e].call(that, evtData); }; }(e) );
        };
        
        this.flushTimer = setInterval(function() {that.flushBuffer();}, this.flushDelay);
    };
    
    this.stopLogging = function() {
        console.log("Stop logging");
        clearInterval(this.flushTimer);
        
        this.pushStylometryEvent();
        
        for (e in this.events) {
            $(document).unbind(e);
        };
        
        if (this.bufferSize() === 0) {
            // console.log("Skipping flush, 0 events.");
            return;
        }
        
        $.ajax({
            type: "POST",
            url: this.enrollURL,
            async: false,
            data: this.jsonData(),
        })
            .done(function(msg) {
                console.log(msg);
        });
        
        console.log("Sent " + this.bufferSize() + " events");
        this.emptyBuffer();
    };
    
    this.bufferSize = function() {
        var size = 0;
        for (b in this.buffer) {
            size += this.buffer[b].length;
        };
        return size;
    };
    
    this.emptyBuffer = function() {
        for (b in this.buffer) {
            this.buffer[b] = [];
        };
    };
    
    this.flushBuffer = function() {
        
        if (this.bufferSize() === 0) {
            // console.log("Skipping flush, 0 events.");
            return;
        }
        
        $.ajax({
            type: "POST",
            url: this.enrollURL,
            async: true,
            data: this.jsonData(),
        })
            .done(function(msg) {
                console.log(msg);
        });
        
        console.log("Sent " + this.bufferSize() + " events");
        this.emptyBuffer();
    };
    
    this.keypress = function(e) {
        console.log("Key press: ", e.keyCode, String.fromCharCode(e.keyCode), e.timeStamp);
        
        // Not a printable character
        if (!KeyManager.isPrintableKeypress(e)) {
            this.pushStylometryEvent();
            return;
        }
        
        if (this.stylometry.length == 0) {
            this.stylometryBegin = Date.now();
        }
        
        this.stylometry += String.fromCharCode(e.keyCode);
    };
    
    this.pushStylometryEvent = function() {
        // Only need to push if the stylometry text buffer has data 
        if (this.stylometry.length == 0) {
            return;
        }
        
        console.log("Pushing stylometry event: " + this.stylometry);
        
        this.buffer.stylometry.push({
            "timestart" : this.stylometryBegin,
            "timefinish" : Date.now(),
            "text" : this.stylometry,
        });
        
        this.stylometry = "";
    };
    
    this.keydown = function(e) {
        // Ignore repeated keystrokes.
        if (e.keyCode in this.keysdown && this.keysdown[e.keyCode]) {
            return;
        }
        console.log("Key down: ", e.keyCode, e.timeStamp);
        
        this.keysdown[e.keyCode] = e.timeStamp;
    };
    
    this.keyup = function(e) {
        // Ignore keys that haven't already been pressed
        if (!this.keysdown[e.keyCode]) {
            return;
        }
        console.log("Key up: " ,e.keyCode, e.timeStamp);
        
        this.buffer.keystroke.push({
            "keyname" : KeyManager.keyname(e.keyCode),
            "keycode" : e.keyCode,
            "timepress" : this.keysdown[e.keyCode],
            "timerelease" : e.timeStamp,
            "target" : e.target.cloneNode(false).outerHTML
        });
        
        this.keysdown[e.keyCode] = false;
        
        // Not a printable character
        if (!KeyManager.isPrintableKeycode(e.keyCode)) {
            this.pushStylometryEvent();
        }
    };
    
    this.mousedown = function(e) {
        console.log("Mouse pressed: ", e.timeStamp);
    
        this.dragging = e.button;
        
        this.buttonsdown[e.button] = {
            "timepress" : e.timeStamp,
            "xpress" : e.screenX,
            "ypress" : e.screenY,
            "xclientpress" : e.clientX,
            "yclientpress" : e.clientY,
            "xoffsetpress" : e.offsetX,
            "yoffsetpress" : e.offsetY,
            "targetpress" : e.target.cloneNode(false).outerHTML
        };
        
        this.pushStylometryEvent();
    };
    
    this.mouseup = function(e) {
        // Ignore buttons that haven't already been pressed
        if (!this.buttonsdown[e.button]) {
            return;
        }
        
        console.log("Mouse released: ", e.timeStamp);
        
        this.buffer.mouseclick.push({
            "timepress" : this.buttonsdown[e.button]['timepress'],
            "timerelease" : e.timeStamp,
            "xpress"    : this.buttonsdown[e.button]['xpress'],
            "ypress"    : this.buttonsdown[e.button]['ypress'],
            "xclientpress" : this.buttonsdown[e.button]['xclientpress'],
            "yclientpress" : this.buttonsdown[e.button]['yclientpress'],
            "xoffsetpress" : this.buttonsdown[e.button]['xoffsetpress'],
            "yoffsetpress" : this.buttonsdown[e.button]['yoffsetpress'],
            "xrelease" : e.screenX,
            "yrelease" : e.screenY,
            "xclientrelease" : e.clientX,
            "yclientrelease" : e.clientY,
            "xoffsetrelease" : e.offsetX,
            "yoffsetrelease" : e.offsetY,
            "button" : e.button,
            "targetrelease" : e.target.cloneNode(false).outerHTML
        });
        
        this.buttonsdown[e.button] = false;
    };
    
    this.mousemove = function(e) {
        console.log("Mouse move: ", e.screenX, e.screenY, e.timeStamp);
        
        this.buffer.mousemotion.push({
            "time" : e.timeStamp,
            "x" : e.screenX,
            "y" : e.screenY,
            "xpage" : e.pageX,
            "ypage" : e.pageY,
            "xclient" : e.clientX,
            "yclient" : e.clientY,
            "dragged" : this.dragging,
            "target" : e.target.cloneNode(false).outerHTML
        });
    };
    
    this.mousewheel = function(e) {
        console.log("Mouse scroll: ", e.deltaX, e.deltaY, e.deltaFactor);
        
        this.buffer.mousescroll.push({
                "time" : e.timeStamp,
                "xdelta" : e.deltaX,
                "ydelta" : e.deltaY,
                "deltafactor" : e.deltaFactor,
                "target" : e.target.cloneNode(false).outerHTML
        });
    };
    
    this.touchstart = function(e) {
        
    };
    
    this.touchmove = function(e) {
        
    };
    
    this.touchend = function(e) {
        
    };
    
    // Keep all the events/callbacks in a map
    // Some callbacks might be used for more than one event name
    this.events = {
        keypress: that.keypress,
        keydown: that.keydown,
        keyup : that.keyup,
        mousedown : that.mousedown,
        mouseup : that.mouseup,
        mousemove : that.mousemove,
        mousewheel : that.mousewheel,
        
        // touchstart : that.touchstart,
        // touchmove : that.touchmove,
        // touchend : that.touchend,
        
        // devicemotion : that.ondevicemotion,
        
        // tapstart : that.ontapstart,
        // tapend : that.ontapend
    };
    
    $(window).on("load",function() {
        that.startLogging();
    })
    
    ;$(window).on("beforeunload",function() {
        that.stopLogging();
    });
};

