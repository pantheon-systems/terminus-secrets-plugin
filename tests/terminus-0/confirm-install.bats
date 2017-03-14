#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Secrets plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus cli version
}

@test "list all secrets commands" {
  run terminus help secrets
  [[ $output == *"Set a secret value"* ]]
  [[ $output == *"Show a secret value"* ]]
  [[ $output == *"Delete a secret value"* ]]
  [ "$status" -eq 0 ]
}
