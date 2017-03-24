#!/usr/bin/env bats

#
# secret-values.bats
#
# Test to see if we can get and set secret values on our test site
#

@test "set and retrieve secrets for t0" {
  # Set 'foo' to 'bar'
  terminus0 secrets set --site=$TERMINUS_SITE --env=dev foo bar

  # Fetch 'foo' back again
  run terminus0 secrets show --site=$TERMINUS_SITE --env=dev
  [[ "$output" == *"bar"* ]]

  # Set 'foo' to 'bar'
  terminus0 secrets set --site=$TERMINUS_SITE --env=dev foo baz

  # Fetch 'foo' back again
  run terminus0 secrets show --site=$TERMINUS_SITE --env=dev
  [[ "$output" == *"baz"* ]]
}
