//Source:
//https://code.google.com/p/geo-location-javascript/
var start_stop_btn; 
var wpid = false;
var map; 
var log_count=0;			//Number of points currently logged 
var op;
var prev_lat;				//Previously recorded latitude
var prev_lng; 				//Previously recorded longitude
var min_speed = 0;			//Minimum speed in MPH
var max_speed = 0;			//Maximum speed in MPH
var sum_speed = 0;			//Sum speed in MPH to calculate average
var avg_speed = 0;			//Average speed in MPH
var min_altitude = 0;		//Minimum altitude in Meters
var max_altitude = 0;		//Maximum altitude in Meters
var sum_altitude = 0;		//Sum altitude for average
var avg_altitude = 0;		//Average altitude in Meters
var distance_travelled = 0; //Total distance travelled
var min_accuracy = 150;		//Minimum accuracy in meters
var date_pos_updated = "";	
var info_string = "";
var latlng_string = "";

var geojson = {};			//Object to hold all pertinent info 

// This function just adds a leading "0" to time/date components which are <10 (because there is no cross-browser way I know of to do this using the date object)
function format_time_component(time_component)
{
    if (time_component < 10){
    	time_component = "0" + time_component;
    }else if (time_component.length < 2){
    	time_component = time_component + "0";
    }

    return time_component;
}

// This is the function which is called each time the Geo location position is updated
function geo_success(position)
{
    start_stop_btn.innerHTML = "Stop"; // Set the label on the start/stop button to "Stop"
    info_string = "";
    var d = new Date(); // Date object, used below for output messahe
    var h = d.getHours();
    var m = d.getMinutes();
    var s = d.getSeconds();

    var current_datetime = format_time_component(h) + ":" + format_time_component(m) + ":" + format_time_component(s);

    // Check that the accuracy of our Geo location is sufficient for our needs
    if (position.coords.accuracy <= min_accuracy)
    {
        // We don't want to action anything if our position hasn't changed - we need this because on IPhone Safari at least, we get repeated readings of the same location with 
        // different accuracy which seems to count as a different reading - maybe it's just a very slightly different reading or maybe altitude, accuracy etc has changed
        if (prev_lat != position.coords.latitude || prev_long != position.coords.longitude)
        {
        	log_count++;								//Keep count of logged values 

            prev_lat = position.coords.latitude;
            prev_long = position.coords.longitude;
            
            sum_speed += position.coords.speed;
            sum_altitude += position.coords.altitude;
	
			geojson['location'] = {
				'lat':position.coords.latitude,
				'lon':position.coords.longitude,
				'accuracy':{
					'value': Math.round(position.coords.accuracy, 1),
					'units':'meters'
				}
			geojson['timestamp'] = current_datetime;
			geojson['altitude'] = position.coords.altitude;
			geojson['speed'] = {
				'value': position.coords.speed,
				'max': max(position.coords.speed,max_speed),
				'min': min(position.coords.speed,min_speed),
				'avg': average(log_count,sum_speed);
			}
 			geojson['altitude'] = {
 				'value': position.coords.altitude,
 				'max': max(position.coords.altitude,max_altitude),
 				'min': min(position.coords.altitude,min_altitude),
 				'avg': average(log_count,sum_altitude);
 			}
			
			console.log(geojson);
			
            info_string = "Current positon: lat=" + position.coords.latitude + ", long=" + position.coords.longitude + " (accuracy " + Math.round(position.coords.accuracy, 1) + "m)<br />Speed: min=" + (min_speed ? min_speed : "Not recorded/0") + "m/s, max=" + (max_speed ? max_speed : "Not recorded/0") + "m/s<br />Altitude: min=" + (min_altitude ? min_altitude : "Not recorded/0") + "m, max=" + (max_altitude ? max_altitude : "Not recorded/0") + "m (accuracy " + Math.round(position.coords.altitudeAccuracy, 1) + "m)<br />last reading taken at: " + current_datetime;
            latlng_string = position.coords.latitude + ',' + position.coords.longitude;
        }
    }else{ 
    	info_string = "Accuracy not sufficient (" + Math.round(position.coords.accuracy, 1) + "m vs " + min_accuracy + "m) - last reading taken at: " + current_datetime;
    }

    if (info_string){
    	op.innerHTML = info_string;
    }
    
    if (latlng_string){
    	$('#current').val(latlng_string);
    }
}

//Average value
function average(count,value){
	return Math.round(value/count, 1);
}

//Max value
function max(a,b){
	return Math.max(a, b);
}

//Min value
function min(a,b){
	return Math.min(a, b);
}




// This function is called each time navigator.geolocation.watchPosition() generates an error (i.e. cannot get a Geo location reading)
function geo_error(error)
{
    switch (error.code)
    {
    case error.TIMEOUT:
        op.innerHTML = "Timeout!";
        break;
    };
}


function get_pos()
{
    // Set up a watchPosition to constantly monitor the geo location provided by the browser - NOTE: !! forces a boolean response
    // We  use watchPosition rather than getPosition since it more easily allows (on IPhone at least) the browser/device to refine the geo location rather than simply taking the first available position
    // Full spec for navigator.geolocation can be foud here: http://dev.w3.org/geo/api/spec-source.html#geolocation_interface
    // First, check that the Browser is capable
    if ( !! navigator.geolocation)
    {
        ShowLocation();
        wpid = navigator.geolocation.watchPosition(geo_success, geo_error, {
            enableHighAccuracy: true,
            maximumAge: 30000,
            timeout: 27000
        });
    }
    else
    {
        op.innerHTML = "ERROR: Your Browser doesnt support the Geo Location API";
    }
}


// Initialiser: This will find the output message div and set up the actions on the start/stop button

function init_geo()
{
    op = document.getElementById("output"); // Note the op is defined in global space and is therefore globally available
    if (op)
    {
        start_stop_btn = document.getElementById("geo_start_stop");

        if (start_stop_btn)
        {
            start_stop_btn.onclick = function ()
            {
                if (wpid) // If we already have a wpid which is the ID returned by navigator.geolocation.watchPosition()
                {
                    start_stop_btn.innerHTML = "Start";
                    navigator.geolocation.clearWatch(wpid);
                    wpid = false;
                }
                else // Else...We should only ever get here right at the start of the process
                {
                    start_stop_btn.innerHTML = "Aquiring Geo Location...";
                    get_pos();
                }
            }


        }
        else op.innerHTML = "ERROR: Couldn't find the start/stop button";
    }
}

// Initialise the whole system (above)
window.onload = init_geo;