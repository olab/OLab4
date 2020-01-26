#!/usr/bin/env bash
#set -x 
echo "Dumping entrada schemas"
mysqldump -uroot -ppassword -h database --databases entrada entrada_auth entrada_clerkship > /tmp/entrada_data.sql

echo "Dumping OLab schema"
mysqldump -uroot -ppassword -h database --databases openlabyrinth > /tmp/openlabyrinth_data.sql
