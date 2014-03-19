<!doctype html>

<html>
  
  <head>
    <title>DOCX Reader by Siddharth Bhatt</title>
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel='icon' type='image/png' href='favicon.png'>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/scrolling.js"></script>
  </head>
  
  <body>
<div class="container">
<?php

$uploaddir = "./files/".$_GET['username']."/";
$file = $_GET['filename'];
$filename = $uploaddir.$file;

?>
<h2>You are viewing : <?php echo $file;?></h2>
<hr>
<?

function read_file_docx($filename){

    $striped_content = '';
    $content = '';

    if(!$filename || !file_exists($filename)) return false;

    $zip = zip_open($filename);

    if (!$zip || is_numeric($zip)) return false;

    while ($zip_entry = zip_read($zip)) {

        if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

        if (zip_entry_name($zip_entry) != "word/document.xml") continue;

        $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

        zip_entry_close($zip_entry);
    }// end while

    zip_close($zip);

    //echo $content;
    //echo "<hr>";
    ///file_put_contents('1.xml', $content);

    $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
    $content = str_replace('</w:r></w:p>', "\r\n", $content);
    $striped_content = strip_tags($content);

    return $striped_content;
}
?>
<?    
$content = read_file_docx($filename);
if($content !== false) {

    echo nl2br($content);
}
else
{?>
    
    <div class='alert alert-dismissable alert-danger'>
    <button type='button' class='close' data-dismiss='alert'>&times;</button>
    <b>Error :</b> Couldn't get the file. Notify me @ <a href="mailto:sidbhatt11@yahoo.in?Subject=Error_with_DOCX_READER">sidbhatt11[at]yahoo[dot]in</a><br>
    </div>
<?}
?>     
    <a href="#" class="back-to-top">&uarr;</a>
    <hr>
    <footer>
        &copy; 2014 <a href="http://siddharthbhatt.com" target="_blank">Siddharth Bhatt</a>
    </footer>
</div>
</body>
</html>