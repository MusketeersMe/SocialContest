#!/bin/bash
# simple deployment script will rsync from local to remote

HOST="mycontest.cloudapp.net"
HERE=`pwd`
# copy files over
echo "Transferring files."

rsync -r --exclude=".idea" --exclude=".svn" --exclude="Config/config.php" -e ssh ./src/ azureuser@"$HOST":/var/www/contest/src/
rsync -r --delete --exclude=".idea" --exclude=".svn" -e ssh ./bin/ azureuser@"$HOST":/var/www/contest/bin/
rsync -rl --delete --exclude=".idea" --exclude=".svn" -e ssh ./vendor/ azureuser@"$HOST":/var/www/contest/vendor/
rsync -r --delete --exclude=".idea" --exclude=".svn" -e ssh ./web/ azureuser@"$HOST":/var/www/contest/web/

