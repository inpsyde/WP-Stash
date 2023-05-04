#!/bin/bash

if ! wp core download --version="${WP_VERSION}"; then
 echo 'WordPress is already installed.'
 exit
fi
