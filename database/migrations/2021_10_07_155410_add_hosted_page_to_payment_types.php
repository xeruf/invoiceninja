<?php

use App\Models\GatewayType;
use App\Models\PaymentType;
use Illuminate\Database\Migrations\Migration;

class AddHostedPageToPaymentTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $type = new PaymentType();

        $type->id = 35;
        $type->name = 'Hosted Page';
        $type->gateway_type_id = GatewayType::HOSTED_PAGE;

        $type->save();
    }
}