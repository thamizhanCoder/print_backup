<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Jobs\CreditCronJob;
use App\Jobs\SendCouponCodeEmail;

class SendCouponCodeImport implements ToModel, WithStartRow
{

    public function  __construct($couponcodes)
    {
        $this->couponcodes = $couponcodes;
        $this->prod = $prod;
    }
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model($prod)
    {
        $import = new SendCouponCodeEmail($this->couponcodes, $prod);

        dispatch($import)->delay(0);
    }


    public function startRow(): int
    {
        return 2;
    }
}
