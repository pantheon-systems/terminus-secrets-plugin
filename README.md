# Terminus Secrets Plugin

A plugin for Terminus-CLI that allows for manipulation of a 'secrets' file for use with Quicksilver.

Use as directed by Quicksilver examples.

### Installation
```
mkdir -p ~/terminus/plugins
cd ~/terminus/plugins
git clone https://github.com/pantheon-systems/terminus-secrets-plugin
```

### Usage
Write "value" into "key" in the "test" environment of "sitename".
```
terminus secrets set --site=sitename --env=test key value
```

Remove the secret "key" in the "test" environment of "sitename".
```
terminus secrets delete --site=sitename --env=test key
```

Show current value of "key" in the "test" environment of "sitename".
```
terminus secrets show --site=sitename --env=test key
```

Show all available keys in the "test" environment of "sitename"
```
terminus secrets show --site=sitename --env=test
```

Learn more about Terminus and Terminus Plugins at:
[https://github.com/pantheon-systems/cli/wiki/Plugins](https://github.com/pantheon-systems/cli/wiki/Plugins)

### Internals

This plugin writes entries into the file `private/secrets.json`.  This file is, naturally enough, a json file containing multiple keys.  The `terminus secrets` script will fetch this file, modify is as requested, and then write it back to the Pantheon site.

Note that the `private` directory is located one level above the local working copy of your git repository on your Pantheon application server. This directory is **not** copied to `test` and `live` during deployments; you must therefore individually set secrets on each environment where you would like them to be available.

### Note on Bug in Help

Terminus has a bug which requires positional arguments to be described in help as 'email', as any other alternative results in the argument failing validation.
