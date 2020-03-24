<?php

if(!defined('PB_DOCUMENT_PATH')){
	die( '-1' );
}

define('PB_ML_META_ORIGINAL_PAGE_ID', 'ml_original_page_id');

function _pb_ml_hook_for_pb_page_statement($statement_, $conditions_ = array()){
	if(isset($conditions_['id']) || isset($conditions_['slug'])) return $statement_;
	global $pb_config;
	
	$default_locale_ = $pb_config->default_locale();

	if(isset($conditions_['ml_locale'])){

		$statement_->add_custom_condition("pages.id IN (

				SELECT pages_meta.page_id
				FROM   pages_meta
				WHERE  pages_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
				AND    pages_meta.meta_value = '".pb_database_escape_string($conditions_['ml_locale'])."'
		
		)");

		if(isset($conditions_['ml_original_page_id'])){
			$statement_->add_custom_condition("pages.id IN (

					SELECT pages_meta.page_id
					FROM   pages_meta
					WHERE  pages_meta.meta_name = '".PB_ML_META_ORIGINAL_PAGE_ID."'
					AND    pages_meta.meta_value = '".pb_database_escape_string($conditions_['ml_original_page_id'])."'
			
			)");
		}


	}else{
		$statement_->add_custom_condition("pages.id NOT IN (
				SELECT pages_meta.page_id
				FROM   pages_meta
				WHERE  pages_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
		
		)");
	}

	return $statement_;
}

pb_hook_add_filter('pb_page_statement', '_pb_ml_hook_for_pb_page_statement');

//페이지 에디팅 영역에 다국어 추가
function _pb_ml_hook_for_manage_page_listtable_subaction($item_){
	$locales_ = pb_ml_available_locales();

	global $pbdb;

	$temp_added_locales_ = $pbdb->select("
		SELECT pages_meta.meta_value locale
				,pages_meta.page_id localed_page_id
		FROM pages_meta
		WHERE pages_meta.page_id = (
			SELECT temp.page_id
			FROM   pages_meta temp
			WHERE  temp.meta_value = '".$item_['id']."'
			AND    temp.meta_name = '".PB_ML_META_ORIGINAL_PAGE_ID."'
		)
		AND   pages_meta.meta_name = '".PB_ML_META_LOCALE_NAME."'
	");

	$added_locales_ = array();

	foreach($temp_added_locales_ as $row_data_){
		$added_locales_[$row_data_['locale']] = $row_data_['localed_page_id'];
	}

	?>

	<div class="ml-edit-group">
			<i class="ml-icon material-icons">language</i>

	<?php

	foreach($locales_ as $locale_){
		$country_code_ = strtolower(substr($locale_, -2));

		$localed_page_id_ = isset($added_locales_[$locale_]) ? $added_locales_[$locale_] : null;
		$edit_url_ = strlen($localed_page_id_) ? pb_admin_url("manage-page/edit/".$localed_page_id_) : pb_admin_url("manage-page/add/?locale=".$locale_."&original_page_id=".$item_['id']);
	?>
		
			<a href="<?=$edit_url_?>">
				<div style="background-image: url('<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png')" class="flag"></div>
			</a>
	<?php }

	?>
	</div>

	<?php

}
pb_hook_add_action("pb_manage_page_listtable_subaction", '_pb_ml_hook_for_manage_page_listtable_subaction');

$adminpage_list_ = pb_adminpage_list();

$adminpage_list_['manage-page']['o_rewrite_handler'] = $adminpage_list_['manage-page']['rewrite_handler'];
$adminpage_list_['manage-page']['rewrite_handler'] = '_pb_ml_hook_for_adminpage_manage_page_rewrite_handler';

global $pb_adminpage_list;
$pb_adminpage_list = $adminpage_list_;

function _pb_ml_hook_for_adminpage_manage_page_rewrite_handler($rewrite_path_, $data_){
	$result_ = call_user_func_array($data_['o_rewrite_handler'], array($rewrite_path_, $data_));

	if(pb_is_error($result_)){
		return $result_;
	}

	if(isset($rewrite_path_[1]) && $rewrite_path_[1] === "add"){
		$original_page_id_ = isset($_GET['original_page_id']) ? $_GET['original_page_id'] : -1;
		$original_page_data_ = pb_page($original_page_id_);

		if(!isset($original_page_data_)){
			return $result_;
		}

		$locale_ = isset($_GET['locale']) ? $_GET['locale'] : null;

		global $pb_config;
		$default_locale_ = $pb_config->default_locale();

		if($default_locale_ === $locale_){
			pb_redirect(pb_admin_url('manage-page/edit/'.$original_page_id_));
			pb_end();
		}

		global $pb_original_page;
		$pb_original_page = $original_page_data_;
	}

	return $result_;
}

//페이지 수정폼 훅
function _pb_ml_page_edit_form_before($item_){
	$rewrite_path_ = pb_adminpage_rewrite_path();

	if(isset($rewrite_path_[1]) && $rewrite_path_[1] === "add"){
		$original_page_id_ = isset($_GET['original_page_id']) ? $_GET['original_page_id'] : -1;
		$locale_ = isset($_GET['locale']) ? $_GET['locale'] : null;
		$original_page_data_ = pb_page($original_page_id_);

		if(isset($original_page_data_)){ ?>
			<input type="hidden" name="ml_original_page_id" value="<?=$original_page_id_?>">
			<input type="hidden" name="ml_locale" value="<?=$locale_?>">
		<?php }

	}
}
pb_hook_add_action("pb_page_edit_form_before", '_pb_ml_page_edit_form_before');

//페이지 삽입 시, 원본 페이지 업데이트
function _pb_ml_hook_for_page_writed($page_id_){
	$page_data_ = $_POST['page_data'];

	$locale_ = isset($page_data_['ml_locale']) ? $page_data_['ml_locale'] : null;
	$original_page_id_ = isset($page_data_['ml_original_page_id']) ? $page_data_['ml_original_page_id'] : null;

	if(strlen($original_page_id_)){
		pb_page_meta_update($page_id_, PB_ML_META_LOCALE_NAME, $locale_);
		pb_page_meta_update($page_id_, PB_ML_META_ORIGINAL_PAGE_ID, $original_page_id_);	
	}
}
pb_hook_add_action('pb_page_writed', '_pb_ml_hook_for_page_writed');

//페이지 삭제 시, localed 페이지 삭제
function _pb_ml_hook_for_page_deleted($page_id_){
	global $pbdb;

	$pbdb->query("DELETE FROM pages 
		WHERE pages.id IN (SELECT pages_meta.page_id
							FROM pages_meta
							WHERE  pages_meta.meta_name = '".PB_ML_META_ORIGINAL_PAGE_ID."'
							AND    pages_meta.meta_value = '".$page_id_."' )");
}
pb_hook_add_action('pb_page_deleted', '_pb_ml_hook_for_page_deleted');


function _pb_ml_hook_for_for_page_rewrite_handler(){
	global $pbpage, $pbpage_meta_map;	
	if(!isset($pbpage)) return;

	$original_page_id_ = isset($pbpage_meta_map['ml_original_page_id']) ? $pbpage_meta_map['ml_original_page_id'] : -1;
	$original_page_data_ = pb_page($original_page_id_);

	if(isset($original_page_data_)){ //다른 경로 접속 시 리다이렉팅
		pb_redirect(pb_page_url($original_page_data_['id']));
		pb_end();
	}

	global $pb_config;
	
	$default_locale_ = $pb_config->default_locale();
	$current_locale_ = pb_current_locale();

	if($default_locale_ === $current_locale_){
		return;
	}

	$localed_page_data_ = pb_page_list(array('ml_original_page_id' => $pbpage['id'], 'ml_locale' => $current_locale_));
	if(count($localed_page_data_) <= 0) return;

	$pbpage = $localed_page_data_[0];
	$pbpage_meta_map = pb_page_meta_map($pbpage['id']);
}
pb_hook_add_action("pb_started", "_pb_ml_hook_for_for_page_rewrite_handler");

?>