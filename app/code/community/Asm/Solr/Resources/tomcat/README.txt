The Tomcat configuration files
=================================

server.xml
---------------------------------
This is the Tomcat server configuration file. It is almost identical to the one distributed when downloading Tomcat.
The only difference is that when using this server.xml file Tomcat will only listen to requests coming from localhost.
Also, the AJP Connector on port 8009 has been turned off since it is not needed for using Solr.

solr.xml
---------------------------------
This file is a so-called Tomcat Context fragment defining the $SOLR_HOME environment variable and where to find
the Solr web application archive (solr.war). This file goes into $CATALINA_HOME/conf/Catalina/localhost/
where $CATALINA_HOME is the Tomcat installation directory.

solr-tomcat
---------------------------------
This is a simple start/stop script to keep Tomcat running as a service when rebooting the server. Make sure to adjust
the variables at the top of the script as needed, the script was written for a Ubuntu environment. Place this file
into /etc/init.d
Usage: 'service solr-tomcat start|stop|restart|status'