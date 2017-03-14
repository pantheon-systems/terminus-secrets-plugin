#!/usr/bin/env bats

#
# secret-values.bats
#
# Test to see if we can get and set secret values on our test site
#

@test "set and retrieve secrets" {
  # Delete all of the secrets
  terminus secrets delete --site=ci-terminus-secrets --env=dev -y

  # Set 'foo' to 'bar'
  terminus secrets set --site=ci-terminus-secrets --env=dev -y foo bar

  # Fetch 'foo' back again
  run terminus secrets show --site=ci-terminus-secrets --env=dev -y foo
  [ $output == "bar" ]

  # Show all of the secrets
  run terminus secrets list --site=ci-terminus-secrets --env=dev -y
  [[ $output == *"foo: bar"* ]]

  # Delete 'foo'
  terminus secrets delete --site=ci-terminus-secrets --env=dev -y foo
}
