<?php

define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

if ( ! $module->isAccessAllowed() )
{
	echo 'You do not have the rights to access this page.';
	exit;
}

// Check that the user exists.
if ( ! isset( $_GET['username'] ) ||
     $module->query( 'SELECT 1 FROM redcap_user_information WHERE username = ? ' .
                     'AND LENGTH(username) = LENGTH(?)',
                     [ $_GET['username'], $_GET['username'] ] )->num_rows == 0 )
{
	echo 'The requested user account does not exist.';
	exit;
}


// Get the list of accessible projects.
$listAccessibleProjects = $module->getAccessibleProjects( USERID );


// Get the list of projects where the user can switch their own role using the role switcher.
$listProjectsRoleSwitcher = [];
$queryRoleSwitcher = $module->query( 'SELECT project_id FROM redcap_external_module_settings ems ' .
                                     'JOIN redcap_external_modules em ON ems.external_module_id =' .
                                     ' em.external_module_id WHERE em.directory_prefix = ' .
                                     "'role_switcher' AND `key` = 'user-roles' AND " .
                                     'JSON_EXTRACT(`value`, ?) IS NOT NULL',
                                     [ '$."' . $_GET['username'] .'"' ] );
while ( $infoRoleSwitcher = $queryRoleSwitcher->fetch_assoc() )
{
	$listProjectsRoleSwitcher[] = $infoRoleSwitcher['project_id'];
}


// Get the user information.
$infoUser = $module->query( 'SELECT rui.*, if( exists( SELECT 1 FROM redcap_auth ra ' .
                            'WHERE ra.username = rui.username ), 1, 0 ) table_based ' .
                            'FROM redcap_user_information rui WHERE username = ?',
                            [ $_GET['username'] ] )->fetch_assoc();

$internalUserRegex = $module->getSystemSetting( 'internal-user-regex' );
$defaultAllowedRoles = explode( "\n", str_replace( [ "\r", "\n\n" ], "\n",
                                           $module->getSystemSetting( 'default-allowed-roles' ) ) );
$projectNotifyEmail = null;
$projectSettings = [ 'project-id' => $module->getSystemSetting( 'project-id' ),
                     'project-allowed-roles' => $module->getSystemSetting( 'project-allowed-roles' ),
                     'project-email' => $module->getSystemSetting( 'project-email' ) ];
$listProjectAllowedRoles = [];
for ( $i = 0; $i < count( $projectSettings['project-id'] ); $i++ )
{
	if ( $projectSettings['project-allowed-roles'][$i] != '' &&
	     $projectSettings['project-id'][$i] != '' )
	{
		$listProjectAllowedRoles[ $projectSettings['project-id'][$i] ] =
			explode( "\n", str_replace( [ "\r", "\n\n" ], "\n",
			                            $projectSettings['project-allowed-roles'][$i] ) );
	}
	if ( $_POST['action'] == 'add_project' &&
	     $projectSettings['project-id'][$i] == $_POST['project_id'] &&
	     $projectSettings['project-email'][$i] != '' )
	{
		$projectNotifyEmail = $projectSettings['project-email'][$i];
	}
}
// Determine the projects for which the user can be assigned an API token through the wizard.
$projectsAllowedAPIToken = [];
$queryAPIToken = $module->query( 'SELECT redcap_user_rights.project_id ' .
                                 'FROM redcap_user_rights LEFT JOIN redcap_user_roles ON ' .
                                 'redcap_user_rights.role_id = redcap_user_roles.role_id AND ' .
                                 'redcap_user_rights.project_id = redcap_user_roles.project_id ' .
                                 'WHERE api_token IS NULL AND ifnull( ' .
                                 'redcap_user_roles.api_export, redcap_user_rights.api_export ) ' .
                                 '= 0 AND ifnull( redcap_user_roles.api_import, ' .
                                 'redcap_user_rights.api_import ) = 0 AND ifnull( ' .
                                 'redcap_user_roles.mobile_app, redcap_user_rights.mobile_app ) ' .
                                 '= 1 AND username = ?', [ $_GET['username'] ] );
while ( $resAPIToken = $queryAPIToken->fetch_assoc() )
{
	$projectsAllowedAPIToken[] = $resAPIToken['project_id'];
}

if ( ! empty( $_POST ) )
{
	// Check that the project is accessible (if applicable).
	if ( $_POST['action'] != 'update_comments' && $_POST['action'] != 'reset_password' &&
	     ! isset( $listAccessibleProjects[ $_POST['project_id'] ] ) )
	{
		echo 'Invalid request: project is not accessible.';
		exit;
	}
	// Perform the requested action.
	if ( $_POST['action'] == 'add_project' )
	{
		// Check the user is not currently assigned to the project.
		if ( $module->query( 'SELECT 1 FROM redcap_user_rights ' .
		                     'WHERE username = ? AND project_id = ?',
		                     [ $_GET['username'], $_POST['project_id'] ] )->num_rows > 0 )
		{
			echo 'Invalid request: user already assigned to project.';
			exit;
		}
		// Check that a valid role is selected.
		if ( SUPER_USER != 1 )
		{
			$projectAllowedRoles =
				isset( $listProjectAllowedRoles[ $_POST['project_id'] ] )
				? $listProjectAllowedRoles[ $_POST['project_id'] ] : $defaultAllowedRoles;
			if ( $module->query( 'SELECT role_id FROM redcap_user_roles ' .
			                     'WHERE project_id = ? AND role_id = ? ' .
			                     'AND role_name IN (' .
			                     substr( str_repeat(',?',count( $projectAllowedRoles )), 1 ) . ')',
			                     array_merge( [ $_POST['project_id'], $_POST['role_id'] ],
			                                  $projectAllowedRoles ) )->num_rows == 0 )
			{
				echo 'Invalid request: cannot assign user to selected role.';
				exit;
			}
		}
		// Get the DAGs to add the user to.
		$listDAGs = [];
		$listDAGNames = [];
		if ( isset( $_POST['dag']['*'] ) ) // no assignment (all DAGs) selected
		{
			if ( count( $_POST['dag'] ) > 1 || SUPER_USER != 1 )
			{
				echo 'Invalid request: not allowed to specify no assignment / all DAGs.';
				exit;
			}
		}
		else // specific DAGs selected
		{
			$queryCurrentProjectDAGs =
				$module->query( 'SELECT group_id, group_name FROM redcap_data_access_groups ' .
				                'WHERE project_id = ? ORDER BY group_name',
				                [ $_POST['project_id'] ] );
			while ( $currentProjectDAG = $queryCurrentProjectDAGs->fetch_assoc() )
			{
				$dagID = $currentProjectDAG['group_id'];
				if ( isset( $_POST['dag'][$dagID] ) )
				{
					$listDAGs[] = $dagID;
					$listDAGNames[] = $module->escapeHTML( $currentProjectDAG['group_name'] );
				}
			}
		}
		// Check that the user is added to at least one DAG.
		if ( SUPER_USER != 1 && count( $listDAGs ) == 0 )
		{
			echo 'Invalid request: user must be assigned to at least one DAG.';
			exit;
		}
		// Add the user to the project.
		$module->addUserToProject( $_GET['username'], $_POST['project_id'],
		                           $_POST['role_id'], $listDAGs );
		// If the notification email address has not been explicitly specified in the settings,
		// query the lookup project.
		if ( $projectNotifyEmail === null )
		{
			$lookupProject = $module->getSystemSetting( 'default-lookup-project' );
			$lookupLogic = $module->getSystemSetting( 'default-lookup-condition' );
			$lookupEmailField = $module->getSystemSetting( 'default-lookup-email' );
			if ( $lookupProject != '' && $lookupLogic != '' && $lookupEmailField != '' )
			{
				$lookupLogic = str_replace( '?', $_POST['project_id'], $lookupLogic );
				$lookupResult = json_decode( REDCap::getData( [ 'project_id' => $lookupProject,
				                                                'return_format' => 'json',
				                                                'filterLogic' => $lookupLogic ] ),
				                             true );
				if ( count( $lookupResult ) == 1 && isset( $lookupResult[0][$lookupEmailField] ) &&
				     $lookupResult[0][$lookupEmailField] != '' )
				{
					$projectNotifyEmail = $lookupResult[0][$lookupEmailField];
				}
			}
		}
		// If a notification email address is defined, either in the settings or the lookup project,
		// send the notification email.
		if ( $projectNotifyEmail !== null )
		{
			$fromUser = $module->getSystemSetting( 'admin-user' );
			if ( $fromUser == '' ||
			     $module->query( 'SELECT 1 FROM redcap_user_information WHERE username = ?',
			                     [ $fromUser ] )->num_rows == 0 )
			{
				$fromUser = USERID;
			}
			$fromUser = $module->query( 'SELECT * FROM redcap_user_information WHERE username = ?',
			                            [ $fromUser ] );
			$fromUser = $fromUser->fetch_assoc();
			$assignedProject = $module->query( 'SELECT app_title FROM redcap_projects ' .
			                                   'WHERE project_id = ?', [ $_POST['project_id'] ] );
			$assignedProject = $assignedProject->fetch_assoc();
			$assignedRole = $module->query( 'SELECT role_name FROM redcap_user_roles ' .
			                                'WHERE role_id = ?', [ $_POST['role_id'] ] );
			$assignedRole = $assignedRole->fetch_assoc();
			REDCap::email( $projectNotifyEmail, $fromUser['user_email'],
			               'REDCap - User ' . $infoUser['username'] . ' (' .
			               $infoUser['user_firstname'] . ' ' . $infoUser['user_lastname'] .
			               ') added to ' . $assignedProject['app_title'],
			               '<html><body>The following user has been added to ' .
			               $module->escapeHTML( $assignedProject['app_title'] ) . ':<br><br>' .
			               '<b>Username:</b> ' . $infoUser['username'] . '<br><b>Name:</b> ' .
			               $module->escapeHTML( $infoUser['user_firstname'] . ' ' .
			                                 $infoUser['user_lastname'] ) .
			               '<br><b>Email:</b> ' . $infoUser['user_email'] . '<br><br>' .
			               '<b>Role:</b> ' . $module->escapeHTML( $assignedRole['role_name'] ) .
			               '<br><br><b>Data Access Group(s):</b><br>' .
			               ( count( $listDAGs ) == 0 ?
			                 '<i>All</i>' : implode( '<br>', $listDAGNames ) ) .
			               '</body></html>', '', '',
			               $fromUser['user_firstname'] . ' ' . $fromUser['user_lastname'] );
		}
	}
	if ( $_POST['action'] == 'update_role' )
	{
		// Check the user is assigned to the project.
		if ( $module->query( 'SELECT 1 FROM redcap_user_rights ' .
		                     'WHERE username = ? AND project_id = ?',
		                     [ $_GET['username'], $_POST['project_id'] ] )->num_rows == 0 )
		{
			echo 'Invalid request: user not assigned to project.';
			exit;
		}
		// Check that the user cannot change their own role with the role switcher.
		if ( in_array( $_POST['project_id'], $listProjectsRoleSwitcher ) )
		{
			echo 'Invalid request: role switcher is in use for this user/project.';
			exit;
		}
		// Check that the old and new roles are valid.
		if ( SUPER_USER != 1 )
		{
			$projectAllowedRoles =
				isset( $listProjectAllowedRoles[ $_POST['project_id'] ] )
				? $listProjectAllowedRoles[ $_POST['project_id'] ] : $defaultAllowedRoles;
			if ( $module->query( 'SELECT 1 FROM redcap_user_rights uri JOIN redcap_user_roles ' .
			                     'uro ON uri.project_id = uro.project_id AND uri.role_id = ' .
			                     'uro.role_id WHERE uri.project_id = ? AND uri.username = ? AND ' .
			                     'uro.role_name IN (' .
			                     substr( str_repeat(',?',count( $projectAllowedRoles )), 1 ) . ')',
			                     array_merge( [ $_POST['project_id'], $_GET['username'] ],
			                                  $projectAllowedRoles ) )->num_rows == 0 )
			{
				echo 'Invalid request: cannot change role for this user.';
				exit;
			}
			if ( $module->query( 'SELECT role_id FROM redcap_user_roles ' .
			                     'WHERE project_id = ? AND role_id = ? ' .
			                     'AND role_name IN (' .
			                     substr( str_repeat(',?',count( $projectAllowedRoles )), 1 ) . ')',
			                     array_merge( [ $_POST['project_id'], $_POST['role_id'] ],
			                                  $projectAllowedRoles ) )->num_rows == 0 )
			{
				echo 'Invalid request: cannot assign user to selected role.';
				exit;
			}
		}
		// Perform the role change.
		$module->changeUserRole( $_GET['username'], $_POST['project_id'], $_POST['role_id'] );
	}
	if ( $_POST['action'] == 'update_dags' )
	{
		// Check the user is assigned to the project.
		if ( $module->query( 'SELECT 1 FROM redcap_user_rights ' .
		                     'WHERE username = ? AND project_id = ?',
		                     [ $_GET['username'], $_POST['project_id'] ] )->num_rows == 0 )
		{
			echo 'Invalid request: user not assigned to project.';
			exit;
		}
		// Check that the user cannot change their own role with the role switcher.
		if ( in_array( $_POST['project_id'], $listProjectsRoleSwitcher ) )
		{
			echo 'Invalid request: role switcher is in use for this user/project.';
			exit;
		}
		// Initialise the lists of DAGs to add and remove.
		$listAddDAGs = [];
		$listRemoveDAGs = [];
		$listHasDAGs = [];
		// Get the DAGs for the project, according to whether user already assigned.
		$queryCurrentProjectDAGs =
			$module->query( 'SELECT if( group_id IN ( SELECT group_id FROM ' .
			                'redcap_data_access_groups_users WHERE username = ? ), 1, 0 ) ' .
			                'AS active, group_concat( group_id SEPARATOR \',\' ) AS dags ' .
			                'FROM redcap_data_access_groups WHERE project_id = ? ' .
			                'GROUP BY active', [ $_GET['username'], $_POST['project_id'] ] );
		// Determine the DAGs to be added/removed.
		while ( $infoCurrentProjectDAGs = $queryCurrentProjectDAGs->fetch_assoc() )
		{
			if ( $infoCurrentProjectDAGs['active'] == 1 )
			{
				foreach ( explode( ',', $infoCurrentProjectDAGs['dags'] ) as $dagID )
				{
					if ( isset( $_POST['dag'][$dagID] ) )
					{
						$listHasDAGs[] = $dagID;
					}
					else
					{
						$listRemoveDAGs[] = $dagID;
					}
				}
			}
			else
			{
				foreach ( explode( ',', $infoCurrentProjectDAGs['dags'] ) as $dagID )
				{
					if ( isset( $_POST['dag'][$dagID] ) )
					{
						$listHasDAGs[] = $dagID;
						$listAddDAGs[] = $dagID;
					}
				}
			}
		}
		// Check that at least one DAG has been selected.
		if ( empty( $listHasDAGs ) )
		{
			echo 'Invalid request: at least one DAG must be selected.';
			exit;
		}
		// Perform the DAG add/remove.
		$module->changeUserDAGs( $_GET['username'], $_POST['project_id'],
		                         $listAddDAGs, $listRemoveDAGs );
	}
	if ( $_POST['action'] == 'app_api_token' )
	{
		// Check that the user has the mobile app privilege for the project and that they do not
		// already have an API token.
		if ( ! in_array( $_POST['project_id'], $projectsAllowedAPIToken ) )
		{
			echo 'Invalid request: cannot grant mobile app access.';
			exit;
		}
		// Grant the API token.
		$module->createAPITokenForUser( $_GET['username'], $_POST['project_id'] );
	}
	if ( $_POST['action'] == 'set_expire' )
	{
		if ( SUPER_USER != 1 && $_GET['username'] == USERID )
		{
			echo 'Invalid request: cannot change expiration on own account.';
			exit;
		}
		if ( isset( $_POST['revoke'] ) )
		{
			$_POST['expiration'] = date( 'Y-m-d' );
		}
		if ( $_POST['expiration'] == '' )
		{
			echo 'Invalid request: expiration date not provided.';
			exit;
		}
		$module->setUserProjectExpiry( $_GET['username'],
		                               $_POST['project_id'], $_POST['expiration'] );
	}
	if ( $_POST['action'] == 'update_comments' )
	{
		// Update the comments on the user record.
		$module->setUserComments( $_GET['username'], $_POST['comments'] );
	}
	if ( $_POST['action'] == 'reset_password' )
	{
		// Perform a password reset.
		$module->resetUserPassword( $_GET['username'] );
		$_SERVER['REQUEST_URI'] .= '&resetpw=1';
	}
	// Ensure that the user project list (first line of the comments on the user record), is set to
	// the projects which the user has been granted access to.
	$module->setUserProjectList( $_GET['username'] );
	header( 'Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	exit;
}

// Check if the user needs to be added to the allowlist.
$userNeedsAllowlist = false;
if ( preg_match( "/" . $internalUserRegex . "/", $_GET['username'] ) &&
     $module->query( 'SELECT 1 FROM redcap_user_allowlist WHERE username = ?',
                     [ $_GET['username'] ] )->num_rows == 0 )
{
	$userNeedsAllowlist = true;
}

// Get project information for the user.
$listAssignedProjects = [];
$listUnassignedProjects = [];
foreach ( $listAccessibleProjects as $projectID => $projectName )
{
	$infoProject =
		$module->query( 'SELECT redcap_projects.project_id, redcap_projects.app_title, ' .
		                'redcap_projects.purpose, redcap_user_rights.role_id, ' .
		                'redcap_user_roles.role_name, redcap_user_rights.group_id, ' .
		                'redcap_user_rights.expiration, if( redcap_user_rights.group_id IS NULL ' .
		                'OR redcap_user_rights.username IN (SELECT username FROM ' .
		                'redcap_data_access_groups_users WHERE project_id = ' .
		                'redcap_projects.project_id AND group_id IS NULL), 1, 0 ) AS ' .
		                'access_all_dags, if( api_token IS NOT NULL AND ' .
		                'ifnull(redcap_user_roles.mobile_app, redcap_user_rights.mobile_app) = 1,' .
		                ' 1, 0 ) AS mobile_app FROM redcap_projects JOIN redcap_user_rights ' .
		                'ON redcap_projects.project_id = redcap_user_rights.project_id ' .
		                'LEFT JOIN redcap_user_roles ON redcap_user_rights.role_id = ' .
		                'redcap_user_roles.role_id WHERE redcap_projects.project_id = ? AND ' .
		                'redcap_user_rights.username = ?',
		                [ $projectID, $_GET['username'] ] )->fetch_assoc();
	if ( $infoProject )
	{
		// For each assigned project, fetch the record and add the roles/DAGs data.
		$infoProject['dags'] = [];
		$infoProject['roles'] = [];
		$projectAllowedRoles =
			isset( $listProjectAllowedRoles[ $infoProject['project_id'] ] )
			? $listProjectAllowedRoles[ $infoProject['project_id'] ] : $defaultAllowedRoles;
		$queryProjectDAG =
			$module->query( 'SELECT group_id, group_name, if( group_id IN ( SELECT group_id FROM ' .
			                'redcap_data_access_groups_users WHERE username = ? ), 1, 0 ) AS ' .
			                'active FROM redcap_data_access_groups WHERE group_id IS NOT NULL ' .
			                'AND project_id = ? ORDER BY active DESC, group_name',
			                [ $_GET['username'], $infoProject['project_id'] ] );
		while ( $infoProjectDAG = $queryProjectDAG->fetch_assoc() )
		{
			if ( $infoProject['group_id'] !== null &&
			     $infoProjectDAG['group_id'] == $infoProject['group_id'] )
			{
				$infoProjectDAG['active'] = 1;
				array_unshift( $infoProject['dags'], $infoProjectDAG );
			}
			else
			{
				$infoProject['dags'][] = $infoProjectDAG;
			}
		}
		if ( ! empty( $projectAllowedRoles ) )
		{
			$queryProjectRole =
				$module->query( 'SELECT role_id, role_name ' .
				                'FROM redcap_user_roles WHERE project_id = ? ' .
				                ( SUPER_USER == 1 ? '' :
				                  ( 'AND role_name IN (' .
				                    substr( str_repeat(',?',count( $projectAllowedRoles )), 1 ) .
				                    ') ' ) ) .
				                'ORDER BY role_name',
				                array_merge( [ $infoProject['project_id'] ],
				                             ( SUPER_USER == 1 ? [] : $projectAllowedRoles ) ) );
			while ( $infoProjectRole = $queryProjectRole->fetch_assoc() )
			{
				$infoProject['roles'][] = $infoProjectRole;
			}
		}
		// Add the last project access time to the record and add to the list of assigned projects.
		$infoLastAccess = $module->query( 'SELECT ts FROM redcap_log_view WHERE user = ? AND ' .
		                                  'project_id = ? ORDER BY ts DESC LIMIT 1',
		                                  [ $_GET['username'],
		                                    $infoProject['project_id'] ] )->fetch_assoc();
		if ( ! $infoLastAccess )
		{
			$infoLastAccess = $module->query( 'SELECT ts FROM redcap_log_view_old WHERE user = ? ' .
			                                  'AND project_id = ? ORDER BY ts DESC LIMIT 1',
			                                  [ $_GET['username'],
			                                    $infoProject['project_id'] ] )->fetch_assoc();
		}
		$infoProject['last_access'] = $infoLastAccess ? $infoLastAccess['ts'] : null;
		$listAssignedProjects[] = $infoProject;
	}
	else
	{
		// For each unassigned project, fetch the record and add the roles/DAGs data.
		$infoProject = $module->query( 'SELECT project_id, app_title, purpose ' .
		                               'FROM redcap_projects WHERE project_id = ?',
		                               [ $projectID ] )->fetch_assoc();
		$projectAllowedRoles =
			isset( $listProjectAllowedRoles[ $infoProject['project_id'] ] )
			? $listProjectAllowedRoles[ $infoProject['project_id'] ] : $defaultAllowedRoles;
		$infoProject['dags'] = [];
		$infoProject['roles'] = [];
		$queryProjectDAG =
			$module->query( 'SELECT group_id, group_name FROM redcap_data_access_groups ' .
			                'WHERE group_id IS NOT NULL AND project_id = ? ORDER BY group_name',
			                [ $infoProject['project_id'] ] );
		while ( $infoProjectDAG = $queryProjectDAG->fetch_assoc() )
		{
			$infoProject['dags'][] = $infoProjectDAG;
		}
		if ( ! empty( $projectAllowedRoles ) )
		{
			$queryProjectRole =
				$module->query( 'SELECT role_id, role_name ' .
				                'FROM redcap_user_roles WHERE project_id = ? ' .
				                ( SUPER_USER == 1 ? '' :
				                  ( 'AND role_name IN (' .
				                    substr( str_repeat(',?',count( $projectAllowedRoles )), 1 ) .
				                    ') ' ) ) .
				                'ORDER BY role_name',
				                array_merge( [ $infoProject['project_id'] ],
				                             ( SUPER_USER == 1 ? [] : $projectAllowedRoles ) ) );
			while ( $infoProjectRole = $queryProjectRole->fetch_assoc() )
			{
				$infoProject['roles'][] = $infoProjectRole;
			}
		}
		// Only allow the user to be added to the project if (allowed) roles exist.
		if ( ! empty( $infoProject['roles'] ) )
		{
			$listUnassignedProjects[] = $infoProject;
		}
	}
}

// Get any comments associated with the user.
$userComments = $module->getUserComments( $_GET['username'] );

$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

require_once APP_PATH_VIEWS . 'HomeTabs.php';


?>
<div style="height:70px"></div>
<h3>User Project Assignment &#8212; <?php echo $module->escapeHTML( $_GET['username'] ); ?></h3>
<?php

if ( $userNeedsAllowlist )
{

?>
<p style="color:#990000;font-weight:bold">
 Warning: The username for this user matches the internal format but is not in the user allowlist.
 This user may therefore be prevented from accessing REDCap.
 Please <?php echo SUPER_USER == 1 ? '' : 'ask an administrator to'; ?> check the status
 of this user in the <?php echo $GLOBALS['lang']['global_07']; ?>.
</p>
<?php

}

if ( $infoUser['user_suspended_time'] != '' ||
     ( $infoUser['user_expiration'] != '' && $infoUser['user_expiration'] < date( 'Y-m-d' ) ) )
{

?>
<p style="color:#990000;font-weight:bold">
 Warning: This user account is currently suspended and is therefore prevented from accessing
 REDCap. Please <?php echo SUPER_USER == 1 ? '' : 'ask an administrator to'; ?> un-suspend this
 user in the <?php echo $GLOBALS['lang']['global_07']; ?> if required.
</p>
<?php

}

?>
<table style="width:100%">
 <tr>
  <th>First&nbsp;name:&nbsp;</th>
  <td style="width:100%"><?php echo $module->escapeHTML( $infoUser['user_firstname'] ); ?></td>
 </tr>
 <tr>
  <th>Last&nbsp;name:&nbsp;</th>
  <td><?php echo $module->escapeHTML( $infoUser['user_lastname'] ); ?></td>
 </tr>
 <tr>
  <th style="vertical-align:top">Email&nbsp;address:&nbsp;</th>
  <td><?php echo $module->escapeHTML( $infoUser['user_email'] ),
                 ( $infoUser['user_email2'] == '' ? '' :
                   ( '<br>' . $module->escapeHTML( $infoUser['user_email2'] ) ) ),
                 ( $infoUser['user_email3'] == '' ? '' :
                   ( '<br>' . $module->escapeHTML( $infoUser['user_email3'] ) ) ); ?></td>
 </tr>
 <tr>
  <th style="vertical-align:top">Comments:&nbsp;</th>
  <td>
   <form method="post">
    <textarea name="comments" onchange="$('#btnUpdateComments').css('display','')"
              onkeydown="$('#btnUpdateComments').css('display','')"
         style="width:100%;height:75px"><?php echo $module->escapeHTML( $userComments ); ?></textarea>
    <input type="submit" value="Update" id="btnUpdateComments" style="display:none">
    <input type="hidden" name="action" value="update_comments">
   </form>
  </td>
 </tr>
 <tr>
  <th>Last&nbsp;login:&nbsp;</th>
  <td><?php
echo $infoUser['user_lastlogin'] == '' ? 'never' : date( 'd M Y H:i',
                                                         strtotime( $infoUser['user_lastlogin'] ) );
?></td>
 </tr>
<?php
if ( $infoUser['table_based'] == 1 )
{
?>
 <tr>
  <th></th>
  <td>
<?php
	if ( isset( $_GET['resetpw'] ) )
	{
?>
   User password reset successfully.
<?php
	}
else
	{
?>
   <form method="post">
    <input type="submit" value="Reset user password"
           onclick="return confirm('Are you sure you want to reset this user\'s password?')">
    <input type="hidden" name="action" value="reset_password">
   </form>
<?php
	}
?>
  </td>
 </tr>
<?php
}
?>
</table>
<?php

if ( $infoUser['super_user'] == 1 )
{

?>
<p>
 <b>Note:</b> This user is an administrator, with access to all projects and data with maximum user
 privileges. They will have full access to all projects regardless of the settings on this page.
</p>
<?php

}

?>
<p>&nbsp;</p>
<h4>Assigned Projects</h4>
<?php
if ( count( $listAssignedProjects ) == 0 )
{
?>
<p>This user does not have any assigned projects.</p>
<p>&nbsp;</p>
<?php
}
foreach ( $listAssignedProjects as $infoProject )
{
?>
<div class="mod-umw-projfrm">
 <div>
  <h5 style="margin-top:15px"><?php echo $module->escapeHTML( $infoProject['app_title'] ); ?></h5>
<?php
	if ( SUPER_USER == 1 )
	{
?>
  <p><a href="<?php echo APP_PATH_WEBROOT, 'index.php?pid=',
                         intval( $infoProject['project_id'] ); ?>">Go to project</a></p>
<?php
	}
?>
 </div>
<?php
	if ( $infoProject['purpose'] != 2 )
	{
?>
 <p><i>(non-research project)</i></p>
<?php
	}
?>

 <form method="post">
  <p><b>Role:</b> <?php
	if ( $infoProject['role_name'] === null )
	{
		echo '<i>Custom role</i>';
	}
	else
	{
		echo '<span>', $module->escapeHTML( $infoProject['role_name'] ), ' </span>';
		if ( ! in_array( $infoProject['project_id'], $listProjectsRoleSwitcher ) &&
		     in_array( $infoProject['role_name'],
		               $listProjectAllowedRoles[ $infoProject['project_id'] ]
		                                                                ?? $defaultAllowedRoles ) &&
		     count( $infoProject['roles'] ) > 1 )
		{
			echo '&nbsp;&nbsp;<a href="#" onclick="$(this).prev().css(\'display\',\'none\');',
			     '$(this).css(\'display\',\'none\');$(this).next().css(\'display\',\'\');',
			     'return false">Change</a>';
			echo '<span style="display:none">';
			echo '<select name="role_id" onchange="$(this).next().css(\'display\',\'\')">';
			foreach ( $infoProject['roles'] as $infoRole )
			{
				echo '<option value="', $module->escapeHTML( $infoRole['role_id'] ), '"',
				     ( $infoProject['role_name'] == $infoRole['role_name'] ? ' selected' : '' ),
				     '>', $module->escapeHTML( $infoRole['role_name'] ), '</option>';
			}
			echo '</select> ';
			echo '<input type="submit" value="Change Role" style="display:none">';
			echo '<input type="hidden" name="project_id" value="',
			     intval( $infoProject['project_id'] ), '">';
			echo '<input type="hidden" name="action" value="update_role">';
			echo '</span>';
		}
	}
?></p>
 </form>
<?php
	if ( $infoProject['access_all_dags'] == 1 )
	{
?>
 <p>
  This user has not been assigned to any specific DAGs and therefore has access to all DAGs for this
  project. If you want to change the DAG assignment, please
  <?php echo SUPER_USER == 1 ? 'amend this on the project user rights page'
                             : 'ask an administrator to assign DAGs'; ?>.
 </p>
<?php
	}
	else
	{
?>
 <p style="margin-bottom:5px">
  <b>DAGs:</b>
<?php
		if ( ! in_array( $infoProject['project_id'], $listProjectsRoleSwitcher ) )
		{
?>
  &nbsp;
  <a onclick="$(this).css('display','none');$(this).parent().next().css('display','none');
              $(this).parent().next().next().css('display','');return false"
     href="#">Edit DAGs</a>
<?php
		}
?>
 </p>
 <ul>
<?php
		foreach ( $infoProject['dags'] as $infoDAG )
		{
			if ( $infoDAG['active'] == 0 )
			{
				break;
			}
?>
  <li><?php echo $module->escapeHTML( $infoDAG['group_name'] ); ?></li>
<?php
		}
?>
 </ul>
 <form method="post" style="display:none">
  <table style="min-width:30%">
<?php
		$lastDAGActive = -1;
		foreach ( $infoProject['dags'] as $infoDAG )
		{
			$DAGSep = ( $lastDAGActive == 1 && $infoDAG['active'] == 0 );
?>
   <tr class="mod-umw-trhover"<?php echo $DAGSep ? ' style="border-top:solid 1px #000"' : ''; ?>>
    <td style="width:32px">
     <input type="checkbox" name="dag[<?php echo intval($infoDAG['group_id']); ?>]" value="1"<?php
			echo $infoDAG['active'] == 1 ? ' checked' : ''; ?>></td>
    <td style="padding-left:5px;color:#<?php echo $infoDAG['active'] == 1 ? '003300' : '660000'; ?>">
     <?php echo $module->escapeHTML( $infoDAG['group_name'] ), "\n"; ?>
    </td>
   </tr>
<?php
			$lastDAGActive = $infoDAG['active'];
		}
?>
  </table>
  <p style="margin-bottom:15px">
   <input type="submit" value="Update DAG assignment">
   <input type="hidden" name="action" value="update_dags">
   <input type="hidden" name="project_id" value="<?php echo intval( $infoProject['project_id'] ); ?>">
  </p>
 </form>
<?php
	}

	if ( in_array( $infoProject['project_id'], $projectsAllowedAPIToken ) )
	{
?>
 <form method="post">
  <p>
   <b>Mobile app:</b>
   <input type="submit" value="Grant access" class="btnGrantAPI" data-project="<?php
		echo $module->escapeHTML( $infoProject['app_title'] );
?>">
   <input type="hidden" name="action" value="app_api_token">
   <input type="hidden" name="project_id" value="<?php echo intval( $infoProject['project_id'] ); ?>">
  </p>
 </form>
<?php
	}
	elseif ( $infoProject['mobile_app'] == 1 )
	{
?>
 <p><b>Mobile app:</b> Access granted.</p>
<?php
	}

?>
 <form method="post">
  <p>
   <b>Expiration:</b>
<?php
	if ( $infoProject['expiration'] == '' )
	{
?>
   The user's access to this project does not expire.&nbsp;
   <a onclick="$(this).css('display','none');$(this).next().css('display','');return false"
     href="#">Set expiration</a>
   <span style="display:none">
    <br><br>
    &nbsp;&nbsp; <input type="date" name="expiration" required>
    <input type="submit" value="Set expiration date">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="revoke" value="Revoke access immediately"
                                   onclick="$(this).prev().prev().prop('required',false)">
    <input type="hidden" name="action" value="set_expire">
    <input type="hidden" name="project_id" value="<?php echo intval( $infoProject['project_id'] ); ?>">
   </span>
<?php
	}
	elseif ( $infoProject['expiration'] > date( 'Y-m-d' ) )
	{
?>
   Access will expire on <?php echo date( 'd M Y', strtotime( $infoProject['expiration'] ) ); ?>.&nbsp;
   <a onclick="$(this).css('display','none');$(this).next().css('display','');return false"
     href="#">Change expiration</a>
   <span style="display:none">
    <br><br>
    &nbsp;&nbsp; <input type="date" name="expiration">
    <input type="submit" value="Change expiration date">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="revoke" value="Revoke access immediately">
    <input type="hidden" name="action" value="set_expire">
    <input type="hidden" name="project_id" value="<?php echo intval( $infoProject['project_id'] ); ?>">
   </span>
<?php
	}
	else
	{
?>
   Access expired on <?php echo date( 'd M Y', strtotime( $infoProject['expiration'] ) ); ?>.
<?php
	}
?>
  </p>
 </form>
 <p>
  <b>Last project access:</b>
  <?php echo $infoProject['last_access'] == '' ? 'never' :
          date( 'd M Y H:i', strtotime( $infoProject['last_access'] ) ); ?>.
 </p>
</div>
<p>&nbsp;</p>
<?php
}
?>
<h4>Assign User to Project</h4>
<form method="post">
 <table>
  <tr>
   <th>Project:&nbsp;</th>
   <td>
    <select name="project_id" id="assign_project_id" required>
     <option value="">Select...</option>
<?php
foreach ( $listUnassignedProjects as $infoProject )
{
?>
     <option value="<?php echo intval( $infoProject['project_id'] ); ?>"
             data-roles="<?php echo $module->escapeHTML( json_encode( $infoProject['roles'] ) ); ?>"
             data-dags="<?php echo $module->escapeHTML( json_encode( $infoProject['dags'] ) ); ?>"><?php
	echo $module->escapeHTML( $infoProject['app_title'] ),
	     ( $infoProject['purpose'] == 2 ? '' : ' &nbsp;(non-research project)' ); ?></option>
<?php
}
?>
    </select>
   </td>
  </tr>
  <tr>
   <th>Role:&nbsp;</th>
   <td>
    <select name="role_id" id="assign_role_id" required>
     <option value="">Select...</option>
    </select>
   </td>
  </tr>
  <tr>
   <th style="vertical-align:top">DAGs:&nbsp;</th>
   <td id="assign_dags"></td>
  </tr>
 </table>
 <p>
  <input type="submit" value="Assign to Project">
  <input type="hidden" name="action" value="add_project">
 </p>
</form>
<p>&nbsp;</p>

<script type="text/javascript">
$(function()
{
  $('input[name^="dag["]').click(function()
  {
    if ( $(this).form().find('input[name^="dag["]:checked').length == 0 )
    {
      $(this).form().find('input[name^="dag["]').each(function()
      {
        this.setCustomValidity('At least one DAG must be selected.')
      })
    }
    else
    {
      $(this).form().find('input[name^="dag["]').each(function(){ this.setCustomValidity('') })
    }
  })
  $('#assign_project_id').change(function()
  {
    var vSelectedItem = $('#assign_project_id :selected')
    var vRoleOptions = '<option value="">Select...</option>'
    var vDAGOptions = ''
    if ( vSelectedItem.val() != '' )
    {
      vSelectedItem.data('roles').forEach(function( vRole )
      {
        vRoleOptions += '<option value="' + vRole.role_id + '">' + vRole.role_name + '</option>'
      })
      if ( vSelectedItem.data('dags').length == 0 )
      {
        vDAGOptions = 'DAGs have not been set up for this project.'
      }
      else
      {
        vDAGOptions = '<table>'
<?php
if ( SUPER_USER == 1 )
{
?>
        vDAGOptions += '<tr class="mod-umw-trhover"><td>'
        vDAGOptions += '<input type="checkbox" name="dag[*]" value="1">'
        vDAGOptions += '</td><td>No Assignment (Access all DAGs)</td></tr>'
<?php
}
?>
        vSelectedItem.data('dags').forEach(function( vDAG )
        {
          vDAGOptions += '<tr class="mod-umw-trhover"><td>'
          vDAGOptions += '<input type="checkbox" name="dag[' + vDAG.group_id + ']" value="1">'
          vDAGOptions += '</td><td>' + vDAG.group_name + '</td></tr>'
        })
        vDAGOptions += '</table>'
      }
    }
    $('#assign_role_id').html(vRoleOptions)
    $('#assign_dags').html(vDAGOptions)
    vSelectedItem.closest('form').find('input[name^="dag["]').each(function()
    {
      this.setCustomValidity('At least one DAG must be selected.')
    })
    vSelectedItem.closest('form').find('input[name^="dag["]').click(function()
    {
      if ( $(this).closest('form').find('input[name^="dag["]:checked').length == 0 )
      {
        $(this).closest('form').find('input[name^="dag["]').each(function()
        {
          this.setCustomValidity('At least one DAG must be selected.')
        })
      }
      else if ( $(this).closest('form').find('input[name="dag[*]"]:checked').length == 1 &&
                $(this).closest('form').find('input[name^="dag["]:checked').length > 1 )
      {
        $(this).closest('form').find('input[name^="dag["]').each(function()
        {
          this.setCustomValidity('No assignment cannot be chosen in combination with specific DAGs.')
        })
      }
      else
      {
        $(this).closest('form').find('input[name^="dag["]').each(function()
        {
          this.setCustomValidity('')
        })
      }
    })
  })
  $('head').append('<style type="text/css">.mod-umw-projfrm{background:#f7f7f7;padding:1px 12px;' +
                   'border-radius:10px;border:solid 1px #ccc} ' +
                   '.mod-umw-projfrm > :first-child{display:flex;justify-content:space-between} ' +
                   '.mod-umw-trhover:hover{background:#ddd} .mod-umw-trhover td {padding:3px}' +
                   '</style>')
  var vGrantAPIDialog = $('<div>Are you sure you want to grant mobile app access for ' +
                          '<br><span></span>?</div>')
  var vGrantButton = null
  vGrantAPIDialog.dialog(
  {
    autoOpen:false,
    buttons:
    {
      OK: function()
      {
        vGrantButton.click()
        vGrantAPIDialog.dialog('close')
      },
      Cancel: function()
      {
        vGrantAPIDialog.dialog('close')
      }
    },
    close: function(e,u)
    {
      vGrantButton = null
    },
    modal:true,
    resizable:false,
    width:400
  })
  $('.btnGrantAPI').click( function(e)
  {
    if ( vGrantButton == null )
    {
      vGrantButton = $(this)
      vGrantAPIDialog.find('span').text(vGrantButton.data('project'))
      vGrantAPIDialog.dialog('open')
      e.preventDefault()
    }
  })
})
</script>
<?php

$HtmlPage->PrintFooterExt();
