define(["jquery", "vue", "lodash", "utils"], function($, Vue, _, Utils) {
    "use strict";
    
    
    function createResponseMarker(response, type, state) {
        
        var marker = L.marker([response.lat, response.lng], {
            icon: type
        });
        
        marker.response = response;
        
        
        marker.on('click', function(e) {
            
            var response = this.response;
            
            $.ajax(Utils.apiUrl("/s/" + response.surveyId + "/r/" + response.id))
            .then(function(data) {
                console.log(data);
                
                var title = data.member.name || 'Unknown';
                
                var body = JSON.stringify(data.response);
                
                state.methods.showDetail(title, body, null);
                
            })
            .catch(function(error) {
                console.log(error);
            });
            
        });
        
        return marker;
    }
    
    
    
    return function(page, component, state) {
        
        // ...
        
        $.ajax({
            url: Utils.getOrigin() + '/s/' + component.surveyID + '/responses?onlygeo',
            success: function(responses) {
                
                var cluster = L.markerClusterGroup();
                // cluster.addLayer(L.marker(getRandomLatLng(map)));
                
                
                var markers = _.each(responses, function(response) {
                    var m = createResponseMarker(response, state.pins.blue, state);
                    cluster.addLayer(m);
                });
                
                state.map.addLayer(cluster);
                // Clustering? ...
            }
        });
        
        // ...
    };
});
