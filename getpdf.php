<?php

$ref = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_STRING);

if (!empty($ref)) {
	try {
		$dbHandle = new PDO('sqlite:library/database/library.sq3');
	}
	catch (PDOException $e) {
		print 'Error: '.$e->getMessage().'.';
		die();
	}
	
	$query = 'FROM library WHERE bibtex='.$dbHandle->quote($ref);
	$result = $dbHandle->query('SELECT COUNT(*) '.$query);
	
	if($result->fetchColumn()) {
		$result = null;
		$result = $dbHandle->query('SELECT file '.$query);
		$row = $result->fetch();
		$file = $row['file'];

		$level1 = substr($file, 0, 1);
		$level2 = substr($file, 1, 1);
		$filename = 'library/pdfs/'.$level1.'/'.$level2.'/'.$file;

		if (file_exists($filename)) {
			header('Content-Type: application/pdf');
			header('Content-Disposition: inline; filename='.$ref);
			readfile($filename);
			die();
		}
	}
}
echo "Error: PDF not found."

?>