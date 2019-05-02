# Welcome to OLab4

OLab4 is an open source web-based educational research platform that supports the creation and publication of virtual scenarios. Much of its original structure is based on OpenLabyrinth v3, a platform that was originally designed to support virtual patients but evolved into so much more. 

For more information please visit our website: http://olab.ca 

In its early phases, OLab4 is based on the Entrada framework, an open source web-based Integrated Teaching and Learning System created to allow teachers, learners and
curriculum managers a simple way of accessing, interacting, and managing curriculum within an educational environment. (http://www.entrada-project.org)

Internally, OLab4 still uses a simplified version of Entrada for basic housekeeping, such as user management, groups, roles etc. 

However, OLab4 remains a standalone project. You do not have to buy Entrada, or Elentra (its subsequent iteration), in order to use or install OLab4. There are no licensing commitments to those groups. The version of Entrada embedded in OLab4 will remain free and open-source. 

## Installing OLab

This note describes the step-by-step instructions for setting up the OLab4 demo Docker container on a host system.

Prerequisites:

•	Windows/Mac/Linux host system
•	Docker CE installed on host system
•	Kitematic installed on the host system
•	Read-access to the OLab4 git repository (contact David for permission)
•	GIT Bash shell installed on the host system (if Windows, part of GIT for Windows install)

Directions:

•	IMPORTANT: ensure that the host system does not have existing applications listening for connections on the following ports: 80, 443, 3306.  Disable/stop these programs before continuing.

•	Edit the host system 'hosts' file and add the following entry:
  127.0.0.1 olab4.localhost    
  
•	Verify you can 'ping olab4.localhost' from a host system command line and that the name resolves to the IP address above.

•	Start Docker on the host system

•	Create a 'docker' directory on the host system (preferably with full permissions to the host system logged in user).  This is now the 'container root directory'.

•	Clone the OLab4 repositories into the container root directory.  Using GIT Bash or similar command line too, execute the following command lines:

  $ git clone --single-branch --branch 4.1dev https://github.com/olab/OLab4.git  
  $ git clone --single-branch --branch 4.1dev https://github.com/olab/OLab4-api.git

•	Change into the generated OLab/OLab4/docker directory.  Verify the file 'docker-compose.yml' exists in this directory.

•	Execute the following command to create the 'olab4-developer' docker container:

  $ docker-compose up -d  
  
  Depending on the speed of the internet connection, those may take some time.  When the creation has completed, open Kitematic (via the Docker context menu) and verify the container is running.  If the container creation was successful, you should see the following two log file lines that signify that the container is running:
  
  2017-11-29 12:28:35,941 INFO success: httpd entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
  
  2017-11-29 12:28:35,941 INFO success: mariadb entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
  
  
•	Open a GIT Bash command prompt. Execute the following command to invoke a command line prompt hosted WITHIN the container:

  $ docker exec -it olab4-developer bash
  
  If GIT Bash (Windows) was installed with an alternate command console, you may be to prefix the command as such:  
  
  $ winpty docker exec -it olab4-developer bash
  
  The window should respond with a different command prompt:
  
  [root@aa0a1928378c /]#
  
  Verify that necessary file shares to the host system are configured properly and are operational.
  
  [root@ aa0a1928378c /]# ls -l /var/lib/mysql
  
  total 0

  [root@ aa0a1928378c /]# ls -l /var/www/vhosts/
  total 0
  drwxrwxrwx 2 root root 0 May 16  2018 OLab

  [root@ aa0a1928378c /]# ls -l /etc/httpd/vhosts.d/
  total 1
  -rwxr-xr-x 1 root root 625 Apr 27  2018 olab4.dev.conf
  
•	Run the following post-setup commands within the container to download and create the OLab4 demo databases, set up apache, and connect file shares from the host system:

  [root@aa0a1928378c /]# cd /tmp
  [root@aa0a1928378c /]# ./post-create.sh
  If, during the post-create step, you see the following message:
  
  Cloning failed using an ssh key for authentication, enter your GitHub credentials to access private repos
  Head to … to retrieve a token. It will be stored in "/root/.composer/auth.json" for future use by Composer.
  Token (hidden):
  a token is required.  Contact the OLab4 github repository manager for the token.
  
  Several steps will run as part of this step.  If there are any errors, screen shot them and report them to OLab managers.
  
•	Open your favorite browser window and navigate to:
  https://olab4.localhost/apidev/olab
  
  The login dialog will appear it the container is configured properly.
•	Log into Olab4 with the ‘admin’ credentials (provided separately).

