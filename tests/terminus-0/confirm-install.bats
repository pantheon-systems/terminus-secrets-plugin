#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Secrets plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus cli version
}
