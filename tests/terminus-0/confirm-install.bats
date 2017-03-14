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
  [[ $output == *"secrets:list"* ]]
  [[ $output == *"secrets:show"* ]]
  [[ $output == *"secrets:set"* ]]
  [[ $output == *"secrets:delete"* ]]
  [ "$status" -eq 0 ]
}
