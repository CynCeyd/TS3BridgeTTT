<?php

	// config
	
	/*
	 *	IMPORTANT INFORMATION
	 *
	 *	The TTT TS3 Channel needs Talk Power for this plugin to work!
	 *
	 */
	
	/**
	 * 	Link type
	 * 	Either mysql or json
	 *
	 *	{
			"SteamID": "TsID",...
		}
	 *
	 * 	Use the mysql module only, if you are able to understand the sql code!
	 */
	$linkType = "json";
	
	/*
	 *	If you want to, you can set your own api key. For security matters.
	 */
	$apiKey = "[apikey]";
	
	/*
	 * Change the server query link for this script to be able to communicate with the TeamSpeak server. Server query needs certain permissions.
	 */
	$teamSpeakServerQuery = "serverquery://[user]:[password]@[host]:[serverqueryport]/?server_port=[tsport]";
	
	/*
	 * Auto kick if connection to server is lost
	 */
	$autoKick = true;
	
	/*
	 * Auto move to TTT Channel on join
	 */
	$autoChannelMove = true;
	
	/*
	 * Channel name of the TTT TS3 Channel
	 */
	$channelName = "Trouble in Terrorist Town";
	
	/*
	 * Directly grant talk power on join; even if the round is still in progress
	 */
	$grantTalkPowerOnJoin = true;
	
	/*
	 * Uncomment if you want to use mysql 
	 */
	 
	//$mysqli = new mysqli("[host]", "[user]", "[password]", "[db]");
	
	
	
	
	
	//
	//
	//
	//			Do not touch anything below, if you don't know what you are doing.
	//
	//
	//
	
	if(isset($_GET['key'])) $key = escape($_GET['key']);
	
	if($key != $apiKey){
		
		die("Api key not valid");
		
	}
	
	$links = array();
	
	switch($linkType)
	{
	
		case "json":
			$links = json_decode(file_get_contents("links.json"), true);
			break;

		case "mysql":
			
			if ($mysqli->connect_errno) {
				printf("Connect failed: %s\n", $mysqli->connect_error);
				exit();
			}
			
			// you would need to change the sql code
			$sql = '[sql query to get cols]';
			
			$result = $mysqli->query($sql);
			
			while ($row = $result->fetch_assoc()) {
				$links[$row['steamIDColumn']] = $row['tsIDColumn'];
			}
			
			$result->free();
			
			$mysqli->close();
			
			break;
	
	}

	require_once('TeamSpeak3/TeamSpeak3.php');
	
	function escape($str)
	{
		if(get_magic_quotes_gpc())
		{
			$str= stripslashes($str);
		}
		return str_replace("'", "''", $str);
	}


	if(isset($_GET['steamID'])) $steamID = escape($_GET['steamID']);
	if(isset($_GET['action'])) $action = escape($_GET['action']);

	function getTsID($steamID)
	{
		global $links;
	
		if(isset($links[$steamID])){
			return $links[$steamID];
		}else{
			die("No linked user account");
		}
	}
		
	$client = getTsID($steamID);
	
	$ts3_VirtualServer = TeamSpeak3::factory($teamSpeakServerQuery);

	switch($action){
		
		case "connect":
			
				try {
						
					if($autoChannelMove) $ts3_VirtualServer->clientGetByUid($client)->move($ts3_VirtualServer->channelGetByName($channelName)->getId());
					
					if($grantTalkPowerOnJoin) $ts3_VirtualServer->clientGetByUid($client)->modify(array(
						"CLIENT_IS_TALKER" => 1
					));
				}catch(Exception $ex){
					if(strpos($ex->getMessage(), "invalid client") !== false) die("You are not on the TeamSpeak 3 Server");
				}	

					
				
			die("OK");	
			
			break;
			
		case "mute":
			$ts3_VirtualServer->clientGetByUid($client)->modify(array(
				"CLIENT_IS_TALKER" => 0
			));
			
			break;

		case "kick":

			try {
				
				if($autoKick) $ts3_VirtualServer->channelGetByName($channelName)->clientGetByName(strval($ts3_VirtualServer->clientGetByUid($client)))->kick();
			}catch(Exception $ex){
				
			}

			break;

		case "unmute":
		
			$ts3_VirtualServer->clientGetByUid($client)->modify(array(
				"CLIENT_IS_TALKER" => 1
			));

			break;
	}

?>