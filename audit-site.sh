#!/bin/bash

if [ ! -d ./vendor ]; then
  echo "Please run composer install first."
  exit 1;
fi

./vendor/bin/drutiny audit:site -d . -p profile $@
