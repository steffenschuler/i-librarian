<?php
ini_set('max_execution_time', 600); // 10 min

date_default_timezone_set('Europe/Berlin');
echo date('r')."\n";

include_once 'functions.php';

define('TARGETFILE', 'export/export.bib');
define('ENCODING', ''); // use 'ASCII', if there are TeX problems with utf-8
define('PDFURL', 'https://intern.ibt.kit.edu/ilibrarian/getpdf.php?ref=');

// define which columns should be exported
$column_names = array(
    'id',
    'authors',
    'title',
    'journal',
    'year',
    'volume',
    'issue',
    'pages',
    'secondary_title',
    'tertiary_title',
    'affiliation',
    'editor',
    'publisher',
    'place_published',
    'keywords',
    'doi',
    'url',
    'custom2',
);

try {
    $dbHandle = new PDO('sqlite:library/database/library.sq3');
}
catch (PDOException $e) {
    print 'Error: '.$e->getMessage().'.';
    die();
}

$result = $dbHandle->query('SELECT COUNT(*) FROM library');
$numberOfRows = $result->fetchColumn();
$result = null;

if($numberOfRows > 0) {

    $result = $dbHandle->query('SELECT * FROM library ORDER BY lower(bibtex) ASC');

    $fp = fopen(TARGETFILE, 'w');
    if(!$fp) die('Error: '.TARGETFILE.' could not be opened.');

    echo 'Starting export of '.$numberOfRows.' items to '.realpath(TARGETFILE)."...\n";
    $paper = '';
    $n = 0;

    while ($item = $result->fetch(PDO::FETCH_ASSOC)) {
    
        $add_item = array();

        if ($item['volume'] == 0)
            $item['volume'] = '';

        foreach ($column_names as $column_name) {
            $add_item[$column_name] = $item[$column_name];
        }

        if (ENCODING == 'ASCII') {
            foreach ($add_item as $key => $value) {
                if (!empty($value))
                    $add_item[$key] = utf8_deaccent($value);
            }
            reset($add_item);
        }

        if (isset($add_item['id'])) {

            if (!empty($item['bibtex'])) {

                $add_item['id'] = $item['bibtex'];
            } else {

                $id_author = substr($item['authors'], 3);
                $id_author = substr($id_author, 0, strpos($id_author, '"'));
                if (empty($id_author))
                    $id_author = 'unknown';

                $id_year_array = explode('-', $item['year']);
                $id_year = '0000';
                if (!empty($id_year_array[0]))
                    $id_year = $id_year_array[0];

                $add_item['id'] = utf8_deaccent($id_author) . '-' . $id_year . '-ID' . $item['id'];

                $add_item['id'] = str_replace(' ', '', $add_item['id']);
            }
        }

        $bibtex_translation = array(
            "author      = " => "authors",
            "title       = " => "title",
            "journal     = " => "journal",
            "year        = " => "year",
            "month       = " => "month",
            "volume      = " => "volume",
            "number      = " => "issue",
            "pages       = " => "pages",
            "abstract    = " => "abstract",
            "journal     = " => "secondary_title",
            "series      = " => "tertiary_title",
            "editor      = " => "editor",
            "publisher   = " => "publisher",
            "address     = " => "place_published",
            "doi         = " => "doi",
            "url         = " => "url",
            "thesis_type = " => "custom2"
        );

        if (isset($add_item['authors'])) {

            $new_authors = array();
            $array = explode(';', $add_item['authors']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
                    $new_authors[] = $last . ', ' . $first;
                }
            }
            $authors = join(" and ", $new_authors);
            $add_item['authors'] = $authors;
        }

        if (isset($add_item['url'])) {

            $urls = explode("|", $add_item['url']);
            $add_item['url'] = $urls[0];
        }

        // bibtex does not have a journal abbreviation tag, but if user wants it, put abbreviation in journal tag
        if ($item['reference_type'] == 'article' && !empty($add_item['journal']) && empty($add_item['secondary_title'])) {

            $add_item['secondary_title'] = $add_item['journal'];
        }

        if (isset($add_item['editor'])) {

            $new_authors = array();
            $array = explode(';', $add_item['editor']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
                    $new_authors[] = $last . ', ' . $first;
                }
            }
            $authors = join(" and ", $new_authors);
            $add_item['editor'] = $authors;
        }

        if (isset($add_item['year'])) {

            if (!empty($add_item['year']) && !is_numeric($add_item['year'])) {
                $add_item['month'] = date('n', strtotime($add_item['year']));
                $add_item['year'] = substr($add_item['year'], 0, 4);
            }
        }

        if (isset($add_item['pages'])) {

            $add_item['pages'] = str_replace('-', '--', $add_item['pages']);
        }

        if ($item['reference_type'] == 'conference' || $item['reference_type'] == 'chapter') {
            unset($bibtex_translation['journal     = ']);
            $bibtex_translation['booktitle   = '] = 'secondary_title';
        } elseif ($item['reference_type'] == 'book') {
            unset($bibtex_translation['journal     = ']);
            $bibtex_translation['series      = '] = 'secondary_title';
        } elseif ($item['reference_type'] == 'thesis') {
            unset($bibtex_translation['journal     = ']);
            $bibtex_translation['school      = '] = 'secondary_title';
        } elseif ($item['reference_type'] == 'manual') {
            unset($bibtex_translation['journal     = ']);
            $bibtex_translation['section     = '] = 'secondary_title';
        } elseif ($item['reference_type'] == 'patent') {
            unset($bibtex_translation['journal     = ']);
            $bibtex_translation['source      = '] = 'secondary_title';
        }

        foreach ($add_item as $key => $value) {

            $value = wordwrap($value, 75, "\n            ");

            // Escape certain special chars.
            $value = str_replace('&', '\&', $value);
            $value = str_replace('%', '\%', $value);
            $value = str_replace('$', '\$', $value);

            $bibtex_name = array_search($key, $bibtex_translation);
            if ($bibtex_name && !empty($value)) {

                // Protect capitalization.
                $protected_fields = array(
                    'title       = ',
                    'booktitle   = ',
                    'series      = ',
                    'journal     = '
                );

                if (in_array($bibtex_name, $protected_fields)) {
                    $value = preg_replace('/(\p{Lu}{2,})/u', '{$1}', $value);
                }

                $columns[] = $bibtex_name . '{' . $value . '}';
            }
        }

        // UIDs.
        if (!empty($add_item['uid'])) {

            $uids = explode('|', $add_item['uid']);

            foreach ($uids as $uid) {

                $uid2 = explode(':', $uid);
                $key = str_pad(str_replace(' ', '-', strtolower($uid2[0])), 9, ' ') . ' = ';
                $columns[] = $key . '{' . $uid2[1] . '}';
            }
        }

        reset($add_item);

        if (!empty($item['bibtex_type'])) {
            $type = $item['bibtex_type'];
        } else {
            $type = convert_type($item['reference_type'], 'ilib', 'bibtex');
        }
        $line = join(',' . PHP_EOL, $columns);
        $paper .= '@' . $type . '{' . $add_item['id'] . ',';
        $paper .= PHP_EOL . $line;
        
        if (!empty($item['file'])) {
            $level1 = substr($item['file'], 0, 1);
            $level2 = substr($item['file'], 1, 1);
            $filename = 'library/pdfs/'.$level1.'/'.$level2.'/'.$item['file'];
            if (file_exists($filename))
                $paper .= ',' . PHP_EOL . 'file        = {FULLTEXT:' . PDFURL . $item['bibtex'] . ':PDF}' . PHP_EOL;
        }
        $paper .= '}' . PHP_EOL . PHP_EOL;
        $columns = null;
        
        $n++;
        if($n % 1000 == 0) {
            fwrite($fp, $paper);
            $paper = '';
            echo 'Exported '.$n.' of '.$numberOfRows." items.\n";
            flush();
        }
    }
    
    fwrite($fp, $paper);
    echo 'Exported '.$n.' of '.$numberOfRows." items.\nExport finished!\n";
    fclose($fp);
    $result = null;
}

echo date('r')."\n";

?>
