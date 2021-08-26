#!/bin/bash

pluginpath=$DOCUMENT_ROOT../

echo "Removing hooks from DirectAdmin<br/>"

for hook in domain_create_post domain_destroy_post domain_pointer_create_post domain_pointer_destroy_post; do
  installedhook="$pluginpath../../scripts/custom/$hook.sh"
  if [ ! -f $installedhook ]; then
    continue
  fi
  cmp --silent $installedhook "${DOCUMENT_ROOT}hooks/$hook.sh"
  if [ $? -ne 0 ]; then
    echo "WARNING! - hook file '$hook.sh' differs from the plugin's one. Skipping it.<br/>"
    else
        rm "$pluginpath../../scripts/custom/$hook.sh"
    fi
done

echo "Plugin Un-Installed!"

exit 0
