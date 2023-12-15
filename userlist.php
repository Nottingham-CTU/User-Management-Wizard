<?php
/**
 *  Outputs the status of features required for the API client module.
 */

namespace Nottingham\UserManageWiz;

if ( $module->getProjectId() !== null )
{
	exit;
}

$listUsers = $module->getSystemSetting( 'wizard-users' );
$listUsers = array_map( 'trim', explode( "\n", $listUsers ) );
$userPlaceholder = substr( str_repeat( ',?', count( $listUsers ) ), 1 );
$queryUsers = $module->query( 'SELECT username, user_firstname, user_lastname FROM ' .
                              'redcap_user_information WHERE username IN (' . $userPlaceholder .
                              ') ORDER BY user_firstname, user_lastname', $listUsers );
$listUsers = [];
while ( $infoUser = $queryUsers->fetch_assoc() )
{
	$infoUser['projects'] = array_values( $module->getAccessibleProjects( $infoUser['username'] ) );
	$listUsers[] = $infoUser;
}

?>

<h4 style="margin-top:0"><i class="fas fa-user"></i> User Management Wizard User List</h4>
<p>
 Below is a list of all users granted access to the User Management Wizard and the projects which
 they can manage users for.
</p>
<table>
<?php
foreach ( $listUsers as $infoUser )
{
	$numProjects = count( $infoUser['projects'] );
?>
 <tr>
  <td style="vertical-align:top">
   <?php echo $module->escapeHTML( $infoUser['user_firstname'] . ' ' . $infoUser['user_lastname'] ); ?><br>
   <?php echo $module->escapeHTML( '(' . $infoUser['username'] . ')' ); ?><br>&nbsp;
  </td>
  <td style="vertical-align:top">
   <ul>
<?php
	for ( $i = 0; $i < ceil( $numProjects / 2 ); $i++ )
	{
?>
    <li><?php echo $module->escapeHTML( $infoUser['projects'][$i] ); ?></li>
<?php
	}
?>
   </ul>
   <br>&nbsp;
  </td>
  <td style="vertical-align:top">
   <ul>
<?php
	for ( $i = ceil( $numProjects / 2 ); $i < $numProjects; $i++ )
	{
?>
    <li><?php echo $module->escapeHTML( $infoUser['projects'][$i] ); ?></li>
<?php
	}
?>
   </ul>
   <br>&nbsp;
  </td>
 </tr>
<?php
}
?>
</table>