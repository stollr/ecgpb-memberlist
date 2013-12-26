#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

chown -R www-data:www-data "$DIR/../app/cache"
chown -R www-data:www-data "$DIR/../app/logs"