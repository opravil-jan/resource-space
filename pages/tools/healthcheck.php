<?php
#
# Healthcheck.php
#
#
# Performs some basic system checks. Useful for remote monitoring of ResourceSpace installations.
#

include "../../include/db.php";
include "../../include/general.php";

# Check database connectivity.
$check=sql_value("select count(*) value from resource_type",0);
if ($check<=0) exit("FAIL - SQL query produced unexpected result");

# Check write access to filestore
if (!is_writable($storagedir)) {exit("FAIL - $storagedir is not writeable.");}
$hash=md5(time());
$file=$storagedir . "/write_text.txt";
file_put_contents($file,$hash);$check=file_get_contents($file);unlink($file);
if ($check!==$hash) {exit("FAIL - test write to disk returned a different string ('$hash' vs '$check')");}


# Check free disk space is sufficient.
$avail=disk_total_space($storagedir);
$free=disk_free_space($storagedir);
if (($avail/$free)<0.1) {exit("FAIL - less than 10% disk space free.");} 
        

exit("OK");
