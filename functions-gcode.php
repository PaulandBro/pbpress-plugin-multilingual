<?php

if(!defined('PB_DOCUMENT_PATH')){
	die( '-1' );
}

global $gcode_locales_do;
$gcode_locales_do = pbdb_data_object("gcode_locales", array(
	'locale'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 10, "pk" => true, "updatable" => true, "comment" => "언어코드"),
	'code_id'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 20, "pk" => true, "updatable" => true, "fk" => array(
		'table' => 'gcode',
		'column' => "code_id",
		'delete' => PBDB_DO::FK_CASCADE,
		'update' => PBDB_DO::FK_CASCADE,
	), "comment" => "코드ID"),
	'code_nm'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 50, "comment" => "코드명"),
	
	'col1'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL1"),
	'col2'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL2"),
	'col3'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL3"),
	'col4'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL4"),
	
	'reg_date'	 => array("type" => PBDB_DO::TYPE_DATETIME, "comment" => "등록일자"),
	'mod_date'	 => array("type" => PBDB_DO::TYPE_DATETIME, "comment" => "수정일자"),
),"공통코드 - 다국어화");

global $gcode_dtl_locales_do;
$gcode_dtl_locales_do = pbdb_data_object("gcode_dtl_locales", array(
	'locale'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 10, "pk" => true, "updatable" => true, "comment" => "언어코드"),
	'code_id'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 20, "pk" => true, "updatable" => true, "comment" => "코드ID"),
	'code_did'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 20, "pk" => true, "updatable" => true, "comment" => "상세코드ID"),
	'code_dnm'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 50, "comment" => "상세코드명"),
	
	'col1'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL1"),
	'col2'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL2"),
	'col3'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL3"),
	'col4'		 => array("type" => PBDB_DO::TYPE_VARCHAR, "length" => 100, "comment" => "COL4"),
	
	'reg_date'	 => array("type" => PBDB_DO::TYPE_DATETIME, "comment" => "등록일자"),
	'mod_date'	 => array("type" => PBDB_DO::TYPE_DATETIME, "comment" => "수정일자"),
),"공통코드 - 상세 - 다국어화");

$gcode_dtl_locales_do->add_custom_fk(array(
	'table' => "gcode_dtl", 
	'from' => "code_id,code_did", 
	'to' => "code_id,code_did", 
	'update' => PBDB_DO::FK_CASCADE,
	'delete' => PBDB_DO::FK_CASCADE,
));

if(!$gcode_locales_do->is_exists() || !$gcode_dtl_locales_do->is_exists()) return;

function pb_gcode_locales_map($code_id_){
	$results_ = array();

	global $gcode_locales_do;
	$statement_ = $gcode_locales_do->statement();
	$statement_->add_compare_condition('gcode_locales.code_id', $code_id_, "=", PBDB::TYPE_STRING);

	$temp_results_ = $statement_->select();

	$available_locales_ = pb_ml_available_locales();

	foreach($temp_results_ as $row_data_){
		$results_[$row_data_['locale']] = $row_data_;
	}

	return $results_;
}

function pb_gcode_dtl_locales_map($code_id_, $code_did_){
	$results_ = array();

	global $gcode_dtl_locales_do;
	$statement_ = $gcode_dtl_locales_do->statement();
	$statement_->add_compare_condition('gcode_dtl_locales.code_id', $code_id_, "=", PBDB::TYPE_STRING);
	$statement_->add_compare_condition('gcode_dtl_locales.code_did', $code_did_, "=", PBDB::TYPE_STRING);

	$temp_results_ = $statement_->select();

	$available_locales_ = pb_ml_available_locales();

	foreach($temp_results_ as $row_data_){
		$results_[$row_data_['locale']] = $row_data_;
	}

	return $results_;
}

pb_hook_add_filter('pb_gcode_statement', function($statement_, $conditions_){

	global $gcode_locales_do;

	$localed_statement_ = $gcode_locales_do->statement();

	$statement_->add_join("LEFT OUTER JOIN", $localed_statement_, "gcode_locales", array(
		array(PBDB_SS::COND_COMPARE, "gcode.code_id", "gcode_locales.code_id"),
		array(PBDB_SS::COND_COMPARE, "gcode_locales.locale", pb_current_locale(), "=", PBDB::TYPE_STRING),
	), array(
		'IFNULL(gcode_locales.code_nm, gcode.code_nm) localed_code_dnm',
		'IFNULL(gcode_locales.col1, gcode.col1) localed_col1',
		'IFNULL(gcode_locales.col2, gcode.col2) localed_col2',
		'IFNULL(gcode_locales.col3, gcode.col3) localed_col3',
		'IFNULL(gcode_locales.col4, gcode.col4) localed_col4',
	));
		
	return $statement_;	
});

pb_hook_add_filter('pb_gcode_dtl_statement', function($statement_, $conditions_){

	global $gcode_dtl_locales_do;

	$localed_statement_ = $gcode_dtl_locales_do->statement();

	$statement_->add_join("LEFT OUTER JOIN", $localed_statement_, "gcode_dtl_locales", array(
		array(PBDB_SS::COND_COMPARE, "gcode_dtl.code_id", "gcode_dtl_locales.code_id"),
		array(PBDB_SS::COND_COMPARE, "gcode_dtl.code_did", "gcode_dtl_locales.code_did"),
		array(PBDB_SS::COND_COMPARE, "gcode_dtl_locales.locale", pb_current_locale(), "=", PBDB::TYPE_STRING),
	), array(
		'IFNULL(gcode_dtl_locales.code_dnm, gcode_dtl.code_dnm) localed_code_dnm',
		'IFNULL(gcode_dtl_locales.col1, gcode_dtl.col1) localed_col1',
		'IFNULL(gcode_dtl_locales.col2, gcode_dtl.col2) localed_col2',
		'IFNULL(gcode_dtl_locales.col3, gcode_dtl.col3) localed_col3',
		'IFNULL(gcode_dtl_locales.col4, gcode_dtl.col4) localed_col4',
	));
		
	return $statement_;	
});

pb_hook_add_filter('pb_gcode_make_options_list', function($list_){

	foreach($list_ as &$row_data_){
		$row_data_['code_dnm'] = $row_data_['localed_code_dnm'];
		$row_data_['col1'] = $row_data_['localed_col1'];
		$row_data_['col2'] = $row_data_['localed_col2'];
		$row_data_['col3'] = $row_data_['localed_col3'];
		$row_data_['col4'] = $row_data_['localed_col4'];
	}

	return $list_;
});

pb_hook_add_filter('pb_query_gcode_dtl_name', function($query_, $code_id_, $column_){
	global $pbdb;
	$query_ = "(
		SELECT IFNULL(gcode_dtl_locales.code_dnm, gcode_dtl.code_dnm) code_dnm
		
		FROM gcode_dtl 

		LEFT OUTER JOIN gcode_dtl_locales
		ON   gcode_dtl_locales.code_id = gcode_dtl.code_id
		AND  gcode_dtl_locales.code_did = gcode_dtl.code_did
		AND  gcode_dtl_locales.locale = '".pb_current_locale()."'

		WHERE gcode_dtl.code_id = '".pb_database_escape_string($code_id_)."'
		AND gcode_dtl.code_did = {$column_})";

	return $query_;
});


pb_hook_add_action('pb-admin-gcode-edit-form-after', function(){

	$nav_id_ = "pb-locale-form-nav-".pb_random_string(5);
	$available_locales_ = pb_ml_available_locales();
	?>
<h3>다국어</h3>
<div class="pb-locale-form-navbar" id="<?=$nav_id_?>">
	<ul class="nav nav-tabs" role="tablist">
		<?php
			$active_ = true;
		foreach($available_locales_ as $locale_){

			$country_code_ = strtolower(substr($locale_, -2));
		?>
			<li role="presentation" class="<?=$active_ ? "active" : ""?>"><a href="#<?=$nav_id_?>-tab-<?=$locale_?>" role="tab" data-toggle="tab"><img class='flag' src='<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png'><?=$locale_?></a></li>
		<?php 
				$active_ = false;
			} ?>
	</ul>

	<div class="tab-content">
		<br>
		<?php $active_ = true;
		foreach($available_locales_ as $locale_){ ?>
			<div role="tabpanel" class="tab-pane fade <?=$active_ ? "active in" : ""?>" id="<?=$nav_id_?>-tab-<?=$locale_?>">
				<table class="pb-form-table " >
					<tbody>
						<tr>
							<th>코드명</th>
							<td>
								<div class="form-group">
									<input type="text" name="code_nm[<?=$locale_?>]" class="form-control" placeholder="코드명 입력">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr>
							<th>여분값명1</th>
							<td>
								<div class="form-group">
									<input type="text" name="col1[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr>
							<th>여분값명2</th>
							<td>
								<div class="form-group">
									<input type="text" name="col2[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr>
							<th>여분값명3</th>
							<td>
								<div class="form-group">
									<input type="text" name="col3[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr>
							<th>여분값명4</th>
							<td>
								<div class="form-group">
									<input type="text" name="col4[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php $active_ = false; } ?>
	</div>
</div>

	<?php
});

pb_hook_add_action('pb-admin-gcode-dtl-edit-form-after', function(){

	$nav_id_ = "pb-locale-form-nav-".pb_random_string(5);
	$available_locales_ = pb_ml_available_locales();
	?>
<h3>다국어</h3>
<div class="pb-locale-form-navbar" id="<?=$nav_id_?>">
	<ul class="nav nav-tabs" role="tablist">
		<?php 
			$active_ = true;
		foreach($available_locales_ as $locale_){
			$country_code_ = strtolower(substr($locale_, -2));
		?>
			<li role="presentation" class="<?=$active_ ? "active" : ""?>"><a href="#<?=$nav_id_?>-tab-<?=$locale_?>" role="tab" data-toggle="tab"><img class='flag' src='<?=PB_ML_URL?>img/flags/<?=$country_code_?>.png'><?=$locale_?></a></li>
		<?php 

			$active_ = false;
		} ?>
	</ul>

	<div class="tab-content">
		<br>
		<?php
			$active_ = true;
		 foreach($available_locales_ as $locale_){
			
		?>
			<div role="tabpanel" class="tab-pane fade <?=$active_ ? "active in" : ""?>" id="<?=$nav_id_?>-tab-<?=$locale_?>">
				<table class="pb-form-table " >
					<tbody>
						<tr>
							<th>상세코드명</th>
							<td>
								<div class="form-group">
									<input type="text" name="code_dnm[<?=$locale_?>]" class="form-control" placeholder="상세코드명 입력">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr data-extra-col="col1">
							<th data-column="col1_title"></th>
							<td>
								<div class="form-group">
									<input type="text" name="col1[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr data-extra-col="col2">
							<th data-column="col2_title"></th>
							<td>
								<div class="form-group">
									<input type="text" name="col2[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr data-extra-col="col3">
							<th data-column="col3_title"></th>
							<td>
								<div class="form-group">
									<input type="text" name="col3[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
						<tr data-extra-col="col4">
							<th data-column="col4_title"></th>
							<td>
								<div class="form-group">
									<input type="text" name="col4[<?=$locale_?>]" class="form-control">
									<div class="help-block with-errors"></div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php 
			$active_ = false;
			} ?>
	</div>
</div>

	<?php
});


pb_hook_add_filter('pb_gcode', function($code_data_){
	$locale_map_ = pb_gcode_locales_map($code_data_['code_id']);

	$available_locales_ = pb_ml_available_locales();	

	foreach($available_locales_ as $locale_code_){
		$localed_row_data_ = isset($locale_map_[$locale_code_]) ? $locale_map_[$locale_code_] : null;

		$locale_code_nm_ = isset($localed_row_data_['code_nm']) ? $localed_row_data_['code_nm'] : null;
		$locale_col1_ = isset($localed_row_data_['col1']) ? $localed_row_data_['col1'] : null;
		$locale_col2_ = isset($localed_row_data_['col2']) ? $localed_row_data_['col2'] : null;
		$locale_col3_ = isset($localed_row_data_['col3']) ? $localed_row_data_['col3'] : null;
		$locale_col4_ = isset($localed_row_data_['col4']) ? $localed_row_data_['col4'] : null;

		$code_data_['code_nm['.$locale_code_.']'] = $locale_code_nm_;
		$code_data_['col1['.$locale_code_.']'] = $locale_col1_;
		$code_data_['col2['.$locale_code_.']'] = $locale_col2_;
		$code_data_['col3['.$locale_code_.']'] = $locale_col3_;
		$code_data_['col4['.$locale_code_.']'] = $locale_col4_;
	}

	return $code_data_;
});

pb_hook_add_filter('pb_gcode_dtl', function($code_dtl_data_){
	$locale_map_ = pb_gcode_dtl_locales_map($code_dtl_data_['code_id'], $code_dtl_data_['code_did']);

	$available_locales_ = pb_ml_available_locales();	

	foreach($available_locales_ as $locale_code_){
		$localed_row_data_ = isset($locale_map_[$locale_code_]) ? $locale_map_[$locale_code_] : null;

		$locale_code_dnm_ = isset($localed_row_data_['code_dnm']) ? $localed_row_data_['code_dnm'] : null;
		$locale_col1_ = isset($localed_row_data_['col1']) ? $localed_row_data_['col1'] : null;
		$locale_col2_ = isset($localed_row_data_['col2']) ? $localed_row_data_['col2'] : null;
		$locale_col3_ = isset($localed_row_data_['col3']) ? $localed_row_data_['col3'] : null;
		$locale_col4_ = isset($localed_row_data_['col4']) ? $localed_row_data_['col4'] : null;

		$code_dtl_data_['code_dnm['.$locale_code_.']'] = $locale_code_dnm_;
		$code_dtl_data_['col1['.$locale_code_.']'] = $locale_col1_;
		$code_dtl_data_['col2['.$locale_code_.']'] = $locale_col2_;
		$code_dtl_data_['col3['.$locale_code_.']'] = $locale_col3_;
		$code_dtl_data_['col4['.$locale_code_.']'] = $locale_col4_;
	}

	return $code_dtl_data_;
});

function _pb_gcode_updated_for_localed($code_id_){
	$raw_data_ = _POST('target_data');
	if(!isset($raw_data_)) return;

	$available_locales_ = pb_ml_available_locales();	

	global $pbdb, $gcode_locales_do;
	$pbdb->delete("gcode_locales", array('code_id' => $code_id_));

	$reg_date_ = pb_current_time();

	foreach($available_locales_ as $locale_){
		if(!isset($raw_data_['code_nm['.$locale_])) continue;
		
		$code_nm_ = $raw_data_['code_nm['.$locale_];
		$col1_ = $raw_data_['col1['.$locale_];
		$col2_ = $raw_data_['col2['.$locale_];
		$col3_ = $raw_data_['col3['.$locale_];
		$col4_ = $raw_data_['col4['.$locale_];

		$gcode_locales_do->insert(array(
			'locale' => $locale_,
			'code_id' => $code_id_,
			'code_nm' => $code_nm_,
			'col1' => $col1_,
			'col2' => $col2_,
			'col3' => $col3_,
			'col4' => $col4_,
			'reg_date' => $reg_date_,
		));

	}
}

pb_hook_add_action('pb_gcode_updated', '_pb_gcode_updated_for_localed');
pb_hook_add_action('pb_gcode_inserted', '_pb_gcode_updated_for_localed');

function _pb_gcode_dtl_updated_for_localed($code_id_, $code_did_){
	$raw_data_ = _POST('target_data');
	if(!isset($raw_data_)) return;

	$available_locales_ = pb_ml_available_locales();	

	global $pbdb, $gcode_dtl_locales_do;
	$pbdb->delete("gcode_dtl_locales", array('code_id' => $code_id_, 'code_did' => $code_did_));

	$gcode_dtl_locales_do->delete($code_id_, $code_did_);

	$reg_date_ = pb_current_time();

	foreach($available_locales_ as $locale_){
		if(!isset($raw_data_['code_dnm['.$locale_])) continue;
		
		$code_dnm_ = $raw_data_['code_dnm['.$locale_];
		$col1_ = $raw_data_['col1['.$locale_];
		$col2_ = $raw_data_['col2['.$locale_];
		$col3_ = $raw_data_['col3['.$locale_];
		$col4_ = $raw_data_['col4['.$locale_];

		$gcode_dtl_locales_do->insert(array(
			'locale' => $locale_,
			'code_id' => $code_id_,
			'code_did' => $code_did_,
			'code_dnm' => $code_dnm_,
			'col1' => $col1_,
			'col2' => $col2_,
			'col3' => $col3_,
			'col4' => $col4_,
			'reg_date' => $reg_date_,
		));

	}
}

pb_hook_add_action('pb_gcode_dtl_updated', '_pb_gcode_dtl_updated_for_localed');
pb_hook_add_action('pb_gcode_dtl_inserted', '_pb_gcode_dtl_updated_for_localed');

?>