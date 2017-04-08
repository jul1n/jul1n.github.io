<?php


/***
! User settings
Edit these lines according to your need
***/

$AUTHENTICATE_USER = true;     // true | false
$USERS = array(
        'J'=>'H'
        ); // set usernames and strong passwords
$DEBUG = false;                         // true | false
$CLEAN_BACKUP = false;           // during backuping a file, remove overmuch backups
$FOLD_JS = false;                        // if javascript files have been expanded during download the fold them
//{{{
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//}}}

//{{{


// Check if file_uploads is active in php config
function check_file_upload_active() {
    if (ini_get('file_uploads') != '1') {
   echo "Error : File upload is not active in php.ini\n";
   toExit($options);
}
}

function get_param($name,$def,$data) {
    $out=$def;
    if (isset($_GET[$name])) {
    $out=$_GET[$name];
    } elseif (isset($data[$name])) {
        $out=$data[$name];
    } else {
        $out=$def;
    }
    return $out;
}

function param() {
    global $AUTHENTICATE_USER;
    global $USERS;
    global $DEBUG;
    global $CLEAN_BACKUP;
    global $FOLD_JS;

    if ($_GET["action"]=="save") {
        $data = file_get_contents('php://input');
        $data=json_decode($data,true);
		} else {
	$data= $_POST;
    }

    
	$options=array();
    $options["authenticate_user"]=$AUTHENTICATE_USER;
    $options["users"]=$USERS;
    $options["debug"]=$options["debug"];
    $options["clean_backup"]=$CLEAN_BACKUP;
    $options["fold_js"]=$FOLD_JS;
	$options["method"]=$_SERVER['REQUEST_METHOD'];
    $options["php_self"]=$_SERVER["PHP_SELF"];
    $options["php_dir"]=dirname($_SERVER["PHP_SELF"]);
    $options["action"]=get_param("action","",$data);
    $options["id"]=get_param("id","",$data);
    $options["type"]=get_param("type","",$data);
    $options["ext"]=get_param("ext","",$data);
    if ($options["ext"]!=="") {
        $options["ext"]=".".$options["ext"];
    }
    if ($options["method"]=="POST") {
    $options["backupDir"]=urldecode($data["backupDir"]);
    $options["user"]=urldecode($data["user"]);
    $options["password"]=urldecode($data["password"]);
    $options["uploaddir"]=urldecode($data["uploaddir"]);
    $options["filename"]=urldecode($data["filename"]);
    $options["content"]=urldecode($data["content"]);
    $options["action"]=urldecode($data["action"]);
    $options["edition"]=urldecode($data["edition"]);
    }
	return $options;
}

function readfile_chunked( $filename, $retbytes = true ) {
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
    $buffer = '';
    $cnt = 0;
    $handle = fopen( $filename, 'rb' );
    if ( $handle === false ) {
        return false;
    }
    ob_end_clean(); //added to fix ZIP file corruption
    ob_start(); //added to fix ZIP file corruption
    //header( 'Content-Type:' ); //added to fix ZIP file corruption
    while ( !feof( $handle ) ) {
        $buffer = fread( $handle, $chunksize );
        //$buffer = str_replace("ï»¿","",$buffer);
        echo $buffer;
        ob_flush();
        flush();
        if ( $retbytes ) {
            $cnt += strlen( $buffer );
        }
    }
    $status = fclose( $handle );
    if ( $retbytes && $status ) {
        return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
} 

function get_extern_tiddler($options) {
    $filename="./tiddler/".$options["id"];
    $tiddler=$options["id"];
    $tiddler=$tiddler.$options["ext"];

    if (is_file($filename)) {
	$begin="";
        if ($options["type"]!="") {
            $begin="title: ".$tiddler."\n";
            $begin.="type: ".$options["type"]."\n";
            $begin.="\n";
        }
	 header('Content-Disposition: attachment; filename="' . $tiddler . '"');
	 header( 'Content-Type:' );
	 if ($begin!="") {
	 echo $begin;
	 ob_flush();
	 flush();
	 }
    readfile_chunked($filename);
    } else {
?>
Tiddler file <?php echo $options["id"];?> existiert nicht!
<?php exit;
    }
    exit;
}

function getStartEdition($options) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
        <a href='upload_js.html'>upload.js</a>
        <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
        <input type="hidden" name="action" value="create"/>
        Filename:
        <input type="text" name="filename"/><br/>
        Edition:<br/>
<?php 
        foreach (glob('./edition/*.html') as &$filepath) {
        $file=basename($filepath);
?>
<input type='radio' name='edition' value='<?php echo $file;?>'/><?php echo $file;?></input><br/>
<?php } ?>
<input type="submit" value="Create" name="submit"/>
        </form>
        </body>
</html>

<?php exit;
}
function isUser($user,$password) {
    global $USERS;
    global $AUTHENTICATE_USER;
    return ((($AUTHENTICATE_USER) && ($USERS[$user] == $password)) || (!$AUTHENTICATE_USER));
    
} 
function getLsView($options) {
    $dir=$options["php_dir"];
    if (isUser($options["user"],$options["password"])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
<?php 
        foreach (glob('./*html') as &$filepath) {
        $file=basename($filepath);
?>
<a href='<?php echo $dir."/".$file;?>'/><?php echo $file;?></input><br/>
<?php } ?>
        </body>
</html>

<?php exit;
}
}

function get_extern_tiddler_view($options) {
    $dir=$options["php_dir"];
    if (isUser($options["user"],$options["password"])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
        <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
        <input type="hidden" name="action" value="externtiddler"/>
        Tiddler: <input type="text" name="id"/><br/>
        File Extension: <input type="text" name="ext"/><br/>
        Type: <input type="text" name="type"/><br/>
<input type="submit" value="View" name="submit"/>
        </form>
        </body>
</html>

<?php exit;
}
}

function getLogin($options,$action,$title) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
<?php 
        foreach (glob('./*html') as &$filepath) {
        $file=basename($filepath);
?>
<a href='<?php echo $dir."/".$file;?>'/><?php echo $file;?></input><br/>
<?php } ?>
        </body>
</html>

<?php exit;
}

function getLoginEmpty($options) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
        <h1>TiddlyWiki PHP Save</h1>
        <a href='upload_js.html'>upload.js</a>
        <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
        User:
        <input type="text" name="user"/><br/>
        Password:
        <input type="password" name="password"/><br/>
        Action:<br/>
<?php if (is_dir("./edition")) { ?>
        <input type="radio" name="action" value="start"/>Create A new Wiki<br/>
<?php } ?>
        <input type="radio" name="action" value="lsview"/>LS<br/>
        <input type="radio" name="action" value="externtiddlerview"/>External Tiddler<br/>
        <input type="submit" value="Login" name="submit"/>
        </form>
        </body>
        </html>

<?php exit;
}

function getStartNoEdition($options) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
        <head>
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
        </head>
        <body>
        <a href='upload_js.html'>upload.js</a>
</body>
</html>

<?php exit;
}

function getStart($options) {
    if (is_dir("edition")) {
        getStartEdition($options);
    } else {
        getStartNoEdition($options);
    }
}

function getCreate($options) {
    $tw5edition=$options["edition"];
    $filename=$options["filename"];
    $filename=str_replace(".html","",$filename);
    $filename=str_replace(".htm","",$filename);
    $filename=$filename.".html";
    $dir=$options["php_dir"];
    if (isUser($options["user"],$options["password"])) {
		if (!is_file($filename)) {
       copy("edition/".$tw5edition,$filename); 
       header("Location: ".$dir."/".$filename);
		} else {
            ?>
            <html>
       <body>
       Error: File <?php echo $filename;?> exist!
	   Not possible to create a new Wiki under that filename
       </body>
</html>	

            <?php exit;
		}
    } else {
?>
<html>
<body>
       Error: user and/or password wrong!
       <?php echo $user;?>
       <?php echo $password;?>
</body>
</html>

<?php exit;
    }
}

function dispatch($options) {
        check_file_upload_active();
	switch($options["action"]) {
        case "create" : getCreate($options);break;
        case "ls" : getLogin($options,"lsview","List TiddlyWikis");break;
        case "lsview": getLsView($options);break;
        case "start" : getStart($options);break;
		case "save" : save($options);break;
        case "upload.js" : upload_js($options);break;
        case "externtiddler": get_extern_tiddler($options);break;
        case "externtiddlerview": get_extern_tiddler_view($options);break;
        default : getLoginEmpty($options);break;
	}
}

function tw5_plugin_tiddler($title,$modified,$module_type,$tiddler_title,$iddler_type,$text){
	return implode("\n",array("<div created=\""+$created+"\" modified=\""+$modified+"\" module-type=\""+$module_type+"\" title=\""+$tiddler_title+"\" type=\""+$tiddler_type+"\">",
"<pre>"+$text+"</pre>",
"</div>"));
}

function upload_js($options) {
$js= <<< EOT
/*\
title: $:/core/modules/savers/upload.js
type: application/javascript
module-type: saver

Handles saving changes via upload to a server.

Designed to be compatible with BidiX's UploadPlugin at http://tiddlywiki.bidix.info/#UploadPlugin

\*/
(function(){
    
    /*jslint node: true, browser: true */
    /*global $tw: false */
    'use strict';
    
    /*
      Select the appropriate saver module and set it up
    */
    var UploadSaver = function(wiki) {
	this.wiki = wiki;
    };

    UploadSaver.prototype.save = function(text, method, callback) {
	// Get the various parameters we need
	var data;
	var backupDir = this.wiki.getTextReference('$:/UploadBackupDir') || '.';
	var username = this.wiki.getTextReference('$:/UploadName');
	var password = $tw.utils.getPassword('upload');
	var uploadDir = this.wiki.getTextReference('$:/UploadDir') || '.';
	var uploadFilename = this.wiki.getTextReference('$:/UploadFilename') || 'index.html';
	var url = this.wiki.getTextReference('$:/UploadURL')+"?action=save";
	// Bail out if we don't have the bits we need
	if(!username || username.toString().trim() === '' || !password || password.toString().trim() === '') {
            return false;
	}
	// Construct the url if not provided
	if(!url) {
            url = 'http://' + username + '.tiddlyspot.com/store.cgi';
	}
	// Assemble the header
	var uploadFormName = 'UploadPlugin';
	var head={};
	
	head.backupDir = encodeURIComponent(backupDir);
	head.user = encodeURIComponent(username);
	head.password = encodeURIComponent(password);
	head.uploaddir = encodeURIComponent(uploadDir);
	head.filename = encodeURIComponent(uploadFilename);
	head.content = encodeURIComponent(text);
	head.action = encodeURIComponent('save');
	
	data=JSON.stringify(head);
	// Do the HTTP post
	var http = new XMLHttpRequest();
	http.open('POST',url,true);
	http.setRequestHeader('Origin', location.origin);
	http.setRequestHeader('Content-Type', 'text/plain; charset=utf-8');
	http.setRequestHeader('Content-length', data.length);
        
	http.onreadystatechange = function() {
            if(http.readyState === 4 && http.status === 200) {
		if(http.responseText.trim().substr(0, 4) === '0 - ') {
                    callback(null);
		} else {
                    callback(http.responseText);
		}
            }
	};
    
	try {
            http.send(data);
	} catch(ex) {
            return callback('Error:' + ex);
	}
	$tw.notifier.display('$:/language/Notifications/Save/Starting');
	return true;
    };
    
    /*
      Information about this saver
    */
    UploadSaver.prototype.info = {
	name: 'upload',
	priority: 2000,
	capabilities: ['save', 'autosave']
    };
    
    /*
      Static method that returns true if this saver is capable of working
    */
    exports.canSave = function(wiki) {
	return true;
    };
    
    /*
      Create an instance of this saver
    */
    exports.create = function(wiki) {
	return new UploadSaver(wiki);
    };
    
})();

EOT;
$js=trim($js);
$js=htmlspecialchars($js);
$tw5= <<< EOT
<html>
<body>
pre_tw5()
<div id="storeArea" style="display:none;">
storearea()
</div>
</body>
</html>
EOT;
$tw5=trim($tw5);
$tiddlers= tw5_plugin_tiddler("$:/core/modules/savers/upload.js","20160418070545126","20160418070545126","saver","application/javascript",$js);
$tw5=str_replace("pre_tw5()","",$tw5);
$tw5=str_replace("storearea()",$tiddlers,$tw5);
header('Content-Disposition: attachment; filename="upload_js.html"');
echo $tw5;
exit;
}

// Recursive mkdir
function mkdirs($dir) {
        if( is_null($dir) || $dir === "" ){
                return false;
        }
        if( is_dir($dir) || $dir === "/" ){
                return true;
        }
        if( mkdirs(dirname($dir)) ){
                return mkdir($dir,0755);
        }
        return false;
}

function toExit($options) {
        if ($options["debug"]) {
                print_r($options);
}
exit;
}

function ParseTWFileDate($s) {
        // parse date element
        preg_match ( '/^(\d\d\d\d)(\d\d)(\d\d)\.(\d\d)(\d\d)(\d\d)/', $s , $m );
        // make a date object
        $d = mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
        // get the week number
        $w = date("W",$d);

        return array(
                'year' => $m[1], 
                'mon' => $m[2], 
                'mday' => $m[3], 
                'hours' => $m[4], 
                'minutes' => $m[5], 
                'seconds' => $m[6], 
                'week' => $w);
}

function cleanFiles($dirname, $prefix) {
        $now = getdate();
        $now['week'] = date("W");

        $hours = Array();
        $mday = Array();
        $year = Array();
        
        $toDelete = Array();

        // need files recent first
        $files = Array();
        ($dir = opendir($dirname)) || die ("can't open dir '$dirname'");
        while (false !== ($file = readdir($dir))) {
                if (preg_match("/^$prefix/", $file))
        array_push($files, $file);
    }
        $files = array_reverse($files);
        
        // decides for each file
        foreach ($files as $file) {
                $fileTime = ParseTWFileDate(substr($file,strpos($file, '.')+1,strrpos($file,'.') - strpos($file, '.') -1));
                if (($now['year'] == $fileTime['year']) &&
                        ($now['mon'] == $fileTime['mon']) &&
                        ($now['mday'] == $fileTime['mday']) &&
                        ($now['hours'] == $fileTime['hours']))
                                continue;
                elseif (($now['year'] == $fileTime['year']) &&
                        ($now['mon'] == $fileTime['mon']) &&
                        ($now['mday'] == $fileTime['mday'])) {
                                if (isset($hours[$fileTime['hours']]))
                                        array_push($toDelete, $file);
                                else 
                                        $hours[$fileTime['hours']] = true;
                        }
                elseif  (($now['year'] == $fileTime['year']) &&
                        ($now['mon'] == $fileTime['mon'])) {
                                if (isset($mday[$fileTime['mday']]))
                                        array_push($toDelete, $file);
                                else
                                        $mday[$fileTime['mday']] = true;
                        }
                else {
                        if (isset($year[$fileTime['year']][$fileTime['mon']]))
                                array_push($toDelete, $file);
                        else
                                $year[$fileTime['year']][$fileTime['mon']] = true;
                }
        }
        return $toDelete;
}

function replaceJSContentIn($content) {
        if (preg_match ("/(.*?)<!--DOWNLOAD-INSERT-FILE:\"(.*?)\"--><script\s+type=\"text\/javascript\">(.*)/ms", $content,$matches)) {
                $front = $matches[1];
                $js = $matches[2];
                $tail = $matches[3];
                if (preg_match ("/<\/script>(.*)/ms", $tail,$matches2)) {               
                        $tail = $matches2[1];
                }
                $jsContent = "<script type=\"text/javascript\" src=\"$js\"></script>";
                $tail = replaceJSContentIn($tail);
                return($front.$jsContent.$tail);
        }
        else
                return $content;
}

function save($options) {
$uploadDir = './';
$uploadDirError = false;
$backupError = false;
$backupFilename = '';

$filename = $options["filename"];
$destfile = $filename;

// authenticate User
if (!isUser($options['user'],$options['password'])) {
        echo "Error : UserName or Password do not match \n";
        echo "UserName : [".$options['user']. "] Password : [". $options['password'] . "]\n";
        toExit($options);
}



// make uploadDir
if ($options['uploaddir']) {
        $uploadDir = $options['uploaddir'];
        // path control for uploadDir   
    if (!(strpos($uploadDir, "../") === false)) {
        echo "Error: directory to upload specifies a parent folder";
        toExit($options);
        }
        if (! is_dir($uploadDir)) {
                mkdirs($uploadDir);
        }
        if (! is_dir($uploadDir)) {
                echo "UploadDirError : $uploadDirError - File NOT uploaded !\n";
                toExit($options);
        }
        if ($uploadDir{strlen($uploadDir)-1} != '/') {
                $uploadDir = $uploadDir . '/';
        }
}
$destfile = $uploadDir . $filename;

// backup existing file
if (file_exists($destfile) && ($options['backupDir'])) {
        if (! is_dir($options['backupDir'])) {
                mkdirs($options['backupDir']);
                if (! is_dir($options['backupDir'])) {
                        $backupError = "backup mkdir error";
                }
        }
        $backupFilename = $options['backupDir'].'/'.substr($filename, 0, strrpos($filename, '.'))
                                .date('.Ymd.His').substr($filename,strrpos($filename,'.'));
        rename($destfile, $backupFilename) or ($backupError = "rename error");
        // remove overmuch backup
        if ($options["clean_backup"]) {
                $toDelete = cleanFiles($options['backupDir'], substr($filename, 0, strrpos($filename, '.')));
                foreach ($toDelete as $file) {
                        $f = $options['backupDir'].'/'.$file;
                        if($options["debug"]) {
                                echo "delete : ".$options['backupDir'].'/'.$file."\n";
                        }
                        unlink($options['backupDir'].'/'.$file);
                }
        }
}

        if ($options["fold_js"]) {
                // rewrite the file to replace JS content
                $fileContent = $options["content"];
                $fileContent = replaceJSContentIn($fileContent);
                if (!$handle = fopen($destfile, 'w')) {
                 echo "Cannot open file ($destfile)";
                 exit;
            }
            if (fwrite($handle, $fileContent) === FALSE) {
                echo "Cannot write to file ($destfile)";
                exit;
            }
            fclose($handle);
        } else {
file_put_contents($destfile,$options["content"]);			
		}
    
        chmod($destfile, 0755);
        if($options["debug"]) {
                echo "Debug mode \n\n";
        }
        if (!$backupError) {
                echo "0 - File successfully loaded in " .$destfile. "\n";
        } else {
                echo "BackupError : $backupError - File successfully loaded in " .$destfile. "\n";
        }
        echo("destfile:$destfile \n");
        if (($backupFilename) && (!$backupError)) {
                echo "backupfile:$backupFilename\n";
        }
        $mtime = filemtime($destfile);
        echo("mtime:$mtime");
toExit($options);
}

dispatch(param());
//}}}