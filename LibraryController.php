<?php
ini_set('max_execution_time', 300);

class LibraryController {
	
	function __construct() {}

	private function parseCSV($text)
	{
		$lines = explode(PHP_EOL, $text);
		$keys = str_getcsv($lines[0]);
		
		$out = array();
		foreach (array_slice($lines, 1) as $line)
		{
			$values = str_getcsv($line);
			if (count($keys) == count($values)) {
				$out[] = array_combine($keys, $values);
			}
		}
		
		return $out;
	}
	
	
	// $authors should be a string of author names
	private function parseAuthors($authors)
	{	
		$result = array();
		$authors = (string)$authors;
		$authors = html_entity_decode(htmlentities($authors));
		$authors = trim(preg_replace('/\s+/', ' ', $authors)); // removes all unneccessary spaces and newlines
		$authors = str_replace(" and ", "; ", $authors); // Done to help parse ACM authors
		$builder = "";
		$readAuthor = false;
		
		for ($i = 0; $i < strlen($authors); $i++)
		{
			if (substr($authors, $i, 2) == "; " || $i == strlen($authors) - 1)
			{
				if ((substr($authors, $i, 1) != ";" && substr($authors, $i, 1) != " ") || $i == strlen($authors) - 1)
				{
					$builder .= substr($authors, $i, 1);
				}
				$result[] = $builder;
				$builder = "";
				$readAuthor = false;
			}
			else if ($readAuthor == true)
			{
				$builder .= substr($authors, $i, 1);
			}
			else if ($readAuthor == false && substr($authors, $i, 1) != " ")
			{
				$builder .= substr($authors, $i, 1);
				$readAuthor = true;
			}
		}
		return $result;
	}
	
	public function getACMPapersWithAuthor($name, $limit)
	{	
		$papers = array();

		$acmURL = 'http://dl.acm.org/exportformats_search.cfm?query=persons%2Eauthors%2EpersonName%3A%28%252B' . urlencode($name) . '%29&srt=%5Fscore&expformat=csv';
		$acmCSV = file_get_contents($acmURL);

		$lines = $this->parseCSV($acmCSV);
		
		foreach ($lines as $line) {
			$paper = array();
			
			$paper["source"] = "acm";
			$paper["id"] = $line["id"];
			$paper["title"] = $line["title"];
			$paper["authors"] = $this->parseAuthors($line["author"]);
			$paper["publication"] = $line["booktitle"];
			// Derive the full text URL name from the ID
			$paper["pdfURL"] = "http://dl.acm.org/ft_gateway.cfm?id=" . $line["id"];
			// Query the paper abstract
			$paper["abstract"] = "";
			
			$line["keywords"] = str_replace(",", "",$line["keywords"]); //remove commas
			$line["keywords"] = strtolower($line["keywords"]); //convert to lower case
			$paper["keywords"] = $line["keywords"];
			
			$papers[] = $paper;
			
			if (count($papers) == $limit)
			{
				break;
			}
		}
		
		return $papers;
	}
	
	public function getACMPapersWithWord($word, $limit)
	{
		$papers = array();

		$acmURL = 'http://dl.acm.org/exportformats_search.cfm?query=' .rawurlencode($word). '&filtered=&within=owners%2Eowner%3DHOSTED&dte=&bfr=&srt=%5Fscore&expformat=csv';
		$acmCSV = file_get_contents($acmURL);

		$lines = $this->parseCSV($acmCSV);
		
		foreach ($lines as $line) {
			$paper = array();
			
			$paper["source"] = "acm";
			$paper["id"] = $line["id"];
			$paper["title"] = $line["title"];
			$paper["authors"] = $this->parseAuthors($line["author"]);
			// Query the paper publication name
			$paper["publication"] = $line["booktitle"];
			// Derive the full text URL name from the ID
			$paper["pdfURL"] = "http://dl.acm.org/ft_gateway.cfm?id=" . $line["id"];
			$paper["abstract"] = "";

			$line["keywords"] = str_replace(",", "",$line["keywords"]); //remove commas
			$line["keywords"] = strtolower($line["keywords"]); //convert to lower case
			$paper["keywords"] = $line["keywords"];
			
			$papers[] = $paper;
			
			if (count($papers) == $limit)
			{
				break;
			}
		}
		
		return $papers;
	}

	// $conference, $limit
	public function getACMPapersWithConference($id){
		// Can't find a way at the moment :(
	}

	private function getACMAbstract($id) {
		$abstractURL = 'http://dl.acm.org/tab_abstract.cfm?id=' . $id;
		$abstractHTML = file_get_contents($abstractURL);
		preg_match("/<p.*?>\n?(.*)<\/p>/si", $abstractHTML, $matches);
		return $matches[1];
	}

	private function getACMBibtex($id) {
		$bibtexURL = 'http://dl.acm.org/exportformats.cfm?expformat=bibtex&id=' . $id;
		$bibtexHTML = file_get_contents($bibtexURL);
		preg_match("/<PRE.*?>\n?(.*)<\/pre>/si", $bibtexHTML, $matches);

		$bibtex = $matches[1];
		for ($i = 0; $i < strlen($bibtex); $i++)
		{
			if (substr($bibtex, $i, 2) == "},")
			{
				$bibtex = substr($bibtex, 0, $i + 2) . "<br> " . substr($bibtex, $i + 2);
			}
		}
		return $bibtex;
	}

	private function getACMKeywords($acmIDs){
		$num = count($acmIDs);
		$keywords = "";

		for($x = 0; $x < $num; $x++){
			$id = $acmIDs[$x];
			$acmURL = 'http://dl.acm.org/exportformats_search.cfm?query=' .rawurlencode($id). '&filtered=&within=owners%2Eowner%3DHOSTED&dte=&bfr=&srt=%5Fscore&expformat=csv';
			$acmCSV = file_get_contents($acmURL);
			$lines = $this->parseCSV($acmCSV);
			$line = $lines[0];
			$line["keywords"] = str_replace(",", "",$line["keywords"]); //remove commas
			$line["keywords"] = strtolower($line["keywords"]); //convert to lower case
			$keywords = $keywords." ".$line["keywords"]; 
		}

		return $keywords;
	}
	
	public function getIEEEPapersWithAuthor($name, $limit)
	{	
		$papers = array();
		$urlBase = 'http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?';
        $author_name = "au=" . $name;
        $limit_number = "&hc=".$limit;
        $query = $urlBase . $author_name . $limit_number;
        $response = file_get_contents($query);
        $documents = simplexml_load_string($response);
        foreach($documents as $document){
        	$paper = array();
        	$paper["source"] = "ieee";
        	$paper["title"] = $document->title[0]; 
        	$paper["id"] = $document->arnumber;
        	$paper["authors"] = $document->authors; //need to parse 
        	$paper["abstract"] = $document->abstract; 
        	$paper["keywords"]  = "";
        	$count = count($document->thesaurusterms->term);
        	
        	for($a = 0; $a < $count; $a++){
        		$word = $document->thesaurusterms->term[$a];
        		$paper["keywords"]  =  $paper["keywords"]. " ". $word;
        	}

        	$paper["pdfURL"] = $document->pdf;
        	$paper["publication"] = $document->pubtitle;
        	$papers[] = $paper;

        }
        // Added the if statement to deal with 2 extraneous "papers" (xml parsing problem?)
		if (count($papers) > 2)
        {
        	$papers = array_slice($papers, 2, count($papers));
        }
		return $papers;
	}
	
	public function getIEEEPapersWithWord($word, $limit)
	{	
		$papers = array();
		$ieeeURL = 'http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?thsrsterms=' .rawurlencode($word). '&hc=' .rawurlencode($limit);
        $response = file_get_contents($ieeeURL);
        $documents = simplexml_load_string($response);
        foreach($documents as $document){
        	$paper = array();
        	$paper["source"] = "ieee";
        	$paper["title"] = $document->title[0]; 
        	$paper["id"] = $document->arnumber;
        	$abc = $document->authors;
			$paper["authors"] = $this->parseAuthors($abc);//need to parse 
        	$paper["abstract"] = $document->abstract; 
        	$paper["keywords"]  = "";
        	$count = count($document->thesaurusterms->term);
        	
        	for($a = 0; $a < $count; $a++){
        		$word = $document->thesaurusterms->term[$a];
        		$paper["keywords"]  =  $paper["keywords"]. " ". $word;
        	}
        	
        	$paper["pdfURL"] = $document->pdf;
        	$paper["publication"] = $document->pubtitle;
        	$papers[] = $paper;

        }
        // Added the if statement to deal with 2 extraneous "papers" (xml parsing problem?)
        if (count($papers) > 2)
        {
        	$papers = array_slice($papers, 2, count($papers));
        }
		return $papers;
	}

	public function getIEEEPapersWithConference($conference, $limit){
		$papers = array();
		$ieeeURL = 'http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?jn=' .rawurlencode($conference). '&hc=' .rawurlencode($limit);
        $response = file_get_contents($ieeeURL);
        $documents = simplexml_load_string($response);
        foreach($documents as $document){
        	$paper = array();
        	$paper["source"] = "ieee";
        	$paper["title"] = $document->title[0];
        	
        	$abc = $document->authors;
			$paper["authors"] = $this->parseAuthors($abc);//need to parse 
        	$paper["abstract"] = $document->abstract; 
        	$paper["keywords"]  = "";
        	$count = count($document->thesaurusterms->term);
        	
        	for($a = 0; $a < $count; $a++){
        		$word = $document->thesaurusterms->term[$a];
        		$paper["keywords"]  =  $paper["keywords"]. " ". $word;
        	}
        	
        	$paper["pdfURL"] = $document->pdf;
        	$paper["publication"] = $document->pubtitle;
        	$papers[] = $paper;

        }
        // Added the if statement to deal with 2 extraneous "papers" (xml parsing problem?)
        if (count($papers) > 2)
        {
        	$papers = array_slice($papers, 2, count($papers));
        }
		return $papers;
	}

	private function getIEEEAbstract($id)
	{

		$ieeeURL = 'http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?an=' .rawurlencode($id);
        $response = file_get_contents($ieeeURL);
        $documents = simplexml_load_string($response);
        $abstract_is = "";
        foreach($documents as $document){
        	$abstract_is = $document->abstract;
        }
        
		return $abstract_is;
	}

	/* // Doesn't work atm
	private function getIEEEBibtex($id)
	{
		$ieeeURL = 'http://www.doi2bib.org/doi2bib?id=' . rawurlencode($id);
		$bibtex = @file_get_contents($ieeeURL);
		return $bibtex;
	}
	*/

	private function getIEEEKeywords($ieeeIDs){
		$num = count($ieeeIDs);
		$keywords = "";

		for($x = 0; $x < $num; $x++){
			$id = $ieeeIDs[$x];
			$ieeeURL = 'http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?an=' .rawurlencode($id);
        	$response = file_get_contents($ieeeURL);
        	$documents = simplexml_load_string($response);
        	foreach($documents as $document){
	        	//$paper["keywords"]  = "";
	        	$count = count($document->thesaurusterms->term);
	        	
	        	for($a = 0; $a < $count; $a++){
	        		$word = $document->thesaurusterms->term[$a];
	        		$keywords  =  $keywords. " ". $word;
	        	}
	        }
		}

		return $keywords;
	}

	public function combineKeywords($author, $limit)
	{
		$acmPapers = $this->getACMPapersWithAuthor($author, $limit);
		$ieeePapers = $this->getIEEEPapersWithAuthor($author, $limit);
		$keywords = "";
		if(count($acmPapers)==0 && count($ieeePapers)==0){

		}else{
			foreach($acmPapers as $paper){
				$keystring = $paper["keywords"];
				$keywords = $keywords." ".$keystring;
			}
			foreach($ieeePapers as $paper){
				$keystring = $paper["keywords"];
				$keywords = $keywords." ".$keystring;
			}
			//$keywords = $acmPapers["keywords"] . " " . $ieeePapers["keywords"];
			$keywords = trim(preg_replace('/\s+/', ' ', $keywords)); // removes all unneccessary spaces and newlines
		}
		
		return $keywords;
	}
	
	public function combinePapers($word, $limit)
	{
		$acmPapers = $this->getACMPapersWithWord($word, $limit);
		$ieeePapers = $this->getIEEEPapersWithWord($word, $limit);
		$papers = array_merge($acmPapers, $ieeePapers);
		$numPapers = count($papers);
		//echo "Initial amount of papers: $numPapers \n";
		if (count($papers > $limit))
		{
			shuffle($papers); // Randomize order of papers
			$papers = array_slice($papers, 0, $limit); // Get only the first $limit papers
		}
		
		return $papers;
	}

	public function getPapersForConference($conference, $source, $limit)
	{
		$papers = array();
		if($source=='acm'){
			//$papers = $this->getACMPapersWithConference($conference, $limit);
		}else{
			$papers = $this->getIEEEPapersWithConference($conference, $limit);
		}

		return $papers;
	}

	public function getAbstractForPaper($title, $source, $id)
	{
		$abstract = "";
		if($source=='acm'){
			$abstract = $this->getACMAbstract($id);
		}else{
			$abstract = $this->getIEEEAbstract($id);
		}
	
		return $abstract;
	}

	public function getBibtexForPaper($source, $id)
	{
		$bibtex = "";
		if ($source == "acm")
		{
			$bibtex = $this->getACMBibtex($id);
		}
		return $bibtex;
	}

	public function combineKeywordsForMultiplePapers($papers){
		$keywords = "";
		$num = count($papers);
		//echo "number is : ".$num."\n";
		$acmIDs = array();
		$ieeeIDs = array();
		for($x = 0; $x < $num; $x++){
			$paper = $papers[$x];
			$pieces = explode("-", $paper);
			if($pieces[0]=='acm'){
				array_push($acmIDs, $pieces[1]);
			}else{
				array_push($ieeeIDs, $pieces[1]);
			}
		}

		$acmKeywords = $this->getACMKeywords($acmIDs);
		//echo "acm keywords are : ".$acmKeywords."\n";
		$ieeeKeywords = $this->getIEEEKeywords($ieeeIDs);
		//echo "ieee keywords are : ".$ieeeKeywords."\n";
		$keywords = $acmKeywords." ".$ieeeKeywords;
		$keywords = trim(preg_replace('/\s+/', ' ', $keywords));
		return $keywords;
	}
}


//http://dl.acm.org/exportformats_search.cfm?query=%281352234%29&filtered=&within=owners%2Eowner%3DHOSTED&dte=&bfr=&srt=%5Fscore&expformat=csv
//http://dl.acm.org/exportformats_search.cfm?query=%281140185%29&filtered=&within=owners%2Eowner%3DHOSTED&dte=&bfr=&srt=%5Fscore&expformat=csv
?>

