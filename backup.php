<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: backup.php,v 1.7 2005/06/23 08:18:54 nao-pon Exp $
/////////////////////////////////////////////////

// バックアップデータを作成する
function make_backup($filename,$body,$oldtime)
{
	global $splitter,$cycle,$maxage;
	$aryages = array();
	$arystrout = array();

	if(function_exists(gzfile))
		$filename = str_replace(".txt",".gz",$filename);

	$realfilename = BACKUP_DIR.$filename;

	if(time() - @filemtime($realfilename) > (60 * 60 * $cycle))
	{
		$aryages = read_backup($filename);
		if(count($aryages) >= $maxage)
		{
			array_shift($aryages);
		}
		
		foreach($aryages as $lines)
		{
			foreach($lines as $key => $line)
			{
				if($key && $key == "timestamp")
				{
					$arystrout[] = "$splitter " . rtrim($line);
				}
				else
				{
					$arystrout[] = rtrim($line);
				}
			}
		}

		$strout = join("\n",$arystrout);
		if(!preg_match("/\n$/",$strout) && trim($strout)) $strout .= "\n";

		$body = "$splitter " . $oldtime . "\n" . $body;
		if(!preg_match("/\n$/",$body)) $body .= "\n";

		$fp = backup_fopen($realfilename,"wb");
		if($fp===FALSE) die_message("cannot write file ".htmlspecialchars($realfilename)."<br>maybe permission is not writable or filename is too long");
		backup_fputs($fp,$strout);
		backup_fputs($fp,$body);
		backup_fclose($fp);
	}
	
	return true;
}

// 特定の世代のバックアップデータを取得
function get_backup($age,$filename)
{
	$aryages = read_backup($filename);
	$retvars = array();
	
	foreach($aryages as $key => $lines)
	{
		if($key != $age) continue;
		foreach($lines as $key => $line)
		{
			if($key && $key == "timestamp") continue;
			$retvars[] = $line;
		}
	}

	return $retvars;
}

// バックアップ情報を返す
function get_backup_info($filename)
{
	global $splitter;
	$lines = array();
	$retvars = array();
	$lines = backup_file(BACKUP_DIR.$filename);

	if(!is_array($lines)) return array();

	$age = 0;
	foreach($lines as $line)
	{
		preg_match("/^$splitter\s(\d+)$/",trim($line),$match);
		if($match[1])
		{
			$age++;
			$retvars[$age] = $match[1];
		}
	}
	
	return $retvars;
}

// バックアップデータ全体を取得
function read_backup($filename)
{
	global $splitter;
	$lines = array();
	$lines = backup_file(BACKUP_DIR.$filename);

	if(!is_array($lines)) return array();

	$age = 0;
	foreach($lines as $line)
	{
		preg_match("/^$splitter\s(\d+)$/",trim($line),$match);
		if($match[1])
		{
			$age++;
			$retvars[$age]["timestamp"] = $match[1] . "\n";
		}
		else
		{
			// gzread に変更したので \n をつける by nao-pon 2003/10/22
			$retvars[$age][] = $line."\n";
			//$retvars[$age][] = $line;
		}
	}

	return $retvars;
}

// バックアップ一覧の取得
function get_backup_list($_page="")
{
	global $script,$date_format,$time_format,$weeklabels,$cantedit;
	global $_msg_backuplist,$_msg_diff,$_msg_nowdiff,$_msg_source,$_title_backup_delete;
	global $X_admin,$X_uid,$vars;

	$ins_date = date($date_format,$val);
	$ins_time = date($time_format,$val);
	$ins_week = "(".$weeklabels[date("w",$val)].")";
	$ins = "$ins_date $ins_week $ins_time";

	$lword = (array_key_exists('lw',$vars))? $vars['lw'] : "";
	if ($lword == " ") $lword = "";

	
	if (!$_page)
	{
		global $cantedit;
		
		/*
		if (!$X_uid)
		{
			$f_cache = CACHE_DIR.md5($lword).".bklist";
			if (file_exists($f_cache)) return join('',file($f_cache));
		}
		*/
		
		$pages = array_intersect(get_existpages(BACKUP_DIR, function_exists(gzopen)? ".gz" : ".txt"),get_existpages_db());
		$pages = array_diff($pages, $cantedit);
		
		if (count($pages) == 0)
			return '';
		
		$retvars = page_list($pages,'backup',$withfilename,"",$lword);
		
		/*
		if (!$X_uid && $fp = @fopen($f_cache,"wb"))
		{
			fputs($fp,$retvars);
			fclose($fp);
		}
		*/
		
		return $retvars;
	}
	else
	{
		$page_url = rawurlencode($_page);
		$s_page = htmlspecialchars(strip_bracket($_page));
		$line["link"] = "";
		$line["name"] = $_page;
		$retvars[] = "<ul>";
		$retvars[] .= "<li><a href=\"$script?cmd=backup\">$_msg_backuplist</a>\n";
	}
	
	$_script = preg_replace("/^https?:\/\/".$_SERVER["HTTP_HOST"]."/i","",$script);
	
	$arybackups = get_backup_info(encode($line["name"]).".txt");
	$page_url = rawurlencode($line["name"]);
	if(count($arybackups))
	{
		$line["link"] .= "<ul>\n";
		if ($X_uid && ($X_admin || $X_uid == get_pg_auther($get["page"])))
		{
			$line["link"] .= "<li><a href=\"$_script?cmd=backup&amp;page=$page_url&amp;action=delete\">".str_replace('$1',$s_page,$_title_backup_delete)."</li>\n";
		}
	}
	else
		$line["link"] .= "</li>\n";
		
	foreach($arybackups as $key => $val)
	{
		$ins_date = date($date_format,$val);
		$ins_time = date($time_format,$val);
		$ins_week = "(".$weeklabels[date("w",$val)].")";
		$backupdate = "($ins_date $ins_week $ins_time)";
		if(!$_page)
		{
				$line["link"] .= "<li><a href=\"$_script?cmd=backup&amp;page=$page_url&amp;age=$key\">$key $backupdate</a></li>\n";
		}
		else
		{
				$line["link"] .= "<li><a href=\"$_script?cmd=backup&amp;page=$page_url&amp;age=$key\">$key $backupdate</a> [ <a href=\"$script?cmd=backup_diff&amp;page=$page_url&amp;age=$key\">$_msg_diff</a> | <a href=\"$script?cmd=backup_nowdiff&amp;page=$page_url&amp;age=$key\">$_msg_nowdiff</a> | <a href=\"$script?cmd=backup_source&amp;page=$page_url&amp;age=$key\">$_msg_source</a> ]</li>\n";
		}
	}
	if(count($arybackups)) $line["link"] .= "</ul></li>";
	$retvars[] = $line["link"];
	$retvars[] = "</ul>";
	
	$retvars = join("\n",$retvars);
	
	return $retvars;
}

// zlib関数が使用できれば、圧縮して使用するためのファイルシステム関数
function backup_fopen($filename,$mode)
{
	if(function_exists(gzopen))
		return gzopen(str_replace(".txt",".gz",$filename),$mode);
	else
		return fopen($filename,$mode);
}

function backup_fputs($zp,$str)
{
	if(function_exists(gzputs))
		return gzputs($zp,$str);
	else
		return fputs($zp,$str);
}

function backup_fclose($zp)
{
	if(function_exists(gzclose))
		return gzclose($zp);
	else
		return fclose($zp);
}

function backup_file($filename)
{
	if(function_exists(gzfile))
	{
		// PHP 4.2.3 dev + Win2k + Apache 1.3.27 環境で
		// zlib のバグかなんか知らんけど、gzfile で正常に読み出せないことがあるので
		// gzread に変更 by nao-pon 2003/10/22
		$zd = backup_fopen($filename,"rb");
		$data = explode("\n",gzread ($zd, 1000000));
		backup_fclose($zd);
		return $data;
		//return @gzfile(str_replace(".txt",".gz",$filename));
	}
	else
		return @file($filename);
}

function backup_delete($filename)
{
	if(function_exists(gzopen))
		return @unlink(str_replace(".txt",".gz",$filename));
	else
		return @unlink($filename);
}
?>