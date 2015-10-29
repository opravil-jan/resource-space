<?php

// ------------------------- LOG_CODE_ -------------------------

// codes used for log entries (including resource and activity logs)

define ('LOG_CODE_ACCESS_CHANGED',		'a');
define ('LOG_CODE_ALTERNATIVE_CREATED',	'b');
define ('LOG_CODE_CREATED',				'c');
define ('LOG_CODE_DOWNLOADED',			'd');
define ('LOG_CODE_EDITED',				'e');
define ('LOG_CODE_EMAILED',				'E');
define ('LOG_CODE_LOGGED_IN',			'l');
define ('LOG_CODE_MULTI_EDITED',		'm');
define ('LOG_CODE_PAYED',				'p');
define ('LOG_CODE_REVERTED_REUPLOADED',	'r');
define ('LOG_CODE_STATUS_CHANGED',		's');
define ('LOG_CODE_TRANSFORMED',			't');
define ('LOG_CODE_UPLOADED',			'u');
define ('LOG_CODE_UNSPECIFIED',			'U');
define ('LOG_CODE_VIEWED',				'v');
define ('LOG_CODE_DELETED',				'x');

// validates log code as if a enumerated type
function is_LOG_CODE($log_code)
	{
	return is_defined_legal('LOG_CODE',$log_code);
	}

// used by is_<>() functions
function is_defined_legal($prefix,$value)
	{
	foreach (get_defined_constants() as $key => $val)
		{
		if (preg_match('/^' . $prefix . '/', $key) && $val == $value)
			{
			return true;
			}
		}
	return false;
	}
