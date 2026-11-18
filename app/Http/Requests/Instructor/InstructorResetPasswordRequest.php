<?php

namespace App\Http\Requests\Instructor;

use App\Http\Requests\BaseResetPasswordRequest;

class InstructorResetPasswordRequest extends BaseResetPasswordRequest
{
    protected function table(): string
    {
        return 'instructors';
    }
}
