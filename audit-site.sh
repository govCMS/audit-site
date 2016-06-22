#!/bin/bash

if [ ! -d ./vendor ]; then
  echo "Please run composer install first."
  exit 1;
fi

./vendor/bin/site-audit audit:site -d . -p profile $@
