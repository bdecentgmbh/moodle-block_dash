@block @block_dash @dash_import_export @javascript @_file_upload
Feature: Export and import a Dash block
  In order to reuse a Dash block on another page or site
  As an admin
  I need to export a Dash block to a file and import it into another Dash block

  Background:
    Given I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block

  Scenario: Import a Dash block file into a new Dash block and export it again
    When I click on "Import from file" "button" in the "New Dash" "block"
    And I upload "blocks/dash/tests/fixtures/users_block.json" file to "Dash block file" filemanager
    And I click on "Import" "button" in the ".modal-dialog" "css_element"
    Then I should see "Dash block imported."
    And I should see "Imported users"
    And I open the "Imported users" blocks action menu
    And following "Export" should download between "100" and "5000" bytes
