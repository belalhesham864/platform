<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\BaseResetPasswordRequest;

class StudentResetPasswordRequest extends BaseResetPasswordRequest
{
    protected function table(): string
    {
        return 'users';
    }
}
