<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseResetPasswordRequest;

class AdminResetPasswordRequest extends BaseResetPasswordRequest
{
    protected function table(): string
    {
        return 'admins';
    }
}
