<?php
//include 'parser.php';
error_reporting(0);

if ($_FILES["file"]["type"] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document")
  {
      if ($_FILES["file"]["error"] > 0)
        {?>
            <div class='alert alert-dismissable alert-danger'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <b>Error :</b> Either you didn't select the file or there was some problem while uploading the file.<br>
            </div>
        <?}
      else
        {
            $uploaddir = "./files/".$_SESSION['user_name']."/";
            
            if (file_exists($uploaddir.$_FILES["file"]["name"]))
              {?>
                  <div class="alert alert-dismissable alert-danger">
                  <button type='button' class='close' data-dismiss='alert'>&times;</button>
                  <b>Sorry !</b> <?php echo $_FILES["file"]["name"];?>already exists.
                  </div>
              <?}
            else
              {
                  move_uploaded_file($_FILES["file"]["tmp_name"],$uploaddir.$_FILES["file"]["name"]);
                  // echo "Stored in: " . "./uploaded/" . $_FILES["file"]["name"]; 
                  $replaced_name = str_replace(' ', '_', $_FILES["file"]["name"]);	
                  rename($uploaddir.$_FILES["file"]["name"], $uploaddir.$replaced_name);
              ?>   
                <div class="alert alert-dismissable alert-success">
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                <b>Success !</b> Your file has been uploaded.<br><br>
                <b>Filename :</b> <?php echo $_FILES["file"]["name"];?><br>
                <b>Size :</b> <?php echo ($_FILES["file"]["size"] / 1024);?> kb
                <!--<b>Type :</b> <?php //echo $_FILES["file"]["type"];?><br>
                <b>Temp file :</b> <?php //echo $_FILES["file"]["tmp_name"];?><br>
                <b>Stored in :</b> <?php //echo $uploaddir;?>-->    
                </div>     
              <?
             }
           
        }
	}
    elseif($_FILES["file"]["type"] == "")
    { }
    else
    {?>
            <div class='alert alert-dismissable alert-danger'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <b>Invalid file !</b> Only .docx files can be uploaded.
            </div>
    <?}

?>
<div class='navbar navbar-default navbar-fixed-top'>
    <div class='container'>
        <div class='navbar-header'>
              <button type='button' class='navbar-toggle' data-toggle='collapse' data-target='.navbar-collapse'>
                <span class='icon-bar'></span><span class='icon-bar'></span><span class='icon-bar'></span>
              </button>
                <a class='navbar-brand' href='index.php'>DOCX Reader By Siddharth Bhatt</a>
        </div>
        <div class='navbar-collapse collapse'>
            <ul class='nav navbar-nav pull-right navbar-right'>
                <li class='active'>
                  <a href='index.php'><?php echo $_SESSION['user_name'];?></a>
                </li>
           
                <li>
                  <a href='index.php?action=logout'>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="container">
    <h1>Upload New File :</h1>
    <hr>
    <div class="well well-lg">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method='post' enctype='multipart/form-data'>
            <input type='file' name='file' id='file'><br>
            <input type='submit' name='submit' class='btn btn-primary' value='Upload'>
        </form>
    </div>

    

    <h2>Files you have uploaded :</h2>
    <hr>
    <table class="table table-bordered">
        <tr>
            <th>#</th>
            <th>Document</th>
        </tr>
<?php
		
  $newvar = "parser.php?file=./files/".$_SESSION['user_name']."/";
  if ($handle = opendir('./files/'.$_SESSION['user_name'].'/')) 
  {
	
    $count= 1;
	
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
        if ($entry == "." || $entry == ".." || $entry == "index.html" || $entry==".DS_Store")
        {    $count= 1; }
        else	
        {?> <tr>
            <td><?php echo $count;?></td>
            <td><a href="parser.php?username=<?php echo $_SESSION['user_name'];?>&filename=<?php echo $entry;?>" target="_blank"><?php echo $entry;?></a></td>
            </tr>
        <?
        $count += 1;	
        }
	
    }//while over
	closedir($handle);
}//if over

?>
    
</table>


</div>