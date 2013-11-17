tu-archive-importer
===================

A wonderfully simple tool to import your Twitter archive into ThinkUp. 

Instructions
------------
This script must be run from the command line, from a location that has access to your Thinkup MySQL Database (probably your web server).  
Download your archived tweets from Twitter.  
Unzip the archive in to the same location as this script  
* Note - you need the whole directory structure.   
* Most important is that your tweets should be under tweets/data/js/tweets/*.js, and user\_details.js should be under tweets/data/js  
Copy config.php.sample to config.php  
Modify config.php, enter your database username, password and Timezone. Only update other settings if necessary.  
Run import.php from the command line. This will perform a dry run - your database will not be updated at all.  
When you're satisfied with the result, change $dry_run to true and re-run import.php.  

Note
----
This is very much beta. Make a backup before you run this.  
[Instructions for downloading your twitter archive](https://support.twitter.com/articles/20170160-downloading-your-twitter-archive)

Thanks
------
HUGE thanks to [@henriwatson](https://github.com/henriwatson) for writing the tweet parsing code. I procrastinated on it and then he was awesome and did it for me.  

Credits
-------
[MeekroDB](http://www.meekro.com/) for making me not want to pull my hair out doing MySQL queries.  
Thanks to [everyone before me](https://github.com/ws/tu-archive-importer/network) for giving me some great code to play with
