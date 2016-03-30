#!/bin/sh

pluginpath=$DOCUMENT_ROOT../
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
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
for conf in configuration.conf directadminapi.conf plugin.conf spamexperts.log; do
	if [ ! -e "$pluginpath$conf" ] ; then
		touch "$pluginpath$conf"
	fi
	chmod 660 "$pluginpath$conf"
	chown diradmin:diradmin "$pluginpath$conf"
done

chown root:root "$pluginpath/scripts/getconfig" 2>&1
chmod 4755 "$pluginpath/scripts/getconfig" 2>&1

rm "$pluginpath/configuration.conf.new";
rm "$pluginpath/directadminapi.conf.new";

# update plugin.conf with new version number and urls
newupdateurl=$(grep "^update_url=.*$" "$pluginpath/plugin.conf.new")
sed -i -e "s|^update_url=.*$|$newupdateurl|" "$pluginpath/plugin.conf"
newversion=$(grep "^version=.*$" "$pluginpath/plugin.conf.new")
sed -i -e "s|^version=.*$|$newversion|" "$pluginpath/plugin.conf"
newversionurl=$(grep "^version_url=.*$" "$pluginpath/plugin.conf.new")
sed -i -e "s|^version_url=.*$|$newversionurl|" "$pluginpath/plugin.conf"

rm "$pluginpath/plugin.conf.new"

echo "Plugin has been updated!"

exit 0
