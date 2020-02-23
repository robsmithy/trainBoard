var timeout = 60;

function startTime() 
{
	var time = new Date();
	var clock = time.toLocaleString('en-UK', {timeZone: "Europe/London", hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false});
	document.getElementById('clock').innerHTML = clock;		
	var t = setTimeout(startTime, 500);
}

function checkTime(i) 
{
  if (i < 10) {i = "0" + i};  
  return i;
}

function loadData() 
{
	setTimeout(function () {
			$.ajax({
				url: "trainData.php",
				type: "GET",
				success: function(result) {
					handleResponse(result); 
					timeout = 60000;
				},
				complete: function(){
					if($('#temp').is(':visible'))
					{
						$('#temp').hide();
						$('#wrapper').show();
						timeout = 60000;
					}
					loadData();
				},
				error : function(){
				alert('something went wrong');
			}
		});
	}, timeout);
}

function handleResponse(result)
{
	var data = JSON.parse(result);
	var trainData = data['train'];
	
	$("#trainTable").html('');
		
	var index;
	
	for (index = 0; index < trainData.length; ++index)
	{
		var borderStyle = '';
		var carriageInfo = ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;';
		
		if (index != (trainData.length-1))
		{
			borderStyle = 'border-bottom: 1px solid white;';
		}
		
		if (trainData[index]['carriages'] != '-')
		{
			carriageInfo = ', this train has ' + trainData[index]['carriages'] + ' carriages';
		}
		
		var html = `
			<!---TRAIN ROW--> 
			<div class="row">
				<div class="col-sm-1">` + trainData[index]['schd'] + `</div>
				<div class="col-sm-8 TrainDestination">` + trainData[index]['destination'] +`</div>
				<div class="col-sm-1 text-md-center">` + trainData[index]['platform'] + `</div>
				<div class="col-sm-2 text-md-center">` + trainData[index]['etd'] + `</div>
			</div>
			<div  class="row">
			</div>
			<div class="row no-gutters" style="padding-left: 94px;` + borderStyle + `">
				<div class="col-sm-12 col-md-1 trainfont">Callling at:</div>
				<div class="col-sm-8 col-md-8 trainfont">
				  <marquee>` + trainData[index]['callingPointsString'] + `</marquee>
				</div>
				<span class="trainfont">Operated by ` + trainData[index]['toc'] + ``+
				carriageInfo +` </span>
			</div>`;
		
		
		$("#trainTable").append(html);
	}
	
	$("#nrccMessages").html(data['nrccMessages']);
	
}


loadData();