## CUSTOM environment configuration - added into the container by Docker build

# This file is executed for login shells.
# Note: we force login shells when using builder.php both for entering the container and for executing scripts

# 1. execute .bashrc stuff for shells which are both login & interactive
# (note that the debian .bashrc will exit immediately if non-interactive shell is found)
if [ -f ~/.bashrc ]; then
   source ~/.bashrc
fi

# If running interactively, don't do anything more - .bashrc has done everything
case $- in
    *i*) return;;
      *) ;;
esac
