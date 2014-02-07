#!/usr/bin/env bash


# TODO adjust references to forge.t3o to point to a public ASM repository or alternative download location

# Usage:
#	sudo ./install-solr.sh
#	sudo ./install-solr.sh english german french

TOMCAT_VER=7.0.50
SOLR_VER=4.6.1
ASM_SOLR_VER=0.1.0

GITBRANCH_PATH="asm_$ASM_SOLR_VER.x"



# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# Set default language for cores to download to english, if no commandline parameters are given
if [ $# -eq 0 ]
then
	LANGUAGES=english
else
	LANGUAGES=$@
fi

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# progressfilt - print a nice progress bar
# used to hide some unnecessary output of wget when downloading configuration files
progressfilt ()
{
	local flag=false c count cr=$'\r' nl=$'\n'
	while IFS='' read -d '' -rn 1 c
	do
		if $flag
		then
			printf '%c' "$c"
		else
			if [[ $c != $cr && $c != $nl ]]
			then
				count=0
			else
				((count++))
				if ((count > 1))
				then
					flag=true
				fi
			fi
		fi
	done
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# wgetresource
# usage: wgetresource relative/filepath/inside/resourcesdir [justcheck]
# second parameter is optional, if set, do not download, only check if resource exists
wgetresource ()
{
	local wget_result

	if [ $BRANCH_TEST_RETURN -eq "0" ]
	then
# FIXME have an actual download location
#		RESOURCE="http://www.infielddesign.com/fileadmin/asm/$GITBRANCH_PATH/"$1
#	else
		RESOURCE="http://www.infielddesign.com/fileadmin/asm/master/"$1
	fi

	if [ "$2" ]
	then
		# If second parameter is set, just check if resource exists, no output
		wget -q -O /dev/null --no-check-certificate $RESOURCE
	else
		echo "wget $RESOURCE"
		wget --progress=bar:force --no-check-certificate $RESOURCE 2>&1 | progressfilt
	fi

	# return wget error code
	return $?
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

# color echo http://www.faqs.org/docs/abs/HTML/colorizing.html

black="\033[30m"
red="\033[31m"
green="\033[32m"
yellow="\033[33m"
blue="\033[34m"
magenta="\033[35m"
cyan="\033[36m"
white="\033[37m"


# Color-echo
# @param string $1 message
# @param string $2 color
cecho ()
{
	local default_msg="No message passed."

	# Defaults to default message.
	message=${1:-$default_msg}

	# Defaults to black, if not specified.
	color=${2:-$black}

	echo -e "$color$message"

	# Reset text attributes to normal + without clearing screen.
	tput sgr0

	return
}

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

clear

cecho "Checking requirements." $green

PASSALLCHECKS=1

# Make sure only root can run this script
echo -n "Running as root? "
if [[ $EUID -ne 0 ]]
	then
		cecho "ERROR This script must be run as root." $red
		PASSALLCHECKS=0
	else cecho "passed" $green
fi

# wget installed?
echo -n "Looking for wget: "
wget --version > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
	then
		cecho "ERROR couldn't find wget." $red
		PASSALLCHECKS=0
	else cecho "passed" $green
fi

# test if release branch exists, if so we'll download from there
wget --no-check-certificate -q -O /dev/null http://www.infielddesign.com/fileadmin/asm/$GITBRANCH_PATH/
BRANCH_TEST_RETURN=$?

# make sure Java is installed
echo -n "Looking for Java: "
java -version > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
	then
		cecho "ERROR couldn't find Java (Oracle Java is recommended)." $red
		PASSALLCHECKS=0
	else cecho "passed" $green
fi

# Can we connect to the Internet?
echo -n "Trying to reach Apache download mirror: "
ping -c 1 apache.osuosl.org > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
	then
		cecho "WARNING Couldn't ping Apache download mirror. Relax, will try again using wget." $yellow
		wget -q -O /dev/null http://apache.osuosl.org
		if [ $? -ne "0" ]
			then
				cecho "ERROR Also couldn't reach the Apache download mirror at Oregon State University Open Source Lab - OSUOSL using wget. Please check your internet connection." $red
				PASSALLCHECKS=0
			else cecho "passed" $green
		fi
	else cecho "passed" $green
fi

# Do we have unzip installed
echo -n "Looking for unzip: "
unzip -v > /dev/null 2>&1
CHECK=$?
if [ $CHECK -ne "0" ]
	then
		cecho "ERROR: couldn't find unzip." $red
		PASSALLCHECKS=0
	else cecho "passed" $green
fi

# Check if solr scheme files etc. for specified languages are available
for LANGUAGE in ${LANGUAGES[*]}
do
	echo -n "Checking availability of language \"$LANGUAGE\": "
	wgetresource solr/magentocores/conf/"$LANGUAGE"/schema.xml justcheck
	if [ $? -ne 0 ]
		then
			cecho "ERROR: Could not find Solr configuration files for language \"$LANGUAGE\"" $red
			PASSALLCHECKS=0
		else cecho "passed" $green
	fi
done

if [ $PASSALLCHECKS -eq "0" ]
	then
		cecho "Please install missing requirements or fix any other errors listed above and try again." $red
		exit 1
	else
		cecho "All requirements met. Hold tight, now installing Solr!" $green
fi

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

mkdir -p /opt/solr-tomcat
cd /opt/solr-tomcat/

cecho "Using the Apache download mirror at Oregon State University Open Source Lab - OSUOSL." $green
cecho "Downloading Apache Tomcat $TOMCAT_VER" $green
TOMCAT_MAINVERSION=`echo "$TOMCAT_VER" | cut -d'.' -f1`
wget --progress=bar:force http://apache.osuosl.org/tomcat/tomcat-$TOMCAT_MAINVERSION/v$TOMCAT_VER/bin/apache-tomcat-$TOMCAT_VER.zip 2>&1 | progressfilt

cecho "Downloading Apache Solr $SOLR_VER" $green
wget --progress=bar:force http://apache.osuosl.org/lucene/solr/$SOLR_VER/solr-$SOLR_VER.zip 2>&1 | progressfilt

cecho "Unpacking Apache Tomcat." $green
unzip -q apache-tomcat-$TOMCAT_VER.zip

cecho "Unpacking Apache Solr." $green
unzip -q solr-$SOLR_VER.zip

mv apache-tomcat-$TOMCAT_VER tomcat

cp -r solr-$SOLR_VER/example/solr .

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Downloading Apache Solr for Magento configuration files." $green
cd solr
SOLRDIR=`pwd`

for LANGUAGE in ${LANGUAGES[*]}
do
	cecho "Downloading configuration for language: $LANGUAGE" $green

	cd $SOLRDIR
	# create / download $LANGUAGE core configuration
	mkdir -p magentocores/conf/$LANGUAGE
	cd magentocores/conf/$LANGUAGE

	wgetresource solr/magentocores/conf/$LANGUAGE/protwords.txt
	wgetresource solr/magentocores/conf/$LANGUAGE/schema.xml
	wgetresource solr/magentocores/conf/$LANGUAGE/stopwords.txt
	wgetresource solr/magentocores/conf/$LANGUAGE/synonyms.txt

	if [ $LANGUAGE = "german" ]
	then
		wgetresource solr/magentocores/conf/$LANGUAGE/german-common-nouns.txt
	fi

done

# download general configuration in /opt/solr-tomcat/solr/magentocores/conf/
cecho "Downloading general configruation" $green
cd ..
wgetresource solr/magentocores/conf/currency.xml
wgetresource solr/magentocores/conf/elevate.xml
wgetresource solr/magentocores/conf/schema_fields_shared.xml
wgetresource solr/magentocores/conf/schema_types_shared.xml
wgetresource solr/magentocores/conf/solrconfig.xml

# download core configuration file solr.xml in /opt/solr-tomcat/solr/
cd ../..
rm solr.xml
wgetresource solr/solr.xml

# Create Solr shared lib directory (although not used currently)
mkdir lib

# clean up
# TODO do conf, data directories still exist?
rm -rf collection1
rm -rf bin
rm -rf conf
rm -rf data
rm README.txt

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Configuring Apache Tomcat." $green
cd /opt/solr-tomcat/tomcat/conf

rm server.xml

wgetresource tomcat/server.xml

cd /opt/solr-tomcat/
mkdir -p tomcat/conf/Catalina/localhost
cd tomcat/conf/Catalina/localhost

# install context descriptor for the solr context/webapp, sets the solr.home property
wgetresource tomcat/solr.xml

# copy libs
cd /opt/solr-tomcat/
cp -r solr-$SOLR_VER/dist solr/
cp -r solr-$SOLR_VER/contrib solr/
cp -r solr-$SOLR_VER/example/lib/ext/* tomcat/lib/
cp -r solr-$SOLR_VER/example/resources/log4j.properties tomcat/lib/

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Setting permissions." $green
cd /opt/solr-tomcat/
chmod a+x tomcat/bin/*

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Cleaning up." $green
rm -rf solr-$SOLR_VER.zip
rm -rf solr-$SOLR_VER
rm -rf apache-tomcat-$TOMCAT_VER.zip

# ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

cecho "Starting Tomcat." $green
./tomcat/bin/startup.sh

cecho "Hooray, your Solr installation is ready." $green
cecho "Now browse to http://localhost:8080/solr/" $green
