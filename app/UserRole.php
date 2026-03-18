<?php

namespace App;

enum UserRole: string
{
    case Agent = 'agent';
    case AgencyManager = 'agency_manager';
    case Staff = 'staff';
}
