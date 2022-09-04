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
\\	Title: Dalek Updates
//	
\\	Desc: Post to the designated channel when there is a new version of Dalek
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
define("LOG_UPDATE", bold("[UPDATE] "));



class dalek_updates {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "dalek_updates";
	public $description = "Post to channel whenever there is a new version of Dalek";
	public $author = "Valware";
	public $version = "1.0";


	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS dalek_versioning (
				id int AUTO_INCREMENT NOT NULL,
				version varchar(255) NOT NULL,
				tarball varchar(255) NOT NULL,
				winball varchar(255) NOT NULL,
				discussion varchar(255) NOT NULL,
				PRIMARY KEY (id)
			)");
		$conn->close();

	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("chanmsg", 'dalek_updates::fantasy');
	}


	function __init()
	{
		if (!self::UpdateChan())
			return false;
		// we are starting this in 5m in case someone has temperamental stuff going on, so we don't spam github lol
		// it also checks every 5m minutes
		$error = NULL;
		Events::Add(servertime() + 300, 0, 300, 'dalek_updates::check_for_new_version', [], $error, 'dalek_updates');
		
		hook::func("chanmsg", 'dalek_updates::fantasy');
		return true;
	}

	static function fantasy($u)
	{
		global $chanserv;
		$cs = Client::find($chanserv['nick']);
		$parv = split($u['params']);
		if (strcasecmp(mb_substr($parv[1],1),"!dalek") || strcasecmp($u['dest'],dalek_updates::UpdateChan()))
			return;

		if (!($version = self::get_last()))
		{
			$cs->msg(dalek_updates::UpdateChan(), "Oops! We haven't checked yet! You have to wait for at 5 minutes after startup before that check is made");
			return;
		}

		$cs->msg(dalek_updates::UpdateChan(), "Last time we checked (we check every 5m) this was the info:");
		$cs->msg(dalek_updates::UpdateChan(), bold("Latest verion:")." v".$version['version']);
		$cs->msg(dalek_updates::UpdateChan(), bold("tar ball:")." ".$version['tarball']);
		$cs->msg(dalek_updates::UpdateChan(), bold("zip file:")." ".$version['zipball']);
		$cs->msg(dalek_updates::UpdateChan(), bold("Join the discussion:")." ".$version['discussion']);
		
	}

	static function check_for_new_version($params = NULL) // we need $params because it's an event function which passes an array, it will error otherwise
	{
		$valuet = NULL;
		$isBeta = 0;
		$version = NULL;
		$download_link = NULL;
		$dl_windows = NULL;
		$discussion = NULL;

		$user_agent = $opts = [
			'http' => [
					'method' => 'GET',
					'header' => [
							'User-Agent: PHP'
						]
				]
		];
		$context = stream_context_create($user_agent);
		$json = (array)json_decode(file_get_contents("https://api.github.com/repos/DalekIRC/Dalek-Services/releases", false, $context));
		if ($json)
		{
			// new blocc
			$json = $json[0];
			foreach((array)$json as $key => $value)
			{
				var_dump($key);
				var_dump($value);
				if (!strcasecmp($key, "tag_name"))
					$version = $value;

				if (!strcasecmp($key, "tarball_url"))
					$download_link = $value;
				
				if (!strcasecmp($key, "zipball_url"))
					$dl_windows = $value;

				if (!strcasecmp($key, "discussion_url"))
					$discussion = $value;
			}
		}
		else
		{
			DebugLog("Could not find JSON dimensions for the GitHub API, aborting",LOG_UPDATE);
			return;
		}
		
		self::set_last($version, $download_link, $dl_windows, $discussion);
		$newver_short = glue(split($version,"-beta"));
		$ourver_short = glue(split(DALEK_VERSION,"-beta"));
		$get_excited = 0; // whether or not we should get excited about a new version =] 

		if (!self::get_last())
			$get_excited = 1;

		if (!is_numeric($version)) // beta version
		{ 
			if ($newver_short > $ourver_short)
				$get_excited = 1;
		}
		elseif ($version > DALEK_VERSION)
			$get_excited = 1;
		
		
		if ($get_excited)
		{
			global $chanserv;
			$cs = Client::find($chanserv['nick']);
			$cs->msg(dalek_updates::UpdateChan(),"Dalek v$version released!");
			$cs->msg(dalek_updates::UpdateChan(),"Download tarball: $download_link");
			$cs->msg(dalek_updates::UpdateChan(),"Windows download: $dl_windows");
			$cs->msg(dalek_updates::UpdateChan(),"Discussion URL: $discussion");
			DebugLog("Found update. New: $version - Short: $newver_short - Current: ".DALEK_VERSION." - Short: $ourver_short");

			$args = []; // empty args for update hook
			Hook::Run(HOOKTYPE_UPDATE_FOUND, $args);
		}
		else
		{
			DebugLog("No update found.",LOG_UPDATE);
		}
	
	}
	static function set_last($ver, $tar, $win, $disc)
	{
		$conn = sqlnew();
		if (!dalek_updates::get_last())
		{
			$conn->query("TRUNCATE TABLE dalek_versioning");
			$conn->query("INSERT INTO dalek_versioning (version, tarball, winball, discussion) VALUES ('$ver', '$tar', '$win', '$disc')");
		}
		else
			$conn->query("UPDATE dalek_versioning SET version = '$ver', tarball = '$tar', winball = '$win', discussion = '$disc' WHERE id = 1");
	}
	static function get_last()
	{
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM dalek_versioning WHERE id = 1");
		if (!$result || !$result->num_rows)
			return 0;

		$row = $result->fetch_assoc();
		return $row;

	}
	static function UpdateChan()
	{
		global $cf;
		if (!isset($cf['update_chan']))
			return false;
		else
			return $cf['update_chan'];
	}
}
