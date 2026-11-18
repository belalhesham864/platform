<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\BaseForgotPasswordRequest;

class StudentForgotPasswordRequest extends BaseForgotPasswordRequest
{
    protected function table(): string
    {
        return 'users';
    }
}
