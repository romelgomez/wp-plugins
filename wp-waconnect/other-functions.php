<?php

	function wa_build_link($number=null,$text=null)
	{
		$api = get_option("wac_api");
		$language = get_option("wac_lang");

		if(empty($language))
		{
			$language = "en";
		}

		if($api == 'scheme')
		{
			$base_link = "whatsapp://send?";
		}
		elseif($api == 'web'){
			$base_link = "https://web.whatsapp.com/send?";
		}
		else{
			// Default
			$base_link = "https://api.whatsapp.com/send?";
		}
	    
	    if(!empty($number))
	    {
	    	$number = preg_replace('/[^0-9]+/', '', $number);
	    	$base_link .= "phone=".$number;
	    }

	    if(!empty($text))
	    {
			  	if(!empty($number))
			    {
			    	$base_link .= "&";
			    }
	    	$base_link .= "text=".$text;
	    }
	    
	    if($api != "scheme"){
	    	$base_link .= "&l=".$language;
	    }

	    return $base_link;

	}