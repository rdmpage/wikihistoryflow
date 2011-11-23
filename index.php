<?php

require_once (dirname(__FILE__) . '/Text/Diff.php');
require_once (dirname(__FILE__) . '/Text/Diff/Renderer.php');
require_once (dirname(__FILE__) . '/history.php');
require_once (dirname(__FILE__) . '/utils.php');

//--------------------------------------------------------------------------------------------------
function pairwise_diff($lines1, $lines2, $author1, &$author2, $author2_id, &$match)
{
	$match = array();

	// perform diff, print output
	$diff = new Text_Diff($lines1, $lines2);
	
	$line1_count = 0;
	$line2_count = 0;
	
	foreach ($diff->_edits as $edit)
	{		
		$edit_type = 1;
		if (is_a($edit, 'Text_Diff_Op_copy'))
		{
			$edit_type = 0;
		}
		
		if ($edit_type == 0)
		{
			// No change, so copy author id across to $author2, and match lines
			foreach ($edit->final as $final)
			{			
				$author2[$line2_count] = $author1[$line1_count];
				
				$match[$line1_count] = $line2_count;
				
				$line1_count++;
				$line2_count++;				
			}
		}
		else
		{
			// Change
			if (is_array($edit->final))
			{
				foreach ($edit->final as $final)
				{
					$author2[$line2_count++] = $author2_id;
				}
			}
			if (is_array($edit->orig))
			{
				$line1_count += count($edit->orig);
			}
		}
	}
		
	// Look for lines that haven't been matched. These may be lines
	// that have been moved elsewhere in the text.
	
	// Get sets of line numbers that have been matched
	$k1 = array_keys($match);
	$k2 = array_values($match);
	
	// Get set of original line numbers
	$l1 = array();
	for ($i = 0; $i < count($lines1); $i++)
	{
		$l1[$i] = $i;
	}
	$l2 = array();
	for ($i = 0; $i < count($lines2); $i++)
	{
		$l2[$i] = $i;
	}
	
	// Find unmatched line numbers
	$x1 = array_diff($l1, $k1);
	$x2 = array_diff($l2, $k2);

	// Get text of unmatched lines
	$extra1 = array();
	$extra2 = array();
	
	foreach ($x1 as $x)
	{
		$extra1[$x] = $lines1[$x];
	}
	foreach ($x2 as $x)
	{
		$extra2[$x] = $lines2[$x];
	}
	
	// Go through unmatched lines in first body of text, and look for same text 
	// in unmatched lines in second body of text. Link identical text together.
	foreach ($extra1 as $k => $v)
	{
		$pos = array_search($v, $extra2);
		if ($pos === false)
		{
		}
		else
		{
			$match[$k] = $pos;
			
			// assign authorship to previous author
			$author2[$pos] = $author1[$k];
			
			// We've matched this line so delete from unmatched
			unset($extra2[$pos]);
		}
	}
}

//--------------------------------------------------------------------------------------------------
function display_svg($page_title)
{
	$user_agent = 'rdmpage@gmail.com';
	
	// Get XML history of Wikipedia page
	
	$page_title = str_replace(' ', '_', $page_title);
	$xml = get('http://en.wikipedia.org/wiki/Special:Export/' . $page_title . '?history', $user_agent);
	
	if ($xml == '')
	{
		//die('Didn\'t get XML');
		return false;
	}
	
	//echo $xml;
	
	// Extra metadata about each revision of this page
	$history = xml_edits($xml);
	
	//print_r($history);
	
	if (count($history->edits) == 0)
	{
		return false;
	}
	
	// Texts to compare
	$texts = array();
	
	foreach ( $history->edits as $e )
	{
		array_push($texts, explode("\n", $e->text));
	}
	
	// First revision
	$start = new stdclass;
	$start->authors = array();
	$start->match =  array();
	$start->id = $history->edits[0]->id;
	for ($i = 0; $i < count($texts[0]); $i++)
	{
		$start->authors[$i] = $history->edits[0]->userid;
	}	
	
	// Get differences between subsequent pairs of revisions
	$revisions = array();
	$revisions[0] = $start;
	
	$n = count($history->edits);
	
	for ($i = 1; $i < $n; $i++)
	{
		$revision = new stdclass;
		$revision->authors = array();
		$revision->match = array();	
		$revision->id = $history->edits[$i]->id;	
		$revisions[$i] = $revision;
		
		pairwise_diff($texts[$i-1], $texts[$i], 
			$revisions[$i-1]->authors, $revisions[$i]->authors, $history->edits[$i]->userid, $revisions[$i-1]->match);	
	}
	
	/*
	foreach ($revisions as $rev)
	{
		echo "Authors\n";
		print_r($rev->authors);
		echo "Match\n";
		print_r($rev->match);
	}	
	*/
	
	
	// Generate SVG
	$author_colour = array();
	
	
	$view_width = 1000;
	$view_height = 500;
	
	$x = 10;
	$y = 20;
	
	
	// How many revisions?
	$num_rev = count($revisions);
	$max_lines = 0;
	foreach ($texts as $text)
	{
		$max_lines = max($max_lines, count($text));
	}
	
	$x_gap = $view_width / $num_rev;
	$x_gap = min(30, $x_gap);
	$x_gap = max(1, $x_gap);
		
	$y_gap = $view_height / $max_lines;
	$y_gap = min(30, $y_gap);
	$y_gap = max(1, $y_gap);
	
	$stroke_width = $x_gap/2;
	$stroke_width = max(1, $stroke_width);
	$half_stroke = $stroke_width/2;
	
	
	$svg = '<?xml version="1.0" encoding="UTF-8"?>
	<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns="http://www.w3.org/2000/svg"
	width="' . $view_width . 'px" 
	height="' . $view_height . 'px" 
	>';
	
	
	foreach ($revisions as $rev)
	{	
	
		// Get authors in this revision
		$a = array();
		foreach ($rev->authors as $k => $v)
		{
			array_push($a, $v);
		}
		$a = array_unique($a);
		
		// New authors
		$n = array_diff($a, array_keys($author_colour));
		
		
		// Random colour from our range
		
		foreach ($n as $author_id)
		{
			$author_colour[$author_id] = 'rgb(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ')'; 
		}
		
		// Column representing this edit
		
		$svg .= '<a xlink:href="http://en.wikipedia.org/w/index.php?oldid=' . $rev->id . '" title="Revision ' . $rev->id . '" target="_new" >';
		foreach ($rev->authors as $k => $v)
		{
			$y_pos = $y + ($k * $y_gap);
			$svg .= '<path style="stroke:' . $author_colour[$v] . ';stroke-width:' . $stroke_width . ';stroke-linecap:butt;"  d="M ' 
				. $x . ' ' . $y_pos . ' ' . $x . ' ' . ($y_pos + $y_gap) . '" />';
		}
		$svg .= '</a>';
		
	
		// Draw polygon linking each segement of text in two adjacent revisions
		$start_x = $x;
		$end_x = $x + $x_gap;
		foreach ($rev->match as $k => $v)
		{
			$start_y = $y + ($k * $y_gap);
			$end_y = $y + ($v * $y_gap);
			
			$user_url = '';
			if (isset($history->user_name_list[$rev->authors[$k]]))
			{
				$user_url = 'http://en.wikipedia.org/wiki/User:' . $history->user_name_list[$rev->authors[$k]];
			}
			else
			{
				$user_url = 'http://en.wikipedia.org/wiki/Special:Contributions/' . $rev->authors[$k];
			}
			
			$svg .= '<a xlink:href="' . $user_url . '" title="Revision ' . $rev->id . '" target="_new" >';			
			$svg .= '<polygon style="fill:' . $author_colour[$rev->authors[$k]] . ';stroke-width:0;opacity:0.4;"  points="' 
				. ($start_x + $half_stroke) . ', ' . $start_y . '  ' . ($end_x - $half_stroke) . ', ' . $end_y 
				. '  ' . ($end_x - $half_stroke) . ', ' . ($end_y + $y_gap) . '  ' . ($start_x + $half_stroke) . ', ' . ($start_y + $y_gap) 
				. '  ' . ($start_x + $half_stroke) . ', ' . $start_y . '" />';
			$svg .= '</a>';
		}
		
		$x += $x_gap;
		
	}	
	
	
	$svg .= '</svg>';
	
	echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Wikipedia History Flow</title>
		
		<style type="text/css" title="text/css">
			body { font-family: sans-serif; }
		</style>
	</head>
	<body>
		<h1>Wikipedia History Flow</h1>';
		
	echo '<p>History flow for Wikipedia page <a href="http://en.wikipedia.org/wiki/' . $page_title . '" target="_new">' . $page_title . '</a> (<a href=".">try another</a>)</p>';
	
	echo '<div style="background-color:rgb(128,128,128);">';
	echo $svg;
	echo '</div>';
	
	echo '<p>Key:</p>
	<ul>
	<li>Columns represent revisions of the page (click on a colunn to see that revision)</li>
	<li>Rows of text that are the same between revisions are connected by lines coloured by author</li>
	<li>Authors are distinguished by colour, click on a line between a column to go to that user\'s Wikipedia page</li>
	</ul>';
	
	echo '
	<body>
</html>';

	return true;
}

//--------------------------------------------------------------------------------------------------
function display_error($page_title)
{
	echo '<!DOCTYPE html>
<html>
	<head>
		<title>Wikipedia History Flow</title>
		<style type="text/css" title="text/css">
			body { font-family: sans-serif; }
		</style>
	</head>
	<body>
		<h1>Wikipedia History Flow</h1>
		<p><b>Error</b> Couldn\'t get history for page "' . $page_title . '" (<a href=".">try another</a>)</p>
	</body>
</html>';

}

//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo '<!DOCTYPE html>
<html>
	<head>
		<title>Wikipedia History Flow</title>
		<style type="text/css" title="text/css">
			body { font-family: sans-serif; }
		</style>
	</head>
	<body>
		<h1>Wikipedia History Flow</h1>
		<p>Enter the name of a Wikipedia page (just the name, not the URL) to get a history flow in SVG.</p>
		<p><b>Note:</b> Pages with an extensive history of edits will cause this script to run very slowly.</p>
		<form action="." method="GET">
			<input type="text" name="page" value="">
			<input type="Submit" value="Go">
		</form>
		<p>For background see blog post <a href="http://iphylo.blogspot.com/2009/09/visualising-edit-history-of-wikipedia.html">Visualising edit history of a Wikipedia page</a>. Inspiration came from Jeff Attwood\'s post <a href="http://www.codinghorror.com/blog/archives/001222.html">Mixing Oil and Water: Authorship in a Wiki World</a>, which discusses the <a href="http://researchweb.watson.ibm.com/visual/projects/history_flow/explanation.htm">History Flow project</a>. Makes use of the <a href="http://pear.php.net/package/Text_Diff">Text_Diff</a> package.</p>
		<p>By <a href="http://iphylo.blogspot.com/">Rod Page</a></p>
	</body>
</html>';

}

//--------------------------------------------------------------------------------------------------
function main()
{	
	$page = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['page']))
	{
		$page = $_GET['page'];
		$result = display_svg($page);
		
		if (!$result)
		{
			display_error($page);
		}
	}	
	else
	{
		default_display();
		exit(0);		
	}

}

main();


?>