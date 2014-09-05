#!/bin/bash
#
# This is a crontab example script
#
# Take backups of your rancid router.* files
#
# Read and understand what this script does
# 
# Fix the directories
# Run this script once to import your current entries into the database 
# Comment out the Import section
# Add it to crontab
#

DATE=`date +%y%m%d%H%M%S`
ROUTERFILE="/var/lib/rancid/network/router.db"
DATABASE="rancid"

# IMPORT
echo name,vendor,status,comment > $HOME/net.csv
grep -v "#" $ROUTERFILE | sed -e 's/:/\",\"/g' -e 's/^/\"/g' -e 's/$/\"/g' >> $HOME/net.csv
mongo $DATABASE --eval "db.dropDatabase()"
mongoimport --db $DATABASE --collection net --type csv --headerline --upsert --ignoreBlanks --file $HOME/net.csv 

# EXPORT
cp $ROUTERFILE $ROUTERFILE.$DATE
mongoexport -d $DATABASE -c net -f name,vendor,status,comment -csv | sort | uniq | sed  -e 's/^"//g' -e 's/\",\"/:/g' -e 's/\"//g' -e 's/,//g' -e 's/.*vendor.*/\# Autogenerated /' > $HOME/router.db.$DATE

/var/lib/rancid/bin/rancid-run
/usr/bin/find /var/lib/rancid/logs/ -type f -mtime +2 -exec rm {} \;
/usr/bin/find $HOME/router.db.* -type f -mtime +2 -exec rm {} \;
