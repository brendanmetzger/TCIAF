# Git: add and commit changes
cd /var/www/thirdcoastfestival.org/data && /usr/bin/git add --all && /usr/bin/git commit -m "data push `date`"

# send data to Git server
cd /var/www/thirdcoastfestival.org/data && /usr/bin/git push origin master
