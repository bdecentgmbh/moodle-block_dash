@block @block_dash @dash_addblock @javascript
Feature: Add blocks and Dash gallery items with Dash's "Add a block" chooser
  In order to start from a ready-made Dash setup
  As a teacher
  I need the Dash gallery offered next to the blocks when I add a block

  Background:
    Given Boost Union lets plugins replace the add block button
    And the following config values are set as admin:
      | theme | boost_union |
    And the following config values are set as admin:
      | replaceaddblock | 1 | block_dash |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    # Boost Union keeps the block drawer closed under Behat once its settings have values.
    And the following "user preferences" exist:
      | user  | preference        | value |
      | admin | drawer-open-block | 1     |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Blocks are selected and added like gallery items
    When I click on "Add a block" "link"
    And I set the field "Search blocks and gallery" to "activity dates"
    And I should not see "Text" in the "Add a block" "dialogue"
    And I click on "Calendar" "button" in the "Add a block" "dialogue"
    Then I should see "Displays events from the calendar, such as activity dates" in the "Add a block" "dialogue"
    And I click on "Add to page" "button" in the "Add a block" "dialogue"
    And "Calendar" "block" should exist
    And I click on "Add a block" "link"
    And I click on "Text" "button" in the "Add a block" "dialogue"
    And I should see "Adds text, multimedia or widgets to the page." in the "Add a block" "dialogue"
    And I should see "You set it up before it is added." in the "Add a block" "dialogue"
    And I click on "Add to page" "button" in the "Add a block" "dialogue"
    And "Add a block" "dialogue" should not be visible
    And I click on "Cancel" "button" in the "Add Text block" "dialogue"
    And "Add a block" "dialogue" should be visible
    And I click on "Add to page" "button" in the "Add a block" "dialogue"
    And I set the following fields in the "Add Text block" "dialogue" to these values:
      | Text block title | Notes            |
      | Content          | Bring your books |
    And I click on "Save changes" "button" in the "Add Text block" "dialogue"
    And "Notes" "block" should exist

  Scenario: A Dash block is added set up from a gallery item
    When I click on "Add a block" "link"
    And I set the field "Search blocks and gallery" to "participants"
    Then I should see "Course participants" in the "Add a block" "dialogue"
    And I should not see "Calendar" in the "Add a block" "dialogue"
    And I click on "Course participants" "button" in the "Add a block" "dialogue"
    And I should see "The participants of the current course as cards" in the "Add a block" "dialogue"
    And I click on "Add to page" "button" in the "Add a block" "dialogue"
    And I should see "Set up from the gallery item \"Course participants\"."
    And "Participants" "block" should exist

  Scenario: Without the setting, the button is core's
    Given the following config values are set as admin:
      | replaceaddblock | 0 | block_dash |
    And I reload the page
    When I click on "Add a block" "link"
    Then I should see "Calendar" in the "Add a block" "dialogue"
    And I should not see "Course participants" in the "Add a block" "dialogue"
