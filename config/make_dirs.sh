#!/bin/sh

# Introductory text
#
cat << EOF
This script will create the Diogenes spool and RCS directories
and set the permissions so that the directories are writable
by the web server.

You can cancel this script at any time using Ctrl-C.

EOF

echo -n "Where do you want the Diogenes RCS repository? [/var/lib/diogenes] "
read RCS_DIR
if [ "x$RCS_DIR" = "x" ]; then
    RCS_DIR="/var/lib/diogenes" 
fi

echo -n "Where do you want the Diogenes spool? [/var/spool/diogenes] "
read SPOOL_DIR
if [ "x$SPOOL_DIR" = "x" ]; then
    SPOOL_DIR="/var/spool/diogenes" 
fi

# Get the web server's group.
#
echo -n "Under what user does the web process run? [www-data] "
read OWNER 
if [ "x$OWNER" = "x" ]; then
    OWNER="www-data" 
fi

# Create directories.
#
echo
echo -n "Creating directories... "
mkdir -p $RCS_DIR
mkdir -p $SPOOL_DIR
mkdir -p $SPOOL_DIR/templates_c
echo "done."

# Set ownership 
#
echo
echo -n "Setting ownership recursively... "
chown -R $OWNER $RCS_DIR
chown -R $OWNER $SPOOL_DIR
echo "done."

