# User-Management-Wizard
This REDCap External Module provides a simplified tool for configuring REDCap users and their
project/DAG assignments.

## System-level configuration options

### Users allowed to access the wizard
These are the usernames of the REDCap users with access to the wizard. Only users in this list will
see the link to the user management wizard. Users not in the list will not have access.

### Standard users can access Operational Support / Quality Improvement projects
Select these options to allow standard users (non-administrators) to manage Operational Support
and/or Quality Improvement projects in addition to Research projects.

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

### File path of cURL CA bundle
Path to a file containing CA certificates to validate HTTPS requests. If this is not specified, the
CA bundle file specified in the php.ini configuration file will be used. If a CA bundle file is not
specified in php.ini, then the CA bundle included with REDCap will be used.

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

### Specific project settings

#### Project
The project to which the specific project settings relate.

#### Exclude this project
Tick to exclude the project from the wizard.

#### Project role names to allow users to be assigned to
If specified, override the global setting for this.

#### Notification email address for project
If specified, use this instead of the notification email address from the lookup project.
