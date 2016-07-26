# Launching instance

## EC2
- Ubuntu HVM, t2.micro
- Moved storage up to 16gb (as this is a site uploading lots of audio, **disc usage needs to be monitored in some fashion**
- Creating a security group called 'web' that will allow access to the EC2 on ports 22, 80, and 443 (ssh, http, https)


## S3

The S3 account was created some time ago, and they remains as-is. There is an Elastic Transcode Job set up and configured (new), and those jobs are automatically kicked off after an audio file is detected (and uploaded properly to the S3 bucket), which will ensure all audio is of m4a format. This allows cross-browser performance and allows byte-stream encoding, so files can be scrubbed easily.

## Network/Elastic IP
An elastic IP (viewable in AWS Console), and that is the destination address in WSM domains. The elastic IP is set to point at the configure instance, and that instance can be moved around as necessary.

## Software

```

sudo apt-get install apache2
sudo apt-get install php5
sudo apt-get install libapache2-mod-php5
sudo apachectl -k start

```

# Apache Config

In ubuntu, this directive is set on the document root, and in ubuntu that is likely to be located in /etc/apache2/sites-enabled/000-something.conf


## Redirects
- all traffic to /library/* will redirect to /overview/library
- all traffic to /competitions/* will redirect to /overview/competition
- all traffic to /happenings/* will redirect to /overview/events

```
RedirectMatch 301 ^/library.*$ /overview/library
RedirectMatch 301 ^/competitions.*$ /overview/competitions
RedirectMatch 301 ^/happenings.*$ /overview/events
```

## Rewrites
Rewrites are very simple, and map the url to a controller and action, and then everything else is 'paramaters'.

```

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^([a-zA-Z]*)\/?([a-zA-Z]*)\/?([a-zA-Z0-9\-\:\/\_\*\=]*)\.?(json|xml|html|svg|jpe?g)?$ index.php?controller=$1&action=$2&params=$3&content-type=$4 [B,QSA,L]
</IfModule>
```

## Other Configs
Various other configs are self explanatory, AWS is configured here, if keys change server must be restarted

```

SetEnv AWS_ACCESS_KEY_ID     "K_E_Y"
SetEnv AWS_SECRET_ACCESS_KEY "S_E_C_R_E_T"

ServerName www.thirdcoastfestival.org

ServerAdmin email@domain
DocumentRoot /path`
SetEnv MODE "production"

```

## deflate.conf

I added a few other configs to deflate, as the content-type returned from the application is xhtml+xml, and that should be deflated as well. SVG images are also XML, so they can be deflated.

```

<IfModule mod_filter.c>
    # these are known to be safe with MSIE 6
    AddOutputFilterByType DEFLATE text/html text/plain text/xml

    # everything else may cause problems with MSIE 6
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/x-javascript application/javascript application/ecmascript
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>

```

## PHP configs

The site requires PHP 5.5, which should be fairly standard at this point. Installing PHP with the apt-get command does almost all of the legwork, but there are a few things to adjust in php.ini

post_max_size = bigger
upload_max_filesize = bigger

(to see current settings, investigate php.ini (100M))



# Site Pages

## Competitions

The competition landing page has two sections, RHD/TCF and ShortDocs. Whenever there is an *upcoming date* 
