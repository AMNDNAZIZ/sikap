<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAngsuranKeToPembayaran extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pembayaran', [
            'angsuran_ke' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'jumlah_bayar', // letakkan setelah jumlah_bayar
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pembayaran', 'angsuran_ke');
    }
}
