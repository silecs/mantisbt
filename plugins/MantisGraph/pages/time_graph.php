<?php

access_ensure_project_level( config_get( 'view_summary_threshold' ) );

layout_page_header();
layout_page_begin( 'summary_page.php' );

$t_filter = summary_get_filter();
print_summary_menu( 'time_graph.php', $t_filter );

# Submenu
$t_mantisgraph = plugin_get();
$t_mantisgraph->print_submenu();
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>
	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<?php print_icon( 'fa-bar-chart-o', 'ace-icon' ); ?>
				Temps de développement
			</h4>
		</div>

		<div class="col-xs-12" style="padding: 20px;">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<?php print_icon( 'fa-bar-chart', 'ace-icon' ); ?>
					Temps de développement mensuel
				</h4>
			</div>
<?php
			$t_metrics = create_time_summary( $t_filter );
			graph_bymonth( $t_metrics );
?>
		</div>
	</div>
</div>

<?php
layout_page_end();
