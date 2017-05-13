requirejs.config({
    paths: {
        handlebars: "./libs/handlebars-v4.0.5",
        jquery: "./libs/jquery-3.1.1.min",
        leaflet: "./libs/leaflet.min",
        leafletGoogle: "./libs/leaflet-google",
        leafletClusterer: "./libs/leaflet-markercluster.min",
        lodash: "./libs/lodash.min",
        text: "./libs/text",
        utils: "./libs/utils",
        vue: "./libs/vue.min"
    },
    shim: {
        leafletGoogle: ["leaflet"],
        leafletClusterer: ["leaflet"]
    }
});


requirejs([
    "jquery", "vue", "lodash",
    "leaflet", "leafletGoogle", "leafletClusterer",
    "utils",
    "components/survey.component"
    
], function($, Vue, _, L, LG, LC, Utils, SurveyComponent) {
    "use strict";
    
    var componentMap = {
        "SurveyMapComponent": SurveyComponent
    };
    
    
    var state = {
        pins: {
            blue: Utils.generatePin(L, "blue"),
            green: Utils.generatePin(L, "green"),
            orange: Utils.generatePin(L, "orange"),
            purple: Utils.generatePin(L, "purple"),
            red: Utils.generatePin(L, "red")
        },
        map: null,
        methods: {
            toggleElem: toggleElem,
            addControl: addMapControl,
            removeControl: removeMapControl,
            addAction: addMapAction,
            removeAction: removeMapAction,
            showDetail: showMapDetail,
            hideDetail: hideMapDetail,
            selectPosition: selectMapPosition,
            toggleOverlay: toggleMapOverlay
        },
        components: {}
    };
    
    function toggleElem(selector, toggle) {
        $(selector).toggleClass("active", toggle);
    }
    
    function addMapControl(id, html) {
        
        // Remove previous instances of the control
        var controlSelector = "#map-controls #" + id;
        $(controlSelector).remove();
        
        // Add the control
        var control = "<div id=\"" + id + "\">" + html + "</div>";
        $("#map-controls .inner").append(control);
        
        var children = $("#map-controls .inner").children().length;
        toggleElem("#map-controls", children > 0);
    }
    
    function removeMapControl(id) {
        $("#map-controls #" + id).remove();
    }
    
    function showMapDetail(title, html, onClose) {
        
        // Close the old detail, if there is one
        $("#map-detail .close-button").trigger("click");
        
        
        // Make sure we aren't minimized
        minimizeDetail(false);
        
        
        // Get the detail object
        var detail = $("#map-detail");
        
        // Set it up with the passed values
        detail.find(".title .text").text(title);
        detail.find(".inner").html(html);
        toggleElem(detail, true);
        toggleElem("#map-actions", false);
        toggleElem("#mobile-buttons", false);
        
        
        // Listen for clicks on the close button
        detail.find(".close-button").on("click", function(e) {
            
            // If there is a callback and it returns false, don't close
            if (onClose && onClose(e) === false) { return; }
            
            hideMapDetail();
        });
        
        detail.find(".mini-button").on("click", function(e) {
            minimizeDetail(!detail.hasClass("minimized"));
        });
    }
    
    function minimizeDetail(minimize) {
        
        var detail = $("#map-detail");
        var button = detail.find(".mini-button");
        var inner = detail.find(".inner");
        
        if (minimize) { $(inner).slideUp("fast"); }
        else { $(inner).slideDown("fast"); }
        
        detail.toggleClass("minimized", minimize);
        button.toggleClass("fa-minus-circle", !minimize);
        button.toggleClass("fa-plus-circle", minimize);
    }
    
    function hideMapDetail() {
        
        // Get the detail object
        var detail = $("#map-detail");
        
        // Stop listening for clicks
        detail.find(".close-button").off("click");
        detail.find(".mini-button").off("click");
        
        // Reset the detail
        detail.find(".title .text").html("");
        detail.find(".inner").html("");
        
        // Hide the detail
        toggleElem(detail, false);
        
        // Re-enable actions
        toggleElem("#map-actions", true);
        
        // Re-show mobile buttons
        toggleElem("#mobile-buttons", true);
    }
    
    function addMapAction(id, options) {
        
        var actionSelector = "#map-actions #" + id;
        
        // Remove previous instances of the action
        $(actionSelector).remove();
        
        options.id = id;
        options.title = options.title || "Action";
        options.icon = options.icon || "fa-info-circle";
        
        $("#map-actions").append(Utils.tpl.mapAction(options));
        
        $(actionSelector).on("click", function(e) {
            actionChosen(e, id);
            if (options.callback) { options.callback(e);}
        });
        
        $("#map-actions").toggleClass("has-actions", $("#map-actions .inner").children().length > 0);
    }
    
    function removeMapAction(id) {
        $("#map-actions #" + id).remove();
    }
    
    function selectMapPosition(callback) {
        
        toggleMapOverlay(true, "selecting", "Pick a place on the map");
        
        toggleElem("#map-actions", false);
        toggleElem("#map-controls", false);
        toggleElem("#map-cancel-button", true);
        toggleElem("#mobile-buttons", false);
        
        $("#map-app").toggleClass("selecting", true);
        
        function finish(position) {
            state.map.off("click");
            toggleMapOverlay(false);
            callback(position);
            $("#map-cancel-button .button").off("click");
            
            toggleElem("#map-actions", true);
            toggleElem("#map-controls", true);
            toggleElem("#map-cancel-button", false);
            toggleElem("#mobile-buttons", true);
            
            $("#map-app").toggleClass("selecting", false);
        }
        
        
        // On poisition chosen
        state.map.on("click", function(e) {
            finish([e.latlng.lat, e.latlng.lng]);
        });
        
        
        // On cancel
        $("#map-cancel-button .button").on("click", function(e) {
            finish(null);
        });
    }
    
    function toggleMapOverlay(toggle, className, message) {
        
        message = message || null;
        
        $("#map-overlay .message").text(toggle ? message : "");
        
        if (!toggle) {
            $("#map-overlay").removeClass();
            $("#map-overlay .message").removeClass("text");
        }
        else {
            $("#map-overlay").attr("class", "active " + className);
            $("#map-overlay .message").toggleClass("text", message !== null);
        }
    }
    
    
    
    
    // Setup the map ...
    function setupMap(config) {
        
        // Print the config for debug
        // console.log("config", config);
        
        
        // Configure the Leaflet map
        var mapConfig = { attributionControl: config.page.tileset !== "Google" };
        state.map = L.map("map", mapConfig).setView([config.page.startLat, config.page.startLng], config.page.startZoom);
        state.map.zoomControl.setPosition("bottomright");
        state.clusterer = L.markerClusterGroup();
        
        
        // Load each component
        _.each(config.components, function(comp) {
            if (componentMap[comp.type]) {
                
                // Get the type of the component to create
                var ComponentType = componentMap[comp.type];
                
                // Create and store the component
                var component = new ComponentType(config.page, comp, state);
                componentMap[component.id] = component;
            }
        });
        
        
        
        
        
        // Add a tileset based on the config
        if (config.page.tileset === "Google") {
            
            // If google, add google tiles
            L.gridLayer.googleMutant({type: "roadmap"}).addTo(state.map);
        }
        else {
            
            // Otherwise, use OpenStreetMap tiles
            var url = "http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
            
            // Add the tiles as a layer
            var osm = new L.TileLayer(url, {
                attribution: "Map data © <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors"
            });
            
            state.map.addLayer(osm);
        }
        
        state.map.addLayer(state.clusterer);
    }
    
    
    
    
    // Load the component config and set up components accordingly
    $.ajax(Utils.getUrl() + "mapConfig").then(setupMap);
    
    
    
    // Add a handler to resize the map height to fit the page
    $(window).resize(function() {
        
        var footerHeight = $("footer").is(":visible") ? $("footer").outerHeight() : 0;
        
        $(".MapPage .main").height(
            $(window).height() - $("header nav").outerHeight() - footerHeight
        );
    }).resize();
    
    
    
    // Listen for the escape key
    $(document).on("keyup", function(e) {
        if (e.keyCode == 27) {
            $("#map-detail .close-button").trigger("click");
        }
    });
    
    
    // Add mobile tap listeners
    $("#mobile-buttons .actions").on("click", function(e) {
        mobileToggle("#map-actions");
    });
    $("#mobile-buttons .controls").on("click", function(e) {
        mobileToggle("#map-controls");
    });
    
    function mobileToggle(selector, message) {
        
        message = message || null;
        
        // Hide the detail if showing
        hideMapDetail();
        
        // Add overlay
        toggleMapOverlay(true, "actions", message);
        
        // Enable the thing
        $(selector).toggleClass("toggled", true);
        
        // Add cancel button
        toggleElem("#map-cancel-button", true);
        
        // Hide mobile buttons
        toggleElem("#mobile-buttons", false);
        
        
        $("#map-cancel-button .button").on("click", function(e) {
            
            // Remove overlay
            toggleMapOverlay(false);
            
            // Diable the thing
            $(selector).toggleClass("toggled", false);
            
            // Remove cancel button
            toggleElem("#map-cancel-button", false);
            
            // Show mobile buttons
            toggleElem("#mobile-buttons", true);
        });
    }
    
    function actionChosen() {
        
        // Remove overlay
        toggleMapOverlay(false);
        
        // Diable actions
        $("#map-actions").toggleClass("toggled", false);
        
        // Remove cancel button
        toggleElem("#map-cancel-button", false);
        
        // Show mobile buttons
        toggleElem("#mobile-buttons", true);
    }
    
});
