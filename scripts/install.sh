#!/bin/bash

pluginpath=$DOCUMENT_ROOT../;
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd );
PDIR="$(dirname "$DIR")";
PLUGIN=${PDIR##*/};

HOOKFILES="$PDIR/hooks/*"
HOOKSCRIPTS="$PDIR/scripts/hooks/*"
ADMINFILES="$PDIR/admin/*"
RESELLERFILES="$PDIR/reseller/*"
USERFILES="$PDIR/user/*"
CONFFILE="$PDIR/plugin.conf"
LIBFILES="$PDIR/lib/*"

mv "$pluginpath/configuration.conf.new" "$pluginpath/configuration.conf";
mv "$pluginpath/directadminapi.conf.new" "$pluginpath/directadminapi.conf";
mv "$pluginpath/plugin.conf.new" "$pluginpath/plugin.conf";

ALLFILES="$HOOKFILES $HOOKSCRIPTS $ADMINFILES $RESELLERFILES $USERFILES $LIBFILES $CONFFILE"

for file in $ALLFILES; do
    sed -i -e "s/<PLUGINNAME>/$PLUGIN/g" $file
done;

echo "Adding hooks to DirectAdmin<br/>"

for hook in domain_create_post domain_destroy_post domain_pointer_create_post domain_pointer_destroy_post; do
  if [ -e "$pluginpath../../scripts/custom/$hook.sh" ] ; then
    echo "WARNING! - hook file '$hook.sh' already exist<br/>";
  else
    cp "$pluginpath/scripts/hooks/$hook.sh" "$pluginpath../../scripts/custom/"
    chmod 770 "$pluginpath../../scripts/custom/$hook.sh"
    chown diradmin:diradmin "$pluginpath../../scripts/custom/$hook.sh"
  fi
done;

echo "Creating configuration files<br/>"

chmod -R 755 $pluginpath/*
chown -R diradmin:diradmin $pluginpath/*

# creating configuration files
for conf in configuration.conf directadminapi.conf plugin.conf; do
  if [ ! -e "$pluginpath$conf" ] ; then
    touch "$pluginpath$conf"
  fi
  chmod 660 "$pluginpath$conf"
  chown diradmin:diradmin "$pluginpath$conf"
done;

chown root:root "$pluginpath/scripts/getconfig" 2>&1
chmod 4755 "$pluginpath/scripts/getconfig" 2>&1

if [ ! -e "$pluginpath/logs" ]; then
    mkdir "$pluginpath/logs"
fi
chmod 777 "$pluginpath/logs"
chown diradmin:diradmin "$pluginpath/logs"

echo "Plugin Installed!<br/>";
exit 0;
