<?php

namespace Src\Evaluators\Domain\Enums;

enum Specialty: string
{
    case BACKEND = 'Backend';
    case FRONTEND = 'Frontend';
    case FULLSTACK = 'Fullstack';
    case DEVOPS = 'DevOps';
    case MOBILE = 'Mobile';
    case QA = 'QA';
    case DATA = 'Data';
    case SECURITY = 'Security';
}
