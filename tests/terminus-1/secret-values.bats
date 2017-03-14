#!/usr/bin/env bats

#
# secret-values.bats
#
# Test to see if we can get and set secret values on our test site
#

@test "set and retrieve secrets" {
  # Delete all of the secrets
  terminus secrets:delete ci-terminus-secrets.dev -y

  # Set 'foo' to 'bar'
  terminus secrets:set ci-terminus-secrets.dev -y foo bar

  # Fetch 'foo' back again
  run terminus secrets:show ci-terminus-secrets.dev -y foo
  [ $output == "bar" ]

  # Show all of the secrets
  run terminus secrets:list ci-terminus-secrets.dev -y
  [[ $output == *"foo: bar"* ]]

  # Delete 'foo'
  terminus secrets:delete ci-terminus-secrets.dev -y foo
}
