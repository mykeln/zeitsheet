<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Zeitsheet</title>
<!-- styles -->
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.8.2r1/build/reset/reset-min.css">
<style type="text/css">
  html{background-color:#222;}
  body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;}
  .result_item{padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid #ccc;}
  .result_item:last-child{border-bottom:0px;}
  #results{background-color:#efefef;padding:10px;border-bottom:5px solid #ddd;width:400px;}
  #results h3{font-size:20px;line-height:30px;font-weight:bold;color:#444;}
  #results p.snippet{font-size:10px;padding:5px 5px 5px 0px;color:#fff;}
  #results p.snippet span{font-weight:bold;color:#ccc;text-shadow:1px 1px 0 #999;}
  #results p.snippet span a{color:#ccc;text-decoration:none;}
  #results p.snippet.in{background-color:#67B2A6}
  #results p.snippet.out{background-color:#FF342F}
  #results p.snippet.neither{background-color:#ddd}
  #results p.snippet.space{margin-bottom:5px;}
  #results p.snippet.in span,#results p.snippet.out span,#results p.snippet.neither span{color:#fff;text-shadow:none;padding-left:5px;}
  .total_time{position:absolute;left:415px;padding:20px;font-size:10px;line-height:51px;padding:0px 10px;background:#444;color:#fff;-moz-border-radius-bottomright:5px;-moz-border-radius-topright:5px;-webkit-border-bottom-right-radius:5px;-webkit-border-top-right-radius:5px;-moz-box-shadow:5px 5px 0 #333;-webkit-box-shadow:5px 5px 0 #333;box-shadow:5px 5px 0 #333;}
  .time_results{display:none;}

</style>
<!-- scripts -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
<script type="text/javascript" src="http://datejs.googlecode.com/files/date.js"></script>

<!-- core code -->
<script type="text/javascript">  

$(function(){
/*
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
USER CONFIG
////////////////////////////////////////
*/
  
  // mac addresses we want to pay attention to
  var namedMac = {
    'f8:1e:df:de:e6:d0':'Kelsey Tracey',
    '58:b0:35:6a:60:b0':'Eric Steil',
    '58:55:ca:f2:da:4f':'Mykel Nahorniak',
  };
  
  // path to the json we're pulling
  var logUrl = "http://purplebox.ericiii.net/localist-timecapsule-json.php?callback=?";


/*
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
UTILITY FUNCTIONS
////////////////////////////////////////
*/

// minified noise generator
(function(a){a.fn.noisy=function(b){b=a.extend({},a.fn.noisy.defaults,b);var f,c=document.createElement("canvas");if(c.getContext){c.width=c.height=b.size;for(var h=c.getContext("2d"),d=h.createImageData(c.width,c.height),i=b.intensity*Math.pow(b.size,2),j=255*b.opacity;i--;){var e=(~~(Math.random()*c.width)+~~(Math.random()*c.height)*d.width)*4,g=i%255;d.data[e]=g;d.data[e+1]=b.monochrome?g:~~(Math.random()*255);d.data[e+2]=b.monochrome?g:~~(Math.random()*255);d.data[e+3]=~~(Math.random()*j)}h.putImageData(d,
0,0);f=c.toDataURL("image/png")}else f=b.fallback;return this.each(function(){a(this).data("original-css")==undefined&&a(this).data("original-css",a(this).css("background-image"));a(this).css("background-image","url("+f+"),"+a(this).data("original-css"))})};a.fn.noisy.defaults={intensity:0.9,size:200,opacity:0.08,fallback:"",monochrome:false}})(jQuery);

// noise generating function
function generateNoise(element,intensity,size,opacity,monochrome) {
  element = "body";
  $(element).noisy({
      intensity: intensity, 
      size: size, 
      opacity: opacity,
      fallback: 'fallback.png',
      monochrome: monochrome
  });
};

// adding noise to body
generateNoise('body',0.9,200,0.08, true);

// random id generator
function getRandomNumber(range){return Math.floor(Math.random() * range);}
function getRandomChar(){var chars = "0123456789abcdefghijklmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";return chars.substr( getRandomNumber(62), 1 );}
function randomID(size){var str = "";for(var i = 0; i < size; i++){str += getRandomChar();}return str;}


/*
////////////////////////////////////////
////////////////////////////////////////
////////////////////////////////////////
CORE CODE
////////////////////////////////////////
*/

  // objects for actually storing the unique mac addy in/out dates/times
  var timeData = {};
  
  // query function
  function tt_query() {
      
    // performing query and displaying results
    $.getJSON(logUrl, function(data) {
      $.each(data, function(i,item) {
                
        // skip this entry if we're ignoring the mac
        if(!namedMac[item.mac]) {
          return;
        }
        
        // initialize the mac entry if we haven't seen this mac before
        if(!timeData[item.mac]) {
          timeData[item.mac] = {};
        }
        
        // get the start/stop dates
        var assoc_date = item.assoc ? new Date(item.assoc) : null;
        var disassoc_date = item.disassoc ? new Date(item.disassoc) : null;
        
        // skip this entry if we don't at least have one date out of this. shouldn't ever happen.
        if(!assoc_date && !disassoc_date) {
          return;
        }
        
        // get a key to use as a string
        var date_key = (assoc_date || disassoc_date).toString('yyyy-MM-dd');

        // initialize the date storage object for this mac/date pair if necessary
        if(!(date_key in timeData[item.mac])) {
          timeData[item.mac][date_key] = {start: null, stop: null};
        }

        // update the start/stop date time if necessary
        var mac_time_data = timeData[item.mac][date_key];
        
        // if START time found is before the one in the object, update the object
        if(!mac_time_data.start || (Number(assoc_date) < Number(mac_time_data.start))) {
          if(!(Number(assoc_date)) == 0) {
            // DEBUG: console.log('Found a new start time for ' + namedMac[item.mac] + ' on ' + date_key + ' (' + Number(assoc_date) + ') that is earlier than what I am storing (' + Number(mac_time_data.start) + ')');
          }
          mac_time_data.start = assoc_date;
        }        
        
        // if the STOP time found is after the one in the object, update the object
        if(!mac_time_data.stop || (Number(disassoc_date) > Number(mac_time_data.stop))) {
          if(!(Number(disassoc_date)) == 0) {
            // DEBUG: console.log('Found a new stop time for ' + namedMac[item.mac] + ' on ' + date_key + ' (' + Number(disassoc_date) + ') that is later than what I am storing (' + Number(mac_time_data.stop) + ')');
          }
          mac_time_data.stop = disassoc_date;
        }
      }); // each item
      
      $.each(timeData, function(key,value) {
        // get display name for each item in the object
        var personName = namedMac[key];
        var tempId = randomID(10);
        
        // adding a 'results item wrapper' to the page
        $("#tt").append('<div class="time_results" id="time_table_' + tempId + '"></div>');
        
        // adding a title to the item, parsing name from namedMac
        $("#time_table_" + tempId).append('<h3>' + personName + '</h3>');
        
        // adding a title to the item, parsing name from namedMac
        $("#time_table_" + tempId).append('<div class="result_item" id="ttresult_' + tempId + '"></div>');
        
        // another each to loop over every date for the object
        $.each(value, function(date,times){          
          
          // preparing difference between start and end times to be human-readable
          var timeDiffStart = Math.abs(Number(times.start) - (Number(times.stop)));
          var timeDiffDisplay = (timeDiffStart / (1000*60*60)).toPrecision(2);
          
          // formatting for not being able to find a proper start time
          if (timeDiffDisplay >= 24) {
            // this only happens if it couldn't find a start time 
            timeDiffDisplay = "A long time!";
          } else {
            // add 'hours' to times that are real
            timeDiffDisplay = timeDiffDisplay + ' hours';
          }

          var totalTimeId = randomID(4);
          
          // showing the clock in date
          $("#ttresult_" + tempId).append('<p class="snippet"><span><a class="date_click" id="' + totalTimeId + '" href="#">' + date + '</a></span></p>');

          $("#ttresult_" + tempId).append('<div class="total_time" id="' + totalTimeId + '">' + timeDiffDisplay + '</div>');
          
          if(times.start) {
            // showing the clock in date
            $("#ttresult_" + tempId).append('<p class="snippet in space"><span>IN&laquo; </span>' + times.start.toString('h:mm tt')  + '</p>');
          } else {
            $("#ttresult_" + tempId).append('<p class="snippet neither space"><span>IN&laquo;</span> Not sure</p>');
          }

          if(times.stop) {
            // showing the clock out date
            $("#ttresult_" + tempId).append('<p class="snippet out"><span>OUT&raquo; </span>' + times.stop.toString('h:mm tt')  + '</p>');
          } else {
            $("#ttresult_" + tempId).append('<p class="snippet neither"><span>Still in the office</span></p>');
          }
        }); // each date
        
        
      }); // each timeData

      
      // hiding the 'grabbing log' message
      $("#tt_h").hide();
      
      // showing the results
      $("#tt").show();
    });
  }

  // running and presenting
  tt_query();
  

});

</script>

</head>
<body>  
  <div id="results">
  
    <h3 id="tt_h">Grabbing log...</h3>
    <div id="tt" style="display:none;">
    </div>
  </div>
</body>
</html>
