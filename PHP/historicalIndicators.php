<?php
 
$urls = array(
    //To get a specific country and indicator
    #'https://api.tradingeconomics.com/historical/country/sweden/indicator/gdp',
    //To get only a start date for your historical data
    #'https://api.tradingeconomics.com/historical/country/united%20states/indicator/gdp/2013-01-01',
    //To get a start date and end date
    #'https://api.tradingeconomics.com/historical/country/united%20states/indicator/gdp/2015-01-01/2015-12-31',
    //To get multiple indicators for specific country
    #'https://api.tradingeconomics.com/historical/country/united%20states/indicator/gdp,population',
    //To get specific indicator for multiple countries
    'https://api.tradingeconomics.com/historical/country/sweden,thailand/indicator/population',
    //To get historical data by ticker
    #'https://api.tradingeconomics.com/historical/ticker/USURTOT/2015-03-01',
); 
$headers = array(
    "Accept: application/xml",
    "Authorization: Client ibilj93f487bmu4:ir15nspgjv4p714"
);
//An array that will contain all of the information
//relating to each request.
$requests = array();
  
//Initiate a multiple cURL handle
$mh = curl_multi_init();
 
//Loop through each URL.
foreach($urls as $k => $url){
    $requests[$k] = array();
    $requests[$k]['url'] = $url;
    //Create a normal cURL handle for this particular request.
    $requests[$k]['curl_handle'] = curl_init($url);
    //Configure the options for this request.
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_HTTPHEADER, $headers);
    curl_setopt($requests[$k]['curl_handle'], CURLOPT_RETURNTRANSFER, true);
    
    //Add our normal / single cURL handle to the cURL multi handle.
    curl_multi_add_handle($mh, $requests[$k]['curl_handle']);
    
}

//Execute our requests using curl_multi_exec.
$stillRunning = true;
do {
    curl_multi_exec($mh, $stillRunning);
} while ($stillRunning);
 
//Loop through the requests that we executed.
foreach($requests as $k => $request){
    //Remove the handle from the multi handle.
    curl_multi_remove_handle($mh, $request['curl_handle']);
    //Get the response content and the HTTP status code.
    $requests[$k]['content'] = curl_multi_getcontent($request['curl_handle']);
    $requests[$k]['http_code'] = curl_getinfo($request['curl_handle'], CURLINFO_HTTP_CODE);
    //Close the handle.
    curl_close($requests[$k]['curl_handle']);
}
//Close the multi handle.
curl_multi_close($mh);


$data = json_decode($requests[0]['content'], true);

$country = array();

$years = array();

$years_ctr = 0;
$start_year = '2010';

foreach ($data as $population):

    $this_year = date('Y', strtotime($population['DateTime']));
    $country_name = $population['Country'];

    if($this_year >= $start_year && !empty($population['Category'])):

        $this_abs_variance = $population['Value'] - $prev_val[$country_name];
        $this_relative_variance = $this_abs_variance / $prev_val[$country_name];

        $country[$country_name]['name'] = $country_name;
        $country[$country_name]['data'][] = round( $this_relative_variance * 100, 2);
        $years[$this_year] = $this_year;

        $prev_val[$country_name] = $population['Value'];

        elseif($start_year - $this_year == 1 && !empty($population['Category'])):

        $prev_val[$country_name] = $population['Value'];

    endif;


endforeach;

?>
<html>
<head>
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <style>
        .highcharts-figure,
        .highcharts-data-table table {
            min-width: 360px;
            max-width: 800px;
            margin: 1em auto;
        }

        .highcharts-data-table table {
            font-family: Verdana, sans-serif;
            border-collapse: collapse;
            border: 1px solid #ebebeb;
            margin: 10px auto;
            text-align: center;
            width: 100%;
            max-width: 500px;
        }

        .highcharts-data-table caption {
            padding: 1em 0;
            font-size: 1.2em;
            color: #555;
        }

        .highcharts-data-table th {
            font-weight: 600;
            padding: 0.5em;
        }

        .highcharts-data-table td,
        .highcharts-data-table th,
        .highcharts-data-table caption {
            padding: 0.5em;
        }

        .highcharts-data-table thead tr,
        .highcharts-data-table tr:nth-child(even) {
            background: #f8f8f8;
        }

        .highcharts-data-table tr:hover {
            background: #f1f7ff;
        }
    </style>
</head>
<body style="background-color:#f2f2f2; font-family: 'Montserrat', sans-serif;">
<h1 style="text-align:center; color:#000;">Trading Economics</h1>
<h2 style="text-align:center; color:#000;">Population annual growth rate Sweden Vs Thailand</h2>
<br>
<figure class="highcharts-figure">
    <div id="container"></div>
    <p class="highcharts-description">
        This chart compares annual population growth rate between Sweden and Thailand.
    </p>
</figure>
</body>
<script type="application/javascript">
    var series = [];

    <?foreach ($country as $key=>$val):?>
        series.push(<?=json_encode($val)?>);
    <?endforeach;?>
    Highcharts.chart('container', {
        chart: {
            type: 'line'
        },
        title: {
            text: 'Annual Population Growth (%)'
        },
        subtitle: {
            text: 'Source: ' +
                '<a href="https://api.tradingeconomics.com/historical/country/sweden,thailand/indicator/population" ' +
                'target="_blank">TradingEconomics.com</a>'
        },
        xAxis: {
            categories: [<?=implode(',', $years)?>]
        },
        yAxis: {
            title: {
                text: 'Population growth rate (%)'
            }
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true
                },
                enableMouseTracking: false
            }
        },
        series: series
    });
</script>
</html>
