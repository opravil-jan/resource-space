<?php
include "../include/db.php";
include "../include/general.php";


$error=false;
$error_extra="";

$user_email=getval("email","");
hook("preuserrequest");

if (!function_exists("auto_create_user_account")){
function auto_create_user_account()
	{
	# Automatically creates a user account (which requires approval unless $auto_approve_accounts is true).
	global $applicationname,$user_email,$baseurl,$email_notify,$lang,$user_account_auto_creation_usergroup,$registration_group_select,$auto_approve_accounts,$auto_approve_domains,$customContents;

	# Work out which user group to set. Allow a hook to change this, if necessary.
	$altgroup=hook("auto_approve_account_switch_group");
	if ($altgroup!==false)
		{
		$usergroup=$altgroup;
		}
	else
		{
		$usergroup=$user_account_auto_creation_usergroup;
		}

	if ($registration_group_select)
		{
		$usergroup=getvalescaped("usergroup","",true);
		# Check this is a valid selectable usergroup (should always be valid unless this is a hack attempt)
		if (sql_value("select allow_registration_selection value from usergroup where ref='$usergroup'",0)!=1) {exit("Invalid user group selection");}
		}

	$username=escape_check(make_username(getval("name","")));

	#check if account already exists
	$check=sql_value("select email value from user where email = '$user_email'","");
	if ($check!=""){return $lang["useremailalreadyexists"];}

	# Prepare to create the user.
	$email=trim(getvalescaped("email","")) ;
	$password=make_password();

	# Work out if we should automatically approve this account based on $auto_approve_accounts or $auto_approve_domains
	$approve=false;
	if ($auto_approve_accounts==true)
		{
		$approve=true;
		}
	elseif (count($auto_approve_domains)>0)
		{
		# Check e-mail domain.
		foreach ($auto_approve_domains as $domain=>$set_usergroup)
			{
			// If a group is not specified the variables don't get set correctly so we need to correct this
			if (is_numeric($domain)){$domain=$set_usergroup;$set_usergroup="";}
			if (substr(strtolower($email),strlen($email)-strlen($domain)-1)==("@" . strtolower($domain)))
				{
				# E-mail domain match.
				$approve=true;

				# If user group is supplied, set this
				if (is_numeric($set_usergroup)) {$usergroup=$set_usergroup;}
				}
			}
		}

	# Create the user
	sql_query("insert into user (username,password,fullname,email,usergroup,comments,approved) values ('" . $username . "','" . $password . "','" . getvalescaped("name","") . "','" . $email . "','" . $usergroup . "','" . escape_check($customContents) . "'," . (($approve)?1:0) . ")");
	$new=sql_insert_id();
    hook("afteruserautocreated", "all",array("new"=>$new));
	if ($approve)
		{
		# Auto approving, send mail direct to user
		email_user_welcome($email,$username,$password,$usergroup);
		}
	else
		{
		# Not auto approving.
		# Build a message to send to an admin notifying of unapproved user (same as email_user_request(),
		# but also adds the new user name to the mail)
		$message=$lang["userrequestnotification1"] . "\n\n" . $lang["name"] . ": " . getval("name","") . "\n\n" . $lang["email"] . ": " . getval("email","") . "\n\n" . $lang["comment"] . ": " . getval("userrequestcomment","") . "\n\n" . $lang["ipaddress"] . ": '" . $_SERVER["REMOTE_ADDR"] . "'\n\n" . $customContents . "\n\n" . $lang["userrequestnotification3"] . "\n$baseurl?u=" . $new;

		send_mail($email_notify,$applicationname . ": " . $lang["requestuserlogin"] . " - " . getval("name",""),$message,"",$user_email,"","",getval("name",""));
		}

	return true;
	}
} //end function replace hook

function email_user_request()
	{
	# E-mails the submitted user request form to the team.
	global $applicationname,$user_email,$baseurl,$email_notify,$lang,$customContents;

	# Build a message
	$message=$lang["userrequestnotification1"] . "\n\n" . $lang["name"] . ": " . getval("name","") . "\n\n" . $lang["email"] . ": " . getval("email","") . "\n\n" . $lang["comment"] . ": " . getval("userrequestcomment","") . "\n\n" . $lang["ipaddress"] . ": '" . $_SERVER["REMOTE_ADDR"] . "'\n\n" . $customContents . "\n\n" . $lang["userrequestnotification2"] . "\n$baseurl";

	send_mail($email_notify,$applicationname . ": " . $lang["requestuserlogin"] . " - " . getval("name",""),$message,"",$user_email,"","",getval("name",""));

	return true;
	}

if (getval("save","")!="")
	{
	# Check for required fields

	# Required fields (name, email) not set?
	$missingFields = hook('replacemainrequired');
	if (!is_array($missingFields))
		{
		$missingFields = array();
		if (getval("name","")=="") { $missingFields[] = $lang["yourname"]; }
		if (getval("email","")=="") { $missingFields[] = $lang["youremailaddress"]; }
		}

	# Add custom fields
	$customContents="";
	if (isset($custom_registration_fields))
		{
		$custom=explode(",",$custom_registration_fields);

		# Required fields?
		if (isset($custom_registration_required)) {$required=explode(",",$custom_registration_required);}

		# Loop through custom fields
		for ($n=0;$n<count($custom);$n++)
			{
			$custom_field_value = getval("custom" . $n,"");
			$custom_field_sub_value_list = "";

			for ($i=1; $i<=1000; $i++)		# check if there are sub values, i.e. custom<n>_<n> form fields, for example a bunch of checkboxes if custom type is set to "5"
				{
				$custom_field_sub_value = getval("custom" . $n . "_" . $i, "");
				if ($custom_field_sub_value == "") continue;
				$custom_field_sub_value_list .= ($custom_field_sub_value_list == "" ? "" : ", ") . $custom_field_sub_value;		# we have found a sub value so append to the list
				}

			if ($custom_field_sub_value_list != "")		# we found sub values
				{
				$customContents.=i18n_get_translated($custom[$n] . ": " . $custom_field_sub_value_list) . "\n\n";		# append with list of all sub values found
				}
			elseif ($custom_field_value != "")		# if no sub values found then treat as normal field
				{
				$customContents.=i18n_get_translated($custom[$n] . ": " . $custom_field_value) . "\n\n";		# there is a value so append it
				}
			elseif (isset($required) && in_array($custom[$n],$required))		# if the field was mandatory and a value or sub value(s) not set then we return false
				{
				$missingFields[] = $custom[$n];
				}
			}
		}

	if (!empty($missingFields))
		{
		$error=$lang["requiredfields"] . ' ' . i18n_get_translated(implode(', ', $missingFields), true);
		}
	# Check the anti-spam code is correct
	elseif (getval("antispamcode","")!=md5(getval("antispam","")))
		{
		$error=$lang["requiredantispam"];
		}
	# Check that the e-mail address doesn't already exist in the system
	elseif (user_email_exists($user_email))
		{
		# E-mail already exists
		$error=$lang["accountemailalreadyexists"];$error_extra="<br/><a href=\"".$baseurl_short."pages/user_password.php?email=" . urlencode($user_email) . "\">" . $lang["forgottenpassword"] . "</a>";
		}
	else
		{
		# E-mail is unique
		
		if ($user_account_auto_creation)
			{	
			# Automatically create a new user account
			$try=auto_create_user_account();
			}
		else
			{
			$try=email_user_request();
			}
			
		if ($try===true)
			{
			redirect($baseurl_short."pages/done.php?text=user_request");
			}
		else
			{
			$error=$try;
			}
		}
	}
include "../include/header.php";
?>

<h1><?php echo $lang["requestuserlogin"]?></h1>
<p><?php echo text("introtext")?></p>

<form method="post" action="<?php echo $baseurl_short?>pages/user_request.php">  

<?php if (!hook("replacemain")) { /* BEGIN hook Replacemain */ ?>

<div class="Question">
<label for="name"><?php echo $lang["yourname"]?> <sup>*</sup></label>
<input type=text name="name" id="name" class="stdwidth" value="<?php echo htmlspecialchars(getvalescaped("name",""))?>">
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label for="email"><?php echo $lang["youremailaddress"]?> <sup>*</sup></label>
<input type=text name="email" id="email" class="stdwidth" value="<?php echo htmlspecialchars(getvalescaped("email",""))?>">
<div class="clearerleft"> </div>
</div>

<?php } /* END hook Replacemain */ ?>

<?php # Add custom fields 
if (isset($custom_registration_fields))
	{
	$custom=explode(",",$custom_registration_fields);
	$required=explode(",",$custom_registration_required);
	
	for ($n=0;$n<count($custom);$n++)
		{
		$type=1;
		
		# Support different question types for the custom fields.
		if (isset($custom_registration_types[$custom[$n]])) {$type=$custom_registration_types[$custom[$n]];}
		
		if ($type==4)
			{
			# HTML type - just output the HTML.
			$html = $custom_registration_html[$custom[$n]];
			if (is_string($html))
				echo $html;
			else if (isset($html[$language]))
				echo $html[$language];
			else if (isset($html[$defaultlanguage]))
				echo $html[$defaultlanguage];
			}
		else
			{
			?>
			<div class="Question" id="Question<?php echo $n?>">
			<label for="custom<?php echo $n?>"><?php echo htmlspecialchars(i18n_get_translated($custom[$n]))?>
			<?php if (in_array($custom[$n],$required)) { ?><sup>*</sup><?php } ?>
			</label>
			
			<?php if ($type==1) {  # Normal text box
			?>
			<input type=text name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" value="<?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?>">
			<?php } ?>

			<?php if ($type==2) { # Large text box 
			?>
			<textarea name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" rows="5"><?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?></textarea>
			<?php } ?>

			<?php if ($type==3) { # Drop down box
			?>
			<select name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth">
			<?php foreach ($custom_registration_options[$custom[$n]] as $option)
				{
				?>
				<option><?php echo htmlspecialchars(i18n_get_translated($option));?></option>
				<?php
				}
			?>
			</select>
			<?php } ?>
			
			<?php if ($type==5) { # checkbox
				?>
				<div class="stdwidth">			
					<table>
						<tbody>
						<?php								
						$i=0;
						foreach ($custom_registration_options[$custom[$n]] as $option)		# display each checkbox
							{
							$i++;
							$option_exploded = explode (":",$option);
							if (count($option_exploded) == 2)		# there are two fields, the first indicates if checked by default, the second is the name
								{
								$option_checked = ($option_exploded[0] == "1");
								$option_label = htmlspecialchars(i18n_get_translated(trim($option_exploded[1])));
								}
							else		# there are not two fields so treat the whole string as the name and set to unchecked
								{
								$option_checked = false;
								$option_label = htmlspecialchars(i18n_get_translated(trim($option)));
								}
							$option_field_name = "custom" . $n . "_" . $i;		# same format as all custom fields, but with a _<n> indicating sub field number
							?>
							<tr>
								<td>
									<input name="<?php echo $option_field_name; ?>" id="<?php echo $option_field_name; ?>" type="checkbox" <?php if ($option_checked) { ?> checked="checked"<?php } ?> value="<?php echo $option_label; ?>"></input>
								</td>
								<td>
									<label for="<?php echo $option_field_name; ?>" class="InnerLabel"><?php echo $option_label;?></label>												
								</td>
							</tr>
							<?php					
							}			
						?>				
						</tbody>
					</table>
				</div>			
			<?php } ?>
			
			<div class="clearerleft"> </div>
			</div>
			<?php
			}
		}
	}
?>

<?php if (!hook("replacegroupselect")) { /* BEGIN hook Replacegroupselect */ ?>
<?php if ($registration_group_select) {
# Allow users to select their own group
$groups=get_registration_selectable_usergroups();
?>
<div class="Question">
<label for="usergroup"><?php echo $lang["group"]?></label>
<select name="usergroup" id="usergroup" class="stdwidth">
<?php for ($n=0;$n<count($groups);$n++)
	{
	?>
	<option value="<?php echo $groups[$n]["ref"] ?>"><?php echo htmlspecialchars($groups[$n]["name"]) ?></option>
	<?php
	}
?>
</select>
<div class="clearerleft"> </div>
</div>	
<?php } ?>
<?php } /* END hook Replacegroupselect */ ?>

<?php if (!hook("replaceuserrequestcomment")){ ?>
<div class="Question">
<label for="userrequestcomment"><?php echo $lang["userrequestcomment"]?></label>
<textarea name="userrequestcomment" id="userrequestcomment" class="stdwidth"><?php echo htmlspecialchars(getvalescaped("userrequestcomment",""))?></textarea>
<div class="clearerleft"> </div>
</div>	
<?php } /* END hook replaceuserrequestcomment */ ?>

<?php hook("userrequestadditional");?>

<br />

<?php
$code=rand(1000,9999);
?>
<input type="hidden" name="antispamcode" value="<?php echo md5($code)?>">
<div class="Question">
<label for="antispam"><?php echo $lang["enterantispamcode"] . " " . $code ?></label>
<input type=text name="antispam" id="antispam" class="stdwidth" value="">
<div class="clearerleft"> </div>
</div>


<div class="QuestionSubmit">
<?php if ($error) { ?><div class="FormError">!! <?php echo $error ?> !!<?php echo $error_extra?></div><br /><?php } ?>
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["requestuserlogin"]?>&nbsp;&nbsp;" />
</div>
</form>

<p><sup>*</sup> <?php echo $lang["requiredfield"] ?></p>	

<?php
include "../include/footer.php";
?>

