<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_id', '100')->unique();
                $table->unsignedTinyInteger('status');
                $table->text('webhook_ids');
                $table->timestamps();
           });
        }

        public function down(): void
        {
            Schema::dropIfExists('payment');
        }
    }
?>