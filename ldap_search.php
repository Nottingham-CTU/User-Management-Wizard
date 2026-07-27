<?php


define( 'MODULE_USER_MANAGEMENT_WIZARD', true );

if ( ! $module->isAccessAllowed() )
{
	echo 'You do not have the rights to access this page.';
	exit;
}


if ( ! $module->getSystemSetting('ldap-search-enable') ||
     ! in_array( $GLOBALS['auth_meth_global'], [ 'ldap', 'ldap_table' ] ) )
{
	exit;
}

if ( isset( $_POST['search'] ) )
{
	require_once APP_PATH_DOCROOT . 'Libraries/PEAR/Auth/Container/LDAP.php';
	$infoLdap = $GLOBALS['ldapdsn'];
	if ( isset( $infoLdap[0] ) && is_array( $infoLdap[0] ) )
	{
		$infoLdap = $infoLdap[0];
	}
	$ldap = new Auth_Container_LDAP( $infoLdap );
	$searchAttrs = $module->getSystemSetting('ldap-search-attrs');
	$searchAttrs = $searchAttrs == '' ? ['mail'] : explode(',', $searchAttrs);
	array_walk( $searchAttrs, fn($v) => preg_replace( '/[^A-Za-z]/', '', $v ) );
	$searchString = $ldap->_quoteFilterString( $_POST['search'] );
	$searchString = '(|' .
		array_reduce( $searchAttrs,
		              fn($c, $i) => ( $c .
		                              ( $i == '' ? '' : ( '(' . $i . '=*' . $searchString . '*)' ) )
		                            ),
		              '' ) . ')';
	$ldap->_connect();
	$ldapSearch = ldap_search($ldap->conn_id,$infoLdap['basedn'],$searchString,
	                          array_merge([$infoLdap['userattr']],$searchAttrs));
	if ( $ldapItem = ldap_first_entry( $ldap->conn_id, $ldapSearch ) )
	{
		echo '<table>';
		do
		{
			echo '<tr>';
			$ldapAttributes = ldap_get_attributes( $ldap->conn_id, $ldapItem );
			unset( $ldapAttributes['count'] );
			if ( is_array( $ldapAttributes[$infoLdap['userattr']] ) )
			{
				if ( isset( $ldapAttributes[$infoLdap['userattr']]['count'] ) )
				{
					unset( $ldapAttributes[$infoLdap['userattr']]['count'] );
				}
				$ldapAttributes[$infoLdap['userattr']] =
					$ldapAttributes[$infoLdap['userattr']][0];
			}
			echo '<td style="padding:3px"><input type="button" value="',
			     $module->escape( $ldapAttributes[$infoLdap['userattr']] ), '" ',
			     'onclick="$(\'[name=&quot;username&quot;]\').val($(this).val());$(this).closest(',
			     '\'[id^=&quot;popup&quot;]\').parent().find(\'.ui-dialog-titlebar button\')',
			     '.click()"></td>';
			foreach ( $ldapAttributes as $ldapAttributeName => $ldapAttributeValue )
			{
				if ( is_int( $ldapAttributeName ) || $ldapAttributeName == $infoLdap['userattr'] )
				{
					continue;
				}
				if ( is_array( $ldapAttributeValue ) && isset( $ldapAttributeValue['count'] ) )
				{
					unset( $ldapAttributeValue['count'] );
				}
				echo '<td style="padding:3px">';
				echo $module->searchHighlight(
				        is_array( $ldapAttributeValue )
				                  ? implode( ', ', $ldapAttributeValue ) : $ldapAttributeValue,
				        $_POST['search'] );
				echo '</td>';
			}
			echo '</tr>';
		} while ( $ldapItem = ldap_next_entry( $ldap->conn_id, $ldapItem ) );
		echo '</table>';
	}
	else
	{
		echo 'No results found.';
	}
	exit;
}


echo '<div><input style="width:calc(100% - 140px)"> <input type="button" value="Search" ',
     'onclick="var e=this;$.post(\'', $module->getUrl('ldap_search.php'),
     '\',{\'search\':$(e).prev().val()},function(d){$(e).parent().html(d)})"></div>';
