<?php

	/**
	 * If you want to, you can set your own api key. For security matters.
	 */
	define("API_KEY", "abc");
	
	/*
	 * Change the TeamSpeak connection parameters in this script to be able to communicate with the TeamSpeak server. Server query needs certain permissions.
	 */
	define("TEAMSPEAK_QUERY_USERNAME", "[query_user]");
	define("TEAMSPEAK_QUERY_PASSWORD", "[query_password]");
	define("TEAMSPEAK_QUERY_HOST", "[host]");
	define("TEAMSPEAK_QUERY_PORT", "[query_port]");
	define("TEAMSPEAK_SERVER_PORT", "[server_port]");
	define("TEAMSPEAK_QUERY_BOT_NICK", "TTT Bot");
	
	/**
	 * Indicates whether players shall be kicked out of the channel after leaving
	 */
	define("AUTO_KICK", true);
	
	/*
	 * Auto move to TTT Channel on join
	 */
	define("AUTO_CHANNEL_MOVE", true);

	/*
	 * Channel name of the TTT TS3 Channel
	 */
	define("CHANNEL_NAME", "Trouble in Terrorist Town");
	
	/*
	 * Directly grant talk power on join; even if the round is still in progress
	 */
	define("GRANT_TALK_POWER_ON_JOIN", true);
	
	/**
	 * Directory with all linking requests. Trailing slash is necessary!
	 */
	define("REQUEST_DIRECTORY", "Requests/");

	/**
	 * For the automatic linking only the specified channels will be searched for users
	 */
	define("SCAN_SPECIFIED_CHANNELS_ONLY", false);

	/**
	 * Channels to scan for automatic linking; please separate by comma
	 */
	define("CHANNELS_TO_SCAN", "Trouble in Terrorist Town");

	/**
	 * When this option is enabled, all account links from the respective Steam IDs and TeamSpeak IDs will be saved permanentely.
	 * 
	 */
	define("USE_LINK_CACHE", true);
	
	/**
	 * When enabled, a log will be generated
	 */
	define("DEBUG_MODE", false);
	
	/**
	 * When enabled, the debug output will be part of the request response
	 */
	define("ALLOW_DEBUG_IN_RESPONSE", false);
	
	/**
	 * Percentage of the names matching the automatic linking decision
	 */
	define("DECISION_PERCENTAGE", 0.9);
	
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	
	/**
	 * If you do not know what you are doing, keep your hands off anything below!
	 */
	 
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
	 
	define("VERSION", 2);

	if(!file_exists("TeamSpeak3/TeamSpeak3.php")) die("TeamSpeak library not found. Please download it from https://github.com/planetteamspeak/ts3phpframework.");
	
	require_once("TeamSpeak3/TeamSpeak3.php");

	class TS3Bridge
	{
		# region Fields
			
		/**
		 * Link cache
		 */
		private $_linkCache;
		
		/**
		 * Currently the GET-parameters
		 */
		private $_params;
		
		/**
		 * On a request, these are the methods that may be called
		 */
		private $_allowedMethods = array(
			"connect",
			"disconnect",
			"mute",
			"unmute",
			"unmute_all",
			"_unlink",
			"show",
			"request",
			"activate",
			"version"
		);
		
		/**
		 * Indicates whether the debug output will be printed to the response
		 */
		private $_debugInResponse = false;
		
		# endregion
		
		# region Constructors
		
		public function __construct($params, $linkCache)
		{
			$this->_params = $params;
			$this->_linkCache = $linkCache;
			
			if(!defined("TEAMSPEAK_QUERY_USERNAME"))
			{
				$this->debug("No TeamSpeak query user is present.");
				exit;
			}
			
			if(!defined("TEAMSPEAK_QUERY_PASSWORD"))
			{
				$this->debug("No TeamSpeak query password is present.");
				exit;
			}
			
			if(!defined("TEAMSPEAK_QUERY_HOST"))
			{
				$this->debug("No TeamSpeak query host is present.");
				exit;
			}
			
			if(!defined("TEAMSPEAK_QUERY_PORT"))
			{
				$this->debug("No TeamSpeak query port is present.");
				exit;
			}
			
			if(!defined("TEAMSPEAK_SERVER_PORT"))
			{
				$this->debug("No TeamSpeak server port is present.");
				exit;
			}
			
			if(!defined("TEAMSPEAK_QUERY_BOT_NICK"))
			{
				$this->debug("No TeamSpeak query bot nick is present.");
				exit;
			}

			if(!defined("AUTO_CHANNEL_MOVE")) define("AUTO_CHANNEL_MOVE", true);
			
			if(!defined("CHANNEL_NAME"))
			{
				$this->debug("No TeamSpeak channel was provided.");
				exit;
			}
			
			if(!defined("GRANT_TALK_POWER_ON_JOIN")) define("GRANT_TALK_POWER_ON_JOIN", true);
			if(!defined("REQUEST_DIRECTORY")) define("REQUEST_DIRECTORY", "Requests/");
			if(!defined("SCAN_SPECIFIED_CHANNELS_ONLY")) define("SCAN_SPECIFIED_CHANNELS_ONLY", false);
			if(!defined("CHANNELS_TO_SCAN")) define("CHANNELS_TO_SCAN", "");
			if(!defined("USE_LINK_CACHE")) define("USE_LINK_CACHE", true);
			if(!defined("DEBUG_MODE")) define("DEBUG_MODE", false);
			if(!defined("ALLOW_DEBUG_IN_RESPONSE")) define("ALLOW_DEBUG_IN_RESPONSE", false);
			if(!defined("DECISION_PERCENTAGE")) define("DECISION_PERCENTAGE", 0.9);

			try 
			{
				$this->_teamSpeak = TeamSpeak3::factory($this->buildConnectionString());
				
				if(DEBUG_MODE && !is_readable(dirname("Log.txt"))){
					die("Unable to use debug mode. Cannot write the log file.");
				}
				
				if(array_key_exists("debug", $this->_params))
				{
					$this->_debugInResponse = true;
				}
			}
			catch(Exception $ex){
				var_dump($ex);
				exit;
			}
		}
		
		# endregion
		
		# region Methods
		
		/**
		 * Builds a connection string to the TeamSpeak server
		 */
		private function buildConnectionString()
		{
			return sprintf("serverquery://%s:%s@%s:%d/?server_port=%d&nickname=%s",
				TEAMSPEAK_QUERY_USERNAME,
				TEAMSPEAK_QUERY_PASSWORD,
				gethostbyname(TEAMSPEAK_QUERY_HOST),
				TEAMSPEAK_QUERY_PORT,
				TEAMSPEAK_SERVER_PORT,
				rawurlencode(TEAMSPEAK_QUERY_BOT_NICK));
		}
		
		/**
		 * Logs a message to Log.txt when debug mode is enabled
		 * @param string $message
		 * @param $exception Optional exception object
		 */
		private function debug($message, $exception=null)
		{
			if(DEBUG_MODE){
				$timestamp = date("c");
				
				$entry = "";
				
				if(isset($exception))
				{
					$entry .= "[EXCEPTION] ";
				}
				
				$entry = "[".$timestamp."] ";
				$entry .= $message;
				
				if(isset($exception))
				{
					ob_start();
					
					var_dump($exception);
					
					$entry .= ob_get_clean();
				}
				
				$entry .= "\n";
				
				file_put_contents("Log.txt", $entry, FILE_APPEND);
				
				if(ALLOW_DEBUG_IN_RESPONSE && $this->_debugInResponse) echo($entry);
			}
		}
		
		/**
		 * Returns a client list of the server or specified channels
		 */
		private function getClientList()
		{
			if(SCAN_SPECIFIED_CHANNELS_ONLY)
			{
				$clientList = array();
				
				$channelsToScan = explode(",", CHANNELS_TO_SCAN);
				
				foreach($channelsToScan as $channelName)
				{
					$clientList = array_merge($clientList, $ts3_VirtualServer->channelGetByName($channelName)->clientList());
				}
			}else{
				return $this->_teamSpeak->clientList();
			}
		}
		
		/**
		 * This method returns a TeamSpeak client using either the Steam id, ip address or user name
		 * @param string $steamId
		 * @param string $ipAddress
		 * @param string $userName
		 * @returns TeamSpeak client
		 */
		private function getTeamSpeakClient($steamId, $ipAddress, $userName)
		{
			if(USE_LINK_CACHE)
			{
				$teamSpeakId = $this->_linkCache->getLink($steamId);
		
				if(isset($teamSpeakId))
				{
					try
					{
						return $this->_teamSpeak->clientGetByUid($teamSpeakId);
					}catch(Exception $ex)
					{
						$this->debug("Could not find the player on the TeamSpeak server", $ex);
					}
				}
			}
		
			$ipAddress = strtok($ipAddress, ":");
			
			$clientsMatchingIpAddress = array();
			$clientMatchingName = null;
			
			foreach($this->getClientList() as $client)
			{
				if($ipAddress == $client->connection_client_ip)
				{
					$clientsMatchingIpAddress[] = $client;
				}
				
				if($userName == strval($client))
				{
					$clientMatchingName = $client;
				}
			}
			
			// We have found exactly one ip address matching this user. Might get buggy if the auto-kick is disabled, 
			// as different users could possibly be on the TeamSpeak and TTT-Server using the same ip address
			if(sizeof($clientsMatchingIpAddress) == 1)
			{
				$client = array_shift($clientsMatchingIpAddress);

				if(USE_LINK_CACHE && (string)$client["client_unique_identifier"] != TEAMSPEAK_CONNECTION_PARAMS["queryUserName"]) 
					$this->_linkCache->createLink($steamId, (string)$client["client_unique_identifier"]);
				
				return $client;
			}else{
				$this->debug("There have been " . sizeof($clientsMatchingIpAddress) . " users with the same ip for user $userName using the ip address $ipAddress and Steam id $steamId.");
			}
			
			
			if(isset($clientMatchingName))
			{
				if(USE_LINK_CACHE && (string)$clientMatchingName["client_unique_identifier"] != TEAMSPEAK_CONNECTION_PARAMS["queryUserName"]) 
					$this->_linkCache->createLink($steamId, (string)$clientMatchingName["client_unique_identifier"]);
				
				return $clientMatchingName;
			}
			
			$this->debug("No client has a matching name for user $userName using the ip address $ipAddress and Steam id $steamId.");
			
			$similarityToClients = array();
			
			foreach($this->getClientList() as $client)
			{
				similar_text($userName, strval($client), $percent);
				
				if(DECISION_PERCENTAGE || !defined("DECISION_PERCENTAGE"))
				{
					if(DECISION_PERCENTAGE && $percent < DECISION_PERCENTAGE * 100) continue;
					
					$similarityToClients[(string)$client["client_unique_identifier"]] = $percent;
				}
			}
			
			if(sizeof($similarityToClients) > 0)
			{
				arsort($similarityToClients);

				$clientUids = array_keys($similarityToClients);
				$client = $this->_teamSpeak->clientGetByUid(array_shift($clientUids));
				
				if(USE_LINK_CACHE && (string)$client["client_unique_identifier"] != TEAMSPEAK_CONNECTION_PARAMS["queryUserName"]) 
					$this->_linkCache->createLink($steamId, (string)$client["client_unique_identifier"]);
				
				return $client;
			}
			
			$this->debug("No user has been matching with ≥ " . DECISION_PERCENTAGE * 100 . "% for user $userName using the ip address $ipAddress and Steam id $steamId.");
			
			return null;
		}
		
		/**
		 * This method handles all requests arriving here
		 * @param array $params
		 */
		public function handleRequest()
		{
			if(defined("API_KEY"))
			{
				if(isset($this->_params["key"])) $apiKey = $this->_params["key"];
				
				if($apiKey !== API_KEY)
				{
					header("HTTP/1.0 403 Forbidden"); 
					echo("Your api key is invalid."); 
					exit;
				}

			}

			if(isset($this->_params["action"])) $action = $this->_params["action"];
				
			if (in_array($action, $this->_allowedMethods) && method_exists($this, $action))
			{
				call_user_func(array($this, $action), $this->_params);
			}else{
				header("HTTP/1.0 404 Not Found"); 
				echo("Your requested action does not exist.");
				exit;
			}
		}
		
		/**
		 * Shows the current api version
		 */
		private function version(){
			echo(VERSION);
			exit;
		}
		
		/**
		 * Shows a list of all clients
		 */
		private function show()
		{
			foreach($this->_teamSpeak->channelGetByName(CHANNEL_NAME) as $client)
			{
				echo((string)$client."\r\n");
			}
			
			exit;
		}
		
		/**
		 * Generates a linking code
		 */
		private function request()
		{	
			if(isset($this->_params["user_name"])) $userName = $this->_params["user_name"];
			
			if(!isset($userName))
			{
				die("No username was specified."); 
			}
			
			$code = rand(1000, 9999);
			
			if(!is_dir(REQUEST_DIRECTORY)) mkdir(REQUEST_DIRECTORY);
			
			$client = $this->_teamSpeak->channelGetByName(CHANNEL_NAME)->clientGetByName($userName);
			
			file_put_contents(REQUEST_DIRECTORY.$code, $client->getUniqueId());
			
			$client->poke("Code: ".$code);
			
			exit;
		}
		
		/**
		 * Finishes the account link
		 */
		private function activate()
		{
			if(isset($this->_params["code"])) $code = $this->_params["code"];
			if(isset($this->_params["steamID"])) $steamId = $this->_params["steamID"];
			
			if(!isset($code))
			{
				die("No code was specified."); 
			}
			
			if(!isset($steamId))
			{
				die("No steam id was specified."); 
			}
			
			if(!file_exists(REQUEST_DIRECTORY.$code))
			{
				die("Invalid code was specified.");
			}
			
			$teamSpeakId = file_get_contents(REQUEST_DIRECTORY.$code);
			
			$this->_linkCache->createLink($steamId, $teamSpeakId);
			
			if($grantTalkPowerOnJoin) $this->_teamSpeak->clientGetByUid($teamSpeakId)->modify(array(
				"CLIENT_IS_TALKER" => 1
			));
			
			@unlink(REQUEST_DIRECTORY.$code);
			
			die("OK");
		}
		
		
		/**
		 * Unlinks an account link
		 */
		private function _unlink()
		{
			if(isset($this->_params["steamID"])) $steamId = $this->_params["steamID"];
			
			if(!isset($steamId))
			{
				die("No steam id was specified."); 
			}
			
			$this->_linkCache->removeLink($steamId);
		}
		
		/**
		 * Mutes the current client
		 */
		private function mute()
		{
			$client = $this->getTeamSpeakClient($this->_params["steamID"], $this->_params["ip_address"], $this->_params["user_name"]);
			
			if(isset($client))
			{
				$this->setTalkPower(false, $client);
			}else{
				$this->debug("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"].". could not be found");
				exit;
			}
		}
		
		/**
		 * Unmutes the current client
		 */
		private function unmute()
		{
			$client = $this->getTeamSpeakClient($this->_params["steamID"], $this->_params["ip_address"], $this->_params["user_name"]);
			
			if(isset($client))
			{
				$this->setTalkPower(true, $client);
			}else{
				$this->debug("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"].". could not be found");
				exit;
			}
		}
		
		/**
		 * Unmutes all users in the TTT channel
		 */
		private function unmute_all()
		{
			foreach($this->_teamSpeak->channelGetByName(CHANNEL_NAME)->clientList() as $client)
			{
				$this->setTalkPower(true, $client);
			}
		}
		
		/**
		 * Sets the talk power of a client
		 * @param bool $talkPower
		 * @param Client $client
		 */
		private function setTalkPower($talkPower, $client)
		{
			try {
				$client->modify(array(
					"CLIENT_IS_TALKER" => intval($talkPower)
				));
			}catch(Exception $ex){
				$this->debug("An error has occured.", $ex);
				exit;
			}		
		}
		
		/**
		 * Moves a user to the correct channel and possibly grants talk power
		 */
		private function connect()
		{
			$client = $this->getTeamSpeakClient($this->_params["steamID"], $this->_params["ip_address"], $this->_params["user_name"]);
			
			if(isset($client))
			{
				try {
					if(AUTO_CHANNEL_MOVE) $client->move($this->_teamSpeak->channelGetByName(CHANNEL_NAME)->getId());
				}catch(Exception $ex){
					if(strpos($ex->getMessage(), "invalid client") !== false)
					{
						die("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"]." is not on the TeamSpeak server.");
					}elseif(strpos($ex->getMessage(), "already member of channel") !== false)
					{
						$this->debug("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"]." is already a member of this channel.");
					}else{
						$this->debug("An exception has occured.", $ex);
					}
				}	
				
				try {
					if(GRANT_TALK_POWER_ON_JOIN)
					{
						$client->modify(array(
							"CLIENT_IS_TALKER" => 1
						));
					}
				}catch(Exception $ex){
					$this->debug("An exception has occured.", $ex);
				}
			}else{
				$this->debug("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"].". could not be found");
				
				die("The user ".$this->_params["user_name"]." using the ip address ".$this->_params["ip_address"]." and Steam id ".$this->_params["steamID"]." is not on the TeamSpeak server.");
			}
		}
		
		/**
		 * Kicks a client after disconnecting if auto kick is enabled.
		 */
		private function disconnect()
		{
			if(AUTO_KICK)
			{
				$this->kick();
			}
		}
		
		private function kick()
		{
			try {
				$client = $this->getTeamSpeakClient($this->_params["steamID"], $this->_params["ip_address"], $this->_params["user_name"]);
				
				$client->kick();
			}catch(Exception $ex){
				$this->debug("An exception has occured.", $ex);
			}
		}
		
		# endregion
	}
		
	interface ILinkCache
	{
		# region Methods
		
		/**
		 * Creates a link and stores it appropiately
		 * @param string $steamId
		 * @param string $teamspeakId
		 */
		public function createLink($steamId, $teamspeakId);
		
		/**
		 * Removes an existing link
		 * @param string $steamId
		 */
		public function removeLink($steamId);
		
		/**
		 * Gets an existing link
		 * @param string $steamId
		 * @returns TeamSpeak Id
		 */
		public function getLink($steamId);
		
		# endregion
	}
	
	class JsonLinkCache implements ILinkCache
	{
		# region Fields
		
		private $_links;
		
		# endregion
		
		# region Constructors
		
		public function __construct()
		{			
			if(!is_readable(dirname("LinkCache.json")) || !is_writable(dirname("LinkCache.json")))
			{
				die("Cannot read or write the LinkCache.json file. Please check the permissions on this file.");
				
				return;
			}
			
			if(file_exists("LinkCache.json"))
			{
				$linksJson = file_get_contents("LinkCache.json");
			}
			
			$this->_links = isset($linksJson) ? json_decode($linksJson, true) : array();
		
			if(!is_array($this->_links))
			{
				die("Error reading the LinkCache.json");
			}
		}
		
		# endregion
		
		# region Methods
		
		/**
		 * Creates a link and stores it appropiately
		 * @param string $steamId
		 * @param string $teamspeakId
		 */
		public function createLink($steamId, $teamspeakId)
		{
			$this->_links[$steamId] = $teamspeakId;
		
			file_put_contents("LinkCache.json", json_encode($this->_links));
		}
		
		/**
		 * Removes an existing link
		 * @param string $steamId
		 */
		public function removeLink($steamId)
		{
			if(isset($this->_links[$steamId]))
			{
				unset($this->_links[$steamId]);
				
				file_put_contents("LinkCache.json", json_encode($this->_links));
			}
		}
		
		/**
		 * Gets an existing link
		 * @param string $steamId
		 * @returns TeamSpeak Id
		 */
		public function getLink($steamId)
		{
			if(isset($this->_links[$steamId]))
			{
				return $this->_links[$steamId];
			}
			
			return null;
		}
		
		# endregion
	}
	
	/**
	 * Initializes the TS3 Bridge
	 */
	$ts3bridge = new TS3Bridge(
		$_GET,
		new JsonLinkCache()
	);
	
	/**
	 * Handle the http request
	 */
	$ts3bridge->handleRequest();
