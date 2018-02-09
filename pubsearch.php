<?php
/* 
  Web-Interface for listing and searching IBT publications in the IBT literature database on the IBT homepage
  history:
  2008/02/12 - ???   - some initial version
  2008/08/.. - ???   - some newer version
  2009/07/30 - mwk   - added subheadings
  2009/09/22 - mwk   - fixed bugs with subheadings before zero-return results. Added some more info into headings for regular search. Fixed bug if no search criterion is specified.
  2009/12/01 - mwk   - changed META to KIT. Added exception for Seemann "selected" Pubs
  2010/04/12 - mwk   - added links to author and year search
  2010/07/07 - mwk   - BugFix: Quicksearch
  2011/02/22 - mwk   - DOI links, Authors separated by ',', Linebraks after authors, title
  2011/02/23 - mwk   - URL links, yearMin, yearMax, google scholar links
  2011/04/05 - mwk   - removed google scholar, changed language to english, added contact button, added abstract
  2011/07/20 - mwk   - "und" --> "and"
  2012/07/24 - mwk   - bugfix: books were not displayed in IBT literature search, output of number of entries per output section (books, journals, etc)
  2016/07/14 - ms    - Fehler korrigiert und bootstrap / jquery eingebaut
  2018/01/30 - ss029 - Switched from refbase to I-Librarian + several improvements (security, valid HTML5 output, formatting, code cleanup, added input form)
*/

//error_reporting(E_ALL);
define('DEBUG', false);
define('BASEURL', 'pubsearch.php');
define('PDFURL', 'getpdf.php?ref=');

function convertSpecialChars($str) {
    $search = array('ä','æ','ö','œ','ü','ß','à','á','â','å','è','é','ê','ì','í','î','ï','ò','ó','ô','ø','ù','ú','û');
    $replace = array('ae','ae','oe','oe','ue','ss','a','a','a','a','e','e','e','i','i','i','i','o','o','o','o','u','u','u');
    return str_replace($search, $replace, strtolower($str));
}
function initialsLetter(&$str) {
    $str = substr($str, 0, 1).'.';
}
function initialsDoubleNames(&$str) {
	$parts = explode('-', $str);
	array_walk($parts, 'initialsLetter');
	$str = implode('-', $parts);
}
function initials($str) {
	$str = preg_replace('/\s+/', ' ', $str);
	$parts = explode(' ', $str);
	array_walk($parts, 'initialsDoubleNames');
	return implode(' ', $parts);
}
function parseNames($str) {
    // Extracts the last and first name from the following format used by I, Librarian:
    // L:"lastname",F:"firstname(s)"
    $pos1 = strpos($str, 'L:"')+3;
    $pos2 = strpos($str, '"', $pos1);
    $lastname = trim(substr($str, $pos1, $pos2-$pos1));
    $pos1 = strpos($str, 'F:"')+3;
    $pos2 = strpos($str, '"', $pos1);
    $firstname = trim(substr($str, $pos1, $pos2-$pos1));
    $firstname = initials($firstname);
    return array($firstname, $lastname);
}

try {
    $dbHandle = new PDO('sqlite:../ilibrarian/library/database/library.sq3');
}
catch (PDOException $e) {
    print 'Error: '.$e->getMessage().'<br>';
    die();
}
$dbHandle->sqliteCreateFunction('convertSpecialChars', 'convertSpecialChars', 1);

// Filter GET variables
$getMode    = filter_input(INPUT_GET, 'mode',    FILTER_SANITIZE_STRING);
$getType    = filter_input(INPUT_GET, 'type',    FILTER_SANITIZE_STRING);
$getAuthor  = filter_input(INPUT_GET, 'author',  FILTER_SANITIZE_STRING);
$getYear    = filter_input(INPUT_GET, 'year',    FILTER_SANITIZE_NUMBER_INT);
$getYearMin = filter_input(INPUT_GET, 'yearMin', FILTER_SANITIZE_NUMBER_INT);
$getYearMax = filter_input(INPUT_GET, 'yearMax', FILTER_SANITIZE_NUMBER_INT);
$getQuickSearchName = filter_input(INPUT_GET, 'quickSearchName', FILTER_SANITIZE_STRING);

// Formatted output => bibtex types
$pubTypes = array(
    'Journal Articles' => array('article'),
    'Books' => array('book','booklet'),
    'Book Chapters' => array('inbook','incollection'),
    'Conference Contributions' => array('conference','inproceedings','proceedings'),
    'Dissertations' => array('phdthesis'),
    'Student Theses' => array('mastersthesis')
);
if ($getType == 'journal')
    $pubTypes = array_slice($pubTypes, 0, 3);
elseif ($getMode == 'selectedpubs')
    $pubTypes = array_slice($pubTypes, 0, 4);

$authors = '';

if ($getMode != 'selectedpubs') {
?>
<!DOCTYPE html>
<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="author" content="Institute of Biomedical Engineering, Karlsruhe Institute of Technology (KIT)">
<meta name="date" content="<?php echo date('Y-m-d'); ?>">
<meta name="Keywords" content="Institute of Biomedical Engineering, IBT, Karlsruhe Institute of Technology, KIT, publication list">
<meta name="Expires" content="700000">
<meta name="robots" content="index,follow">
<meta name="revisit-after" content="30 days">

<!-- [ms] 14.7.2016 -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<link type="text/css" rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" media="screen">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<link type="text/css" rel="stylesheet" href='https://fonts.googleapis.com/css?family=Roboto'>
<link type="text/css" rel="stylesheet" href="css/modern-business.css">
<!-- [ms] 14.7.2016 -->

<title>Institute of Biomedical Engineering (IBT) - Publications</title>

</head>
<body>

<!-- Navigation -->
<div class="container container1">
    <div class="container container1">
         <a href="http://www.ibt.kit.edu" ><img class="img-responsive img-portfolio img-hover" src="images/logo1_de.jpg" alt=""></a>
    </div>
    <nav class="navbar navbar-custom navbar-inverse">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <a class="navbar-brand" href="http://www.ibt.kit.edu">IBT Literature</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
        </div>
        <!-- /.container -->
    </nav>

<?php
// [ms] 14.7.2016, [ss029]
$restriction = '';
if (!empty($getAuthor)) $restriction = ' by <i>'.$getAuthor.'</i> ';
if (empty($getYear) && !empty($getYearMin) && !empty($getYearMax)) {
	if($getYearMin == $getYearMax) $getYear = $getYearMin;
    else {
		if($getYearMin > $getYearMax) {
			$tmp = $getYearMax;
			$getYearMax = $getYearMin;
			$getYearMin = $tmp;
		}
        $restriction .= ' between <i> '.$getYearMin.' and '.$getYearMax.'</i> ';
    }
}
elseif (!empty($getYearMin)) $restriction .= ' since <i>'.$getYearMin.'</i> ';
elseif (!empty($getYearMax)) $restriction .= ' until <i>'.$getYearMax.'</i> ';
if (!empty($getYear)) $restriction .= ' of the year <i>'.$getYear.'</i> ';
if (!empty($getQuickSearchName)) $restriction = ' matching the search <i>"'.$getQuickSearchName.'"</i>';
?>

<div class="container" style="width:96%">
<button class="btn btn-warning" onclick="$('#filter').toggle(); if($('#filter').is(':visible')) {$(this).text('Hide filter');} else {$(this).text('Show filter');}">Show filter</button>
<div id="filter" style="display: none;">
<form class="form-horizontal" action="<?php echo BASEURL; ?>" method="get">
<fieldset>
<div class="form-group"><label class="col-md-4 control-label" for="author">Author</label><div class="col-md-2"><input id="author" name="author" placeholder="Last name" class="form-control input-md" type="text"<?php if(!empty($getAuthor)) echo ' value="'.$getAuthor.'"'; ?>></div></div>
<div class="form-group"><label class="col-md-4 control-label" for="yearMin">From</label><div class="col-md-2"><input id="yearMin" name="yearMin" placeholder="Year (YYYY)" class="form-control input-md" type="text"<?php echo empty($getYearMin) ? (empty($getYear) ? '' : ' value="'.$getYear.'"') : ' value="'.$getYearMin.'"'; ?>></div></div>
<div class="form-group"><label class="col-md-4 control-label" for="yearMax">To</label><div class="col-md-2"><input id="yearMax" name="yearMax" placeholder="Year (YYYY)" class="form-control input-md" type="text"<?php echo empty($getYearMax) ? (empty($getYear) ? '' : ' value="'.$getYear.'"') : ' value="'.$getYearMax.'"'; ?>></div></div>
<div class="form-group"><label class="col-md-4 control-label" for="send2"></label><div class="col-md-4"><button id="send2" name="filter" class="btn btn-primary">filter</button></div></div>
</fieldset>
</form>
<hr>
<form class="form-horizontal" action="<?php echo BASEURL; ?>" method="get">
<fieldset>
<div class="form-group"><label class="col-md-4 control-label" for="quickSearchName">Search</label><div class="col-md-5"><input id="quickSearchName" name="quickSearchName" placeholder="Search terms (author, title, year, journal, BibTeX key)" class="form-control input-md" type="search" <?php if(!empty($getQuickSearchName)) echo ' value="'.$getQuickSearchName.'"'; ?>></div></div>
<div class="form-group"><label class="col-md-4 control-label" for="send1"></label><div class="col-md-4"><button id="send1" name="filter" class="btn btn-primary">search</button></div></div>
</fieldset>
</form>
</div>

<?php
echo '<h3>IBT Publications'.$restriction.'</h3><p><hr>';
} // if ($getMode != 'selectedpubs')

foreach ($pubTypes as $pubText => $bibtex_types) {

    $query = null;
    $result = null;

    // define ORDER BY and build WHERE clause
    $order_by = 'authors ASC';
    $where_array = array('custom1="IBT"', 'bibtex_type IN ("'.implode('","', $bibtex_types).'")');
    if (!empty($getQuickSearchName)) {
        $searchTerms = explode(' ', $getQuickSearchName);
        foreach($searchTerms as $searchTerm) {
            if(!empty($searchTerm))
                array_push($where_array, 'convertSpecialChars(authors || title || journal || secondary_title || year || publisher || bibtex) LIKE '.$dbHandle->quote('%'.convertSpecialChars($searchTerm).'%'));
        }
    }
    else {
        if (!empty($getAuthor))  array_push($where_array, 'convertSpecialChars(authors) LIKE '.$dbHandle->quote('%'.convertSpecialChars($getAuthor).'%'));
        if (!empty($getYear))    array_push($where_array, 'substr(year,1,4)='.$dbHandle->quote($getYear)); // year field in database contains YYYY-MM-DD
        if (!empty($getYearMin)) array_push($where_array, 'substr(year,1,4)>='.$dbHandle->quote($getYearMin));
        if (!empty($getYearMax)) array_push($where_array, 'substr(year,1,4)<='.$dbHandle->quote($getYearMax));
        
        if ($getMode == 'selectedpubs') {
            // this is not necessary anymore since we filter journal articles and first-author-conference articles now
            //array_push($where_array, 'custom3="yes"');
            $order_by = 'year DESC, authors';
        }
    }
    $where_clause = implode(' AND ', $where_array);

    $query = 'FROM library WHERE '.$where_clause.' ORDER BY '.$order_by.';';
    if (DEBUG) echo 'Query: ' . $query . '<br>';
    $result = $dbHandle->query('SELECT COUNT(*) '.$query);
    if (!$result) echo 'PDO error: '.$dbHandle->errorCode().'<br>';
    $numberOfRows = $result->fetchColumn();
    $result = null;
    if (DEBUG) echo 'Number of rows: '. $numberOfRows .'<br>';

    if ($numberOfRows>0) {

        $result = $dbHandle->query('SELECT * '.$query);

        echo '<h4>'.$pubText.' ('.$numberOfRows.')</h4>'."\n";

        $list_items_arr = array(); // will be filled with all the single items
        $list_items_arr_thegivenauthor = array(); // will be filled with all the single items
        $list_items_arr_other = array(); // will be filled with all the single items

        $entryNum = 0;
        while ($row = $result->fetch()) {

            $firstAuthor = false;
            /* START: author-formatting */
            $author_array = array();
            $authors = explode(';', $row['authors']);

            $n = 0;
            while(!empty($authors[$n])) {

                list($firstname, $lastname) = parseNames($authors[$n]);
                if ($n == 0) $firstauthor_lastname = $lastname;

                $author_link = '<a href="'.BASEURL.'?author='.urlencode($lastname).'">'.$firstname.' '.$lastname.'</a>';

                // if searched for a specfic author, it's name will be printed bold
                // if no author search is selected, the first author will be printed bold
                // all other authors are being printed in regular font
                $authorSearchedAndFound = !empty($getAuthor) && convertSpecialChars($getAuthor) == convertSpecialChars($lastname);
                $authorNotSearchedButIsFirstAuthor = empty($getAuthor) && $n == 0;
                if ($authorSearchedAndFound || $authorNotSearchedButIsFirstAuthor) {
                    array_push($author_array, '<b>'.$author_link.'</b>');
                    if ($n == 0) $firstAuthor = true;
                }
                else array_push($author_array, $author_link);
                
                $n++;
            }
            $author_display = array_pop($author_array);
            if ($n > 1) $author_display = implode(', ', $author_array).', and '.$author_display;
            $author_text = strip_tags($author_display);
            /* END: author-formatting */

            $considerEntry = true;
            // if selectedpubs is chosen, choose only journal and first-author-conference articles
            // if selected pubs && author search && journal --> select entry
            // elseif selected pubs && author search && conference && first author --> select entry
            // elseif selected pubs --> do not consider entry
            if ($getMode == 'selectedpubs') {
                $considerEntry = false;
                if (!empty($getAuthor)) {
                    if (in_array($pubText, array('Journal Articles','Book Chapters','Books')) ||
                        $pubText == 'Conference Contributions' && ($getAuthor != 'Seemann' || $getAuthor == 'Seemann' && $firstAuthor)) { // Extrawurst für Gunnar
                        $considerEntry = true;
                    }
                } // if author
                else
                   $considerEntry = true;
            }

            if ($considerEntry) {

					$title = preg_replace('/[\x00-\x1F\x7F\x80-\x9F]/u', '', $row['title']); // remove UTF-8 control characters
                    $year = substr($row['year'], 0, 4); // year field in database contains YYYY-MM-DD
                    $parts = explode('|', $row['url']);
                    $url = $parts[0]; // there can be multiple URLs in the url field - we are linking the first

                    $file_display = '';
                    $buttonItems = '';
                    //$file_display .= "<br>";

                    if (!empty($row['file'])) $buttonItems .= '&nbsp;&nbsp;<a class="btn btn-success" href="'.PDFURL.$row['bibtex'].'"><img style="border: 0;" alt="PDF" src="http://pubsearch.ibt.kit.edu/pdf_icon.gif"></a>';
                    // Contact Button
                    $buttonItems .= '&nbsp;&nbsp;<input class="btn" type="button" value="&nbsp;Request PDF&nbsp;" onclick="location.href=\'mailto:publications@ibt.kit.edu?subject=Manuscript%20Request:%20'.urlencode($title).'%20('.urlencode($row['bibtex']).')&amp;body=Dear%20Madam%20or%20Sir,%0A%0Aplease%20send%20me%20a%20PDF%20copy%20of%20the%20following%20manuscript:%0A%20%20Title:%20'.urlencode($title).'%0A%20%20Authors:%20'.urlencode($author_text).'%0A%20%20Appeared%20in:%20'.urlencode($row['secondary_title']).'%0A%20%20ID:%20'.urlencode($row['bibtex']).'%0A%0APersonal%20Information:%0A%20%20Name:%20...%0A%20%20Insitution:%20...%0A%20%20Email:%20...%0A%20%20Reason%20for%20request:%20...%0A%0AThank%20you%20for%20your%20assistance.%0A%0AKind%20regards,%20%20%0A%0A\'">';

                    if (!empty($row['doi'])) $buttonItems .= '&nbsp;&nbsp;<form style="display: inline-block;" action="http://dx.doi.org/'.$row['doi'].'" target="_blank"><input class="btn" type="submit" value="&nbsp;DOI&nbsp;"></form>';
                    if (!empty($url)) $buttonItems .= '&nbsp;&nbsp;<form style="display: inline-block;" action="'.$url.'" target="_blank"><input class="btn" type="submit" value="&nbsp;URL&nbsp;"></form>';
                    // Google Scholar link
                    //$file_display .= '&nbsp; <a href="http://scholar.google.com/scholar?q='.urlencode($title).'" target="_blank">[Google Scholar]</a>';

                    // Show abstract
                    if (!empty($row['abstract'])) {
                        $abstract_id = 'abstract-'.$row['bibtex'];
                        $abstract_text = htmlspecialchars(preg_replace('/[\x00-\x1F\x7F\x80-\x9F]/u', '', $row['abstract'])); // remove UTF-8 control characters
                        $abstract = '<div id="'.$abstract_id.'" style="display:none; width: 750px; margin-left: auto; text-align: left; font-style:italic;"><p><b>Abstract:</b></p><p style="column-width: 325px;">'.$abstract_text.'</p></div>';
                        $buttonItems .= '&nbsp;&nbsp;<input class="btn btn-primary" type="button" value="&nbsp;Abstract&nbsp;" onclick="if(document.getElementById(\''.$abstract_id.'\').style.display==\'none\'){document.getElementById(\''.$abstract_id.'\').style.display=\'block\'}else{document.getElementById(\''.$abstract_id.'\').style.display=\'none\'};">'.$abstract;
                    }
                
                    /* START: the different record-types */
                    $list_item = '<div class="row" style="padding: 15px 0px 10px 10px; border-bottom: 2px solid #ccc;">';
                    if (isset($pubTypes['Journal Articles']) && in_array($row['bibtex_type'], $pubTypes['Journal Articles']) ||
                        isset($pubTypes['Conference Contributions']) && in_array($row['bibtex_type'], $pubTypes['Conference Contributions'])) {
                        $list_item .= '<div class="col-md-12">'.$author_display.'.<br>'.$title.'.<br>';
                        $journalAvailable = false;
                        if (!empty($row['secondary_title'])) {
                            // use full journal name
                            $list_item .= 'In <i>'.$row['secondary_title'].'</i>';
                            $journalAvailable = true;
                        }
                        elseif (!empty($row['journal'])) {
                            // use journal abbreviation
                            $list_item .= 'In <i>'.$row['journal'].'</i>';
                            $journalAvailable = true;
                        }
                        if($journalAvailable) {
                            if (!empty($row['volume'])) $list_item .= ', vol. '.$row['volume'];
                            if (!empty($row['issue']))  $list_item .= '('.$row['issue'].') ';
                            if (!empty($row['pages']))  $list_item .= ', pp. '.$row['pages'];
                            $list_item .= ', ';
                        }
                        $list_item .= '<a href="'.BASEURL.'?year='.$year.'">'.$year.'</a></div><div style="text-align: right; margin-top: 0.5em;">'.$file_display.$buttonItems.'</div>';
                    }
                    elseif (isset($pubTypes['Books']) && in_array($row['bibtex_type'], $pubTypes['Books'])) {
                        $list_item .= '<div class="col-md-12 text-left">'.$author_display.'.<br>'.$title.'.<br>';
                        if (!empty($row['publisher']))       $list_item .= $row['publisher'].', ';
                        if (!empty($row['place_published'])) $list_item .= $row['place_published'].'. ';
                        $list_item .= '<a href="'.BASEURL.'?year='.$year.'">'.$year.'</a>.</div><div style="text-align: right; margin-top: 0.5em;">'.$file_display.$buttonItems.'</div>';
                    }
                    elseif (isset($pubTypes['Book Chapters']) && in_array($row['bibtex_type'], $pubTypes['Book Chapters'])) {
                        $list_item .= '<div class="col-md-12 text-left">'.$author_display.'.<br>'.$title.'.<br>In <i>'.$row['secondary_title'].'</i>';
                        if (!empty($row['editor'])) {
                            $editors = explode(';', $row['editor']);
                            $editor_array = array();
                            foreach ($editors as $editor)
                            {
                                list($firstname, $lastname) = parseNames($editor);
                                array_push($editor_array, $firstname.' '.$lastname);
                            }
                            $editor_display = implode(', ', $editor_array);
                            $list_item .= ', '.$editor_display.' (eds)';
                        }
                        if (!empty($row['publisher']))       $list_item .= ', '.$row['publisher'];
                        if (!empty($row['place_published'])) $list_item .= ', '.$row['place_published'];
                        $list_item .=', pp. '.$row['pages'].', <a href="'.BASEURL.'?year='.$year.'">'.$year.'</a></div><div style="text-align: right; margin-top: 0.5em;">'.$file_display.$buttonItems.'</div>';
                    }
                    elseif (isset($pubTypes['Dissertations']) && in_array($row['bibtex_type'], $pubTypes['Dissertations']) ||
                            isset($pubTypes['Student Theses']) && in_array($row['bibtex_type'], $pubTypes['Student Theses'])) {
                        $thesis_type = trim(strtolower($row['custom2']));
                        switch ($thesis_type) {
                            case 'bachelor':     $thesis_display = 'Bachelorarbeit'; break;
                            case 'master':       $thesis_display = 'Masterarbeit'; break;
                            case 'diploma':      $thesis_display = 'Diplomarbeit'; break;
                            case 'phd':          $thesis_display = 'Dissertation'; break;
                            case 'habilitation': $thesis_display = 'Habilitationsschrift'; break;
                            default:             $thesis_display = '';
                        }
                        $publisher_display = '';
                        if(!empty($row['publisher'])) $publisher_display = $row['publisher'].'. ';
                        $list_item .= '<div class="col-md-12 text-left">'.$author_display.'.<br>'.$title.'.<br> '.$publisher_display.$thesis_display.'. <a href="'.BASEURL.'?year='.$year.'">'.$year.'</a></div><div style="text-align: right; margin-top: 0.5em;">'.$file_display.$buttonItems.'</div>';
                    }
                    else $list_item = '';
                    if($list_item != '') $list_item .= "</div>\n";
                    /* END: the different record-types */
                    
                    if (empty($getQuickSearchName)) {
                        /* If the author is given (assume we are not in quicksearch-mode) and he is the first author, these publications should be displayed first.
                        In order to realize this, we consider the array to be a stack and put the current list item on top (if author=firstauthor) or to the bottom (else). */
                        if (convertSpecialChars($firstauthor_lastname) == convertSpecialChars($getAuthor))
                            array_unshift($list_items_arr_thegivenauthor, $list_item); // put the item on top of the stack
                        else
                            array_push($list_items_arr_other, $list_item); // put the item below the stack
                    }
                    else /* if we are in quicksearch-mode */
                        array_push($list_items_arr, $list_item); // put the item below the stack, to keep the order

                    $entryNum++;
            } // if $considerEntry

        } // while

        if (empty($getQuickSearchName)) {
            $list_items_arr_thegivenauthor = array_reverse($list_items_arr_thegivenauthor);
            $list_items_arr = array_merge($list_items_arr_thegivenauthor, $list_items_arr_other);
        }

        $i = 0;
        while (!empty($list_items_arr[$i])) {
            echo $list_items_arr[$i];
            $i++;
        }

    } // if ($numberOfRows>0)

} // foreach

$dbHandle = null;

if ($getMode != 'selectedpubs') {
?>
</div>
    <p>&nbsp;<p>
    <nav class="navbar navbar-custom navbar-inverse">
        <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div id="owner"><br>© IBT <?php echo date('Y'); ?> | KIT – University of the State of Baden-Wuerttemberg and National Research Center of the Helmholtz Association</div>
        </div>
    </nav>
</div>

</body>
</html>
<?php
}
?>
