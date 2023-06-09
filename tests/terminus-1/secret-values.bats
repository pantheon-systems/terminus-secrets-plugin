#!/usr/bin/env bats

#
# secret-values.bats
#
# Test to see if we can get and set secret values on our test site
#

@test "set and retrieve secrets for t1" {
  # Delete all of the secrets
  terminus secrets:delete $TERMINUS_SITE.dev -y

  # Set 'foo' to 'bar'
  terminus secrets:set $TERMINUS_SITE.dev -y foo bar

  # Fetch 'foo' back again
  run terminus secrets:show $TERMINUS_SITE.dev -y foo
  [[ "$output" == *"bar"* ]]

  # Fetch 'foo' back again
  run terminus secrets:get $TERMINUS_SITE.dev -y foo
  [[ "$output" == *"bar"* ]]

  # Show all of the secrets
  run terminus secrets:list $TERMINUS_SITE.dev -y
  [[ "$output" == *"foo: bar"* ]]

  # Delete 'foo'
  terminus secrets:delete $TERMINUS_SITE.dev -y foo
}
