<?php

namespace Nottingham\UserManageWiz;


class UserManageWiz extends \ExternalModules\AbstractExternalModule
{

	const REDCAP_CAINFO = APP_PATH_DOCROOT . '/Resources/misc/cacert.pem';
	const ROOT_PATH = APP_PATH_WEBROOT_FULL;
	const VERSION_PATH = self::ROOT_PATH . 'redcap_v' . REDCAP_VERSION . '/';



	// Hook to run at the top of every REDCap page.
	public function redcap_every_page_top( $project_id = null )
	{
		// Return immediately from this function if in a project or access to the user management
		// wizard is not allowed for the current user.
		if ( $project_id !== null || ! $this->isAccessAllowed() )
		{
			return;
		}

		// Add the user management wizard link to the navigation bar.
?>
<script type="text/javascript">
$(function()
{
  var navBar = $('#redcap-home-navbar-collapse ul').first()
  navBar.append('<li class="nav-item"><a class="nav-link" style="color:#008000;padding:15px 8px"' +
                ' href="<?php echo $this->getUrl( 'wizard_find_user.php' ); ?>">' +
                '<i class="fas fa-users"></i> User Management</a></li>')
  fixNavBarHeight()
<?php

		// Highlight the user management wizard link if on a wizard page.
		if ( defined( 'MODULE_USER_MANAGEMENT_WIZARD' ) )
		{
?>
  $('.nav-item.active').removeClass('active')
  $('.nav-item .fas.fa-users').parent().parent().addClass('active')
<?php
		}

?>
})
</script>
<?php
	}



	// Prohibit enabling this module on projects.
	public function redcap_module_project_enable( $version, $project_id )
	{
		$this->removeProjectSetting( 'enabled', $project_id );
	}



	// Check if the current user is allowed to access the user management wizard.
	public function isAccessAllowed()
	{
		$listUsers = $this->getSystemSetting( 'wizard-users' );
		if ( $listUsers == '' || ! defined( 'USERID' ) || USERID == '' )
		{
			return false;
		}
		$listUsers = array_map( 'trim', explode( "\n", $listUsers ) );
		return in_array( USERID, $listUsers );
	}



	// Create a user profile for a new internal (LDAP) user. This should be run after the user has
	// been added to the allowlist.
	public function addInternalUserProfile( $username, $firstname, $lastname, $email )
	{
		// Open session as the target user.
		$sessionID = $this->startUserSession( $username );
		// Save the user profile (firstname, lastname, email).
		$curl = curl_init( self::VERSION_PATH . 'Profile/user_info_action.php' );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'firstname=' . rawurlencode( $firstname ) .
		                                        '&lastname=' . rawurlencode( $lastname ) .
		                                        '&email=' . rawurlencode( $email ) .
		                                       '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// Close user session.
		$this->endUserSession( $sessionID );
		// If wizard user not an admin, set their username as the new user's sponsor.
		if ( ! SUPER_USER )
		{
			$this->query( 'UPDATE redcap_user_information ' .
			              'SET user_sponsor = ? WHERE username = ? LIMIT 1',
			              [ USERID, $username ] );
		}
		// Ensure that the user does NOT receive emails about system notifications.
		$this->query( 'UPDATE redcap_user_information SET messaging_email_general_system = 0 ' .
		              'WHERE username = ?', [ $username ] );
	}



	// Create a user profile for a new external (table based) user.
	public function addExternalUserProfile( $username, $firstname, $lastname, $email, $sponsor )
	{
		// If wizard user not an admin, force sponsor value to their username.
		$sponsor = SUPER_USER ? $sponsor : USERID;
		// Open administrative session.
		$sessionID = $this->startUserSession();
		// Submit the create user page.
		$curl = curl_init( self::VERSION_PATH . 'ControlCenter/create_user.php' );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'username=' . rawurlencode( $username ) .
		                                        '&user_firstname=' . rawurlencode( $firstname ) .
		                                        '&user_lastname=' . rawurlencode( $lastname ) .
		                                        '&user_email=' . rawurlencode( $email ) .
		                                        '&user_email2=&user_email3=&user_inst_id=' .
		                                        '&user_comments=&user_expiration=' .
		                                        '&user_sponsor=' . rawurlencode( $sponsor ) .
		                                        '&messaging_email_urgent_all=1' .
		                                        '&display_on_email_users=on' .
		                                       '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Ensure that the user does NOT receive emails about system notifications.
		$this->query( 'UPDATE redcap_user_information SET messaging_email_general_system = 0 ' .
		              'WHERE username = ?', [ $username ] );
	}



	// Add an internal (LDAP) user to the user allowlist.
	public function addUserToAllowlist( $username )
	{
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Submit allowlist entry.
		$curl = curl_init( self::VERSION_PATH . 'ControlCenter/user_allowlist.php' );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest'] );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'action=add&username=' . rawurlencode( $username ) .
		                                       '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// End administrative session.
		$this->endUserSession( $sessionID );
	}



	// Add a user to a project, with the specified role and DAGs.
	public function addUserToProject( $username, $projectID, $roleID, $listDAGs )
	{
		$projectID = intval( $projectID );
		// Determine whether the user should be notified about their new project (send the
		// notification only if the user has previously logged in to REDCap).
		$userNotify =
			$this->query( 'SELECT 1 FROM redcap_user_information WHERE username = ? AND ' .
			              'user_firstvisit IS NOT NULL', [ $username ] )->num_rows;
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Submit user project assignment.
		$curl = curl_init( self::VERSION_PATH . 'UserRights/assign_user.php?pid=' . $projectID );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS,
		                    'username=' . rawurlencode( $username ) .
		                    '&role_id=' . rawurlencode( $roleID ) .
		                    '&notify_email_role=' . intval( $userNotify ) .
		                    '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// Clear any existing DAG switcher assignments which may remain if the user was previously
		// assigned to this project.
		$this->query( 'DELETE FROM redcap_data_access_groups_users ' .
		              'WHERE project_id = ? AND username = ?', [ $projectID, $username ] );
		// Add the user to the DAGs using both the standard DAG assignment and the DAG switcher.
		$first = true;
		foreach ( $listDAGs as $dag )
		{
			if ( $first )
			{
				$curl = curl_init( self::VERSION_PATH . 'index.php?route=DataAccessGroups' .
				                   'Controller:ajax&action=add_user&pid=' . $projectID );
				$this->configureCurl( $curl, $sessionID );
				curl_setopt( $curl, CURLOPT_POST, true );
				curl_setopt( $curl, CURLOPT_POSTFIELDS,
				                    'user=' . rawurlencode( $username ) .
				                    '&group_id=' . intval( $dag ) .
				                    '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
				curl_exec( $curl );
				curl_close( $curl );
			}
			$curl = curl_init( self::VERSION_PATH .
			                   'index.php?route=DataAccessGroupsController:saveUserDAG&pid=' .
			                   $projectID );
			$this->configureCurl( $curl, $sessionID );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS,
			                    'user=' . rawurlencode( $username ) .
			                    '&dag=' . intval( $dag ) . '&enabled=true' .
			                    '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
			curl_exec( $curl );
			curl_close( $curl );
			$first = false;
		}
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Write the action to the project log.
		\REDCap::logEvent( 'User Management Wizard',
		                   "User '$username' added to project by '" . USERID ."'", null, null,
		                   null, $projectID );
	}



	// Change the DAGs the user is assigned to for a specified project.
	public function changeUserDAGs( $username, $projectID, $addDAGs, $removeDAGs )
	{
		$projectID = intval( $projectID );
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Add/remove the user from the specified DAGs.
		foreach ( [ 'true' => $addDAGs, 'false' => $removeDAGs ] as $enableDAG => $listDAGs )
		{
			foreach ( $listDAGs as $dag )
			{
				$curl = curl_init( self::VERSION_PATH .
				                   'index.php?route=DataAccessGroupsController:saveUserDAG&pid=' .
				                   $projectID );
				$this->configureCurl( $curl, $sessionID );
				curl_setopt( $curl, CURLOPT_POST, true );
				curl_setopt( $curl, CURLOPT_POSTFIELDS,
				                    'user=' . rawurlencode( $username ) .
				                    '&dag=' . intval( $dag ) . '&enabled=' . $enableDAG .
				                    '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
				curl_exec( $curl );
				curl_close( $curl );
			}
		}
		// Check that the user is assigned to a DAG which is within their DAG switcher assignments
		// and reassign the user if not.
		if ( $this->query( 'SELECT 1 FROM redcap_user_rights r WHERE username = ? AND ' .
		                   'project_id = ? AND group_id IS NOT NULL AND group_id IN ( ' .
		                   'SELECT group_id FROM redcap_data_access_groups_users ' .
		                   'WHERE username = r.username AND project_id = r.project_id )',
		                   [ $username, $projectID ] )->num_rows == 0 )
		{
			$selectedDAG = $this->query( 'SELECT group_id FROM redcap_data_access_groups_users ' .
			                             'WHERE username = ? AND project_id = ? AND group_id ' .
			                             'IS NOT NULL LIMIT 1',
			                             [ $username, $projectID ] )->fetch_assoc();
			if ( $selectedDAG !== null && $selectedDAG['group_id'] !== null )
			{
				$selectedDAG = $selectedDAG['group_id'];
				$curl = curl_init( self::VERSION_PATH . 'index.php?route=DataAccessGroups' .
				                   'Controller:ajax&action=add_user&pid=' . $projectID );
				$this->configureCurl( $curl, $sessionID );
				curl_setopt( $curl, CURLOPT_POST, true );
				curl_setopt( $curl, CURLOPT_POSTFIELDS,
				                    'user=' . rawurlencode( $username ) .
				                    '&group_id=' . intval( $selectedDAG ) .
				                    '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
				curl_exec( $curl );
				curl_close( $curl );
			}
		}
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Write the action to the project log.
		\REDCap::logEvent( 'User Management Wizard',
		                   "DAGs changed for user '$username' by '" . USERID . "'", null, null,
		                   null, $projectID );
	}



	// Change the role the user is assigned to for a specific project.
	public function changeUserRole( $username, $projectID, $roleID )
	{
		$projectID = intval( $projectID );
		$currentDAG = $this->query( 'SELECT group_id FROM redcap_user_rights ' .
		                            'WHERE project_id = ? AND username = ?',
		                            [ $projectID, $username ] )->fetch_assoc();
		$currentDAG = $currentDAG['group_id'] ?? '';
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Change user role.
		$curl = curl_init( self::VERSION_PATH .
		                   'UserRights/assign_user.php?pid=' . $projectID );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'username=' . rawurlencode( $username ) .
		                                        '&role_id=' . rawurlencode( $roleID ) .
		                                        '&group_id=' . rawurlencode( $currentDAG ) .
		                                       '&redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Write the action to the project log.
		\REDCap::logEvent( 'User Management Wizard',
		                   "Role changed for user '$username' by '" . USERID . "'", null, null,
		                   null, $projectID );
	}



	// Create an API token for the specified user and project.
	// This is intended only to allow the user to use the mobile app. The token will not be returned
	// by this function as the user is not expected to use it directly.
	public function createAPITokenForUser( $username, $projectID )
	{
		$projectID = intval( $projectID );
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Create API token.
		$curl = curl_init( self::VERSION_PATH .
		                   'ControlCenter/user_api_ajax.php?action=createToken&api_pid=' .
		                   $projectID . '&api_username=' . rawurlencode( $username ) .
		                   '&api_export=0&api_import=0&mobile_app=1&api_send_email=0' );
		$this->configureCurl( $curl, $sessionID );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, 'redcap_csrf_token=' . rawurlencode( $sessionID ) );
		curl_exec( $curl );
		curl_close( $curl );
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Write the action to the project log.
		\REDCap::logEvent( 'User Management Wizard',
		                   "API token created for user '$username' by '" . USERID . "'", null, null,
		                   null, $projectID );
	}



	// Escapes text for inclusion in HTML.
	function escapeHTML( $text )
	{
		return htmlspecialchars( $text, ENT_QUOTES );
	}



	// Get a list of the accessible projects for the wizard user.
	public function getAccessibleProjects( $username )
	{
		// Get excluded projects.
		$excludedProjects = [];
		$projectSettings = [ 'project-id' => $this->getSystemSetting( 'project-id' ),
		                     'project-exclude' => $this->getSystemSetting( 'project-exclude' ) ];
		for ( $i = 0; $i < count( $projectSettings['project-id'] ); $i++ )
		{
			if ( $projectSettings['project-exclude'][$i] &&
			     preg_match( '/^[0-9]+$/', $projectSettings['project-id'][$i] ) )
			{
				$excludedProjects[] = $projectSettings['project-id'][$i];
			}
		}
		$excludedProjects = empty( $excludedProjects ) ? '' :
		                    ( 'AND project_id NOT IN (' . implode( ',', $excludedProjects ) . ')' );
		// Determine whether the user is an administrator.
		$isAdmin = $this->query( 'SELECT 1 FROM redcap_user_information WHERE super_user = 1 ' .
		                           'AND username = ?', [ $username ] )->fetch_assoc() != false;
		// Determine whether operational support / quality improvement projects can be accessed.
		$canOpSup = $this->getSystemSetting( 'access-op-sup' );
		$canQualImp = $this->getSystemSetting( 'access-qual-imp' );
		$purposeSQL = 'purpose = 2' . ( $canOpSup ? ' OR purpose = 4' : '' ) .
		                              ( $canQualImp ? ' OR purpose = 3' : '' );
		// Get project IDs and titles for accessible projects.
		$listProjects = [];
		$queryProject = $this->query( 'SELECT project_id, app_title FROM redcap_projects ' .
		                              'WHERE completed_time IS NULL AND project_id ' .
		                              'NOT IN (SELECT project_id FROM redcap_projects_templates) ' .
		                              ( $isAdmin ? '' : ( 'AND (' . $purposeSQL . ') AND ' .
		                                'project_id IN ( SELECT project_id ' .
		                                'FROM redcap_user_rights WHERE username = ? AND ' .'
		                                ( expiration IS NULL OR expiration > NOW() ) ) ' ) ) .
		                              $excludedProjects . ' ORDER BY ' .
		                              'if( purpose > 1, purpose, 6 - purpose ), app_title',
		                              ( $isAdmin ? [] : [ $username ] ) );
		while ( $infoProject = $queryProject->fetch_assoc() )
		{
			$listProjects[ $infoProject['project_id'] ] = $infoProject['app_title'];
		}
		return $listProjects;
	}



	// Get any comments associated with this user.
	// This excludes the first line of the comments field in the database, as this is used to record
	// the research projects the user is added to, for easy identification when viewed within the
	// admin interface.
	public function getUserComments( $username )
	{
		$infoUser = $this->query( 'SELECT user_comments FROM redcap_user_information ' .
		                          'WHERE username = ?', [ $username ] )->fetch_assoc();
		if ( $infoUser === null )
		{
			return '';
		}
		$comments = preg_split( "/(\r\n|\n|\r)/", $infoUser['user_comments'], 2 );
		if ( count( $comments ) < 2 )
		{
			return '';
		}
		return $comments[1];
	}



	// Get the list of research projects the user is added to, as recorded in the first line of
	// the comments field in the database for the user.
	public function getUserProjectList( $username )
	{
		$infoUser = $this->query( 'SELECT user_comments FROM redcap_user_information ' .
		                          'WHERE username = ?', [ $username ] )->fetch_assoc();
		if ( $infoUser === null )
		{
			return '';
		}
		$comments = preg_split( "/(\r\n|\n|\r)/", $infoUser['user_comments'], 2 );
		if ( count( $comments ) < 2 )
		{
			return '';
		}
		return $comments[0];
	}



	// Reset a user's password.
	public function resetUserPassword( $username )
	{
		// Get user information.
		$infoUser = $this->query( 'SELECT ui_id, user_lastlogin FROM redcap_user_information ' .
		                          'WHERE username = ?', [ $username ] )->fetch_assoc();
		// Start administrative session.
		$sessionID = $this->startUserSession();
		// Perform password reset.
		if ( $infoUser['user_lastlogin'] == '' )
		{
			// Not logged in yet, just resend account creation email.
			$curl = curl_init( self::VERSION_PATH . 'ControlCenter/view_users.php?' .
			                   'criteria_search=1&msg=admin_save&d=&search_term=&search_attr=' );
			$this->configureCurl( $curl, $sessionID );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS,
			                    'redcap_csrf_token=' . rawurlencode( $sessionID ) .
			                    '&uiid_' . rawurlencode( $infoUser['ui_id'] ) . '=on' .
			                    '&type=' . rawurlencode( 'resend account creation email' ) );
		}
		else
		{
			// Previously logged in, send password reset.
			$curl = curl_init( self::VERSION_PATH . 'ControlCenter/user_controls_ajax.php?' .
			                   'action=reset_password&username=' . rawurlencode( $username ) );
			$this->configureCurl( $curl, $sessionID );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS,
			                    'redcap_csrf_token=' . rawurlencode( $sessionID ) );
		}
		curl_exec( $curl );
		curl_close( $curl );
		// End administrative session.
		$this->endUserSession( $sessionID );
		// Write the action to the log.
		if ( $infoUser['user_lastlogin'] == '' )
		{
			\REDCap::logEvent( 'User Management Wizard',
			                  "Resent account creation email for '$username' by '" . USERID . "'" );
		}
		else
		{
			\REDCap::logEvent( 'User Management Wizard',
			                   "Password reset for user '$username' by '" . USERID . "'" );
		}
	}



	// Add bold formatting to any instances of $query found within $string.
	// Used for highlighting search terms within results.
	public function searchHighlight( $string, $query )
	{
		$string = htmlspecialchars( $string );
		$query = htmlspecialchars( $query );
		return preg_replace( '(' . preg_quote( $query ) . ')i', '<b>$0</b>', $string );
	}



	// Set the comments associated with the user.
	// This will leave the first line of the comments field unchanged, as this records the research
	// projects the user has been added to.
	public function setUserComments( $username, $newComments )
	{
		$infoUser = $this->query( 'SELECT user_comments FROM redcap_user_information ' .
		                          'WHERE username = ?', [ $username ] )->fetch_assoc();
		if ( $infoUser === null )
		{
			return;
		}
		$comments = preg_replace( "/(\r\n|\n|\r).*$/sD", '$1', $infoUser['user_comments'], 2 );
		if ( preg_match( "/^(\r\n|\n|\r)?$/D", $comments ) )
		{
			$comments = ".\r\n";
		}
		elseif ( ! preg_match( "/(\r\n|\n|\r)/", $comments ) )
		{
			$comments .= "\r\n";
		}
		$comments .= $newComments;
		$this->query( 'UPDATE redcap_user_information SET user_comments = ? ' .
		              'WHERE username = ? LIMIT 1', [ $comments, $username ] );
	}



	// Set the user's expiry date for a project.
	public function setUserProjectExpiry( $username, $projectID, $dateExpiry )
	{
		$infoUser = $this->query( 'SELECT 1 FROM redcap_user_rights ' .
		                          'WHERE username = ? AND project_id = ?',
		                          [ $username, $projectID ] )->fetch_assoc();
		if ( $infoUser === null ||
		     !preg_match( '/^2[0-9]{3}-(0[1-9]|1[012])-([012][0-9]|3[01])$/', $dateExpiry ) )
		{
			return;
		}
		$this->query( 'UPDATE redcap_user_rights SET expiration = ? ' .
		              'WHERE username = ? AND project_id = ? LIMIT 1',
		              [ $dateExpiry, $username, $projectID ] );
		// Write the action to the project log.
		\REDCap::logEvent( 'User Management Wizard',
		                   "Access to project for user '$username' set to expire " .
		                   "on $dateExpiry by '" . USERID . "'", null, null, null, $projectID );
	}



	// Sets the first line of the user comments field in the database to the list of research
	// projects the user has been added to.
	public function setUserProjectList( $username )
	{
		$oldProjectList = $this->getUserProjectList( $username );
		$newProjectList = $this->query( "SELECT group_concat(app_title ORDER BY app_title " .
		                                "SEPARATOR ', ') AS list FROM redcap_projects " .
		                                "WHERE purpose = 2 AND project_id IN (SELECT project_id " .
		                                "FROM redcap_user_rights WHERE username = ?)",
		                                [ $username ] )->fetch_assoc();
		if ( $newProjectList === null )
		{
			$newProjectList = '';
		}
		else
		{
			$newProjectList = $newProjectList['list'];
		}
		if ( $oldProjectList == $newProjectList )
		{
			return;
		}
		$comments = $this->getUserComments( $username );
		$comments = $newProjectList . "\r\n" . $comments;
		$this->query( 'UPDATE redcap_user_information SET user_comments = ? ' .
		              'WHERE username = ? LIMIT 1', [ $comments, $username ] );
	}



	// Validation of the module settings.
	public function validateSettings( $settings )
	{
		if ( $this->getProjectID() !== null )
		{
			return null;
		}

		$errMsg = '';

		// If the administrator username is set, ensure that username exists.
		if ( $settings['admin-user'] != '' &&
		     $this->query( 'SELECT 1 FROM redcap_user_information WHERE username = ? ' .
		                   'AND super_user = 1 AND account_manager = 1',
		                   [ $settings['admin-user'] ] )->num_rows == 0 )
		{
			$errMsg .= "\n- Administrator user " . $settings['admin-user'] . " does not exist";
		}

		// Check the validity of the regular expression for internal usernames.
		if ( $settings['internal-user-regex'] != '' &&
		     preg_match( '/' . $settings['internal-user-regex'] . '/', '' ) === false )
		{
			$errMsg .= "\n- Invalid regular expression for internal usernames";
		}

		// Check the validity of the regular expression for internal email addresses.
		if ( $settings['internal-email-regex'] != '' &&
		     preg_match( '/' . $settings['internal-email-regex'] . '/', '' ) === false )
		{
			$errMsg .= "\n- Invalid regular expression for internal email addresses";
		}

		// Check that the cURL CA bundle file exists.
		if ( $settings['curl-ca-bundle'] != '' && ! is_file( $settings['curl-ca-bundle'] ) )
		{
			$errMsg .= "\n- The cURL CA bundle file does not exist";
		}

		// Check that specific project settings are completed correctly.
		if ( count( $settings['project-id'] ) > 1 && in_array( '', $settings['project-id'] ) )
		{
			$errMsg .= "\n- Project must be specified for each set of project specific settings.";
		}
		elseif ( count( array_unique( $settings['project-id'] ) ) < count( $settings['project-id'] ) )
		{
			$errMsg .= "\n- Each project can be specified at most once in project specific settings.";
		}

		if ( $errMsg != '' )
		{
			return "Your configuration contains errors:$errMsg";
		}

		return null;
	}



	// Set up a cURL connection.
	private function configureCurl( &$curl, $sessionID )
	{
		$curlCertBundle = $this->getSystemSetting('curl-ca-bundle');
		if ( $curlCertBundle != '' )
		{
			curl_setopt( $curl, CURLOPT_CAINFO, $curlCertBundle );
		}
		elseif ( ini_get( 'curl.cainfo' ) == '' )
		{
			curl_setopt( $curl, CURLOPT_CAINFO, self::REDCAP_CAINFO );
		}
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $curl, CURLOPT_COOKIESESSION, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_COOKIE, session_name() . '=' . $sessionID );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

	}



	// Start a session to perform actions through REDCap as another user.
	private function startUserSession( $username = null )
	{
		$sessionID = 'userwiz_';
		while ( strlen( $sessionID ) < 16 )
		{
			do
			{
				$byte = random_bytes( 1 );
			} while ( preg_match( '/[^a-z0-9]/', $byte ) );
			$sessionID .= $byte;
		}

		if ( $username === null )
		{
			$username = $this->getSystemSetting( 'admin-user' );
			$username = $username == '' ? USERID : $username;
		}
		$ts = time() - 1;

		$sessionData = [ '_authsession' => [ 'data' => [ 'uid' => $username ],
		                                     'registered' => true,
		                                     'username' => $username,
		                                     'timestamp' => $ts,
		                                     'idle' => $ts ],
		                 'username' => $username,
		                 'redcap_csrf_token' => [ date( 'Y-m-d H:i:s', $ts ) => $sessionID ] ];
		$sessionData = $this->sessionSerialize( $sessionData );

		$sessionExp = date( 'Y-m-d H:i:s', $ts + 300 );

		$this->query( 'INSERT INTO redcap_sessions ' .
		              'SET session_id = ?, session_data = ?, session_expiration = ?',
		              [ $sessionID, $sessionData, $sessionExp ] );

		return $sessionID;
	}



	// End a previously started user session.
	private function endUserSession( $sessionID )
	{
		$this->query( 'DELETE FROM redcap_sessions WHERE session_id = ?', [ $sessionID ] );
	}



	// Serialization for session data.
	private function sessionSerialize( $inputData )
	{
		$outputData = '';
		foreach ( $inputData as $key => $value )
		{
			$outputData .= $key . '|' . serialize( $value );
		}
		return $outputData;
	}



	// Unserialization for session data.
	private function sessionUnserialize( $inputData )
	{
		$outputData = [];
		$offset = 0;
		while ($offset < strlen($inputData)) {
			if (!strstr(substr($inputData, $offset), "|")) {
				return [];
			}
			$pos = strpos( $inputData, "|", $offset );
			$num = $pos - $offset;
			$varname = substr( $inputData, $offset, $num );
			$offset += $num + 1;
			$data = unserialize( substr( $inputData, $offset ) );
			$outputData[ $varname ] = $data;
			$offset += strlen( serialize( $data ) );
		}
		return $outputData;
	}

}

