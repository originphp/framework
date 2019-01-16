# Cron Jobs

Many applications will need to run cron jobs on scripts, these can be to clean the database, send out emails, carry out tasks etc. You can run your shell scripts through cron editing the cron file

On Ubunu or other debian based flavors of unix use the crontab command.
````linux
    sudo crontab -u www-data -e
````
For Redhat or Redhat base distributions edit the `/etc/crontab` file, although at the time of writing Redhat does not support Php 7.0 as of yet.

To setup a cron to run the send_emails method in the users shell once each day

````
0 1 * * * cd /var/www/project && bin/console users send_emails
````