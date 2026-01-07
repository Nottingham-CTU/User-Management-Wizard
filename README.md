# User Management Wizard
This REDCap External Module provides a simple tool for configuring REDCap users and their
project/DAG assignments. Any user granted access to the User Management Wizard can add new users and
assign users to defined roles in their projects, without needing to be granted neither the manage
user accounts administrative permission nor the project-level user rights permission.

Note that if you have REDCap configured to use table based users plus an additional authentication
method, the User Management Wizard refers to users from the additional authentication method (e.g.
LDAP or Shibboleth users) as *Internal* users and table based users as *External* users, to reflect
the user's likely place with regard to your organisation. If necessary, these labels can be changed
in the settings.

&#9888;&#65039; If you are using two-factor authentication in REDCap, you should add the localhost
IP address (`127.0.0.1` or `::1`) and the server's IP address to the IP address exceptions,
otherwise this module may not function.

## System-level configuration options

### Users allowed to access the wizard
These are the usernames of the REDCap users with access to the wizard. Only users in this list will
see the link to the user management wizard. Users not in the list will not have access.

### Standard users can access Operational Support / Quality Improvement projects
Select these options to allow standard users (non-administrators) to manage Operational Support
and/or Quality Improvement projects in addition to Research projects.

### Standard users can access projects in Analysis/Cleanup status
Select this option to allow standard users (non-administrators) to manage projects which have been
placed into Analysis/Cleanup status.

### Standard users can assign users to access all DAGs
For projects with DAGs, select this option to allow standard users to assign users to access all
DAGs (i.e. no DAG assignment). If this option is not selected, only administrators can add users to
projects without a DAG assignment.
<br><br>

### Administrator username
Some functions of the wizard are performed through REDCap as an administrator. Specify the
administrator username to be used for this here.

### Regular expression of internal usernames
When adding internal users, their username will be validated to match this regular expression.
When adding external users, their username will be validated to not match.

For example, if internal usernames consist of 2 or 3 letters followed by zero or more digits, use
`^[a-z]{2,3}[0-9]*$`

### Regular expression of internal email addresses
When adding external users, their email address will be validated to not match this regular
expression.

For example, to treat *@example.com* addresses as internal, use `@example\.com$`

### Custom heading (Internal User / External User)
Enter values here to change the *Internal User* and *External User* headings from the default.

### File path of cURL CA bundle
Path to a file containing CA certificates to validate HTTPS requests. If this is not specified, the
CA bundle file specified in the php.ini configuration file will be used. If a CA bundle file is not
specified in php.ini, then the CA bundle included with REDCap will be used.
<br><br>

### Project role names to allow users to be assigned to
Define the role names that a user can be assigned to within a project when using the wizard. Only
administrators will be able to use the wizard to assign a user to a role not in this list.

### Lookup project
Optionally specify a project which contains additonal information about projects.

### Lookup condition logic
The conditional logic to filter the records in the lookup project and to return the record for the
specified project.

### Lookup notification email field name
The field name in the lookup project for the field which contains an email address to send a
notification whenever a user is added to the project.
<br><br>

### Specific project settings

#### Project
The project to which the specific project settings relate.

#### Exclude this project
Tick to exclude the project from the wizard.

#### Project role names to allow users to be assigned to
If specified, override the global setting for this.

#### Notification email address for project
If specified, use this instead of the notification email address from the lookup project.
