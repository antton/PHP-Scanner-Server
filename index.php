<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html><?php
// Warning is displayed if there is less then the anmout specifyed
$FreeSpaceWarn=2048;// In Megabytes
$Fortune=true;// Enable/disable fortunes in the debug console
$ExtraScanners=false;// Adds sample scanners from ./inc/scanhelp
// Sorry for the lack of explanations in the code feel free to ask what something does

$NAME="PHP Scanner Server";
$VER="1.3-3_dev";
$SAE_VER="1.4"; // scanner access enabler version

# ****************
# Varables
# ****************

$SCANNER=Get_Values('scanner');
$QUALITY=Get_Values('quality');
$SIZE=Get_Values('size');
$BRIGHT=Get_Values('bright');
$CONTRAST=Get_Values('contrast');
$MODE=Get_Values('mode');
$ORNT=Get_Values('ornt');
$ORNT=(strlen($ORNT)==0?'vert':$ORNT);
$ROTATE=Get_Values('rotate');
$FILETYPE=Get_Values('filetype');
$LANG=Get_Values('lang');
$SCALE=Get_Values('scale');
$SAVEAS=Get_Values('saveas');
$SET_SAVE=Get_Values('set_save');
$M_WIDTH=Get_Values('loc_maxW');
$M_HEIGHT=Get_Values('loc_maxH');
$WIDTH=Get_Values('loc_width');
$HEIGHT=Get_Values('loc_height');
$X_1=Get_Values('loc_x1');
$Y_1=Get_Values('loc_y1');
#$X_2=Get_Values('loc_x2'); Un-used
#$Y_2=Get_Values('loc_y2'); Un-used
$SOURCE=Get_Values('source'); // scan.php main.js index.php
$SOURCE=(strlen($SOURCE)==0?'Inactive':$SOURCE);

$notes='Please read the <a href="index.php?page=About">release notes</a> for more information.';
$user=posix_getpwuid(posix_geteuid());
$user=$user['name'];
$here=$user.'@'.$_SERVER['SERVER_NAME'].':'.getcwd();
$debug='';

# ****************
# Functions
# ****************

function Get_Values($name){
	if(isset($_REQUEST[$name]))
		return str_replace("`","'",$_REQUEST[$name]);// backticks (`) are striped to prevent the use of malicious code
	else
		return null;
}

function html($X){
	return htmlspecialchars($X);// name is too long and subject to frequent typos
}

function Put_Values() { # Update values back to form (There is no redo for croping)
	echo '<script type="text/javascript">'.
	"config({'scanner':".addslashes($GLOBALS['SCANNER']).
		",'source':'".addslashes($GLOBALS['SOURCE'])."'".
		",'quality':".addslashes($GLOBALS['QUALITY']).
		",'size':'".addslashes($GLOBALS['SIZE'])."'".
		",'ornt':'".addslashes($GLOBALS['ORNT'])."'".
		",'mode':'".addslashes($GLOBALS['MODE'])."'".
		",'bright':".addslashes($GLOBALS['BRIGHT']).
		",'contrast':".addslashes($GLOBALS['CONTRAST']).
		",'rotate':".addslashes($GLOBALS['ROTATE']).
		",'scale':".addslashes($GLOBALS['SCALE']).
		",'filetype':'".addslashes($GLOBALS['FILETYPE'])."'".
		",'lang':'".addslashes($GLOBALS['LANG'])."'".
		",'set_save':'".addslashes($GLOBALS['SET_SAVE'])."'".
	"});</script>";
}

function Print_Message($TITLE,$MESSAGE,$ALIGN) { # Add a Message div after the page has loaded
	$TITLE=addslashes($TITLE);
	$MESSAGE=addslashes($MESSAGE);
	include "inc/message.php";
}

function Update_Preview($l) { # Change the Preview Pane image via JavaScript
	echo '<script type="text/javascript">';
	echo 'document.getElementById("preview_img").childNodes[0].childNodes[0].src="'.addslashes($l).'";';
	echo '</script>';
}

function Update_Links($l,$p) { # Change the Preview Pane image links via JavaScript
	echo '<script type="text/javascript" src="inc/previewlinks.php?page='.html($p).'&file='.html($l).'"></script>';
}

function InsertHeader($l) { # Spit out HTML header
	include "inc/header.php";
}

function Footer() { # Spit out HTML footer
	include "inc/footer.php";
}

function SaveFile($file,$content){// @ supresses any warnings
	$file=@fopen($file,'w+');
	@fwrite($file,$content);
	@fclose($file);
	if(is_bool($file)){
		return $file;
	}
	return true;
}

function checkFreeSpace($X){
	$pace=disk_free_space('scans')/1024/1024;
	if($pace<$X){//there is less than X MB of free space
		Print_Message("Warning: Low Disk Space","There is only ".number_format($pace)." MB of free space, please delete some scans.<br/>Low disk space can cause really bad problems.",'center');
	}
	return $pace;
}

function fileSafe($l){
	if(strpos($l,"/")>-1){
		$l=substr($l,strrpos($l,"/")+1);
	}
	return $l;
}

function validNum($arr){
	for($i=0,$m=count($arr);$i<$m;$i++){
		if(is_int($arr[$i]))
			return false;
	}
	return true;
}

function exe($shell,$force){
	$output=str_replace("\\n","\n",shell_exec($shell.($force?' 2>&1':'')).($force?'':'The output of this command unfortunately has to be suppressed to prevent errors :(\nRun `sudo -u www-data '.$shell.'` for output info'));
	$GLOBALS['debug'].=$GLOBALS['here'].'$ '.$shell."\n".$output.(substr($output,-1)=="\n"?"":"\n");
	return $output;
}

function debugMsg($msg){/* good for printing a quick message during testing */
	Print_Message("Debug Message",$msg,'center');
}

function findLangs(){
	$tess="/usr/share/tesseract-ocr/tessdata";// This is where tesseract stores it language files
	$langs="/usr/share/doc";// This is where documentation is stored
	if(is_dir($tess)){
		$langs=array();
		$tess=scandir($tess);
		for($i=2,$max=count($tess);$i<$max;$i++){
			$pos=strpos($tess[$i],'.');
			if($pos){
				$tess[$i]=substr($tess[$i],0,strpos($tess[$i],'.',$pos));
				if(!in_array($tess[$i],$langs)){
					array_push($langs,$tess[$i]);
				}
			}
		}
	}
	else if(is_dir($langs)){
		$langs=explode("\n",substr(exe("ls $langs | grep 'tesseract-ocr-' | sed 's/tesseract-ocr-//'",true),0,-1));
	}
	else{
		Print_Message("Tesseract Error:","Unable to find any installed language files or documentation.<br/>You can edit lines 145 and or 146 of <code>".getcwd()."/index.php</code> with the correct location for your system.","center");
		$langs=array();
	}
	return $langs;
}

function uuid2bus($d){// bug #13
	$id=$d->{"UUID"};
	$d=$d->{"DEVICE"};
	$data=exe("lsusb -d $id # See Bug #13",true);
	$bus=substr($data,strpos($data,"Bus ")+4,3);
	$dev=substr($data,strpos($data,"Device ")+7,3);
	$pos=strpos($d,"libusb:")+7;
	return substr($d,0,$pos)."$bus:$dev".substr($d,$pos+9);
}

function quit(){
	echo '<script type="text/javascript">Debug("'.rawurlencode(html($GLOBALS['debug'])).html($GLOBALS['here']."$ ").'",'.(isset($_COOKIE["debug"])?$_COOKIE["debug"]:'false').');</script>';
	die('</body></html>');
}

# ****************
# Generate that Fortune
# ****************

$dir="/usr/games";// This is where fortune and cowsay are installed to
if(file_exists("$dir/fortune") && $Fortune===true){
	if(!isset($_COOKIE["fortune"])){
		$_COOKIE["fortune"]=$Fortune;
	}
	else{
		$_COOKIE["fortune"]=$_COOKIE["fortune"]=='true'?true:false;
	}
	if($Fortune && $_COOKIE["fortune"]){
		if(file_exists("$dir/cowsay")&&file_exists("$dir/cowthink")){
			$cows=scandir("/usr/share/cowsay/cows/");// This is where cowsay's ACSII art is stored
			$type=Array('say','think');
			exe("$dir/fortune | $dir/cow".$type[rand(0,1)]." -f ".$cows[rand(2,count($cows)-1)],true);
		}
		else{
			exe("$dir/fortune",true);
		}
	}
}
else{
	$Fortune=NULL;
}

# ****************
# Spit out that HTML!
# ****************

$PAGE=Get_Values('page');
$ACTION=Get_Values('action');

if($PAGE==NULL)
	$PAGE="Scan";

# ****************
# Verify Install (For anyone who installs from git and does not read the notes written in several places)
# ****************

$dirs=Array('scans','config','config/parallel');
foreach($dirs as $dir){
	if(!is_dir($dir)){
		@mkdir($dir);
		if(is_dir($dir))
			continue;
		$here=getcwd();
		$PAGE="Incomplete Installation";
		InsertHeader($PAGE);
		Print_Message("Missing Directory","<i>$here/$dir</i> does not exist!<br/><code>$user</code> also needs to have write access to it<br>To fix run this in a terminal as root<br><code>mkdir $here/$dir && chown $user $here/$dir</code>","center");
		Footer();
		quit();
	}
}

# ****************
# All Scans Page
# ****************
if($PAGE=="Scans"){
	InsertHeader("Scanned Images");

	# Delete selected scanned image
	$DELETE=Get_Values('delete');

	if($DELETE=="Remove"){
		$FILE=fileSafe(Get_Values('file'));
		if($FILE==null){
			exe("rm scans/*",true);
		}
		else{
			$FILE2=addslashes(substr($FILE,0,strrpos($FILE,".")+1));
			@unlink("scans/Preview_".$FILE2."jpg");
			@unlink("scans/Scan_$FILE");
			Print_Message("File Deleted","The file <code>".html($FILE)."</code> has been removed.",'center');
		}
	}

	# Display Thumbnails of scanned images, if any
	if(count(scandir("scans"))==2){ // Wonder if I should rewrite this section without using the exe function, but I think that will make the code much longer
		Print_Message("No Images","All files have been removed. There are no scanned images to display.",'center');
	}
	else{
		echo '<div class="box box-full"><h2>PDF Downloader</h2><p style="text-align:center;">Double Click a file name to select/deselect it for download<br/>'.
			'The order they are selected determines the page order<br/>'.
			'<a href="#" onclick="return selectScans(false);"><button>Select All</button></a> '.
			'<a href="#" onclick="return makePDF(this);"><button>Download</button></a> '.
			'<a href="#" onclick="return selectScans(true);"><button>Select None</button></a>'.
			'</p></div>';
		$FILES=explode("\n",substr(exe('cd "scans"; ls "Preview"*',true),0,-1));
		echo '<div id="scans">';
		for($i=0,$max=count($FILES);$i<$max;$i++){
			$FILE=addslashes(substr($FILES[$i],7,-3));
			$FILE=substr(exe('cd "scans"; ls "Scan'.$FILE.'"*',true),5,-1);//Should only have one file listed
			$IMAGE=$FILES[$i];
			include "inc/scans.php";
		}// Chrome's css columns fail (also disabled in css)
		echo '</div><script type="text/javascript">if(document.body.style.WebkitColumnGap==""||document.body.style.MozColumnGap==""||document.body.style.columnGap=="")getID("scans").className="enable";</script>';
	}
	checkFreeSpace($FreeSpaceWarn);
	Footer();
}
# ****************
# Config Page
# ****************
else if($PAGE=="Config"){
	InsertHeader("Configure");

	if($ACTION=="Delete-Setting"){ # Delete saved scan settings
		$val=Get_Values('value');
		if($val==null){
			if(file_exists("config/settings.json")){
				unlink("config/settings.json");
			}
			else{
				Print_Message("Unable to remove saved scanner settings:","There are no settings to remove, therefore that action can not be completed","center");
			}
		}
		else{
			$file=json_decode(file_get_contents("config/settings.json"));
			unset($file->{$val});
			if(!SaveFile("config/settings.json",json_encode($file)))
				Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
		}
	}
	else if($ACTION=="Detect-Paper"){
		$paper=explode("\n",exe("paperconf -a -N -h -w -m",true));
		unset($paper[count($paper)-1]);//delete empty value
		sort($paper);//lets sort this while we have the chance
		$PAPER=json_decode('{}');
		for($i=0,$s=count($paper);$i<$s;$i++){
			$sheet=explode(" ",$paper[$i]);
			if($sheet[3]<$sheet[1]){
				$tmp=$sheet[3];
				$sheet[3]=$sheet[1];
				$sheet[1]=$tmp;
			}
			$PAPER->{$sheet[0]}=json_decode('{"height":'.$sheet[3].',"width":'.$sheet[1].'}');
		}

		if(SaveFile("config/paper.json",json_encode($PAPER))){
			Print_Message("Paper:","$s different paper sizes were detected and are now usable.<br/>The number varies from scanner to scanner",'center');
		}
		else{
			Print_Message("Paper:","$s different paper sizes were detected.<br/>However, <code>$user</code> does not have permission to write files to the <code>".html(getcwd()).'/config</code> folder.','center');
		}
	}
	else if($ACTION=="Delete-Paper"){
		if(@unlink("config/paper.json")){
			Print_Message("Paper:","Paper configuration has been deleted","center");
		}
		else{
			Print_Message("Paper:","Failed to delete paper configuration","center");
		}
	}
	else if($ACTION=="Imgur-Key-Save"){
		$key=$_POST['key'];
		if(SaveFile("config/IMGUR_API_KEY.txt",$key)){
			Print_Message("Imgur:","Your Imgur API key (<code>$key</code>) has be saved successfully!","center");
		}
		else{
			Print_Message("Imgur:","Failed to save your Imgur API key<br/><code>$user</code> does not have permission to write files to the <code>".html(getcwd()).'/config</code> folder.','center');
		}
	}
	else if($ACTION=="Imgur-Key-Delete"){
		if(@unlink("config/IMGUR_API_KEY.txt")){
			Print_Message("Imgur:","Imgur API key has been deleted","center");
		}
		else{
			Print_Message("Imgur:","Failed to delete Imgur API key","center");
		}
	}

	if(file_exists("config/settings.json"))
		$file=json_decode(file_get_contents("config/settings.json"));
	else
		$file=json_decode('[]');
	include("inc/config.php");

	Footer();

	if($ACTION=="Search-For-Scanners"){ # Find avalible scanners on the system
		$OP=json_decode(
			"[".substr(
				exe('scanimage -f "{\\"ID\\":%i,\\"INUSE\\":0,\\"DEVICE\\":\\"%d\\",\\"NAME\\":\\"%v %m %t\\"},"',true),
				0,
				-1
			)."]"
		);
		$scan=scandir('config/parallel');
		for($i=0,$max=count($scan);$i<$max;$i++){
			if($scan[$i]=="."||$scan[$i]=="..")
				continue;
			$ct=count($OP);
			$OP[$ct]=json_decode(file_get_contents("config/parallel/".$scan[$i]));
			$OP[$ct]->{'ID'}=$ct;
			$OP[$ct]->{'INUSE'}=0;
		}
		$FakeCt=0;
		if($ExtraScanners){
			$sample=scandir('inc/scanhelp');
			unset($sample[0]);unset($sample[1]);// Delete ./ and ../ from the list
			foreach($sample as $key => $val){
				$ct=count($OP);
				$help=file_get_contents('inc/scanhelp/'.$val);
				$help=substr($help,strpos($help,'Options specific to device `')+28);
				$help=substr($help,0,strpos($help,"':"));
				$OP[$ct]=json_decode('{"ID":'.$ct.',"INUSE":0,"DEVICE":"'.$help.'","NAME":"'.$val.'"}');
				$FakeCt++;
			}
		}
		for($i=0,$max=count($OP);$i<$max;$i++){//get scanner specific data
			if($i<$max-$FakeCt)
				$help=exe("scanimage --help -d \"".addslashes($OP[$i]->{"DEVICE"})."\"",true);
			else
				$help=file_get_contents('inc/scanhelp/'.$OP[$i]->{"NAME"});
			// get Source
			$sources=substr($help,strpos($help,'--source ')+9);
			$defSource=substr($sources,strpos($sources,' [')+2);
			$defSource=substr($defSource,0,strpos($defSource,']'));
			$OP[$i]->{"SOURCE"}=strtolower($defSource)=='inactive'?'Inactive':substr($sources,0,strpos($sources,' ['));
			$sources=explode('|',$OP[$i]->{"SOURCE"});

			foreach($sources as $key => $val){
				if($val=='Inactive'||$val==$defSource)
					$help2=$help;
				else{
					if($i<$max-$FakeCt)
						$help2=exe("scanimage --help -d \"".addslashes($OP[$i]->{"DEVICE"})."\" --source \"$val\"",true);
					else{
						$help2=file_get_contents('inc/scanhelp/'.$OP[$i]->{"NAME"});
						exe("echo 'scanimage --help -d \"SIMULATED_$i-$key\" --source \"$val\"'",true);
					}
				}
				// get dpi
				$res=substr($help2,strpos($help2,'--resolution ')+13);
				$res=substr($res,0,strpos($res,'dpi'));
				if(is_int(strpos($res,".."))){//range of sizes of not it is a list (i want list form)
					$res=explode('..',$res);
					$arr=Array();
					array_push($arr,$res[0]);
					for($x=intval(ceil(($res[0]+1)/100).'00');$x<=$res[1];$x+=100){
						array_push($arr,$x);
					}
					$res=implode("|",$arr);
				}
				else if(is_int(strpos($res,"auto||"))){
					$res='auto'.substr($res,5);
				}
				$OP[$i]->{"DPI-$val"}=$res;
				if($val=='Inactive')
					break;
			}

			// get color modes
			$modes=substr($help,strpos($help,'--mode ')+7);
			$OP[$i]->{"MODE"}=substr($modes,0,strpos($modes,' ['));
			// get bay width
			$width=substr($help,strpos($help,' -x ')+4);
			$width=substr($width,0,strpos($width,'mm'));
			$OP[$i]->{"WIDTH"}=floatval(substr($width,strpos($width,'..')+2));
			// get bay height
			$height=substr($help,strpos($help,' -y ')+4);
			$height=substr($height,0,strpos($height,'mm'));
			$OP[$i]->{"HEIGHT"}=floatval(substr($height,strpos($height,'..')+2));
			if(!is_bool(strpos($OP[$i]->{"DEVICE"},"Deskjet_2050_J510_series"))){// Dirty hack to make scanner work on this model (sane bug?)
				$OP[$i]->{"HEIGHT"}=297.01068878173;# that is as close as php will go without rounding true size is 297.01068878173825282^9
			}
			// Get device vendor ID and product ID (Bug #13)
			$dev=strpos($OP[$i]->{"DEVICE"},"libusb:");
			if(is_bool($dev))
				$OP[$i]->{"UUID"}=NULL;
			else{
				$dev=substr($OP[$i]->{"DEVICE"},$dev+7,7);
				$dev=exe("lsusb -s '$dev'",true);
				$OP[$i]->{"UUID"}=substr($dev,strpos($dev,"ID ")+3,9);
			}
			// lamp on/off
			//$OP[$i]->{"LAMP"}=(!is_bool(strpos($help,'--lamp-switch[=(yes|no)]'))&&!is_bool(strpos($help,'--lamp-off-at-exit[=(yes|no)]')))?true:false;
		}
		$save=SaveFile("config/scanners.json",json_encode($OP));
		$CANNERS='<table border="1" align="center"><tbody><tr><th>Name</th><th>Device</th></tr>';
		for($i=0,$max=count($OP);$i<$max;$i++){
			$CANNERS.='<tr><td>'.html($OP[$i]->{"NAME"}).'</td><td>'.html($OP[$i]->{"DEVICE"}).'</td></tr>';
		}
		$CANNERS.='<tr><td colspan="2" align="center">Missing a scanner? Make sure the scanner is plugged in and turned on.<br/>You may have to use the <a href="index.php?page=Access%20Enabler">Access Enabler</a>.<br/><a href="index.php?action=Parallel-Form">[Click here for parallel-port scanners]</a>'.
			($save?'':'</td></tr><tr><td colspan="2" style="color:red;font-weight:bold;text-align:center;">Bad news: <code>'.$user.'</code> does not have permission to write files to the <code>'.html(getcwd()).'/config</code> folder.<br/><code>sudo chown '.$user.' '.html(getcwd()).'/config</code>').
			'</td></tr>';
		$CANNERS.='</tbod></table>';
		if($max>1){
			$CANNERS.='<small>It looks like you have more than one scanner. You can change the default scanner on the <a href="index.php?page=Device%20Notes">Scanner List</a> page if you want.</small>';
		}
		if(count($OP)==0)
			Print_Message("No Scanners Found","There were no scanners found on this server. Make sure the scanners are plugged in and turned on. The scanner must also be supported by SANE.<br/>".
				"<a href=\"index.php?action=Parallel-Form\">[Click here for parallel-port scanners]</a><br/>".
				"If it is supported by sane and still does not showup (usb) or does not work (parallel) you may need to use the <a href=\"index.php?page=Access%20Enabler\">Access Enabler</a>".
				(in_array('lp',explode(' ',exe('groups www-data',true)))===false?'<br/>It appears www-data is not in the lp group did you read the <a href="index.php?page=About">Installation Notes</a>?':''),'center');
		else
			Print_Message("Scanners Found:",$CANNERS,'center');
	}
}
# ****************
# Parallel Port Scanner Configuration
# ****************
else if($ACTION=="Parallel-Form"){
	InsertHeader("Parallel Port Scanner Setup");
	$file=fileSafe(Get_Values('file'));
	$name=Get_Values('name');
	$device=Get_Values('device');
	if($file!=null){
		$file=addslashes($file);
		unlink("config/parallel/$file");
	}
	else if($name!=null&&$device!=null){
		$can=scandir('config/parallel');
		$int=0;
		while(in_array($int.'.json',$can)){
			$int++;
		}
		$save=SaveFile('config/parallel/'.$int.'.json','{"NAME":"'.addslashes($name).'","DEVICE":"'.addslashes($device).'"}');
	}
	$scan=scandir('config/parallel');
	include "inc/parallel.php";
	Footer();
	if($name!=null&&$device!=null&&$file==null){
		if(!$save)
			Print_Message("Permissions Error:","<code>$user</code> does not have permission to write files to <code>".html(getcwd())."/config/parallel</code><br/>".
				"<code>sudo chown $user ".html(getcwd())."/config/parallel</code>",'center');
	}
}
# ****************
# Release Notes
# ****************
else if($PAGE=="About"){
	InsertHeader("Release Notes");
	include "inc/about.php";
	Footer();
}
# ***************
# Paper Manager
# ***************
else if($PAGE=="Paper Manager"){
	InsertHeader("Paper Manager");
	include "inc/paper.php";
	Footer();
}
# ****************
# Access Enabler
# ****************
else if($PAGE=="Access Enabler"){
	InsertHeader("Release Notes");
	include "inc/enabler.php";
	Footer();
}
# ****************
# Scanner Info
# ****************
else if($PAGE=="Device Notes"){
	$id=Get_Values('id');
	if($id!=null){
		$id=intval($id);
		if(is_int($id)&&file_exists("config/scanners.json")){
			$CANNERS=json_decode(file_get_contents('config/scanners.json'));
			$s=count($CANNERS);
			if($s>$id){
				for($i=0;$i<$s;$i++){
					if(isset($CANNERS[$i]->{"SELECTED"}))
						unset($CANNERS[$i]->{"SELECTED"});
				}
				$CANNERS[$id]->{"SELECTED"}=1;
				SaveFile("config/scanners.json",json_encode($CANNERS));
			}
		}
	}
	if(isset($ACTION)){
		InsertHeader("Device Info");
		// bug #13 START
		$CANNERS=json_decode(file_get_contents('config/scanners.json'));
		foreach($CANNERS as $key){
			if($key->{"DEVICE"}==$ACTION){
				if(!is_null($key->{"UUID"})){
					$ACTION=uuid2bus($key);
				}
				break;
			}
		}
		// bug #13 END
		$help=exe("scanimage --help -d \"".addslashes($ACTION)."\"",true);
		echo "<div class=\"box box-full\"><h2>$ACTION</h2><pre>".$help."</pre></div>";
	}
	else{
		InsertHeader("Device List");
		if(!isset($CANNERS)){
			if(file_exists("config/scanners.json"))
				$CANNERS=json_decode(file_get_contents("config/scanners.json"));
			else
				$CANNERS=json_decode('[]');
		}
		else{
			Print_Message("New Default Scanner:",$CANNERS[$id]->{"DEVICE"},'center');
		}
		echo "<div class=\"box box-full\"><h2>Installed Device List</h2>".'<a style="margin-left:5px;" href="index.php?page=Config&action=Search-For-Scanners" onclick="printMsg(\'Searching For Scanners\',\'Please Wait...\',\'center\',0);">Scan for Devices</a>'."<ul>";
		for($i=0,$max=count($CANNERS);$i<$max;$i++){
			$name=html($CANNERS[$i]->{"NAME"});
			$DEVICE=html($CANNERS[$i]->{"DEVICE"});
			$WIDTH=round($CANNERS[$i]->{"WIDTH"}/25.4,2);
			$HEIGHT=round($CANNERS[$i]->{"HEIGHT"}/25.4,2);
			$res='';
			$sources=explode('|',$CANNERS[$i]->{"SOURCE"});
			for($x=0,$ct=count($sources);$x<$ct;$x++){
				$val=$sources[$x];
				$DPI=explode('|',$CANNERS[$i]->{"DPI-$val"});
				$res.="<li>Scanner resolution is ".($DPI[0]=='auto'?$DPI[1]:$DPI[0])." DPI to ".number_format($DPI[count($DPI)-1])." DPI".($ct==1?'':" for $val")."</li>";
			}
			echo "<li>$name<ul><li><a onclick=\"printMsg('Loading','Please Wait...','center',0);\" href=\"index.php?page=Device%20Notes&action=$DEVICE\"><code>$DEVICE</code></a></li>".
				"<li>Bay width is $WIDTH\"</li>".
				"<li>Bay height is $HEIGHT\"</li>".
				$res.
				(isset($CANNERS[$i]->{"SELECTED"})?'':"<li><a href=\"index.php?page=Device%20Notes&id=$i\">Set as default scanner</a></li>")."</ul></li>";
		}
		echo '</ul></div>';
	}
	Footer();
}
# ****************
# View Page
# ****************
else if($PAGE=="View"){
	InsertHeader("View File");
	$file=fileSafe(Get_Values('file'));
	include "inc/view.php";
	Footer();
}
# ***************
# Edit Page
# ***************
else if($PAGE=="Edit"){
	InsertHeader("Edit Image");
	$file=fileSafe(Get_Values('file'));
	if($file!=null){
		if(substr($file,-3)=="txt"){
			$preview="Preview_".substr($file,0,-3)."jpg";
			if(isset($_POST['file-text'])){ // 1_Mar_8_2012~11-22-41.txt  1_Mar_8_2012~11-22-41-edit-42.txt
				$edit=strpos($file,'-edit-');
				$name=(is_bool($edit)?substr($file,0,-4):substr($file,0,$edit));
				$int=1;
				while(file_exists("scans/Preview_$name-edit-$int.jpg")){
					$int++;
				}
				copy("scans/$preview","scans/Preview_$name-edit-$int.jpg");
				if(SaveFile("scans/Scan_$name-edit-$int.txt",$_POST['file-text'])){
					Print_Message("Saved","You hve successfully edited $file",'center');
					$file="$name-edit-$int.txt";
				}
			}
			echo "<div class=\"box box-full\" id=\"text-editor\"><div id=\"preview_links\"></div>".
			"<img src=\"scans/$preview\"><br/>".
			'<form action="index.php?page=Edit&file='.$file.'" method="POST"><textarea name="file-text">'.html(file_get_contents("scans/Scan_$file"))."</textarea><br/>".
			'<input value="Save" type="submit"/><input type="button" value="Cancel" onclick="history.go(-1);"/></forum></div>';
			Update_Links("Scan_$file",$PAGE);
		}
		else{
			if(Get_Values('edit')!=null){
				if(file_exists("scans/Scan_$file")){
					$langs=findLangs();
					if(!validNum(Array($WIDTH,$HEIGHT,$X_1,$Y_1,$BRIGHT,$CONTRAST,$SCALE,$ROTATE))||
					  ($FILETYPE!="txt"&&$FILETYPE!="png"&&$FILETYPE!="tiff"&&$FILETYPE!="jpg")||
					  !in_array($LANG,$langs)){
						echo "<h1>No, you can not do that</h1>Input data is invalid and most likely an attempt to run malicious code on the server <i>denied</i>";
						Footer();
						quit();
					}
					$tmpFile="/tmp/Scan_".addslashes($file);
					$file='scans/Scan_'.addslashes($file);
					copy($file,$tmpFile);
					if($MODE!='color'&&$MODE!=null){
						if($MODE=='gray')
							exe("convert \"$tmpFile\" -colorspace Gray \"$tmpFile\"",true);
						else
							exe("convert \"$tmpFile\" -monochrome \"$tmpFile\"",true);
					}
					if($BRIGHT!="0"||$CONTRAST!="0"){
						exe("convert \"$tmpFile\" -brightness-contrast $BRIGHT".'x'."$CONTRAST \"$tmpFile\"",true);
					}
					if($WIDTH!="0"&&$HEIGHT!="0"&&$WIDTH!=null&&$HEIGHT!=null){
						$TRUE=explode("x",exe("identify -format '%wx%h' \"$file\"",true));
						$TRUE_W=$TRUE[0];
						$TRUE_H=$TRUE[1];
						$WIDTH=round($WIDTH/$M_WIDTH*$TRUE_W);
						$HEIGHT=round($HEIGHT/$M_HEIGHT*$TRUE_H);
						$X_1=round($X_1/$M_WIDTH*$TRUE_W);
						$Y_1=round($Y_1/$M_HEIGHT*$TRUE_H);
						exe("convert \"$tmpFile\" -crop \"$WIDTH x $HEIGHT + $X_1 + $Y_1\" +repage \"$tmpFile\"",true);
					}

					if($SCALE!="100"){
						exe("convert \"$tmpFile\" -scale \"".addslashes($SCALE)."%\" \"$tmpFile\"",true);
					}
					if($ROTATE!="0"){
						$ROTATE=addslashes($ROTATE);
						exe("convert \"$tmpFile\" -rotate \"$ROTATE\" \"$tmpFile\"",true);
					}
					exe("convert \"$tmpFile\" -alpha off \"$tmpFile\"",true);
					$file=substr($file,11);
					$edit=strpos($file,'-edit-');
					$name=(is_bool($edit)?substr($file,0,-4):substr($file,0,$edit));
					$ext=substr($file,strrpos($file,'.')+1);
					$int=1;
					while(file_exists("scans/Preview_$name-edit-$int.jpg")){
						$int++;
					}
					$file="scans/Scan_$name-edit-$int.$ext";//scan
					$name=str_replace("scans/Scan_","scans/Preview_",$file);//preview
					if($FILETYPE==substr($file,strrpos($file,'.')+1)){
						@rename($tmpFile,$file);//incorrct access denied message is generated
						if(file_exists($tmpFile)&&!file_exists($file)){//just incase it becomes accurate
							#exe("mv \"$tmpFile\" \"$file\"",true);
							copy($tmpFile,$file);
							unlink($tmpFile);
						}
					}
					else if($FILETYPE!='txt'){
						$file=substr($file,0,strrpos($file,'.')+1).addslashes($FILETYPE);
						exe("convert \"$tmpFile\" \"$file\"",true);
					}
					else{
						$t=time();
						$S_FILENAMET=substr($file,0,strrpos($file,'.'));
						exe("convert \"$tmpFile\" -fx '(r+g+b)/3' \"/tmp/edit_scan_file$t.tif\"",true);
						exe("tesseract \"/tmp/edit_scan_file$t.tif\" \"$S_FILENAMET\" -l \"$LANG\"",true);
						unlink("/tmp/edit_scan_file$t.tif");
						if(!file_exists("$S_FILENAMET.txt"))//in case tesseract fails
							SaveFile("$S_FILENAMET.txt","");
					}
					$FILE=substr($name,0,strrpos($name,'.')+1).'jpg';//preview
					if($FILETYPE!='txt'){
						exe("convert \"$file\" -scale 450x471 \"$FILE\"",true);
						$file=substr($file,11);
					}
					else{
						exe("convert \"$tmpFile\" -scale 450x471 \"$FILE\"",true);
						unlink($tmpFile);
						$file=substr($file,11,strrpos($file,'.')-10).'txt';
					}
				}
			}
			if(file_exists("scans/Scan_$file"))
				include("inc/edit.php");
			else{
				Print_Message("404 Not Found","It appears that <code>$file</code> has been deleted.",'center');
			}
		}
	}
	else{
		if(count(scandir("scans"))==2){
			Print_Message("No Images","All files have been removed. There are no scanned images to display.",'center');
		}
		else{
			Print_Message("No File Specified","Please select a file to edit",'center');
			$FILES=explode("\n",substr(exe('cd "scans"; ls Preview*',true),0,-1));
			for($i=0,$max=count($FILES);$i<$max;$i++){
				$FILE=addslashes(substr($FILES[$i],7,-3));
				$FILE=substr(exe('cd "scans"; ls Scan'.$FILE.'*',true),5);//Should only have one file listed
				$IMAGE=$FILES[$i];
				include "inc/editscans.php";
			}
		}
	}
	checkFreeSpace($FreeSpaceWarn);
	Footer();
}
# ****************
# Scanner Page
# ****************
else{
	InsertHeader("Scan Image");
	if(file_exists("config/scanners.json")){
		$CANNERS=json_decode(file_get_contents("config/scanners.json"));
	}
	else{
		$CANNERS=json_decode('[]');
	}
	if(strlen($SAVEAS)>0||$ACTION=="Scan Image"){
		$langs=findLangs();
		if(!validNum(Array($SCANNER,$BRIGHT,$CONTRAST,$SCALE,$ROTATE))||!in_array($LANG,$langs)||!in_array($QUALITY,explode("|",$CANNERS[$SCANNER]->{"DPI-$SOURCE"}))){//security check
			echo "<h1>No, you can not do that</h1>Input data is invalid and most likely an attempt to run malicious code on the server <i>denied</i>";
			Footer();
			quit();
		}
	}
	if(strlen($SAVEAS)>0){ # Save settings to conf file
		if(strlen($SET_SAVE)>0){
			$ACTION="Save Set";
			$SCANNER=addslashes($SCANNER);
			$SIZE=addslashes($SIZE);
			$QUALITY=addslashes($QUALITY);
			$ORNT=addslashes($ORNT);
			$MODE=addslashes($MODE);
			$FILETYPE=addslashes($FILETYPE);
			if(file_exists("config/settings.json")){
				$file=json_decode(file_get_contents("config/settings.json"));
				$file->{$SET_SAVE}=json_decode("{\"scanner\":$SCANNER,\"source\":\"$SOURCE\",\"quality\":$QUALITY,\"size\":\"$SIZE\",\"ornt\":\"$ORNT\",\"mode\":\"$MODE\",\"bright\":$BRIGHT,\"contrast\":$CONTRAST,\"rotate\":$ROTATE,\"scale\":$SCALE,\"filetype\":\"$FILETYPE\",\"lang\":\"$LANG\"}");
				SaveFile("config/settings.json",json_encode($file));
			}
			else{
				$line='{"'.$SET_SAVE.'":{"scanner":'.$SCANNER.',"quality":'.$QUALITY.',"size":"'.$SIZE.'","ornt":"'.$ORNT.'","mode":"'.$MODE.'","bright":'.$BRIGHT.',"contrast":'.$CONTRAST.',"rotate":'.$ROTATE.',"scale":'.$SCALE.',"filetype":"'.$FILETYPE.'","lang":"'.$LANG.'"}}';
				if(!SaveFile("config/settings.json",$line)){
					Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
				}
			}
		}
	}

	if(count($CANNERS)==0){ # Add scanners to scanner list
		Print_Message("No Scanners Found",'There aren\'t any scanners setup yet! Go to the <a href="index.php?page=Config">config page</a> to setup scanners.','center');
	}
	else{
		if(file_exists('config/settings.json'))
			$file=file_get_contents('config/settings.json');
		else
			$file='{}';
		include "inc/scan.php";
	}
	echo '<script type="text/javascript">scanReset();</script>';
	if(strlen($ACTION)>0){ # Only update values back to form if they aren't empty
		Put_Values();
	}
	Footer();

	if($ACTION=="Scan Image"){# Check to see if scanner is in use
		$SCAN_IN_USE=$CANNERS[$SCANNER]->{"INUSE"};
		if($SCAN_IN_USE==1){
			Print_Message("Scanner in Use","The scanner you are trying to use is currently in use. Please try again later...",'center');
			$ACTION="Do Not Scan";
		}
	}

	if($ACTION=="Scan Image"){ # Scan Image!
		if(is_nan($SCANNER)){
			Print_Message("Error:","<code>$SCANNER</code> is not a number, you must be trying to attack the server",'center');
			quit();
		}
		$CANDIR="/tmp/scandir$SCANNER";

		if(is_dir($CANDIR)){ # Make sure we can save the scan
			$trash=scandir($CANDIR);
			unset($trash[0]);unset($trash[1]);// Delete ./ and ../ from the list
			foreach($trash as $key)
				@unlink($key);
			rmdir($CANDIR);
			if(is_dir($CANDIR)){
				Print_Message("Permission Error:","<code>$user</code> does not have permission to delete <code>$CANDIR</code>.<br/>".
					"This can be easly fixed by running the following command at the Scanner Server.<br/><code>rm -r $CANDIR</code><br/>".
					"Once you have done that you can press F5 (Refresh) to try again with your prevously entered settings.",'center');
				quit();
			}
		}

		if(!@mkdir("$CANDIR")){
			Print_Message('Error',"Unable to create directory $CANDIR.<br>Why does <code>$user</code> not have permission?",'center');
			quit();
		}

		$sizes=explode('-',$SIZE);
		if((!validNum(Array($SCANNER,$WIDTH,$HEIGHT,$X_1,$Y_1,$BRIGHT,$CONTRAST,$SCALE,$ROTATE)))||
		   (count($sizes)!=2&&$SIZE!='full')||
		   (!in_array($MODE,explode('|',$CANNERS[$SCANNER]->{"MODE"})))||
		   (!in_array($SOURCE,explode('|',$CANNERS[$SCANNER]->{"SOURCE"})))||
		   ($FILETYPE!="txt"&&$FILETYPE!="png"&&$FILETYPE!="tiff"&&$FILETYPE!="jpg")){
			Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server. <i>Denied</i>",'center');
			quit();
		}
		else if((!is_numeric($sizes[0])||!is_numeric($sizes[1]))&&$SIZE!='full'){
			Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server. <i>Denied</i>",'center');
			quit();
		}

		# Scanner in Use
		$CANNERS[$SCANNER]->{"INUSE"}=1;
		if(!SaveFile("config/scanners.json",json_encode($CANNERS))){
			Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
			quit();
		}
		$X=0;
		$Y=0;
		# Get Device
		$DEVICE=addslashes($CANNERS[$SCANNER]->{"DEVICE"});

		$scanner_w=$CANNERS[$SCANNER]->{"WIDTH"};
		$scanner_h=$CANNERS[$SCANNER]->{"HEIGHT"};

		$lastORNT=Get_Values('ornt0');
		if($lastORNT!=$ORNT&&$lastORNT!=null&&$SIZE!="full"){
			$WIDTH="0";
			$HEIGHT="0";
		}
		# Set size & orientation of scan
		if($WIDTH!="0"&&$HEIGHT!="0"){// selected scan
			if($SIZE=="full"){
				$TRUE_W=$scanner_w;
				$TRUE_H=$scanner_h;
			}
			else{
				if($ORNT=="vert"){
					$TRUE_W=$sizes[0];
					$TRUE_H=$sizes[1];
				}
				else{
					$TRUE_W=$sizes[1];
					$TRUE_H=$sizes[0];
				}
			}
			$WIDTH=$WIDTH/$M_WIDTH*$TRUE_W;
			$HEIGHT=$HEIGHT/$M_HEIGHT*$TRUE_H;
			$X=$X_1/$M_WIDTH*$TRUE_W;
			$Y=$Y_1/$M_HEIGHT*$TRUE_H;
			$SIZE_X=$WIDTH;
			$SIZE_Y=$HEIGHT;
		}
		else if($SIZE=="full"){// full scan
			$SIZE_X=$scanner_w;
			$SIZE_Y=$scanner_h;
		}
		else if($sizes[0]<=$scanner_w&&$sizes[1]<=$scanner_h&&$sizes[1]<=$scanner_w&&$sizes[0]<=$scanner_h){// fits both ways
			if($ORNT!="vert"){
				$SIZE_X=$sizes[0];
				$SIZE_Y=$sizes[1];
			}
			else{
				$SIZE_X=$sizes[0];
				$SIZE_Y=$sizes[1];
			}
		}
		else if($sizes[0]<=$scanner_w&&$sizes[1]<=$scanner_h){//fits tall way
			$SIZE_X=$sizes[0];
			$SIZE_Y=$sizes[1];
		}
		else if($sizes[1]<=$scanner_w&&$sizes[0]<=$scanner_h){//fits wide way
			$SIZE_X=$sizes[1];
			$SIZE_Y=$sizes[0];
		}
		else{
			Print_Message("Sorry...","The scan page should not have offered this page size as it does not fit in your scanner.<br/>That paper will not fit in the scanner running a full scan.".
				"<br/>Scanner width is $scanner_w mm<br/>Scanner height is $scanner_h mm".
				"<br/>Paper width is ".$sizes[0]." mm<br/>Paper height is ".$sizes[1]." mm",'center');
			$SIZE="-x $scanner_w -y $scanner_h";
			$SIZE_X=$scanner_w;
			$SIZE_Y=$scanner_h;
		}
		$LAMP='';
		/*if($CANNERS[$SCANNER]->{'LAMP'}===true){
			$LAMP='--lamp-switch=yes --lamp-off-at-exit=yes ';
		}*/

		$SOURCE=($SOURCE=='Inactive')?'':"--source $SOURCE ";
		if(!is_null($CANNERS[$SCANNER]->{"UUID"})){// bug #13
			$DEVICE=uuid2bus($CANNERS[$SCANNER]);
			$CANNERS[$SCANNER]->{"DEVICE"}=$DEVICE;
		}
		$cmd="scanimage -d \"$DEVICE\" -l $X -t $Y -x $SIZE_X -y $SIZE_Y --resolution $QUALITY $SOURCE--mode $MODE $LAMP--format=ppm";
		if($SOURCE=='ADF'||$SOURCE=='Automatic Document Feeder') # Multi-page scan
			exe("cd $CANDIR;$cmd --batch",true);// be careful with this, doing this without a ADF feeder will result in scanning the flatbed over and over, include --batch-count=3 for testing
		else # Single page scan
			exe("$cmd > \"$CANDIR/scan_file$SCANNER.ppm\"",false);

		if(file_exists("$CANDIR/scan_file$SCANNER.ppm")){
			if(Get_Values('size')=='full'&&filesize("$CANDIR/scan_file$SCANNER.ppm")==0){
				exe("echo \"Scan Failed...\"",true);
				exe("echo \"Maybe this scanner does not report it size correctly, maybe the default scan size will work it may or may not be a full scan.\"",true);
				exe("echo \"If it is not a full scan you are welcome to manually edit your $here/config/scanners.json file with the correct size.\"",true);
				@unlink("$CANDIR/scan_file$SCANNER.ppm");
				exe("echo \"Attempting to scan without forcing full scan\"");
				exe("scanimage -d \"$DEVICE\" --resolution $QUALITY --mode $MODE $LAMP--format=ppm > \"$CANDIR/scan_file$SCANNER.ppm\"",false);
			}
		}
		$files=scandir($CANDIR);
		for($i=2,$ct=count($files);$i<$ct;$i++){
			$SCAN="$CANDIR/".$files[$i];

			# Dated Filename for scan image & preview image
			$FILENAME=date("M_j_Y~G-i-s",filemtime($SCAN));
			$S_FILENAME="Scan_$SCANNER"."_"."$FILENAME.$FILETYPE";
			$P_FILENAME="Preview_$SCANNER"."_"."$FILENAME.jpg";

			# Adjust Brightness
			if($BRIGHT!="0"||$CONTRAST!="0"){
				exe("convert \"$SCAN\" -brightness-contrast $BRIGHT".'x'."$CONTRAST \"$SCAN\"",true);
			}

			# Rotate Image
			if($ROTATE!="0"){
				exe("convert \"$SCAN\" -rotate \"$ROTATE\" \"$SCAN\"",true);
			}

			# Scale Image
			if($SCALE!="100"){
				exe("convert \"$SCAN\" -scale $SCALE% \"$SCAN\"",true);
			}

			# Generate Preview Image
			exe("convert \"$SCAN\" -scale 450x471 \"scans/$P_FILENAME\"",true);

			# Convert scan to file type
			if($FILETYPE=="txt"){
				$S_FILENAMET=substr($S_FILENAME,0,strrpos($S_FILENAME,'.'));
				exe("convert \"$SCAN\" -fx '(r+g+b)/3' \"/tmp/_scan_file$SCANNER.tif\"",true);
				exe("tesseract \"/tmp/_scan_file$SCANNER.tif\" \"scans/$S_FILENAMET\" -l \"$LANG\"",true);
				unlink("/tmp/_scan_file$SCANNER.tif");
				if(!file_exists("scans/$S_FILENAMET.txt"))//in case tesseract fails
					SaveFile("scans/$S_FILENAMET.txt","");
			}
			else{
				exe("convert \"$SCAN\" -alpha off \"scans/$S_FILENAME\"",true);
			}
			@unlink("$SCAN");
		}
		@rmdir($CANDIR);

		# Remove Crop Option / set last scan / remember last orientation
		echo '<script type="text/javascript">';
		if(($WIDTH!="0"&&$HEIGHT!="0")||$ROTATE!="0"){
			echo '$(document).ready(function(){stripSelect();});';
		}
		else{
			echo "Set_Cookie( 'scan', '$S_FILENAME', 1, '/', '', '' );".
				"Set_Cookie( 'preview', '$P_FILENAME', 1, '/', '', '' );".
				"Set_Cookie( 'scanner', '$SCANNER', 1, '/', '', '' );";
		}
		$ORNT=($ORNT==''?'vert':$ORNT);
		echo "var ornt=document.createElement('input');ornt.name='ornt0';ornt.value='$ORNT';ornt.type='hidden';document.scanning.appendChild(ornt);".
			"var p=document.createElement('p');p.innerHTML='<small>Changing orientation will void select region.</small>';document.getElementById('opt').appendChild(p);</script>";

		# Check if image is empty and post error, otherwise post image to page
		if(!file_exists("scans/$P_FILENAME")){
			Print_Message("Could not scan",'<p style="text-align:left;margin:0;">This is can be cauesed by one or more of the following:</p>'.
				'<ul><li>The scanner is not on.</li><li>The scanner is not connected to the computer.</li>'.
				'<li>You need to run the <a href="index.php?page=Access%20Enabler">Access Enabler</a>.</li>'.
				(file_exists("/tmp/scan_file$SCANNER.ppm")?"<li>Removeing <code>/tmp/scan_file$SCANNER.ppm</code> may help.</li>":'').
				'<li><code>'.$user.'</code> does not have permission to write files to the <code>'.getcwd().'/scans</code> folder.</li>'.
				'<li>You may have to <a href="index.php?page=Config">re-configure</a> the scanner.</li></ul>'.$notes,'left');
		}
		else{
			Update_Links($S_FILENAME,$PAGE);
			Update_Preview("scans/$P_FILENAME");
		}
		if(count($CANNERS)>1)
			$CANNERS=json_decode(file_get_contents("config/scanners.json"));
		$CANNERS[$SCANNER]->{"INUSE"}=0;
		SaveFile("config/scanners.json",json_encode($CANNERS));
		if($ct>3)
			Print_Message("Info",'Multiple scans made, only displaying last one, go to <a href="index.php?page=Scans">Scanned Files</a> for the rest','center');
	}
	echo '<script type="text/javascript">if(document.scanning)document.scanning.action.disabled=false;</script>';
	checkFreeSpace($FreeSpaceWarn);
}
echo '<script type="text/javascript">Debug("'.rawurlencode(html($debug)).html($here."$ ").'",'.(isset($_COOKIE["debug"])?$_COOKIE["debug"]:'false').');</script>';
?></body></html>
