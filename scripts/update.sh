#!/bin/bash

pluginpath=$DOCUMENT_ROOT../
DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PDIR="$(dirname "$DIR")"
PLUGIN=${PDIR##*/}

HOOKFILES="$PDIR/hooks/*"
HOOKSCRIPTS="$PDIR/scripts/hooks/*"
ADMINFILES="$PDIR/admin/*"
RESELLERFILES="$PDIR/reseller/*"
USERFILES="$PDIR/user/*"
CONFFILE="$PDIR/plugin.conf"
LIBFILES="$PDIR/lib/*"

ALLFILES="$HOOKFILES $HOOKSCRIPTS $ADMINFILES $RESELLERFILES $USERFILES $LIBFILES $CONFFILE"

for file in $ALLFILES; do
  sed -i -e "s/<PLUGINNAME>/$PLUGIN/g" $file
done

chmod -R 755 $pluginpath/*
chown -R diradmin:diradmin $pluginpath/*

# creating configuration files
for conf in configuration.conf directadminapi.conf plugin.conf; do
  if [ ! -e "$pluginpath$conf" ]; then
    touch "$pluginpath$conf"
  fi
  chmod 660 "$pluginpath$conf"
  chown diradmin:diradmin "$pluginpath$conf"
done

chown root:root "$pluginpath/scripts/getconfig" 2>&1
chmod 4755 "$pluginpath/scripts/getconfig" 2>&1

if [ ! -e "$pluginpath/logs" ]; then
  mkdir "$pluginpath/logs"
fi
chmod 777 "$pluginpath/logs"
chown diradmin:diradmin "$pluginpath/logs"

rm "$pluginpath/configuration.conf.new"
rm "$pluginpath/directadminapi.conf.new"

echo "Plugin has been updated!"

exit 0
