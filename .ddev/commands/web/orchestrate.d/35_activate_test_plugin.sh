#!/bin/bash

pushd "${DDEV_DOCROOT}" || exit

flags=""
if [ "${WP_MULTISITE}" = "true" ]; then
  flags+=" --network"
fi

wp plugin activate wp-stash-test-plugin $flags

popd
