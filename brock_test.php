<?php
	// プログラムファイル読み込み
	require("func.php");
	require("file.php");
	require("html.php");
	require("init.php");

	$get["page"] = "RecentChanges";
	$postdata = join("",get_source($get["page"]));
	$post_lines = split("[\n]+",trim($postdata));
	$show_num = 10;

	$title = htmlspecialchars(strip_bracket($get["page"]));
	$page = make_search($get["page"]);
	$body = $postdata;

	for ($i = 0; $i <= $show_num; $i++){
		$show_line = split(" ",$post_lines[$i]);
		echo make_search($show_line[4])." ".substr($show_line[0],1)."<br />";
	}

?>