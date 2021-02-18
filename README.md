## This repo is in transition from a restrictive vue.js/Entrada-based OLab player codebase to react.js/.Net.  The transition is expected to be complete in Q1 2021 while two separate feature branches are being reconciled.  In the meantime, the 'master' branch is stable for demo purposes.  CMW - 06-Dec-2020

# Welcome to OLab4

OLab4 is an open source web-based educational research platform that supports the creation and publication of virtual scenarios. Much of its original structure is based on OpenLabyrinth v3, a platform that was originally designed to support virtual patients but evolved into so much more. 

For more information please visit our website: http://olab.ca 

In its early phases, OLab4 is based on the Entrada framework, an open source web-based Integrated Teaching and Learning System created to allow teachers, learners and
curriculum managers a simple way of accessing, interacting, and managing curriculum within an educational environment. (http://www.entrada-project.org)

Internally, OLab4 still uses a simplified version of Entrada for basic housekeeping, such as user management, groups, roles etc. 

However, OLab4 remains a standalone project. You do not have to buy Entrada, or Elentra (its subsequent iteration), in order to use or install OLab4. There are no licensing commitments to those groups. The version of Entrada embedded in OLab4 will remain free and open-source. 

## Installing OLab

OLab does not have a production-grade release (yet).  ## The Docker deployment below has hard-coded passwords and is not, not, NOT intended for any publish-facing installations. ##.  At this time, the OLab4 container is purely for local development or demo purposes.

This note describes the step-by-step instructions for setting up the OLab4 demo Docker container on a host system.

### Prerequisites:

•	Host system with internet access (tested with Windows host, not tested with Linux or MAC (yet))

•	Docker Desktop

•	Read-access to the OLab4 git repository

•	GIT (command line, or a GUI like Git Kraken)

### Directions:

•	IMPORTANT: ensure that the host system does not have existing applications/services listening on the following ports: 80, 443, 3306.  Disable/stop these programs before continuing.

•	Edit the host system 'hosts' file and add the following entry:
	127.0.0.1 olab4.localhost    
  
•	Verify you can 'ping olab4.localhost' from a host system command prompt and that the name resolves to the IP address above.

•	Start Docker on the host system

•	Create a 'docker' directory on the host system (preferably with full permissions to the host system logged in user).  This is now the 'container root directory'.

•	Clone the OLab4 git repository into the container root directory.  Using GIT Bash or similar command line too, execute the following command lines:

	$ git clone https://github.com/olab/OLab4.git

•	Change into the OLab4/docker directory.  Verify the file 'docker-compose.yml' exists.

•	Execute the following command to create the 'olab4-developer' docker container:

	$ docker-compose up -d  
  
  Depending on the speed of the internet connection, those may take some time as the container creation downloads several images and operating system prerequisites.  We are aware that there are some warnings of deprecated packages (like ruby), but this will not adversely affect the container functionality.
  
  When docker-compose has completed, verify the 'olab4-developer' container is running.  If the container creation was successful, you should see the following two log file lines that signify that the container is running:
  
	2017-11-29 12:28:35,941 INFO success: httpd entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
	2017-11-29 12:28:35,941 INFO success: mariadb entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
    
•	Open a container shell prompt by selecting the running container in Docker Desktop and clicking the 'CLI' icon.   If working, the shell window should display the following command prompt:
  
	sh-4.2#
  
  Verify that necessary file shares to the host system are configured properly and are operational.
  
	sh-4.2# ls -l /var/lib/mysql
  
	total 28688
	-rw-rw---- 1 root root    16384 Dec  9 07:36 aria_log.00000001
	-rw-rw---- 1 root root       52 Dec  9 07:36 aria_log_control
	drwx------ 1 root root     4096 Dec  9 07:36 entrada
	drwx------ 1 root root     4096 Dec  9 07:36 entrada_auth
	drwx------ 1 root root     4096 Dec  9 07:36 entrada_clerkship
	-rw-rw---- 1 root root  5242880 Dec  9 07:36 ib_logfile0
	-rw-rw---- 1 root root  5242880 Dec  9 07:36 ib_logfile1
	-rw-rw---- 1 root root 18874368 Dec  9 07:36 ibdata1
	drwx------ 1 root root     4096 Dec  9 07:36 mysql
	-rw-rw---- 1 root root      350 Dec  9 07:36 olab4docker-slow.log
	drwx------ 1 root root     4096 Dec  9 07:36 openlabyrinth
	drwx------ 1 root root     4096 Dec  9 07:36 performance_schema
	drwx------ 1 root root     4096 Dec  9 07:36 test

  	sh-4.2# ls -l /var/www/vhosts/
  
	total 340
	-rwxrwxrwx 1 root root  35147 Dec  9 07:30 LICENSE
	-rwxrwxrwx 1 root root  26040 Dec  9 07:30 OLab4 Docker Setup Notes v2a.docx
	drwxrwxrwx 1 root root   4096 Dec  9 07:30 OLab4-api
	drwxrwxrwx 1 root root   4096 Dec  9 07:30 OLab4-designer
	drwxrwxrwx 1 root root   4096 Dec  9 07:30 OLab4-site
	-rwxrwxrwx 1 root root 267847 Dec  9 07:30 OLab4.phpproj
	-rwxrwxrwx 1 root root    921 Dec  9 07:30 OLab4.sln
	-rwxrwxrwx 1 root root   5452 Dec  9 07:30 README.md
	drwxrwxrwx 1 root root   4096 Dec  9 07:36 docker  
	
•	Run the following post-setup commands within the container to set up the runtime environment download and create the OLab4 demo databases, set up apache, and connect file shares from the host system:

	sh-4.2# cd /tmp
	sh-4.2# ./post-create.sh
	
  If, during the post-create step, you see the following message:
  
	Cloning failed using an ssh key for authentication, enter your GitHub credentials to access private repos
	Head to … to retrieve a token. It will be stored in "/root/.composer/auth.json" for future use by Composer.
	Token (hidden):
	a token is required.  Contact the OLab4 github repository manager for the token.
	
  let us know, as we are trying to diagnose and get around this problem.
  
  Several steps will run as part of the post-create.  If there are any other errors, screen shot them and report them to us.
  
•	Open your favorite browser window and navigate to:
	https://olab4.localhost/player
  
  The login dialog will appear if the container is configured properly.
  
•	Log into Olab4 as user ‘demo’ and password 'oldemo'.  Once logged in, look for the 'Maps' menu item and you should be good to go.

Any problems or questions, please contact us!

