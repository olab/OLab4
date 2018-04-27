<?php
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;

class CourseContext implements Context {

    /** @var \Behat\MinkExtension\Context\MinkContext */
    private $minkContext;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext('Behat\MinkExtension\Context\MinkContext');
        $this->featureContext = $environment->getContext('FeatureContext');
    }

    /**
     * @When I add Curriculum Period to course
     */
    public function iAddCurriculumPeriodToCourse(TableNode $fields) {
        foreach ($fields->getHash() as $value) {

            $this->featureContext->iWaitForAjaxToFinish();
            $this->featureContext->iScrollTo("period_select");
            $this->minkContext->selectOption("period_select", $value["name"]);
            $num = $this->minkContext->getSession()->getPage()->findField("period_select")->getValue();
            $this->featureContext->iWaitForAjaxToFinish();
            $this->minkContext->clickLink("Add Audience");
            $this->minkContext->selectOption("audience_type_select_" . $num, $value["type"]);
            $this->minkContext->selectOption("cohort_select_" . $num, $value["typeValue"]);
        }
    }
}