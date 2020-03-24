<?php

if(!defined('PB_DOCUMENT_PATH')){
	die( '-1' );
}

define('PB_ML_PATH', dirname(__FILE__)."/");
define('PB_ML_URL', PB_PLUGINS_URL . str_replace(PB_PLUGINS_PATH, "", PB_ML_PATH));

if(!defined('PB_ML_LOCALES')){
	define('PB_ML_LOCALES', 'en_US|zh_CN');
}

define('PB_ML_META_LOCALE_NAME', 'ml_locale_name');

function pb_ml_available_locales(){
	return explode("|", PB_ML_LOCALES);
}


function _pb_ml_hook_for_load_library(){
	?>
	<link rel="stylesheet" type="text/css" href="<?=PB_ML_URL?>styles.css">
	<?php
}
pb_hook_add_action('pb_admin_head', '_pb_ml_hook_for_load_library');

include PB_ML_PATH."functions-page.php";

global $posts_do;
if(isset($posts_do)){
	include PB_ML_PATH."functions-post.php";	
}

?>