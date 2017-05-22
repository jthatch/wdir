wdir
================

wdir is a lightweight web directory listing PHP script that provides you with a clean, fully-responsive view of your files and directories. You can sort by name, size or date modified and is great for when you need a bit more control than an apache or nginx directory listing can provide. wdir is written and maintained by [jthatch](https://github.com/jthatch).

![wdir screenshot](http://wireside.co.uk/wdir-screenshot.png)

## Installation
Simply download the wdir.php file directory from github, or optionally clone the repo yourself using the following commands: 
```
git clone https://github.com/jthatch/wdir.git  
cd wdir
cp wdir.php /var/www/html/yourwebsite/files/index.php
visit: http://yourwebsite.com/files/
```

Note: if you would like to track how many hits files get, then make sure you sqlite3 installed and copy wdir.sqlite into the root of your web directory. This file with not be visible.

## Coming soon
- Admin support for files. Ability to add/remove.
- Theming support
- sqlite for tracking hits
- Text logging
- Support for transmission-cli (BitTorrent) daemon
