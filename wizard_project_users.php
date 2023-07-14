<?php

define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

// Prohibit access to this page if the user is not allowed to access the wizard.
if ( ! $module->isAccessAllowed() )
{
	echo 'You do not have the rights to access this page.';
	exit;
}


// Prohibit access to this page if the chosen project is excluded.
$projectSettings = [ 'project-id' => $module->getSystemSetting( 'project-id' ),
                     'project-exclude' => $module->getSystemSetting( 'project-exclude' ) ];
for ( $i = 0; $i < count( $projectSettings['project-id'] ); $i++ )
{
	if ( $projectSettings['project-exclude'][$i] &&
	     $projectSettings['project-id'][$i] == $_GET['proj'] )
	{
		echo 'You do not have the rights to access this page.';
		exit;
	}
}

// Prohibit access to this page if the user does not have access to the project.
$queryProject = $module->query( 'SELECT project_id, app_title FROM redcap_projects ' .
	                            'WHERE project_id = ? AND completed_time IS NULL AND project_id ' .
	                            'NOT IN (SELECT project_id FROM redcap_projects_templates)' .
	                            ( SUPER_USER == 1 ? '' : ( ' AND purpose = 2 AND ' .
	                              'project_id IN ( SELECT project_id ' .
	                              'FROM redcap_user_rights WHERE username = ? AND ' .'
	                              ( expiration IS NULL OR expiration > NOW() ) )' ) ),
	                            ( SUPER_USER == 1 ? [ $_GET['proj'] ]
	                                              : [ $_GET['proj'], USERID ] ) );
$infoProject = $queryProject->fetch_assoc();

if ( $infoProject == null )
{
	echo 'You do not have the rights to access this page.';
	exit;
}

$queryProjectUsers =
	$module->query( 'WITH users AS ( SELECT username, expiration, role_id, group_id ' .
	                'FROM redcap_user_rights WHERE project_id = ? UNION SELECT gu.username, ' .
	                'ur.expiration, ur.role_id, gu.group_id FROM redcap_data_access_groups_users ' .
	                'gu JOIN redcap_user_rights ur ON gu.project_id = ur.project_id AND ' .
	                'gu.username = ur.username AND (gu.group_id <> ur.group_id OR ( gu.group_id ' .
	                'IS NULL AND ur.group_id IS NOT NULL ) OR ( gu.group_id IS NOT NULL AND ' .
	                'ur.group_id IS NULL ) ) WHERE gu.project_id = ? ) ' .
	                'SELECT users.username, users.expiration, redcap_user_roles.role_name, ' .
	                'redcap_data_access_groups.group_name, ui.user_firstname, ui.user_lastname, ' .
	                'ui.user_email, ui.user_suspended_time, ui.user_expiration FROM users ' .
	                'LEFT JOIN redcap_user_roles ON users.role_id = redcap_user_roles.role_id ' .
	                'AND redcap_user_roles.project_id = ? LEFT JOIN redcap_data_access_groups ' .
	                'ON users.group_id = redcap_data_access_groups.group_id AND ' .
	                'redcap_data_access_groups.project_id = ? LEFT JOIN redcap_user_information ' .
	                'ui ON users.username = ui.username ORDER BY if( group_name IS NULL, 1, 0 ), ' .
	                'group_name, if( role_name IS NULL, 1, 0 ), user_firstname, user_lastname',
	                [ $_GET['proj'], $_GET['proj'], $_GET['proj'], $_GET['proj'] ] );
$listProjectUsers = [];
while( $res = $queryProjectUsers->fetch_assoc() )
{
	$listProjectUsers[] = $res;
}

$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

require_once APP_PATH_VIEWS . 'HomeTabs.php';

?>
<div style="height:70px"></div>
<h3>Project Users &#8212; <?php echo htmlspecialchars( $infoProject['app_title'] ); ?></h3>
<?php
$lastGroupName = '';
foreach ( $listProjectUsers as $infoProjectUser )
{
	$inactiveUser = ( ( $infoProjectUser['user_suspended_time'] !== null &&
	                    strtotime( $infoProjectUser['user_suspended_time'] ) < time() ) ||
	                  ( $infoProjectUser['user_expiration'] !== null &&
	                    strtotime( $infoProjectUser['user_expiration'] ) < time() ) ||
	                  ( $infoProjectUser['expiration'] !== null &&
	                    strtotime( $infoProjectUser['expiration'] ) < time() ) );
	if ( $infoProjectUser['group_name'] !== $lastGroupName )
	{
		if ( $lastGroupName !== '' )
		{
?>
</table>
<?php
		}
		$thisGroupName = ( $infoProjectUser['group_name'] === null
		                   ? 'No Group (access all data)'
		                   : htmlspecialchars( $infoProjectUser['group_name'] ) );
?>
<p>&nbsp;</p>
<h4><?php echo $thisGroupName; ?></h4>
<table class="usertable">
 <tr>
  <th style="width:15%">Username</th>
  <th style="width:25%">Name</th>
  <th style="width:30%">Email</th>
  <th style="width:20%">Role</th>
  <th style="width:10%">Expires</th>
 </tr>
<?php
	}
?>
 <tr<?php echo $inactiveUser ? ' style="text-decoration:line-through"' : ''; ?>>
  <td><a href="<?php echo $module->getUrl( 'wizard_user_projects.php?username=' .
                                           rawurlencode( $infoProjectUser['username'] ) );
?>"><?php echo htmlspecialchars( $infoProjectUser['username'] ); ?></a></td>
  <td><?php echo htmlspecialchars( $infoProjectUser['user_firstname'] . ' ' .
                                   $infoProjectUser['user_lastname'] ); ?></td>
  <td><?php echo htmlspecialchars( $infoProjectUser['user_email'] ); ?></td>
  <td><?php echo htmlspecialchars( $infoProjectUser['role_name'] ); ?></td>
  <td><?php echo $infoProjectUser['expiration'] == '' ? ''
                  : date( 'd M Y', strtotime( $infoProjectUser['expiration'] ) ); ?></td>
 </tr>
<?php
	$lastGroupName = $infoProjectUser['group_name'];
}
?>
</table>
<script type="text/javascript">
$('head').append('<style type="text/css">.usertable {width:100%} .usertable th, ' +
                 '.usertable td {border:solid 1px #000;padding:3px} ' +
                 '#pagecontainer {max-width:1000px}</style>')
</script>
<?php


$HtmlPage->PrintFooterExt();
