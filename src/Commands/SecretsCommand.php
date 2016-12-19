<?php
/**
 * This command will manage secrets on a Pantheon site.
 *
 * See README.md for usage information.
 */

namespace Pantheon\TerminusSecrets\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Manage secrets on a Pantheon instance
 */
class SecretsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    protected $file = 'secrets.json';
    protected $info;
    protected $tmpDirs = [];

    /**
     * Object constructor
     */
    public function __construct()
    {
        parent::__construct();
        // Insure that $workdir will be deleted on exit.
        register_shutdown_function([$this, 'cleanup']);
    }

    /**
     * Hook every command defined in this file; read
     * the --file option, if present, and set the file
     * being operated on if requested.
     *
     * @hook pre-command
     * @option string file The name of the secrets file to operate on. Default is secrets.json.
     */
    public function selectFile(CommandData $commandData)
    {
        $input = $commandData->input();
        if ($input->hasOption('file')) {
            $file = $input->getOption('file');
            if (!empty($file)) {
                $this->file = $file;
            }
        }
    }

    /**
     * Set a secret value
     *
     * @command secrets:set
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param string $key Item to set
     * @param string $value Value to set it to
     */
    public function set($site_env_id, $key, $value)
    {
        $secretValues = $this->downloadSecrets($site_env_id);
        $secretValues[$key] = $value;

        $this->uploadSecrets($site_env_id, $secretValues);
    }

    /**
     * Delete a secret value
     *
     * @command secrets:delete
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param string $key Item to delete
     */
    public function delete($site_env_id, $key)
    {
        $secretValues = $this->downloadSecrets($site_env_id);
        unset($secretValues[$key]);

        $this->uploadSecrets($site_env_id, $secretValues);
    }

    /**
     * Show a secret value
     *
     * @command secrets:show
     * @alias secrets:get
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param string $key Item to show
     * @return string
     */
    public function show($site_env_id, $key)
    {
        $secretValues = $this->downloadSecrets($site_env_id);
        return $secretValues[$key];
    }

    /**
     * Show a all secret values
     *
     * @command secrets:list
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     */
    public function list($site_env_id)
    {
        $secretValues = $this->downloadSecrets($site_env_id);
        return new PropertyList($secretValues);
    }

    protected function getSftpCommand($site_env_id)
    {
        list(, $env) = $this->getSiteEnv($site_env_id);
        $info = $env->connectionInfo();

        $sftpCommand = $info['sftp_command'];
        return $sftpCommand;
    }

    protected function downloadSecrets($site_env_id)
    {
        $sftpCommand = $this->getSftpCommand($site_env_id);
        $workdir = $this->tempdir();
        chdir($workdir);
        exec("(echo 'cd files' && echo 'cd private' && echo 'get {$this->file}') | $sftpCommand", $fetch_output, $fetch_status);
        // if ($fetch_status) { ... }
        if (file_exists($this->file)) {
            $secrets = file_get_contents($this->file);
            $secretValues = (array)json_decode($secrets);
            return $secretValues;
        }
        else {
            $this->log()->notice("Initializing {$this->file}");
            touch($this->file);
            exec("(echo 'cd files' && echo 'mkdir private' && echo 'cd private' && echo 'put {$this->file}') | $sftpCommand", $fetch_output, $fetch_status);
        }
        return [];
    }

    protected function uploadSecrets($site_env_id, $secretValues)
    {
        $sftpCommand = $this->getSftpCommand($site_env_id);
        $workdir = $this->tempdir();
        chdir($workdir);

        file_put_contents($this->file, json_encode($secretValues));

        // Upload secrets.json, if possible
        exec("(echo 'cd files' && echo 'cd private' && echo 'put {$this->secrets}') | $sftpCommand", $upload_output, $upload_status);
        // if ($uplaod_status) { ... }
    }

    // Create a temporary directory
    // TODO: Is there a Terminus library we could use to do this?
    public function tempdir($dir=FALSE, $prefix='php')
    {
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

    // Delete our work directory on exit.
    public function cleanup()
    {
        $fs = new Filesystem();
        $fs->remove($this->tmpDirs);
    }
}
