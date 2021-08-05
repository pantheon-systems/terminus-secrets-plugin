#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Secrets plugin have been installed correctly
#

@test "confirm terminus version for t1" {
  terminus --version
}

@test "list all secrets commands for t1" {
  run terminus list secrets
  [[ $output == *"secrets:list"* ]]
  [[ $output == *"secrets:show"* ]]
  [[ $output == *"secrets:set"* ]]
  [[ $output == *"secrets:get"* ]]
  [[ $output == *"secrets:delete"* ]]
  [ "$status" -eq 0 ]
}
