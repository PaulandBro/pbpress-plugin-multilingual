<?php

if(!defined('PB_DOCUMENT_PATH')){
	die( '-1' );
}

define('PB_ML_META_ORIGINAL_POST_ID', 'ml_original_post_id');

function _pb_ml_hook_for_pb_post_statement($statement_, $conditions_ = array()){
	if(isset($conditions_['id']) || isset($conditions_['slug'])) return $statement_;
	global $pb_config;
	
	$default_locale_ = $pb_config->default_locale();

	if(isset($conditions_['ml_locale'])){

		$statement_->add_custom_condition("posts.id IN (

				SELECT posts_meta.post_id
				FROM   posts_meta
				WHERE  posts_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
				AND    posts_meta.meta_value = '".pb_database_escape_string($conditions_['ml_locale'])."'
		
		)");

		if(isset($conditions_['ml_original_post_id'])){
			$statement_->add_custom_condition("posts.id IN (

					SELECT posts_meta.post_id
					FROM   posts_meta
					WHERE  posts_meta.meta_name = '".PB_ML_META_ORIGINAL_POST_ID."'
					AND    posts_meta.meta_value = '".pb_database_escape_string($conditions_['ml_original_post_id'])."'
			
			)");
		}


	}else{
		$statement_->add_custom_condition("posts.id NOT IN (
				SELECT posts_meta.post_id
				FROM   posts_meta
				WHERE  posts_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
		
		)");
	}

	return $statement_;
}

pb_hook_add_filter('pb_post_statement', '_pb_ml_hook_for_pb_post_statement');

//페이지 에디팅 영역에 다국어 추가
function _pb_ml_hook_for_manage_post_listtable_subaction($item_){
	$locales_ = pb_ml_available_locales();

	global $pbdb;

	$temp_added_locales_ = $pbdb->select("
		SELECT posts_meta.meta_value locale
				,posts_meta.post_id localed_post_id
		FROM posts_meta
		WHERE posts_meta.post_id = (
			SELECT temp.post_id
			FROM   posts_meta temp
			WHERE  temp.meta_value = '".$item_['id']."'
			AND    temp.meta_name = '".PB_ML_META_ORIGINAL_POST_ID."'
		)
		AND   posts_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
	");

	$added_locales_ = array();

	foreach($temp_added_locales_ as $row_data_){
		$added_locales_[$row_data_['locale']] = $row_data_['localed_post_id'];
	}

	?>

	<div class="ml-edit-group">
			<i class="ml-icon material-icons">language</i>

	<?php

	$post_type_ = $item_['type'];

	foreach($locales_ as $locale_){
		$country_code_ = strtolower(substr($locale_, -2));

		$localed_post_id_ = isset($added_locales_[$locale_]) ? $added_locales_[$locale_] : null;
		$edit_url_ = strlen($localed_post_id_) ? pb_admin_url("manage-{$post_type_}/edit/".$localed_post_id_) : pb_admin_url("manage-{$post_type_}/add/?locale=".$locale_."&original_post_id=".$item_['id']);
	?>
		
			<a href="<?=$edit_url_?>">
				<div style="background-image: url('<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png')" class="flag"></div>
			</a>
	<?php }

	?>
	</div>

	<?php
}
pb_hook_add_action("pb_manage_post_listtable_subaction", '_pb_ml_hook_for_manage_post_listtable_subaction');

$adminpage_list_ = pb_adminpage_list();

$post_types_ = pb_post_types();

foreach($post_types_ as $key_ => $type_data_){
	$adminpage_list_["manage-{$key_}"]['o_rewrite_handler'] = $adminpage_list_["manage-{$key_}"]['rewrite_handler'];
	$adminpage_list_["manage-{$key_}"]['rewrite_handler'] = '_pb_ml_hook_for_adminpage_manage_post_rewrite_handler';
}

global $pb_adminpage_list;
$pb_adminpage_list = $adminpage_list_;

function _pb_ml_hook_for_adminpage_manage_post_rewrite_handler($rewrite_path_, $data_){
	$result_ = call_user_func_array($data_['o_rewrite_handler'], array($rewrite_path_, $data_));

	if(pb_is_error($result_)){
		return $result_;
	}

	if(isset($rewrite_path_[1]) && $rewrite_path_[1] === "add"){
		$original_post_id_ = isset($_GET['original_post_id']) ? $_GET['original_post_id'] : -1;
		$original_post_data_ = pb_post($original_post_id_);

		if(!isset($original_post_data_)){
			return $result_;
		}

		$locale_ = isset($_GET['locale']) ? $_GET['locale'] : null;

		global $pb_config;
		$default_locale_ = $pb_config->default_locale();

		$original_post_type_ = $original_post_data_['type'];

		if($default_locale_ === $locale_){
			pb_redirect(pb_admin_url('manage-{$original_post_type_}/edit/'.$original_post_id_));
			pb_end();
		}

		global $pb_original_post;
		$pb_original_post = $original_post_data_;
	}

	return $result_;
}

//페이지 수정폼 훅
function _pb_ml_post_edit_form_before($item_){
	$rewrite_path_ = pb_adminpage_rewrite_path();

	if(isset($rewrite_path_[1]) && $rewrite_path_[1] === "add"){
		$original_post_id_ = isset($_GET['original_post_id']) ? $_GET['original_post_id'] : -1;
		$locale_ = isset($_GET['locale']) ? $_GET['locale'] : null;
		$original_post_data_ = pb_post($original_post_id_);

		if(isset($original_post_data_)){ ?>
			<input type="hidden" name="ml_original_post_id" value="<?=$original_post_id_?>">
			<input type="hidden" name="ml_locale" value="<?=$locale_?>">
		<?php }

	}
}
pb_hook_add_action("pb_post_edit_form_before", '_pb_ml_post_edit_form_before');

//페이지 삽입 시, 원본 페이지 업데이트
function _pb_ml_hook_for_post_writed($post_id_){
	$post_data_ = $_POST['post_data'];

	$locale_ = isset($post_data_['ml_locale']) ? $post_data_['ml_locale'] : null;
	$original_post_id_ = isset($post_data_['ml_original_post_id']) ? $post_data_['ml_original_post_id'] : null;

	if(strlen($original_post_id_)){
		pb_post_meta_update($post_id_, PB_ML_META_LOCALE_NAME, $locale_);
		pb_post_meta_update($post_id_, PB_ML_META_ORIGINAL_POST_ID, $original_post_id_);	
	}
}
pb_hook_add_action('pb_post_writed', '_pb_ml_hook_for_post_writed');

//페이지 삭제 시, localed 페이지 삭제
function _pb_ml_hook_for_post_deleted($post_id_){
	global $pbdb;

	$pbdb->query("DELETE FROM posts 
		WHERE posts.id IN (SELECT posts_meta.post_id
							FROM posts_meta
							WHERE  posts_meta.meta_name = '".PB_ML_META_ORIGINAL_POST_ID."'
							AND    posts_meta.meta_value = '".$post_id_."' )");
}
pb_hook_add_action('pb_post_deleted', '_pb_ml_hook_for_post_deleted');


function _pb_ml_hook_for_for_post_rewrite_handler(){
	global $pbpost, $pbpost_meta_map;	
	if(!isset($pbpost)) return;

	$original_post_id_ = isset($pbpost_meta_map['ml_original_post_id']) ? $pbpost_meta_map['ml_original_post_id'] : -1;
	$original_post_data_ = pb_post($original_post_id_);

	if(isset($original_post_data_)){ //다른 경로 접속 시 리다이렉팅
		pb_redirect(pb_post_url($original_post_data_['id']));
		pb_end();
	}

	global $pb_config;
	
	$default_locale_ = $pb_config->default_locale();
	$current_locale_ = pb_current_locale();

	if($default_locale_ === $current_locale_){
		return;
	}

	$localed_post_data_ = pb_post_list(array('ml_original_post_id' => $pbpost['id'], 'ml_locale' => $current_locale_));

	if(count($localed_post_data_) <= 0) return;

	$pbpost = $localed_post_data_[0];
	$pbpost_meta_map = pb_post_meta_map($pbpost['id']);
}
pb_hook_add_action("pb_post_setup", "_pb_ml_hook_for_for_post_rewrite_handler");

?>