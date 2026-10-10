@block @block_dash @dash_gallery @javascript @_file_upload
Feature: Gallery of ready-made Dash setups
  In order to reuse good Dash setups
  As an admin
  I need to save blocks to the gallery, manage the gallery and set up blocks from it

  Background:
    Given I log in as "admin"

  Scenario: Save a block to the gallery and set up a new block from it
    Given I create dash "Users" datasource
    And I configure the "New Dash" block
    And I set the field "Block title" to "Our team"
    And I press "Save changes"
    When I open the "Our team" blocks action menu
    And I click on "Save to gallery" "link" in the "Our team" "block"
    And I set the following fields to these values:
      | Name      | Team list |
      | Shortname | team-list |
    And I click on "Save changes" "button" in the ".modal-dialog" "css_element"
    Then I should see "Saved \"Team list\" to the gallery."
    And I add the "Dash" block
    And I click on "Choose from gallery" "button" in the "New Dash" "block"
    And I click on "Team list" "button" in the ".modal-dialog" "css_element"
    And I click on "Use this item" "button" in the ".modal-dialog" "css_element"
    And I should see "Set up from the gallery item \"Team list\"."
    And I should not see "New Dash"

  Scenario: Hide a shipped item and upload an item on the gallery page
    Given I navigate to "Plugins > Blocks > Dash gallery" in site administration
    And I should see "My learning" in the "block-dash-gallery-items" "table"
    When I click on "Actions" "button" in the "My learning" "table_row"
    And I click on "Hide" "link" in the "My learning" "table_row"
    Then I should see "Hidden" in the "My learning" "table_row"
    And I click on "Upload item" "button"
    And I upload "blocks/dash/tests/fixtures/users_block.json" file to "Dash block file" filemanager
    And I set the following fields to these values:
      | Name      | Uploaded users |
      | Shortname | uploaded-users |
    And I click on "Save changes" "button" in the ".modal-dialog" "css_element"
    And I should see "Saved \"Uploaded users\" to the gallery."
    And I should see "uploaded-users" in the "Uploaded users" "table_row"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Choose from gallery" "button" in the "New Dash" "block"
    And I should see "Uploaded users" in the ".modal-dialog" "css_element"
    And I should not see "My learning" in the ".modal-dialog" "css_element"
