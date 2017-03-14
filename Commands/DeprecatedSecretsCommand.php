<?php
/**
 * This command will manage secrets on a Pantheon site.
 *
 * DEPRECATED - This is the Terminus 0.x version of the secrets file.
 * It is recommended to upgrade to Terminus 1.x, which uses the
 * command at `src/Commands/SecretsCommand.php`.
 *
 * See README.md for usage information.
 */

namespace Terminus\Commands;

use Terminus\Collections\Sites;
use Terminus\Commands\TerminusCommand;
use Terminus\Session;

/**
 * Manage secrets on a Pantheon instance
 *
 * @command secrets
 */
class DeprecatedSecretsCommand extends TerminusCommand {

  protected $sites;
  protected $info;
  protected $tmpDirs = [];

  /**
   * Object constructor
   *
   * @param array $options Options to construct the command object
   * @return SiteCommand
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->sites = new Sites();
    // Insure that $workdir will be deleted on exit.
    register_shutdown_function([$this, 'cleanup']);
  }

  /**
   * Set a secret value
   *
   * TODO: Terminus has a limitation that arg values are not correctly validated.
   * 'email' is handled specially, though, so we will use that for now until
   * this issue can be addressed.  Note also that the help text for positional
   * arguments is also not shown.
   *
   *  ## OPTIONS
   * <email>
   * : The key to the secret to set
   *
   * <email>
   * : The value of the secret
   *
   * [--site=<site>]
   * : Name of the site
   *
   * [--env=<env>]
   * : Site environment
   */
  public function set($args, $assoc_args) {
    $secretValues = $this->downloadSecrets($assoc_args);
    $key = array_shift($args);
    $value = array_shift($args);
    $secretValues[$key] = $value;

    $this->uploadSecrets($assoc_args, $secretValues);
  }

  /**
   * Delete a secret value
   *
   *  ## OPTIONS
   * <email>
   * : The key to the secret to remove
   *
   * [--site=<site>]
   * : Name of the site
   *
   * [--env=<env>]
   * : Site environment
   */
  public function delete($args, $assoc_args) {
    $secretValues = $this->downloadSecrets($assoc_args);
    $key = array_shift($args);
    unset($secretValues[$key]);

    $this->uploadSecrets($assoc_args, $secretValues);
  }

  /**
   * Show a secret value
   *
   *  ## OPTIONS
   * [<email>]
   * : The key to the secret to fetch. Optional. If not specified, shows all secret values.
   *
   * [--site=<site>]
   * : Name of the site
   *
   * [--env=<env>]
   * : Site environment
   */
  public function show($args, $assoc_args) {
    $secretValues = $this->downloadSecrets($assoc_args);
    if (count($args) > 1) {
      $key = array_shift($args);
      $this->output()->outputValue($secretValues[$key]);
    }
    else {
      // TODO: can we get outputRecord to preserve rather than ucfirst the 'key' field?
      $this->output()->outputRecord($secretValues);
    }
  }

  protected function getSiteInfo($assoc_args) {
    if (!isset($this->info)) {
      $site        = $this->sites->get(
        $this->input()->siteName(array('args' => $assoc_args))
      );
      $env_id      = $this->input()->env(array('args' => $assoc_args, 'site' => $site));
      $environment = $site->environments->get($env_id);
      $this->info        = $environment->connectionInfo();
    }
    return $this->info;
  }

  protected function getSftpCommand($assoc_args) {
    $info = $this->getSiteInfo($assoc_args);
    $sftpCommand = $info['sftp_command'];
    return $sftpCommand;
  }

  protected function downloadSecrets($assoc_args) {
    $sftpCommand = $this->getSftpCommand($assoc_args);
    $workdir = $this->tempdir();
    chdir($workdir);
    exec("(echo 'cd files' && echo 'cd private' && echo 'get secrets.json') | $sftpCommand", $fetch_output, $fetch_status);
    // if ($fetch_status) { ... }
    if (file_exists('secrets.json')) {
      $secrets = file_get_contents('secrets.json');
      $secretValues = (array)json_decode($secrets);
      return $secretValues;
    }
    else {
      echo "Initializing secrets.json\n";
      exec("touch secrets.json");
      exec("(echo 'cd files' && echo 'mkdir private' && echo 'cd private' && echo 'put secrets.json') | $sftpCommand", $fetch_output, $fetch_status);
    }
    return [];
  }

  protected function uploadSecrets($assoc_args, $secretValues) {
    $sftpCommand = $this->getSftpCommand($assoc_args);
    $workdir = $this->tempdir();
    chdir($workdir);

    file_put_contents('secrets.json', json_encode($secretValues));

    // Upload secrets.json, if possible
    exec("(echo 'cd files' && echo 'cd private' && echo 'put secrets.json') | $sftpCommand", $upload_output, $upload_status);
    // if ($uplaod_status) { ... }
  }

  // Create a temporary directory
  // TODO: Is there a Terminus library we could use to do this?
  public function tempdir($dir=FALSE, $prefix='php') {
    $tempfile=tempnam($dir ? $dir : sys_get_temp_dir(), $prefix ? $prefix : '');
    if (file_exists($tempfile)) {
      unlink($tempfile);
    }
    mkdir($tempfile);
    if (is_dir($tempfile)) {
      $this->tmpDirs[] = $tempfile;
      return $tempfile;
    }
  }

  // Recursively remove directories
  // TODO: Is there a Terminus library we could use to do this?
  public static function rrmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir."/".$object))
            rrmdir($dir."/".$object);
          else
            unlink($dir."/".$object);
        }
      }
      rmdir($dir);
    }
  }

  // Delete our work directory on exit.
  public function cleanup() {
    global $workdir;
    foreach ($this->tmpDirs as $dir) {
      static::rrmdir($dir);
    }
  }
}
