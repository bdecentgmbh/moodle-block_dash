@block @block_dash @relations_conditions @javascript @_file_upload
Feature: Dash program to show the list of cohort course
  In order to show the course data source in dash block on the dashboard
  As an admin
  I can add the dash block to the dashboard

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Category 2 | 0        | CAT2     |
      | Category 3 | 0        | CAT3     |
      | Category 4 | CAT3     | CAT4     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | CAT2     | 0                |
      | Course 3 | C3        | CAT3     | 1                |
      | Course 4 | C4        | CAT4     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | student2 | Student   | Second   | student2@example.com |
      | student3 | Student   | Third    | student3@example.com |
      | student4 | Student   | Fourth   | student4@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | teacher2 | Teacher   | Second   | teacher2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | admin    | C1     | manager |
      | admin    | C2     | manager |
      | admin    | C4     | manager |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | student1 | C4     | student |
      | student2 | C1     | student |
      | student2 | C2     | student |
      | student3 | C1     | student |
      | student4 | C1     | student |
      | student4 | C2     | student |
      | teacher1 | C1     | teacher |
      | teacher1 | C2     | teacher |
      | teacher2 | C1     | teacher |
    And I log in as "admin"
    And I turn dash block editing mode on
    And I create dash "Users" datasource
    And I configure the "New Dash" block
    And I set the following fields to these values:
      | Block title  | Users 	 |
      | Region       | content |
    And I press "Save changes"
    And I press "Reset Dashboard for all users" 
    And I log out

  Scenario: new condition:relations
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    #---Create new parent role---#
    And I set the following fields to these values:
      | Short name 			 					 | Parent1 	|
      | Custom full name 					 | Parent 1 |
      | contextlevel30             | 1      	|
      | moodle/user:viewdetails    | 1 				|
			| moodle/user:viewalldetails | 1 				|
    And I click on "Create this role" "button"
    And I click on "List all roles" "button"
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    #---Create new parent role---#
    And I set the following fields to these values:
      | Short name       					 | Parent2 	|
      | Custom full name 					 | Parent 2 |
      | contextlevel30             | 1      	|
      | moodle/user:viewdetails    | 1 				|
			| moodle/user:viewalldetails | 1 				|
    And I click on "Create this role" "button"
    And I follow "Dashboard"
    #---Assign parent to child---#    
    And I am on the "student1" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 1"
    And I set the field "addselect" to "Teacher First (teacher1@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"
    
    And I am on the "student2" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 1"
    And I set the field "addselect" to "Teacher First (teacher1@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"

    And I am on the "student3" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 1"
    And I set the field "addselect" to "Teacher First (teacher1@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"   
    
    #---Assign parent to child---#
    And I am on the "teacher1" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 2"
    And I set the field "addselect" to "Teacher Second (teacher2@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"

    And I am on the "student2" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 2"
    And I set the field "addselect" to "Teacher Second (teacher2@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"

    And I am on the "student4" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    #And I click on "Preferences" "link" in the ".breadcrumb" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent 2"
    And I set the field "addselect" to "Teacher Second (teacher2@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"
    #---Condition setting---#
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    #---Set User i manage in conditions---#
    And I open the "Users" block preference
    And I click on "Conditions" "link"
    And I set the field "config_preferences[filters][parentrole][enabled]" to "1"
    And I press "Save changes"
    And I press "Reset Dashboard for all users"
    And I log out
    #---Teacher login---#
    And I log in as "teacher2"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
		And I should see "student2@example.com" in the ".dash-table tbody tr:nth-child(1) td:nth-child(3)" "css_element"
		And I should see "student4@example.com" in the ".dash-table tbody tr:nth-child(2) td:nth-child(3)" "css_element"
		And I should see "teacher1@example.com" in the ".dash-table tbody tr:nth-child(3) td:nth-child(3)" "css_element"
		And I log out
    #---Teacher login---#
    And I log in as "teacher1"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
		And I should see "student1@example.com" in the ".dash-table tbody tr:nth-child(1) td:nth-child(3)" "css_element"
		And I should see "student2@example.com" in the ".dash-table tbody tr:nth-child(2) td:nth-child(3)" "css_element"
		And I should see "student3@example.com" in the ".dash-table tbody tr:nth-child(3) td:nth-child(3)" "css_element"
		And I log out
		And I log in as "admin"
    #---Condition setting---#
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    #---Set User i manage in conditions---#
    And I open the "Users" block preference
    And I click on "Conditions" "link"
    And I set the field "config_preferences[filters][parentrole][enabled]" to "1"
    And I set the field "config_preferences[filters][parentrole][roleids][]" to "Parent 1"
    And I press "Save changes"
    And I press "Reset Dashboard for all users"
    #---Check parent and child users---#
    And I follow "Dashboard"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
    #---Admin log out---#
    And I log out
    #---Parent login---#
    And I log in as "teacher1"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
    And I should see "student1@example.com" in the ".dash-table tbody tr:nth-child(1) td:nth-child(3)" "css_element"
    And I should see "student2@example.com" in the ".dash-table tbody tr:nth-child(2) td:nth-child(3)" "css_element"
    And I should see "student3@example.com" in the ".dash-table tbody tr:nth-child(3) td:nth-child(3)" "css_element"
    And I log out
    #---Parent login---#
    And I log in as "teacher2"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
    And I should not see "student1@example.com"
    And I should not see "student2@example.com"
    And I log out
    #---Admin login---#
    And I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    #---Set User i manage in conditions---#
    And I open the "Users" block preference
    And I click on "Conditions" "link"
    And I set the field "config_preferences[filters][parentrole][enabled]" to "1"
    And I set the field "config_preferences[filters][parentrole][roleids][]" to "Parent 2"
    And I press "Save changes"
    And I press "Reset Dashboard for all users"
    And I log out
    #---Parent login---#
    And I log in as "teacher1"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
    And I should not see "student1@example.com"
		And I should not see "student2@example.com"
    And I log out
    #---Parent login---#
    And I log in as "teacher2"
    And I should see "Users" in the ".block_dash-local-layout-grid_layout" "css_element"
    And I should see "student2@example.com" in the ".dash-table tbody tr:nth-child(1) td:nth-child(3)" "css_element"
		And I should see "student4@example.com" in the ".dash-table tbody tr:nth-child(2) td:nth-child(3)" "css_element"
		And I should see "teacher1@example.com" in the ".dash-table tbody tr:nth-child(3) td:nth-child(3)" "css_element"
