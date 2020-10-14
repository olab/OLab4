## Overview

Fourth version of Open Labyrinth project.

## Important v0.4.0 Information
- "react-digraph" package was injected in Olab.
- Structure of the project was refactored.

## Launch

To kick off development of the project:
```bash
npm start
```

To make production build:
```bash
npm build
```

## Environment variables

API_URL - base api url
PLAYER_PUBLIC_URL - base player url

## Production runbook

Clone your repository and Open the folder
```bash
git clone <remote_url> <branch> <directory_folder> && cd <directory_folder>
```

Run downloading of npm modules
```bash
npm i
```

If you need update the API_URL update it `./env/.env.production`
If you need update the PLAYER_PUBLIC_URL update it `./env/.env.production`

Run the generating of the production build
```bash
PUBLIC_URL=<subpath_url> npm run build
```
env variables are optional, and default values will be get from `./env/.env.production`

Copy/move the `*` from `./build` folder to web hosts directory
```bash
cp ./build/* -R /var/www/olab4/
```

So your application will be available by `/var/www/olab4/index.html`

## Releases

In the root placed files `release_<N>.patch` and relative to npm git tag
