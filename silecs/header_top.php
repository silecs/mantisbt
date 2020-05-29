<?php
if ( auth_is_user_authenticated() ) {
    /*
	$id_project = helper_get_current_project();
	if ($id_project != ALL_PROJECTS) {
		$t_current_project = project_get_field($id_project, 'name');
		echo '<h1 class="project">' . htmlspecialchars($t_current_project) . "</h1>";
    }
     */
    echo <<<EOJS
<script src="/silecs/silecs.js"></script>
EOJS;
}
?>
