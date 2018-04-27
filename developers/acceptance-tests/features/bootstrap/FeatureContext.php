<?php

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext {
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    private function getPage() {
        return $this->getSession()->getPage();
    }

    /**
     * @Given I am logged in as :arg1 with password :arg2
     */
    public function iAmLoggedInAsWithPassword($username, $password) {
        $this->getSession()->getDriver()->maximizeWindow();
        $this->visitPath('/');
        $this->getPage()->fillField('username', $username);
        $this->getPage()->fillField('password', $password);
        $this->getPage()->pressButton("Login");
        $this->iWaitForAjaxToFinish();
    }

    /**
     * @Then I fill in texteditor on field :arg1 with :arg2
     */
    public function iFillInTextEditorFieldWith($locator, $value) {
        $el = $this->getPage()->findField($locator);

        if (empty($el)) {
            throw new ExpectationException('Could not find WYSIWYG with locator: ' . $locator, $this->getSession());
        }

        $fieldId = $el->getAttribute('id');

        if (empty($fieldId)) {
            throw new Exception('Could not find an id for field with locator: ' . $locator);
        }

        $this->getSession()->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");");
    }

    /**
     * @Then I scroll to :element
     */
    public function iScrollTo($element) {
        $this->getSession()->wait(
            10000,
            "jQuery('html,body').scrollTop(
                jQuery('#" . $element . "').offset().top
            );"
        );
        $this->iWaitASecond();
    }

    /**
     * Wait for AJAX to finish.
     *
     * @Given /^I wait for AJAX to finish$/
     */
    public function iWaitForAjaxToFinish() {
        $this->getSession()->wait(10000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
        $this->iWaitASecond();
    }

    /**
     * @When /^I check the "([^"]*)" radio button$/
     */
    public function iCheckTheRadioButton($radioLabel) {
        $radioButton = $this->getPage()->findField($radioLabel);
        if (null === $radioButton) {
            throw new Exception("Cannot find radio button ".$radioLabel);
        }
        $this->getSession()->getDriver()->click($radioButton->getXPath());
    }

    /**
     * @Given /^I check the "([^"]*)" check button with "([^"]*)" value$/
     */
    public function iCheckTheCheckBoxWithValue($element, $value) {
        foreach ($this->getPage()->findAll('css', 'input[type="checkbox"][name="'. $element .'"]') as $check) {
            if ($check->getAttribute('value') == $value) {
                $check->check();
                return true;
            }
        }
        return false;
    }

    /**
     * @Then I click on the text :selector
     */
    public function iClickOnTheText($selector) {
        $element = $this->getPage()->find("named", array("content", $selector));
        $id = $element->getAttribute("id");
        if ($id != "") {
            $this->iScrollTo($id);
        }

        $element->click();
    }

    /**
     * @When /^(?:|I )click in the following:$/
     */
    public function fillFields(TableNode $fields) {
        foreach ($fields->getRowsHash() as $field => $value) {
            $this->iWaitForAjaxToFinish();
            $this->iClickOnTheText($field);
        }
    }

    /**
     * Wait a second. OK actually half a second
     *
     * @Then /^I wait a second$/
     */
    public function iWaitASecond() {
        $this->getSession()->wait(500);
    }

    /**
     * @Then I follow link where :arg1 is :arg2
     */
    public function iFollowLinkWhereIs($attr, $value) {
        $this->getSession()->wait(
            10000,
            "jQuery('[" . $attr . "=\"" . htmlentities($value) . "\"]').click();"
        );
    }

    /** @AfterStep */
    public function afterStep(AfterStepScope $scope)
    {
        $navigation_steps =
            [
                "I follow",
                "I am on",
                "I press",
                "I select",
                "I check",
                "I scroll to",
                "I click on the text"
            ];

        $step_text = $scope->getStep()->getText();

        foreach ($navigation_steps as $nav) {
            if (strpos($step_text, $nav) !== false) {
                $this->iWaitForAjaxToFinish();
                return;
            }
        }
    }
}
