# Git: add and commit changes
cd /var/www/thirdcoastfestival.org/data && /usr/bin/git commit -a -m "data push `date`"

# send data to Git server
cd /var/www/thirdcoastfestival.org/data && /usr/bin/git push origin master
