<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseForgotPasswordRequest;

class AdminForgotPasswordRequest extends BaseForgotPasswordRequest
{
    protected function table(): string
    {
        return 'admins';
    }
}
