### Acceptance tests folder

Acceptance tests for Entrada are created using Behat/Gherkin.

To run the tests you will have to install the Selenium Browser Automation tool, and the webdriver for the Chrome browser.
You will also need a clean installation of the Entrada-1x-me software.

1. Selenium

Go to <http://www.seleniumhq.org/download/> and select the latest version of the 'Selenium Standalone Server'

2. Web Driver for Chrome

Go to <https://sites.google.com/a/chromium.org/chromedriver/> and download the latest release of the driver

3. Java

Your system must have Java installed

## Running the tests

In a terminal window, start the Selenium Standalone Server, with the Chrome web driver

```
java -Dwebdriver.chrome.driver="chromedriver" -jar selenium-server-standalone-3.11.0.jar
```

The scripts assume that you have a blank, unconfigured Entrada installation at http://entrada-1x.me.localhost, 
and empty entrada databases `entrada_test`, `entrada_test_auth` and `entrada_test_clerkship` with an assigned account `entrada`, password `password`

If you want to change the location of the installation that is used, edit the `baseurl` in `behat.yml`

To run the test after configuring the blank Entrada install, start another terminal window and navigate to the Entrada-1x-me installation, then execute:

```
sh developers/acceptance-tests/behat.sh
```

## Running with Vagrant
To run the acceptance tests in a Vagrant environment, some additional configuration is required.

1. Vagrant Configuration
Set up port forwarding for 4444 in the Vagrantfile
```
  config.vm.network "forwarded_port", guest: 80, host: 80
  config.vm.network "forwarded_port", guest: 4444, host: 4444
  config.vm.network "forwarded_port", guest: 3306, host: 3306
```
2. Install Java on the Vagrant container
```
vagrant ssh
sudo yum install java
```
3. On the Vagrant VM, start the Selenium server in 'hub' mode
```
vagrant ssh
java -jar selenium-server-standalone-3.6.0.jar -role hub
```
4. On the host machine (Mac, Windows) start the Selenium server in 'node' mode
```
java -Dwebdriver.chrome.driver="chromedriver" -jar selenium-server-standalone-3.6.0.jar -role node -hub http://entrada-1x-me.dev:4444/grid/register
```
5. On the Vagrant VM, start the tests
```
vagrant ssh
cd /var/www/vhosts/entrada-1x-me.dev
sh developers/acceptance-tests/behat.sh
```