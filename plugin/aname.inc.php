<?php
// $Id: aname.inc.php,v 1.2 2003/06/28 11:33:03 nao-pon Exp $

function plugin_aname_convert()
{
  if (!func_num_args()) return "Aname no argument!!\n";
  $aryargs = func_get_args();
  if (eregi("^[A-Z][A-Z0-9\-_]*$", $aryargs[0]))
    return "<a name=\"$aryargs[0]\" id=\"$aryargs[0]\"></a>";
  else
    return "Bad Aname!! -- ".$aryargs[0]."\n";
}
?>
