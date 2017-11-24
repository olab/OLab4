# Docker

## Introduction

From: https://www.docker.com/what-docker#/developers

Docker automates the repetitive tasks of setting up and configuring development environments so that developers can focus on what matters: building great software.

Developers using Docker don’t have to install and configure complex databases nor worry about switching between incompatible language toolchain versions. When an app is dockerized, that complexity is pushed into containers that are easily built, shared and run. Onboarding a co-worker to a new codebase no longer means hours spent installing software and explaining setup procedures. Code that ships with Dockerfiles is simpler to work on: Dependencies are pulled as neatly packaged Docker images and anyone with Docker and an editor installed can build and debug the app in minutes.

## Prerequisites

1. You must have Docker installed on your local development machine. The simplest way to install everything is to use Docker Community Edition. You should also install Kitematic (GUI):

    https://www.docker.com/community-edition
2. You must have the Entrada ME Git repository cloned to `~/Sites/entrada-1x-me.dev`.
3. You must edit your local `hosts` file and add each hostname you would like to use. For example:
    ```
    127.0.0.1   entrada-1x-me.dev
    ```
    
## Usage

### macOS

```
mkdir ~/Data
cd ~/Documents
git clone git@github.com:EntradaProject/entrada-1x-docs.git
cd ~/Documents/entrada-1x-docs/resources/docker
docker-compose up -d
```

You can connect to the terminal of your new container by typing:

```
docker exec -it entrada-developer bash
```
