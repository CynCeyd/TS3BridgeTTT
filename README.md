# TS3BridgeTTT

A simple TeamSpeak 3 bridge for TTT which automatically mutes / unmutes players.

This plugin consists of two parts:

- The Steam workshop plugin (http://steamcommunity.com/sharedfiles/filedetails/?id=859582611); it adds a lua script that communicates with a web script.
- The web script; it needs to be run on any web server that supports PHP

## Setup

### Garry's Mod Server

When you've added the workshop plugin, you need to set two required and five optional convars :

#### Required Convars

- ttt_ts3_bridge_url "http://example.com/TS3Bridge.php" (please note the quotes)
- ttt_ts3_bridge_key "abc" (anything; just some key for security purposes, so that noone else can abuse the web script)


#### Optional Convars

- ttt_ts3_bridge_kick_non_ts_users [0 or 1] (indicates whether players that are not on the TeamSpeak server shall be kicked or not)
- ttt_ts3_bridge_kick_warning_message "warning message" (the message shown to a player prior to kicking)
- ttt_ts3_bridge_kick_message "kick message" (the message shown to a player when kicked)
- ttt_ts3_bridge_kick_warning_repetition 3 (the amount of times the warning shall be repeated before kicking)
- ttt_ts3_bridge_kick_warning_period 10 (the amount of time in seconds between each warning)

### Web Server

On the web server you need to place the contents of the following GitHub repository:

https://github.com/CynCeyd/TS3BridgeTTT/tree/master/Web

The following connection parameters have to be set for the script to be able to communicate with the TeamSpeak server. Note: The default ports are 10011 for query port and 9987 for the server port.

    define("TEAMSPEAK_CONNECTION_PARAMS", array(
                    	"queryUserName" => "[query_user]",
                    	"queryPassword" => "[query_password]",
                    	"host" => "[host]",
                    	"queryPort" => [query_port],
                    	"serverPort" => [server_port],
                    	"botNick" => "TTT Bot"));
Further parameters are:
- `define("API_KEY", "abc");` - The api key "abc" should be replaced with whatever you put in the convar ttt_ts3_bridge_key, as it is used for authentication between the Garry's Mod- and Web-Server.
- `define("AUTO_KICK", true);` - Whether users shall be kicked automatically, when they leave the server.
- `define("AUTO_CHANNEL_MOVE", true);` - Whether users shall be moved to the TTT channel when they enter the server.
- `define("CHANNEL_NAME", "Trouble in Terrorist Town");` - The TTT Channel name. This channel must have talk power enabled.
- `define("GRANT_TALK_POWER_ON_JOIN", true);` - Whether users are granted talk power on joining the server.
- `define("REQUEST_DIRECTORY", "Requests/");` - Directory where manual linking requests are temporarily stored.
- `define("SCAN_SPECIFIED_CHANNELS_ONLY", false);` - Whether only specified channels are used for scanning the server's users for automatic linking, if the ip address or direct name matching have not been working
- `define("CHANNELS_TO_SCAN", array("Trouble in Terrorist Town"));` - A list of all channels that are scanned for alternative automatic linking.
- `define("USE_LINK_CACHE", true);` - Whether the user links shall be stored or remade each time (it's recommended to be left on).
- `define("DEBUG_MODE", false);` - This is purely for debugging purposes. Do not set to true in productive mode.
- `define("ALLOW_DEBUG_IN_RESPONSE", false);` - The debug output is not only stored in the Log.txt, but also sent back with every server response.
- `define("DECISION_PERCENTAGE", 0.9);` - When automatic linking using the ip address and the user name have not been working, this script will compare the player's name with the nicknames on the TeamSpeak server. The decision percentage states how similar the player's name and the TeamSpeak nickname have to be in order for automatic linking.

If there are any problems with this script, feel free to ask / comment - I will try to help or fix and issues as fast as possible.

-----------------
This plugin uses the TeamSpeak 3 PHP Framework (https://docs.planetteamspeak.com/ts3/php/framework/).

It's recommended to disable the talk power granted / revoked sounds in TeamSpeak, as it might get annoying after a while of playing.


