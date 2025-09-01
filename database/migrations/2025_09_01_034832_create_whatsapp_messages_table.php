<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone'); // Recipient phone number
            $table->string('template'); // Template name (e.g., student_credentials)
            $table->json('data'); // Message data (e.g., {student_name, username, password})
            $table->tinyInteger('status')->default('1')->comment('1=Queued, 2=Sent, 3=Failed');
            $table->text('error_message')->nullable(); // API error if failed
            $table->unsignedInteger('attempts')->default(0); // Retry count
            $table->timestamp('sent_at')->nullable(); // When sent successfully
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
