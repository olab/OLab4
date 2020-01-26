<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This context defines steps using in all the Scenarios for Assessment & Evaluation
 *
 * @author Organisation: Queens University
 * @author Unit: Medtech Unit
 * @author Developer: Ivanilson Melo <im42@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 *
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Defines application features from the specific context.
 */
class ExamContext implements Context {

    /** @var \Behat\MinkExtension\Context\MinkContext */
    private $minkContext;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->minkContext = $environment->getContext('Behat\MinkExtension\Context\MinkContext');
    }

    /**
     * @Then /^I click on "([^"]*)"$/
     */
    public function iClickOn($element)
    {
        $page = $this->minkContext->getSession()->getPage();
        $findName = $page->find("css", $element);
        if (!$findName) {
            throw new Exception($element . " could not be found");
        } else {
            $findName->click();
        }
    }

    /**
     * @When I click on :arg1 where :arg2 is :arg3
     */
    public function iClickOnWhereIs($element, $attr, $value)
    {
        $page = $this->minkContext->getSession()->getPage();
        $selector = $element . '[' . $attr . '="' . $value . '"]';
        $findName = $page->find("css", $selector);
        if (!$findName) {
            throw new Exception($element . " could not be found");
        } else {
            $findName->click();
        }
    }

    /**
     * @When I click on the :arg1 :arg2 element
     */
    public function iClickOnTheElement($position, $element)
    {
        $page = $this->minkContext->getSession()->getPage();

        $selector = $element . ':nth-child(' . $position . ')';

        $findName = $page->find("css", $selector);
        if (!$findName) {
            throw new Exception($element . " could not be found");
        } else {
            $findName->click();
        }
    }
}
