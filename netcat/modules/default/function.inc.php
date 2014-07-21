<?php
global $ru_monthes, $day_of_week;
$ru_monthes = array(
	'01' => 'января',
	'02' => 'февраля',
	'03' => 'марта',
	'04' => 'апреля',
	'05' => 'мая',
	'06' => 'июня',
	'07' => 'июля',
	'08' => 'августа',
	'09' => 'сентября',
	'10' => 'октября',
	'11' => 'ноября',
	'12' => 'декабря'
);
$day_of_week = array( "воскресенье", "понедельник", "вторник", "среда", "четверг", "пятница", "суббота" );

function imageResize( $field, $width, $height, $mode ){
	global $nc_core, $classID, $message, $db;
	$field_id = $db->get_var( "SELECT Field_ID FROM Field WHERE Class_ID='{$classID}' AND Field_Name='".mysql_real_escape_string( $field )."'");
	if( $_FILES['f_'.$field] && !$_FILES['f_'.$field]['error'] ){
		require_once $nc_core->INCLUDE_FOLDER.'classes/nc_imagetransform.class.php';
		$pic = $nc_core->DOCUMENT_ROOT.nc_file_path( $classID, $message, $field );
		nc_ImageTransform::imgResize( $pic, $pic, $width, $height, $mode, NULL, 90, $message, $field_id );
	}
}

function inflect( $text ){
		$text = trim( $text );
		$inflectxml = file_get_contents( "http://export.yandex.ru/inflect.xml?name=".urlencode( $text ) );
		$inflects = array();
		if( preg_match_all( '%<inflection case="(\d)">(.*?)</inflection>%ims', $inflectxml, $m ) ){
			for( $i=0; $i<count($m[0]); $i++ ){
				$inflects[$m[1][$i]] = trim( $m[2][$i] );
			}
		}
		return $inflects;
}

function formatPrice( $price ){
	return preg_replace('/(?<=[0-9])(?=(?:[0-9]{3})+(?![0-9]))/', '&nbsp;', $price );
}

function humanDate( $dateField, $showTime = false, $timeSeparator = ", " ){
	global $ru_monthes;
	if( preg_match( '%^(\d{4})-(\d\d)-(\d\d)(\s+(\d\d):(\d\d):(\d\d))?$%ims', trim( $dateField ), $m ) ){
		//проверяем, получили ли мы действительно неткатовское значение даты
		$year = $m[1];
		$month = $m[2];
		$day = $m[3];
		if( !trim($m[4]) ) $showTime = false;
		$hours = $m[5];
		$minutes = $m[6];
		$seconds = $m[7];
	} else return $dateField;
	if( date("Ymd")==$year.$month.$day ) $dateString = "сегодня";
	else if( date("Ymd", time()-86400)==$year.$month.$day ) $dateString = "вчера";
	else $dateString = $day." ".$ru_monthes[$month]." ".$year."";
	if( $showTime ) $dateString .= $timeSeparator."{$hours}:{$minutes}";
	return $dateString;
}

function firstSentence( $text, $maxlen = 100, $tobecon = '...' ){
	$text = trim( preg_replace( '/\s+/ims', ' ', $text ) );
	if( preg_match( '/^(.{1,'.$maxlen.'}\.)\s+[“”"«&А-ЯA-Z].*?$/msu', $text, $m ) ) $result = trim( $m[1] );
	else if( preg_match( '/^(.{1,'.$maxlen.'})(\s.*?)?$/imsu', $text, $m ) ){
		$result = trim( $m[1] );
		if( strlen( $text ) > strlen( $result ) ) $result .= $tobecon;
	}
	else $result = NULL;
	return $result;
}

define( _ANTICAPTCHA_NOTVALID, '<p>Система антиспама заподозрила спам в Вашем сообщении, если это не так, то просто нажмите еще раз кнопку &laquo;Отправить&raquo;</p>' );
function anticaptcha( $uri, $check = false, $ac_id = "ac" ){
	//session_start();
	if( $check ){
		$result = $_SESSION['anticaptcha'][$uri] && $_POST['ac']==$_SESSION['anticaptcha'][$uri];
		$_SESSION['anticaptcha'][$uri] = NULL;
		return $result;
	} else {
		$_SESSION['anticaptcha'][$uri] = md5( time() );
		if( $_POST['a'] ){
			ob_end_clean();
			echo preg_replace('/(.)(.)/sim', '$2$1', $_SESSION['anticaptcha'][$uri] );
			exit;
		} 
		return '<input type="hidden" name="ac" id="'.$ac_id.'" value="0"><script type="text/javascript">$.post("'.$uri.'",{"a":1},function(d){$("#'.$ac_id.'").val(d.replace(/(.)(.)/img, "$2$1"));});</script>';
	}
}

function update_row( $table, $row, $where ){
	global $db;
	if( !$row || !is_array( $row ) || !count( $row ) ) return NULL;
	$query = "UPDATE `{$table}` SET ";
	$comma = false;
	foreach( $row as $k=>$v ){
		if( $comma ) $query .= ",";
		$comma = true;
		if( $v===NULL ) $query .= "`{$k}`=NULL";
		else $query .= "`{$k}`='".mysql_real_escape_string( $v )."'";
	}
	$query .= " WHERE {$where}";
	$db->query( $query );
	return $db->insert_id;
}

function insert_row( $table, $row ){
	global $db;
	$db->insert_id = NULL;
	$query = "INSERT INTO `{$table}` SET ";
	$comma = false;
	foreach( $row as $k=>$v ){
		if( $comma ) $query .= ",";
		$comma = true;
		if( $v===NULL ) $query .= "`{$k}`=NULL";
		else $query .= "`{$k}`='".mysql_real_escape_string( $v )."'";
	}
	$db->query( $query );
	return $db->insert_id;
}

function translit( $string, $url = true ) {
	$russians = array("а","б","в","г","д","е","ё","ж","з","и","й","к","л","м","н","о","п","р","с","т","у","ф","х","ц","ч","ш","щ","ъ","ы","ь","э","ю","я","А","Б","В","Г","Д","Е","Ё","Ж","З","И","Й","К","Л","М","Н","О","П","Р","С","Т","У","Ф","Х","Ц","Ч","Ш","Щ","Ъ","Ы","Ь","Э","Ю","Я");
	$latinians = array("a","b","v","g","d","e","jo","zh","z","i","j","k","l","m","n","o","p","r","s","t","u","f","kh","ts","ch","sh","sch","","y","","je","ju","ja","a","b","v","g","d","e","jo","zh","z","i","j","k","l","m","n","o","p","r","s","t","u","f","kh","ts","ch","sh","sch","","y","","je","ju","ja");
	$translited = str_replace( $russians, $latinians, strtolower( trim( $string ) ) );
	if( $url ) $translited = preg_replace('#[^\d\w]+#i', '-', $translited);
	return $translited;
}

?>