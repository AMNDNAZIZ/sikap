<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LicenseRehash extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'License';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'license:rehash';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Regenerate license and security hashes (.lic_hash and .security_hash)';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'license:rehash [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write('Regenerating license and security hashes...', 'yellow');

        $results = \App\Libraries\LicenseGuard::regenerateAllHashesSync();

        if ($results['lic_hash']) {
            CLI::write('✅ License hashes (.lic_hash) successfully generated.', 'green');
        } else {
            CLI::write('❌ Failed to generate .lic_hash.', 'red');
        }

        if ($results['security_hash']) {
            CLI::write('✅ Security hashes (.security_hash) successfully generated.', 'green');
        } else {
            CLI::write('❌ Failed to generate .security_hash.', 'red');
        }

        CLI::write('Rehash completed.', 'green');
    }
}
