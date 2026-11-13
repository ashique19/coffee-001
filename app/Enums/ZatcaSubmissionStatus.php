<?php

namespace App\Enums;

enum ZatcaSubmissionStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
