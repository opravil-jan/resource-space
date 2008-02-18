<?
include "include/db.php";
include "include/authenticate.php";if (!checkperm("t")) {exit ("Permission denied.");}
include "include/general.php";
include "include/reporting_functions.php";

$report=getvalescaped("report","");

$from=getvalescaped("from","");
$to=getvalescaped("to","");

if ($report!="")
	{
	do_report($report, getvalescaped("from-y",""), getvalescaped("from-m",""), getvalescaped("from-d",""), getvalescaped("to-y",""), getvalescaped("to-m",""), getvalescaped("to-d",""));
	}
include "include/header.php";
?>

<div class="BasicsBox"> 
  <h2>&nbsp;</h2>
  <h1><?=$lang["viewreport"]?></h1>
  <p><?=text("introtext")?></p>
  
<form method="post">
<div class="Question">
<label for="report"><?=$lang["viewreports"]?><br/><!--* = Does not use date range--></label><select id="report" name="report" class="stdwidth">
<?
$reports=get_reports(); 
for ($n=0;$n<count($reports);$n++)
	{
	?><option value="<?=$reports[$n]["ref"]?>" <? if ($report==$reports[$n]["ref"]) { ?>selected<? } ?>><?=i18n_get_translated($reports[$n]["name"])?></option><?
	}
?>
</select>
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label><?=$lang["fromdate"]?><br/><?=$lang["inclusive"]?></label>
<?
$name="from";
$dy=getval($name . "-y",2000);
$dm=getval($name . "-m",1);
$dd=getval($name . "-d",1);
?>
<select name="<?=$name?>-d">
<?for ($m=1;$m<=31;$m++) {?><option <?if($m==$dd){echo " selected";}?>><?=sprintf("%02d",$m)?></option><?}?>
</select>
<select name="<?=$name?>-m">
<?for ($m=1;$m<=12;$m++) {?><option <?if($m==$dm){echo " selected";}?> value="<?=sprintf("%02d",$m)?>"><?=$lang["months"][$m-1]?></option><?}?>
</select>
<input type=text size=5 name="<?=$name?>-y" value="<?=$dy?>">
<div class="clearerleft"> </div>
</div>

<div class="Question">
<label><?=$lang["todate"]?><br/><?=$lang["inclusive"]?></label>
<?
$name="to";
$dy=getval($name . "-y",date("Y"));
$dm=getval($name . "-m",date("m"));
$dd=getval($name . "-d",date("d"));
?>
<select name="<?=$name?>-d">
<?for ($m=1;$m<=31;$m++) {?><option <?if($m==$dd){echo " selected";}?>><?=sprintf("%02d",$m)?></option><?}?>
</select>
<select name="<?=$name?>-m">
<?for ($m=1;$m<=12;$m++) {?><option <?if($m==$dm){echo " selected";}?> value="<?=sprintf("%02d",$m)?>"><?=$lang["months"][$m-1]?></option><?}?>
</select>
<input type=text size=5 name="<?=$name?>-y" value="<?=$dy?>">
<div class="clearerleft"> </div>
</div>

<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?=$lang["viewreport"]?>&nbsp;&nbsp;" />
</div>
</form>

</div>

<?
include "include/footer.php";
?>