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
				WHERE  posts_meta.meta_name = 'ml_locale'
				AND    posts_meta.meta_value = '".pb_database_escape_string($conditions_['ml_locale'])."'
		
		)");
	}

	return $statement_;
}

pb_hook_add_filter('pb_post_statement', '_pb_ml_hook_for_pb_post_statement');

function _pb_ml_hook_for_post_edit_form_after($post_data_){
	global $pb_config,$pbpost_meta_map;
	$ml_locale_ = isset($pbpost_meta_map['ml_locale']) ? $pbpost_meta_map['ml_locale'] : $pb_config->default_locale();

	$default_locale_ = $pb_config->default_locale();
	$default_country_code_ = strtolower(substr($default_locale_, -2));
	?>
	<div class="panel panel-default" id="pb-post-edit-form-ext-link-panel">
		<div class="panel-heading" role="tab">
			<h4 class="panel-title">
				<a role="button" data-toggle="collapse" href="#pb-post-edit-form-ext-link-panel-body" >언어</a>
			</h4>
		</div>
		<div id="pb-post-edit-form-ext-link-panel-body" class="panel-collapse collapse in" role="tabpanel">
			<div class="panel-body">
				<div class="form-group">
					<select class="selectpicker" name="ml_locale" required data-error="언어를 선택하세요">
						<option data-content="<img class='flag' src='<?=PB_ML_URL?>img/flags/<?=$default_country_code_?>.png'> <?=$default_locale_?>"><?=$default_locale_?></option>
					<?php

						$locale_list_ = pb_ml_available_locales();
					foreach ($locale_list_ as $locale_){
						$country_code_ = strtolower(substr($locale_, -2));
					?>
					  <option data-content="<img class='flag' src='<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png'> <?=$locale_?>" <?=pb_selected($locale_, $ml_locale_)?>><?=$locale_?></option>
					<?php
					}
					?>
					</select>
				</div>
			</div>
		</div>
	</div>

	<?php 
}
pb_hook_add_action("pb_post_edit_form_control_panel_after", '_pb_ml_hook_for_post_edit_form_after');

$post_easytable_ = pb_easytable("pb-admin-post-table");
$post_easytable_->insert_column(1, "ml_locale", array(
	'name' => '언어',
	'class' => 'col-1 text-center',
	'render' => function($table_, $item_, $page_index_){

		$locale_ = pb_post_meta_value($item_['id'], "ml_locale");
		$country_code_ = strtolower(substr($locale_, -2));
		?>
		
		<div style="background-image: url('<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png')" class="flag"></div>

		<?php
	}
));

pb_hook_add_action('pb_post_inserted', "_pb_ml_hook_for_post_update_hook");
pb_hook_add_action('pb_post_updated', "_pb_ml_hook_for_post_update_hook");
function _pb_ml_hook_for_post_update_hook($post_id_){
	global $pb_config;
	$post_data_ = $_POST['post_data'];
	$ml_locale_ = isset($post_data_['ml_locale']) ? $post_data_['ml_locale'] : $pb_config->default_locale();
	pb_post_meta_update($post_id_, "ml_locale", $ml_locale_);
}

?>