<?php
/**
 * This command will manage secrets on a Pantheon site.
 *
 * See README.md for usage information.
 */

namespace Pantheon\TerminusSecrets\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Manage secrets on a Pantheon instance
 */
class SecretsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    protected $info;
    protected $tmpDirs = [];

    /**
     * Object constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Register our shutdown function if any of our commands are executed.
     *
     * @hook init
     */
    public function initialize(InputInterface $input, AnnotationData $annotationData)
    {
        // Insure that $workdir will be deleted on exit.
        register_shutdown_function([$this, 'cleanup']);
    }

    /**
     * Set a secret value
     *
     * @command secrets:set
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param string $key Item to set
     * @param string $value Value to set it to
     * @option file Name of file to store secrets in
     * @option clear Overwrite existing values
     * @option skip-if-empty Don't write anything unless '$value' is non-empty
     */
    public function set(
        $site_env_id,
        $key,
        $value,
        $options = [
            'file' => 'secrets.json',
            'clear' => false,
            'skip-if-empty' => false,
        ]
    ) {
    
        if ($options['skip-if-empty'] && empty($value)) {
            return;
        }
        if (!$options['clear']) {
            $secretValues = $this->downloadSecrets($site_env_id, $options['file']);
        }
        $secretValues[$key] = $value;

        $this->uploadSecrets($site_env_id, $secretValues, $options['file']);
    }

    /**
     * Delete a secret value
     *
     * @command secrets:delete
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param string $key Item to delete (or empty to delete everything)
     */
    public function delete($site_env_id, $key = '', $options = ['file' => 'secrets.json'])
    {
        $secretValues = [];
        if (!empty($key)) {
            $secretValues = $this->downloadSecrets($site_env_id, $options['file']);
            unset($secretValues[$key]);
        }
        $this->uploadSecrets($site_env_id, $secretValues, $options['file']);
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
    public function show($site_env_id, $key, $options = ['file' => 'secrets.json'])
    {
        $secretValues = $this->downloadSecrets($site_env_id, $options['file']);
        if (!array_key_exists($key, $secretValues)) {
            throw new TerminusException('Key {key} not found in {file}', ['key' => $key, 'file' => $options['file']]);
        }
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
    public function listSecrets($site_env_id, $options = ['file' => 'secrets.json'])
    {
        $secretValues = $this->downloadSecrets($site_env_id, $options['file']);
        return new PropertyList($secretValues);
    }

    /**
     * Look up the appropriate sftp command to use from the site
     * connection info record.
     */
    protected function getSftpCommand($site_env_id)
    {
        list(, $env) = $this->getSiteEnv($site_env_id);
        $info = $env->connectionInfo();

        $sftpCommand = $info['sftp_command'];
        return $sftpCommand;
    }

    /**
     * Call rsync to or from the specified site.
     *
     * @param string $site_env_id Remote site
     * @param string $src Source path to copy from. Start with ":" for remote.
     * @param string $dest Destination path to copy to. Start with ":" for remote.
     * @param boolean $ignoreIfNotExists Silently fail and do not return error if remote source does not exist.
     */
    protected function rsync($site_env_id, $src, $dest, $ignoreIfNotExists = false)
    {
        list($site, $env) = $this->getSiteEnv($site_env_id);
        $env_id = $env->getName();

        $siteInfo = $site->serialize();
        $site_id = $siteInfo['id'];

        $siteAddress = "$env_id.$site_id@appserver.$env_id.$site_id.drush.in:";

        $src = preg_replace('/^:/', $siteAddress, $src);
        $dest = preg_replace('/^:/', $siteAddress, $dest);

        $this->passthru("rsync -rlIvz --ipv4 --exclude=.git -e 'ssh -p 2222' $src $dest >/dev/null 2>&1",
            $ignoreIfNotExists == true ? [0, 23] : [0]);
    }

    protected function passthru($command, $acceptedResults = [0])
    {
        $result = 0;
        passthru($command, $result);

        if (!in_array($result, $acceptedResults)) {
            throw new TerminusException('Command `{command}` failed with exit code {status}', ['command' => $command, 'status' => $result]);
        }
    }

    /**
     * Download a copy of the secrets.json file from the appropriate Pantheon site.
     */
    protected function downloadSecrets($site_env_id, $filename)
    {
        $workdir = $this->tempdir();
        $this->rsync($site_env_id, ":files/private/$filename", $workdir, true);

        if (file_exists("$workdir/$filename")) {
            $secrets = file_get_contents("$workdir/$filename");
            $secretValues = (array)json_decode($secrets);
            return $secretValues;
        }
        return [];
    }

    /**
     * Upload a modified secrets.json to the target Pantheon site.
     */
    protected function uploadSecrets($site_env_id, $secretValues, $filename)
    {
        $workdir = $this->tempdir();
        mkdir("$workdir/private");

        file_put_contents("$workdir/private/$filename", json_encode($secretValues));
        $this->rsync($site_env_id, "$workdir/private", ':files/');
    }

    // Create a temporary directory
    public function tempdir($dir = false, $prefix = 'php')
    {
        $tempfile=tempnam($dir ? $dir : sys_get_temp_dir(), $prefix ? $prefix : '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        chmod($tempfile, 0700);
        if (is_dir($tempfile)) {
            $this->tmpDirs[] = $tempfile;
            return $tempfile;
        }
    }

    // Delete our work directory on exit.
    public function cleanup()
    {
        if (empty($this->tmpDirs)) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($this->tmpDirs);
    }
}
