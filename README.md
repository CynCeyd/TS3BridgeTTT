# TS3BridgeTTT

A simple TeamSpeak 3 bridge for TTT which automatically mutes / unmutes players.

This plugin consists of two parts:

- The Steam workshop plugin (http://steamcommunity.com/sharedfiles/filedetails/?id=859582611); it adds a lua script that communicates with a web script.
- The web script; it needs to be run on any web server that supports PHP

When you've added the workshop plugin, you need to set two convars:

ttt_ts3_bridge_url (URL Format: http://example.com/ts3bridge.php)
ttt_ts3_bridge_key (anything; just some key for security purposes, so that noone else can abuse the web script)

On the web server you need to place the contents of the following GitHub repository:

https://github.com/CynCeyd/TS3BridgeTTT/tree/master/Web

For the web script to work, you have to make some config changes:

$linkType = "json/mysql" = Default is json, it uses links.json for the SteamID to TsID link. If you want to use the mysql module, you will have to modify the sql query and change it so that $links[steamID] = tsID is fulfilled.

$apiKey = It should be the same as ttt_ts3_bridge_key; it will be verified on any call of this script.

$teamSpeakServerQuery = The connection string to the teamspeak server. You will need username and password of a client with serverquery access, the hostname, the serverquery port (default is 10011) and the ts3 server port (default is 9987).

$autoKick = default is true; will automatically kick players from the assigned channel when they leave the game

$autoChannelMove = default is true; will automatically move players to the assigned channel when they connect

$channelName = default is "Trouble in Terrorist Town"; the TTT channel that is used for the plugin; Channel requires talk power for this plugin to work

$grantTalkPowerOnJoin = default is true; when anyone connects to the server, they will automatically receive talk power, even if the round is still going

$mysqli = default is commented; only uncomment if you want to use the mysql module and are able to change the sql query

If there are any problems with this script, feel free to ask / comment - I will try to help or fix and issues as fast as possible.

-----------------

This plugin uses the TeamSpeak 3 PHP Framework (https://docs.planetteamspeak.com/ts3/php/framework/).

It's recommended to disable the talk power granted / revoked sounds in TeamSpeak, as it might get annoying after a while of playing.
