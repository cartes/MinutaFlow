<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';          // Administrador global de la plataforma SaaS [cite: 181]
    case TenantAdmin = 'tenant_admin';        // Administrador de la empresa de catering [cite: 9, 181]
    case KitchenOperator = 'kitchen_operator';// Chef / Personal de cocina y empaque [cite: 181]
    case CompanyAdmin = 'company_admin';      // RRHH / Administrador de la empresa cliente [cite: 10, 181]
    case Employee = 'employee';                // Comensal final [cite: 11, 181]
}
