<?php
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;

class CurriculumLayoutContext implements Context {

    /** @var \Behat\MinkExtension\Context\MinkContext */
    private $minkContext;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext('Behat\MinkExtension\Context\MinkContext');
    }

    /**
     * @Then I add Curriculum Period
     */
    public function iAddCurriculumPeriod(TableNode $fields) {
        $count = 1;
        foreach ($fields->getHash() as $value) {
            $this->minkContext->getSession()->getPage()->pressButton("Add Curriculum Period");
            $this->minkContext->getSession()->getPage()->fillField("start_add-". $count, $value["start"]);
            $this->minkContext->getSession()->getPage()->fillField("finish_add-". $count, $value["end"]);
            $this->minkContext->getSession()->getPage()->fillField("curriculum_period_title_add-". $count, $value["name"]);
            $count++;
        }
    }
}