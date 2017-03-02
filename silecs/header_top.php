<?php
if ( auth_is_user_authenticated() ) {
	$id_project = helper_get_current_project();
	if ($id_project != ALL_PROJECTS) {
		$t_current_project = project_get_field($id_project, 'name');
		echo '<h1 style="float: right;">' . htmlspecialchars($t_current_project) . "</h1>";
	}
}
?>
