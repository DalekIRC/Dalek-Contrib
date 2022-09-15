<?php
 
/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//					
\\	Title:  ChanServ types back!
//	
\\	Desc:	Makes ChanServ respond to typing notifications with more typing notifications
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
 

class cs_typing {
 
	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_typing";
	public $description = "Makes ChanServ send typing notifications when someone else types lol";
	public $author = "Valware";
	public $version = "1.0";

	function __init()
	{
		hook::func(HOOKTYPE_TAGMSG, 'cs_typing::type_lol');
		return true;
	}
	
	public static function type_lol($u)
	{
		if ($u['dest'][0] != "#") // if this isn't a channel we dgaf
			return;
		
		foreach($u['mtags'] as $key => $value)
		{
			if (!strstr($key, "typing"))
				continue;

			Client::find("ChanServ")->tagmsg([$key => $value], $u['dest']);
		}	
	}
}
