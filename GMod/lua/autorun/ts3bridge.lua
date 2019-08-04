-- TeamSpeak 3 Bridge for TTT: Version 2
-- @author CynCeyd
-- @license MIT
-- @date 2019-06-22

-- Fields

-- URL to the PHP script
-- URL Format: http://example.com/ts3bridge.php
ts_bridge_url = CreateConVar("ttt_ts3_bridge_url", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The URL for the TS3 bridge") 

-- Some key for authentication, otherwise the PHP api could potentially be abused
ts_bridge_key = CreateConVar("ttt_ts3_bridge_key", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The API key for the TS3 bridge")

ts_bridge_kick_non_ts_users = CreateConVar("ttt_ts3_bridge_kick_non_ts_users", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "Whether users shall be kicked out of the channel when they leave the server")
ts_bridge_kick_warning_message = CreateConVar("ttt_ts3_bridge_kick_warning_message", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The message shown to a user as a warning before kicking")
ts_bridge_kick_message = CreateConVar("ttt_ts3_bridge_kick_message", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The message shown to a user when kicked")
ts_bridge_kick_warning_repetition = CreateConVar("ttt_ts3_bridge_kick_warning_repetition", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The amount of warnings shown to a user prior to kicking")
ts_bridge_kick_warning_period = CreateConVar("ttt_ts3_bridge_kick_warning_period", "", {FCVAR_SERVER_CAN_EXECUTE, FCVAR_ARCHIVE, FCVAR_REPLICATED}, "The time between the repetitions in seconds")

ts_bridge_url = ts_bridge_url:GetString()
ts_bridge_key = ts_bridge_key:GetString()

ts_bridge_kick_non_ts_users = ts_bridge_kick_non_ts_users:GetBool()
ts_bridge_kick_warning_message = ts_bridge_kick_warning_message:GetString()
ts_bridge_kick_message = ts_bridge_kick_message:GetString()
ts_bridge_kick_warning_repetition = ts_bridge_kick_warning_repetition:GetInt()
ts_bridge_kick_warning_period = ts_bridge_kick_warning_period:GetInt()

-- Set the static parameters
static_params = {key=ts_bridge_key}

-- Set the version
version = 1

-- Set the help message for the chat command
help_message = "The !tsbridge command allows you to manually link a player to a TeamSpeak user.\nUse !tsbridge list or !tsbridge [PlayerName] or !tsbridge [TeamSpeakID] first.\nThis will, if the TeamSpeak user is found, send the TeamSpeak user a message with a code.\nUse !tsbridge activate [code] to finish the link.\nUse !tsbridge unlink or !tsbridge unlink [Name] or !tsbridge unlink [SteamID] or !tsbridge unlink [TeamSpeakID] to remove a link between a user and TeamSpeak user."
user_list = {}

-- End Fields

-- Methods

-- Checks whether a string is nil or empty
-- @param str String to validate
local function is_nil_or_empty(str)
  return str == nil or str == ''
end

-- Checks if an indexed table contains an element
-- @param table Table to check
-- @param str String to find
-- @see https://stackoverflow.com/questions/33510736/check-if-array-contains-specific-value
local function array_contains(tab, val)
    for index, value in ipairs(tab) do
        if value == val then
            return true
        end
    end

    return false
end

-- Gets the index of a value in a table
-- @param table Table to check
-- @param str String to find
local function array_get_index_of_value(tab, val)
    for index, value in ipairs(tab) do
        if value == val then
            return index
        end
    end

    return -1
end

-- Splits a string
-- @param str String to split
-- @param delimiter Delimiter to use
-- @see https://helloacm.com/split-a-string-in-lua/
local function split(str, delimiter)
    result = {}
	
    for match in (str..delimiter):gmatch("(.-)"..delimiter) do
        table.insert(result, match)
    end
	
    return result
end

-- Following two methods for url escaping
-- @see https://www.rosettacode.org/wiki/URL_encoding#Lua

-- Encodes a single char
-- @param str Char to encode
function encode_char(chr)
	return string.format("%%%X",string.byte(chr))
end
 
-- Encodes a string
-- @param str String to encode
function encode_string(str)
	local output, t = string.gsub(str,"[^%w]",encode_char)
	return output
end

-- Encodes an url with given parameters
-- @param url Base url
-- @param params Parameters to encode the url with (as table)
local function url_encode(url, params)
	
	params = params or {}
	query = "?"
	
	for key,value in pairs(static_params) do
		if key ~= nil and value ~= nil then
			query = query .. key .. "=" .. encode_string(value) .. "&"
		end
	end
	
	for key,value in pairs(params) do
		if key ~= nil and value ~= nil then
			query = query .. key .. "=" .. encode_string(value) .. "&"
		end
	end
	
	query = string.sub(query, 0, string.len(query)-1)

	return url .. query
end

-- Logs a message
-- @param message Message to log
local function log(message)
	ServerLog("[TS3 Bridge TTT] " .. message .. "\r\n")
end

-- Simulates a try-catch-block
-- @param _function Function to execute
-- @param catch_function Function to execute, when exception is raised
-- https://www.lua.org/wshop06/Belmonte.pdf
local function try(_function, catch_function)
	local status, exception = pcall(_function)
	
	if not status then
		catch_function(exception)
	end
end

-- Sends a http get request
-- @param params Parameters to encode the url with (as table)
-- @param callback Callback that is called after the response
-- @param error_callback Callback that is called after an error has occured within the request
local function http_get(params, callback, error_callback)

	encoded_url = url_encode(ts_bridge_url, params)

	http.Fetch( encoded_url,
		function( body, len, headers, code )
			if code >= 200 and code < 300 then
				if callback then
					callback(body)
				end
			else
				log("HTTP request had an error (" .. code .. "): " .. body)
			end
		end,
		function(error)
		
			log("HTTP request failed: " .. error)
			
			if error_callback then
				error_callback(error)
			end
		end
	)
end

local function mute(steam_id, ip_address, user_name)
	http_get({action="mute", steamID=steam_id, ip_address=ip_address, user_name=user_name}) -- camelCase only legacy support
end

local function unmute(steam_id, ip_address, user_name)
	http_get({action="unmute", steamID=steam_id, ip_address=ip_address, user_name=user_name}) -- camelCase only legacy support
end

local function unmute_all()
	if version >= 2 then
		http_get({action="unmute_all"})
	else
		for k, v in pairs(player.GetAll()) do
		   unmute(v:SteamID(), v:IPAddress(), v:Nick())
		end
	end
end

local function connect(steam_id, ip_address, user_name, callback)
	http_get({action="connect", steamID=steam_id, ip_address=ip_address, user_name=user_name}, callback) -- camelCase only legacy support
end

local function disconnect(steam_id, ip_address, user_name)
	if version >= 2 then
		http_get({action="disconnect", steamID=steam_id, ip_address=ip_address, user_name=user_name}) -- camelCase and "kick" only legacy support
	else
		http_get({action="kick", steamID=steam_id, ip_address=ip_address, user_name=user_name}) -- camelCase and "kick" only legacy support
	end
end

local function get_player_by_entity_id(entity_id)
	for k, v in pairs(player.GetAll()) do
		if v:EntIndex() == entity_id then
			return v
		end
	end
end

local function hook_begin_round()
	hook.Add("TTTBeginRound", "TTTBeginRound_ts3_bridge", function()
		hook.Add( "PlayerDeath", "PlayerDeath_ts3_bridge", 
			function( victim, inflictor, attacker )
				mute(victim:SteamID(), victim:IPAddress(), victim:Nick())
			end
		)
		
		hook.Add( "PlayerSilentDeath", "PlayerSilentDeath_ts3_bridge", 
			function( ply )
				mute(ply:SteamID(), ply:IPAddress(), ply:Nick())
			end
		)
	end
	)
end

local function hook_end_round()
	hook.Add("TTTEndRound", "TTTEndRound_ts3_bridge", 
		function()
			hook.Remove( "PlayerDeath", "PlayerDeath_ts3_bridge")
			hook.Remove( "PlayerSilentDeath", "PlayerSilentDeath_ts3_bridge")
		
			unmute_all()
		end
	)
end

warning_queue = {}

local function init_player_kick(ply)
	if ts_bridge_kick_non_ts_users then
		if not array_contains(warning_queue, ply:SteamID()) then
			steam_id = ply:SteamID()
			table.insert(warning_queue, steam_id)
			
			if ts_bridge_kick_warning_repetition > 0 then
				already_joined = false
				
				for i = 0, ts_bridge_kick_warning_repetition - 1 do		
					timer.Simple(ts_bridge_kick_warning_period * i, 
						function()
							if not already_joined then
								connect(ply:SteamID(), ply:IPAddress(), ply:Nick(),
									function(response)
										if(response ~= "OK" and version < 2 or response ~= "" and version >= 2) then
											ply:PrintMessage(HUD_PRINTCENTER, ts_bridge_kick_warning_message)
											ply:ChatPrint(ts_bridge_kick_warning_message)
										else
											already_joined = true
										end
									end
								)
							end
						end 
					)
				end
				
				timer.Simple(ts_bridge_kick_warning_period * ts_bridge_kick_warning_repetition, 
					function()
						if not already_joined then
							connect(ply:SteamID(), ply:IPAddress(), ply:Nick(),
								function(response)
									if(response ~= "OK" and version < 2 or response ~= "" and version >= 2) then
										if ply then
											ply:Kick(ts_bridge_kick_message)
										end
									end
								end
							)
						end
					end 
				)
			else
				ply:Kick(ts_bridge_kick_message)
			end
		end
	end
end

local function hook_player_spawn()
	hook.Add("player_spawn", "player_spawn_ts3_bridge", 
		function(data)
			ply = Player(data.userid)
			
			if not ply:IsBot() then
				connect(ply:SteamID(), ply:IPAddress(), ply:Nick(),
					function(response)
						if(response ~= "OK" and version < 2 or response ~= "" and version >= 2) then
							if ply then
								init_player_kick(ply)
							end
						end
					end
				)
			end
		end
	)
end

local function hook_player_disconnect()
	hook.Add("player_disconnect", "player_disconnect_ts3_bridge", 
		function(data)
			index = array_get_index_of_value(warning_queue, data.networkid)
			
			if index ~= -1 then
				table.remove(warning_queue, index)
			end
		
			disconnect(data.networkid, data.address, data.name)
		end
	)
end

-- registers the ts3bridge command for manual adding
local function hook_player_say()
	hook.Add("PlayerSay", "PlayerSay_ts3_bridge", 
		function(ply, text)
			text = string.lower(text)

			if string.sub(text, 0, 9) == "!tsbridge" or string.sub(text, 0, 10) == "!ts3bridge" then
				if version < 2 then
					ply:ChatPrint("This command is only available using the version 2 or higher api.")
					
					return false
				end
				
				arguments = split(text, " ")
				
				command = arguments[2]
				
				if command == nil then
					ply:ChatPrint("Use !tsbridge help for instructions on this command.")
				elseif command == "help" then
					for line in help_message:gmatch("[^\r\n]+") do
						ply:ChatPrint(line)
					end
				elseif command == "show" then
					http_get({action="show", steamID=ply:SteamID(), ip_address=ply:IPAddress(), user_name=ply:Nick()}, 
						function(response)
							index = 1
							
							user_list = {}
							
							for line in response:gmatch("[^\r\n]+") do
								table.insert(user_list, line)
								
								ply:ChatPrint(index .. ". " .. line)
							end
							
							ply:ChatPrint("Use !tsbridge request [user number] to send that user a code.")
						end
					)
				elseif command == "request" then
					user_number = tonumber(arguments[3])
					
					if user_number ~= nil or user_number > #user_list then
						user_name = user_list[user_number]
						
						if user_name ~= nil then
							http_get({action="request", steamID=ply:SteamID(), ip_address=ply:IPAddress(), user_name=user_name})
							
							ply:ChatPrint("Request sent to " .. user_name)
						end
					else
						ply:ChatPrint("Invalid user number")
					end
				elseif command == "activate" then
					code = tonumber(arguments[3])
					
					if code ~= nil then
						http_get({action="activate", steamID=ply:SteamID(), ip_address=ply:IPAddress(), user_name=ply:Nick(), code=code},
							function (response)
								if response ~= "OK" then
									ply:ChatPrint("Your TeamSpeak user could not be linked to your Steam account.")
								else
									ply:ChatPrint("Your TeamSpeak user was successfully linked to your Steam account.")
								end
							end
						)
					else
						ply:ChatPrint("You have entered an invalid code.")
					end
				elseif command == "unlink" then
					http_get({action="_unlink", steamID=ply:SteamID(), ip_address=ply:IPAddress(), user_name=ply:Nick()})
					
					ply:ChatPrint("Your TeamSpeak user has been unlinked (if it was linked to a Steam account)")
				end
				
				return false
			end
		end
	)
end

-- Sets up the bridge
local function setup()
	gameevent.Listen( "PlayerDeath" )
	gameevent.Listen( "PlayerSilentDeath" )
	gameevent.Listen( "player_spawn" )
	gameevent.Listen( "player_disconnect" )
	gameevent.Listen( "TTTBeginRound" )
	gameevent.Listen( "TTTEndRound" )
	gameevent.Listen( "PlayerSay" )
	
	hook_player_say()
	
	hook_begin_round()
	hook_end_round()
	hook_player_spawn()
	hook_player_disconnect()
	
	log("Hooks applied")
end

-- End Methods

-- Script

log("Loading TeamSpeak 3 bridge")

static_params = static_params or {}

if not is_nil_or_empty(ts_bridge_key) then

	try(function() -- try
			
			-- Fetch the api version
			http_get({action="version"}, 
				function(response)
					-- Get the api version
					version = tonumber(response) or 1
				
					-- Add the api version to the static parameters for every request
					static_params.version = version
					
					setup()
				end
			)
		end,
		function(exception) -- catch
			
			-- Improved error handling
			
			if(string.match(exception, "Assertion Failed: pHost && *pHost")) then
				log("The given uri is invalid. Did you maybe forget the quotation marks enclosing the uri in the server configuration?")
			else
				log(exception)
			end
		
		end
	)

else
	log("TS3 Bridge uri is empty")
end

-- End Script