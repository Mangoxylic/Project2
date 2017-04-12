<?php
include 'WordCloud.php';


$author = $_GET['author'];
$word = $_GET['word'];
$limit = $_GET['limit'];
$provider = new LibraryController;
$paper_list = $provider->combinePapers($word, $limit);
?>

<html>
<head>
<link rel="stylesheet" href="./css/papers-page.css">
</head>
<header>
	<div id="header"><?php echo strtoupper($word)?></div>
</header>
<body>
	<?php 
		echo "<center><table border=0 style=\"width: 100%; height: 100%;\">
        <tr>
        <th align=\"center\">&nbsp;</td>
        <th class = \"td1\" align=\"center\" height=50px>Paper</td>
        <th class = \"td1\" align=\"center\">Author List</td>
        <th class = \"td1\" align=\"center\">Conference Name</td>
        </tr>";
		for($x = 0; $x < count($paper_list); $x++){
			$title_is = $paper_list[$x]['title'];
			$source_is = $paper_list[$x]['source'];
			echo "<tr>"
			."<td><div class=\"checkbox-inline\"><input type=\"checkbox\" value=\"\"></div></td>"
			."<td class = \"td1\" align=\"center\">";
			echo $source_is."  :  ";
			echo "<a href=\"getAbstractForPaper.php?title={$title_is}&word={$word}&source={$source_is}\">$title_is</a> ";
			echo "</td>"
			."<td class = \"td1\" align=\"center\">";
			$author_array = $paper_list[$x]["authors"];
			for($y = 0; $y < count($author_array); $y++){
				$author_is = $author_array[$y];
				echo "<a href=\"getWordCloudForAuthor.php?author={$author_is}&limit={$limit}\">$author_is</a> \n "	;
			}
			$conf_is = $paper_list[$x]['publication'];
			
			echo "</td>"
			."<td class = \"td1\" align=\"center\">";
			echo "<a href=\"getPapersForConference.php?conference={$conf_is}&limit={$limit}&source={$source_is}\">$conf_is</a> \n "	;
			echo"</td></tr>";
		}

		echo "</table>"
		."</center>";
	?>
<a href="index.html"><button id="back">Back</button></a>
</body>
</html>
	