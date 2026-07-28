#!/bin/sh
set -eu

cat > /usr/local/etc/php/conf.d/runtime-limits.ini <<EOF
upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE:-20M}
post_max_size=${PHP_POST_MAX_SIZE:-25M}
memory_limit=${PHP_MEMORY_LIMIT:-512M}
max_execution_time=${PHP_MAX_EXECUTION_TIME:-180}
EOF

exec "$@"
