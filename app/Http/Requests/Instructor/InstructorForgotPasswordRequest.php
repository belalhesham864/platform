<?php

namespace App\Http\Requests\Instructor;

use App\Http\Requests\BaseForgotPasswordRequest;

class InstructorForgotPasswordRequest extends BaseForgotPasswordRequest
{
    protected function table(): string
    {
        return 'instructors';
    }
}
