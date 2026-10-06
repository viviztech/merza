<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSavedReply extends Model
{
    protected $table = 'whatsapp_saved_replies';

    protected $fillable = ['title', 'body', 'created_by'];
}
