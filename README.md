# Terminus Secrets Plugin

[![CircleCI](https://circleci.com/gh/pantheon-systems/terminus-secrets-plugin.svg?style=shield)](https://circleci.com/gh/pantheon-systems/terminus-secrets-plugin)
[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/1.x)
[![Terminus v0.x Compatible](https://img.shields.io/badge/terminus-v0.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/0.x)

Terminus Plugin that allows for manipulation of a simple `secrets.json` file for use with Quicksilver on [Pantheon](https://www.pantheon.io) sites.

Adds a command 'secrets' to Terminus 1.x which you can use to add, fetch, remove and update. For a version that works with Terminus 0.x, see the [0.x branch](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/0.x).

Use as directed by Quicksilver examples.

Be aware that since this manages a simple json file in the network attached storage, filesystem synchronization operations will affect the secrets. You should not use this method if your use-case is to have different secrets in different environments. For that, or for secrets that are sensitive, we recommend [Lockr](https://github.com/lockr/lockr-terminus).

## Background

This plugin writes entries into the file `~/files/private/secrets.json` (NOTE: This refers to a different `private` directory than the `private` directory used to store your Quicksilver scripts!). This file is, naturally enough, a JSON file containing multiple keys that is not included in your project's source code. The `terminus secrets` script will fetch this file, modify is as requested, and then write it back to the Pantheon site.

Before this Terminus plugin can be used, the `secrets.json` file must be created in each environment. To create the file call the secrets set command and add a key. This will automatically create the file in that environment.
```
terminus secrets:set site.env key value
```
The secrets directory is **not** copied to `test` and `live` during deployments (as it is not tracked in the project repository); you must therefore individually set secrets on each environment where you would like them to be available.

**Also, be aware that your secrets may be overwritten by filesystem sync operations. For instance, if you check the "pull files and database from Live" option when deploying to `TEST`, that will overwrite the `TEST` env with secrets (or a lack thereof) in `LIVE`. If you intend to use `secrets.json` for production, make sure you set the same file in all environments to avoid confusion.**

You can create all your keys in the live environment and then Clone files to other environments to copy the keys.

## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins pantheon-systems/terminus-secrets-plugin:~1
```

## Configuration

This plugin requires no configuration to use.

## Usage
Write "value" into "key" in the "test" environment of "sitename".
```
terminus secrets:set sitename.env key value
```

Remove the secret "key" in the "test" environment of "sitename".
```
terminus secrets:delete sitename.env key
```

Show current value of "key" in the "test" environment of "sitename".
```
terminus secrets:show sitename.env key
```

Show all available keys in the "test" environment of "sitename"
```
terminus secrets:list sitename.env
```

You may pass the `--file` option to this terminus plugin to read/write keys to a file named something other than `secrets.json`; this can be exceedingly helpful when storing configuration keys that may differ between environments as this prevents your environment-specific files getting overwritten by database and file clone operations.

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)

## Terminus 0.x Version

This plugin is compatible with both Terminus 1.x and Terminus 0.x. This works because Terminus 1.x searches for commandfiles in `src/Commands`, and Terminus 0.x searches in `Commands`. In general, Terminus plugins should only support one version of Terminus. It is recommended to use the branches `1.x` and `0.x` for this purpose. The exception to this rule is Terminus plugins that have been widely installed in Continuous Integration scripts via `git clone` without using a `--branch` designation. In that case, placing both versions on the same branch can be helpful in maintaining backwards compatibility with these scripts.

## Help
Run `terminus list secrets` for a complete list of available commands. Use `terminus help <command>` to get help on one command.

## Tip
If you need multiple versions of a key for different environments or one for development and one for production add them all with a naming structure that helps when listing the keys
```
keyName_production: value
keyName_development: value
```
or
```
keyName_multiDevName: value
```
There is no arrays of keys in the json file so all keys are at the root.
