<?php

require_once( 'core.php' );
require_api('authentication_api.php');
require_api('compress_api.php');
require_api('config_api.php');
require_api('current_user_api.php');
require_api('filter_api.php');
require_api('gpc_api.php');
require_api('html_api.php');
require_api('lang_api.php');
require_api('print_api.php');
require_api('project_api.php');
require_api('user_api.php');

require_js('bugFilter.js');
require_css('status_config.php');

require_api( 'category_api.php' );
require_api( 'columns_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'current_user_api.php' );
require_api( 'event_api.php' );
require_api( 'filter_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'html_api.php' );
require_api( 'lang_api.php' );
require_api( 'print_api.php' );


auth_ensure_user_authenticated();

$f_page_number = gpc_get_int('page_number', 1);
# Get Project Id and set it as current
$t_project_id = gpc_get_int('project_id', helper_get_current_project());
if (( ALL_PROJECTS == $t_project_id || project_exists($t_project_id) ) && $t_project_id != helper_get_current_project()) {
    helper_set_current_project($t_project_id);
    # Reloading the page is required so that the project browser
    # reflects the new current project
    print_header_redirect($_SERVER['REQUEST_URI'], true, false, true);
}

$t_per_page = null;
$t_bug_count = null;
$t_page_count = null;

if (empty($_REQUEST['f'])) {
    print_header_redirect('view_all_bug_page.php');
}
$filter = $_REQUEST['f'];
$t_rows = filter_get_bug_rows($f_page_number, $t_per_page, $t_page_count, $t_bug_count, $filter, null, null, true);
if ($t_rows === false) {
    print_header_redirect('view_all_set.php?type=0');
}

compress_enable();

# don't index view issues pages
html_robots_noindex();

if (count($filter) === 1 && !empty($filter['category_id'])) {
    html_page_top1(join('+', $filter['category_id']));
} else {
    html_page_top1(lang_get('view_bugs_link'));
}
html_page_top2();


filter_init( $filter );

list( $t_sort, ) = explode( ',', $g_filter['sort'] ?? '');
list( $t_dir, ) = explode( ',', $g_filter['dir'] ?? '');

$g_checkboxes_exist = false;

$t_icon_path = config_get( 'icon_path' );

# Improve performance by caching category data in one pass
if( helper_get_current_project() > 0 ) {
	category_get_all_rows( helper_get_current_project() );
}

$g_columns = helper_get_columns_to_view( COLUMNS_TARGET_VIEW_PAGE );

bug_cache_columns_data( $t_rows, $g_columns );

$t_col_count = count( $g_columns );

$t_filter_position = config_get( 'filter_position' );

# -- ====================== FILTER FORM ========================= --
if( ( $t_filter_position & FILTER_POSITION_TOP ) == FILTER_POSITION_TOP ) {
	filter_draw_selection_area( $f_page_number );
}
# -- ====================== end of FILTER FORM ================== --


# -- ====================== BUG LIST ============================ --
html_status_legend( STATUS_LEGEND_POSITION_TOP, true );

?>
<br />
<form id="bug_action" method="post" action="bug_actiongroup_page.php">
<?php # CSRF protection not required here - form does not result in modifications ?>
<table id="buglist" class="width100" cellspacing="1">
<thead>
<tr class="buglist-nav">
	<td class="form-title" colspan="<?php echo $t_col_count; ?>">
		<span class="floatleft">
		<?php
			# -- Viewing range info --
			$v_start = 0;
			$v_end   = 0;

			if( count( $t_rows ) > 0 ) {
				$v_start = ($g_filter['per_page'] ?? 0) * ($f_page_number - 1) + 1;
				$v_end = $v_start + count( $t_rows ) - 1;
			}

			echo lang_get( 'viewing_bugs_title' );
			echo ' (' . $v_start . ' - ' . $v_end . ' / ' . $t_bug_count . ')';
		?> </span>

		<span class="floatleft small">
		<?php
			# -- Print and Export links --
			echo '&#160;';
			print_bracket_link( 'print_all_bug_page.php', lang_get( 'print_all_bug_page_link' ) );
			echo '&#160;';
			print_bracket_link( 'csv_export.php', lang_get( 'csv_export' ) );
			echo '&#160;';
			print_bracket_link( 'excel_xml_export.php', lang_get( 'excel_export' ) );

			$t_event_menu_options = $t_links = event_signal( 'EVENT_MENU_FILTER' );

			foreach ( $t_event_menu_options as $t_plugin => $t_plugin_menu_options ) {
				foreach ( $t_plugin_menu_options as $t_callback => $t_callback_menu_options ) {
					if( !is_array( $t_callback_menu_options ) ) {
						$t_callback_menu_options = array( $t_callback_menu_options );
					}

					foreach ( $t_callback_menu_options as $t_menu_option ) {
						if( $t_menu_option ) {
							print_bracket_link_prepared( $t_menu_option );
						}
					}
				}
			}
		?> </span>

		<span class="floatright small"><?php
			# -- Page number links --
			$f_filter	= gpc_get_int( 'filter', 0 );
			print_page_links( 'view_all_bug_page.php', 1, $t_page_count, (int)$f_page_number, $f_filter );
		?> </span>
	</td>
</tr>
<?php # -- Bug list column header row -- ?>
<tr class="buglist-headers row-category">
<?php
	$t_title_function = 'print_column_title';
	foreach( $g_columns as $t_column ) {
		helper_call_custom_function( $t_title_function, array( $t_column ) );
	}
?>
</tr>

<?php # -- Spacer row -- ?>
<tr class="spacer">
	<td colspan="<?php echo $t_col_count; ?>"></td>
</tr>
</thead><tbody>

<?php
/**
 * Output Bug Rows
 *
 * @param array $p_rows An array of bug objects.
 * @return void
 */
function write_bug_rows( array $p_rows ) {
	global $g_columns, $g_filter;

	$t_in_stickies = ( $g_filter && ( 'on' == ($g_filter[FILTER_PROPERTY_STICKY] ?? null) ) );

	# -- Loop over bug rows --

	$t_rows = count( $p_rows );
	for( $i=0; $i < $t_rows; $i++ ) {
		$t_row = $p_rows[$i];

		if( ( 0 == $t_row->sticky ) && ( 0 == $i ) ) {
			$t_in_stickies = false;
		}
		if( ( 0 == $t_row->sticky ) && $t_in_stickies ) {	# demarcate stickies, if any have been shown
?>
		   <tr>
				   <td class="left sticky-header" colspan="<?php echo count( $g_columns ); ?>">&#160;</td>
		   </tr>
<?php
			$t_in_stickies = false;
		}

		# choose color based on status
		$t_status_label = html_get_status_css_class( $t_row->status, auth_get_current_user_id(), $t_row->project_id );

		echo '<tr class="' . $t_status_label . '">';

		$t_column_value_function = 'print_column_value';
		foreach( $g_columns as $t_column ) {
			helper_call_custom_function( $t_column_value_function, array( $t_column, $t_row ) );
		}

		echo '</tr>';
	}
}


write_bug_rows( $t_rows );
# -- ====================== end of BUG LIST ========================= --

# -- ====================== MASS BUG MANIPULATION =================== --
# @@@ ideally buglist-footer would be in <tfoot>, but that's not possible due to global g_checkboxes_exist set via write_bug_rows()
?>
	<tr class="buglist-footer">
		<td class="left" colspan="<?php echo $t_col_count; ?>">
			<span class="floatleft">
<?php
		if( $g_checkboxes_exist ) {
			echo '<input type="checkbox" id="bug_arr_all" name="bug_arr_all" value="all" class="check_all" />';
			echo '<label for="bug_arr_all">' . lang_get( 'select_all' ) . '</label>';
		}

		if( $g_checkboxes_exist ) {
?>
			<select name="action">
				<?php print_all_bug_action_option_list( $t_unique_project_ids ) ?>
			</select>
			<input type="submit" class="button" value="<?php echo lang_get( 'ok' ); ?>" />
<?php
		} else {
			echo '&#160;';
		}
?>			</span>
			<span class="floatright small">
				<?php
					$f_filter	= gpc_get_int( 'filter', 0 );
					print_page_links( 'view_all_bug_page.php', 1, $t_page_count, (int)$f_page_number, $f_filter );
				?>
			</span>
		</td>
	</tr>
<?php # -- ====================== end of MASS BUG MANIPULATION ========================= -- ?>
</tbody>
</table>
</form>

<?php
html_status_legend( STATUS_LEGEND_POSITION_BOTTOM, true );

# -- ====================== FILTER FORM ========================= --
if( ( $t_filter_position & FILTER_POSITION_BOTTOM ) == FILTER_POSITION_BOTTOM ) {
	filter_draw_selection_area( $f_page_number );
}
# -- ====================== end of FILTER FORM ================== --


html_page_bottom();
