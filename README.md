# Terminus Secrets Plugin

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/1.x)
[![Terminus v0.x Compatible](https://img.shields.io/badge/terminus-v0.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/0.x)

Terminus Plugin that allows for manipulation of a simple 'secrets.json' file for use with Quicksilver on [Pantheon](https://www.pantheon.io) sites.

Adds a command 'secrets' to Terminus 1.x which you can use to add, fetch, remove and update. For a version that works with Terminus 0.x, see the [0.x branch](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/0.x).

Use as directed by Quicksilver examples.

Be aware that since this manages a simple json file in the network attached storage, filesystem synchronization operations will affect the secrets. You should not use this method if your use-case is to have different secrets in different environments. For that, or for secrets that are sensitive, we recommend [Lockr](https://github.com/lockr/lockr-terminus).

## Configuration

This plugin requires no configuration to use.

## Examples
Write "value" into "key" in the "test" environment of "sitename".
```
terminus secrets:set site.env key value
```

Remove the secret "key" in the "test" environment of "sitename".
```
terminus secrets:delete site.env key
```

Show current value of "key" in the "test" environment of "sitename".
```
terminus secrets:show site.env key
```

Show all available keys in the "test" environment of "sitename"
```
terminus secrets:list site.env
```

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)

## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins pantheon-systems/terminus-secrets-plugin:~1
```

## Internals

This plugin writes entries into the file `private/secrets.json`.  This file is, naturally enough, a json file containing multiple keys.  The `terminus secrets` script will fetch this file, modify is as requested, and then write it back to the Pantheon site.

Note that the `private` directory is located one level above the local working copy of your git repository on your Pantheon application server. This directory is **not** copied to `test` and `live` during deployments; you must therefore individually set secrets on each environment where you would like them to be available.

Also, be aware that your secrets may be overwritten by filesystem sync operations. For instance, if you check the "pull files and database from Live" option when deploying to Test, that will overrite the Test env with secrets (or a lack thereof) in Live. If you intend to use secrets.json for production, make sure you set the same file in all environments to avoid confusion.

## Terminus 0.x Version

This plugin is compatible with both Terminus 1.x and Terminus 0.x. This works because Terminus 1.x searches for commandfiles in `src/Commands`, and Terminus 0.x searches in `Commands`. In general, Terminus plugins should only support one version of Terminus. It is recommended to use the branches `1.x` and `0.x` for this purpose. The exception to this rule is Terminus plugins that have been widely installed in Continuous Integration scripts via `git clone` without using a `--branch` designation. In that case, placing both versions on the same branch can be helpful in maintaining backwards compatibility with these scripts.

## Testing

To run the tests locally, [install bats](https://github.com/sstephenson/bats#installing-bats-from-source), and then run:

`bats tests/terminus-0`

 * or *
 
`bats tests/terminus-1`

The tests presume that Terminus 1.x is available as `terminus`, and Terminus 0.x is available as `terminus0`.

## Help
Run `terminus list secrets` for a complete list of available commands. Use `terminus help <command>` to get help on one command.
