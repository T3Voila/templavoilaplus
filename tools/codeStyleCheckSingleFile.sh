#!/usr/bin/env bash
FILENAME=$1
if [ -z "$FILENAME" ]
then
  echo "please supply a filename"
  exit 1
fi
if [ ! -f "$FILENAME" ]; then
   echo "filename $FILENAME does not exist."
  exit 1
fi
echo "Processing $FILENAME"
./.Build/bin/php-cs-fixer fix --config .php-cs-fixer.php -v --dry-run --using-cache no --diff $FILENAME
./.Build/bin/phpcs $FILENAME
./.Build/bin/phpstan --no-progress analyse --level 0 $FILENAME
