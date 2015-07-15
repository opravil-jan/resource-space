<?php

include "../../include/db.php";
include "../../include/general.php";
include "../../include/authenticate.php";


include "../../include/header.php";
?>
<div class="BasicsBox"> 
  	<h1><?php echo $lang["userpreferences"]?></h1>
  	<p><?php echo $lang["modifyuserpreferencesintro"]?></p>
  	
	<?php
	$userpreferences_plugins= array();
	$plugins_dir = dirname(__FILE__)."/../../plugins/";
	foreach($plugins as $plugin)
		{
		$plugin_yaml = get_plugin_yaml($plugins_dir.$plugin.'/'.$plugin.'.yaml', false);
		if(isset($plugin_yaml["userpreferencegroup"]))
			{
			$upg = trim(mb_strtolower($plugin_yaml["userpreferencegroup"]));
			$userpreferences_plugins[$upg][$plugin]=$plugin_yaml;
			}
		}

	/* Display */
	$options_available = 0; # Increment this to prevent a "No options available" message

	/* User Colour Theme Selection */
	if(FALSE)//(isset($userfixedtheme) && $userfixedtheme=="") && $defaulttheme=="" && count($userpreferences_plugins["colourtheme"])>0)
		{ ?>
		<div class="Question">
			<label for="">
				<?php echo $lang["userpreferencecolourtheme"]; ?>
			</label>
			<script>
				function updateColourTheme(theme) {
					alert("Update User Colourtheme to: "+theme);
					//TODO post to update user preferences
				}
			</script>
			<?php
			# If there are multiple options provide a radio button selector
			if(count($userpreferences_plugins["colourtheme"])>1)
				{ ?>
				<table id="" class="radioOptionTable">
					<tbody>
						<tr>
						<?php
						foreach($userpreferences_plugins["colourtheme"] as $colourtheme)
							{ ?>
							<td valign="middle">
			                    <input 
			                    	type="radio" 
			                    	name="defaulttheme" 
			                    	value="<?php echo $colourtheme["name"];?>" 
			                    	onChange="updateColourTheme('<?php echo $colourtheme["name"];?>');"
			                    />
			                </td>
			                <td align="left" valign="middle">
			                    <label class="customFieldLabel" for="defaulttheme">
			                    	<?php echo $colourtheme["name"];?>
			                    </label>
			                </td>
			                <?php
							}
						?>
						</tr>
	            	</tbody>
			    </table>
	    		<?php
				}
			?>
			<div class="clearerleft"> </div>
		</div>
		<?php
		$options_available++;
		}
	/* End User Colour Theme Selection */

	/* Default display if there are no options available */
	if($options_available == 0)
		{ ?>
		<div class="FormError"><?php echo $lang["no-options-available"];?></div>
		<?php
		} 
	?>
</div>

<?php
include "../../include/footer.php";

