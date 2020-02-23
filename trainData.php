<?php
	require("soapHandler.php");
	$train = new train("aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaaa");
	$response = $train->GetDepBoardWithDetails(5,"MBR");
	header("Content-Type: text/plain");

	$response = json_encode($response);	
	$response = json_decode($response, true);

	//print_r($response); exit;
	
	$trainData = array();
	$nrccMessages = '';

	// Handle NRCC messages
	if (isset($response['GetStationBoardResult']['nrccMessages']['message']))
	{
		$nrccMessage = $response['GetStationBoardResult']['nrccMessages']['message'];

		if (!isAssocArray($nrccMessage))
		{
			foreach ($nrccMessage as $msg)
			{
				foreach ($msg as $k=>$v)
				 {
					$nrccMessages .= $v . ' ';
				 }
			}
		}
		else
		{
			foreach ($response['GetStationBoardResult']['nrccMessages']['message'] as $k => $v)
			{
				$nrccMessages .= strip_tags($v) . ' ';
			}
		}
	}

	// Get Trains
	if (isset($response['GetStationBoardResult']['trainServices']['service']))
	{
		foreach ($response['GetStationBoardResult']['trainServices']['service'] as $train)
		{
			$temp = array();
			$temp['destination'] = $train['destination']['location']['locationName'];

			$temp['platform'] = isset($train['platform']) ? $train['platform'] : '-';
			$temp['toc'] = $train['operator'];
			$temp['carriages'] = isset($train['length']) ? $train['length'] : '-';

			// Work out times
			$temp['schd'] = $train['std'];
			$temp['etd'] = $train['etd'];

			if ($train['etd'] != 'On time')
			{
				// Train is late, lol.
				$schd = strtotime($train['std']);
				$etd = strtotime($train['etd']);
				$delay = ($etd - $schd) / 60;

				$temp['delay'] = $delay;

				// Is a delay reason given?
				if (isset($train['delayReason']))
				{
					$temp['delayReason'] = $train['delayReason'];
				}
			}

			// is train cancelled
			$trainCancelled = isset($train['cancelReason']);

			if ($trainCancelled)
			{
				if ($train['cancelReason'] != '')
				{
					$temp['cancelReason'] = $train['cancelReason'];
				}

				$temp['cancelled'] = true;
			}

			// Get callpoints and times
			$temp['callingPoints'] = array();

			if (!$trainCancelled)
			{
				$trainCallingPoints = $train['subsequentCallingPoints']['callingPointList']['callingPoint'];

				// Direct train or multiple call points?
				if (isAssocArray($trainCallingPoints))
				{
					$temp['callingPoints'] = array(
						'location' => $trainCallingPoints['locationName'],
						'st' => $trainCallingPoints['st'],
						'et' => $trainCallingPoints['et']);
				}
				else
				{
					foreach ($trainCallingPoints as $callingPoints)
					{
						$callPoint = array(
							'location' => $callingPoints['locationName'],
							'st' => $callingPoints['st'],
							'et' => $callingPoints['et']);

						$temp['callingPoints'][] = $callPoint;
					}
				}

				// Make a string of the calling points because I prefer PHP...
				$cpString = '';
				
				
				if (!isAssocArray($temp['callingPoints']))
				{
					foreach ($temp['callingPoints'] as $cp)
					{
						if ($cp['et'] != 'On time')
						{
							$cpString .= $cp['location'] . ' (<strike>'. $cp['st'].'</strike>) ' . '('.$cp['et']  .'), ' ;
						}
						else
						{
							$cpString .= $cp['location'] . ' (' . $cp['st'] .'), ' ;
						}
					}
				}
				else
				{
					$cpString = 'Calling at destination only';
				}
				

				$temp['callingPointsString'] = $cpString;
			}

			// push data to array
			$trainData['train'][] = $temp;
		}	
	}
	else
	{
		$trainData['train'][] = array('destination' => 'NO SERVICES', 'toc' => 'sleep', 'carriages' => '0', 'schd' => '', 'etd' => '', 'callingPointsString' => '', 'platform' => '');
	}
	

	$trainData['nrccMessages'] = $nrccMessages;

	//print_r($trainData); exit;
	print_r(json_encode($trainData));


function isAssocArray($arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}