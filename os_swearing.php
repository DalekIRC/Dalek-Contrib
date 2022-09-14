<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: OperServ SWEARING
//	
\\	Desc: WARNING: makes services very rude
//
\\  Requested by Wakkymike
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_swearing {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_swearing";
	public $description = "Makes services into angsty lil bitches";
	public $author = "Valware";
	public $version = "1.0";

	public static $swear;
	public static $swearwords = [
		" fucking ",
		" bloody ",
		" goddamn ",
		" stupid ",
		" pissing ",
		" bleeding ",
		" bastard "
	];

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}


	function __init()
	{
		$cmd = "SWEARING";
		$help_string = "Makes services swear (a lot)";
		$syntax = "$cmd";
		$extended_help = 	"$help_string\nMust have oper or above.";

		if (!AddServCmd(
			'os_swearing', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_swearing::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		hook::func(HOOKTYPE_USER_MESSAGE, 'os_swearing::refresh');

		

		return true;
	}
	public static function refresh($u)
	{
		$u['target'] = Client::find($u['dest']);
		$n = rand(0,6);
		self::$swear = self::$swearwords[$n];

		foreach(Filter::$filter_list as &$filter)
		{
			if ($filter['modname'] == "os_swearing" && $filter['nick'] == $u['target']->nick)
			{
				$filter['replace'] = self::$swear;
				break;
			}

		}
	}
	public static function cmd($u)
	{
		/* we just assume that they are allowed to do this based on if they can message OperServ */
		/* want it better? make it better */
		$nick = $u['nick'];
		$os = $u['target'];
		$parv = explode(" ",$u['msg']);
		
		if (count($parv) < 3)
		{
			$os->notice($nick->uid, "Invalid parameters");
			return;
		}

		if (!($client = Client::find($parv[1])))
		{
			$os->notice($nick->uid, "Client not found: ".$parv[1]);
			return;
		}

		if (!strcasecmp($parv[2],"on"))
		{
			Filter::Add('os_swearing', $client, ' ', ' fucking ');
			Filter::Add('os_swearing', $client, '  ', ' ');
			$os->notice($nick->uid, "$client->nick is now swearing lmao");
			SVSLog("$nick->nick made $client->nick start swearing like a FOOKIN trooper lmao");
			return;
		}
		elseif (!strcasecmp($parv[2],"off"))
		{
			Filter::Del('os_swearing',$client->nick);
			$os->notice($nick->uid, "$client->nick is no longer swearing uwu");
			SVSLog("$nick->nick made $client->nick stop swearing >:/");
			return;
		}
		else
		{
			$os->notice($nick->uid, "Unknown subsetting \"".$parv[2]."\"");
		}

	}
}
