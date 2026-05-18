<?php
declare(strict_types=1);

/**
 * Canonical admin sidebar menus and submenus (single source of truth).
 *
 * @return array<string, array{title:string,icon:string,section:string,links:array<string,string>}>
 */
function xander_admin_menu_registry(): array
{
    return [
        'all_admissions' => [
            'title' => 'All university admissions',
            'icon' => 'bi-mortarboard',
            'section' => 'Applications',
            'links' => [
                'application-list.php' => 'Student application Report',
                'students-manage.php' => 'Applicants Management',
                'receipt_viewer.php' => 'Check payment Receipt',
                'task-assignment-monitoring.php' => 'Task assignment monitoring',
            ],
        ],
        'loan_applications' => [
            'title' => 'Study Loan Applications',
            'icon' => 'bi-bank',
            'section' => 'Applications',
            'links' => [
                'loan-applicants-report.php' => 'Loan Application list',
                'loan_search.php' => 'User-iD',
            ],
        ],
        'I-20_applications' => [
            'title' => 'I-20 Applications',
            'icon' => 'bi-file-earmark-text',
            'section' => 'Applications',
            'links' => [
                'form-20-report.php' => 'I-20 Applicant List',
            ],
        ],
        'staff_reporting' => [
            'title' => 'Staff Management',
            'icon' => 'bi-people',
            'section' => 'Applications',
            'links' => [
                'staff-management.php' => 'Manage staff',
                'admin/contracts-admin.php' => 'View staffs Contracts',
                'salary-report.php' => 'View Requested Salaries',
                'leave-approvals.php' => 'Manage Permissions',
                'overtime-approvals.php' => 'Overtime Management',
                'jobs_report.php' => 'Check job report',
                'admin-payroll.php' => 'Payroll',
                'cards/generate_staff_card.php' => 'Generate staff cards',
            ],
        ],
        'commission_request' => [
            'title' => 'Commission Request',
            'icon' => 'bi-cash-coin',
            'section' => 'Applications',
            'links' => [
                'Commission-Request.php' => 'Request commission',
                'commission-requests-report.php' => 'All Requests',
            ],
        ],
        'credit_transfer' => [
            'title' => 'Credit Transfer Applications',
            'icon' => 'bi-arrow-left-right',
            'section' => 'Applications',
            'links' => [
                'Credit-Transfer-report.php' => 'Transfer Requests list',
                'credit-search.php' => 'credit userID',
            ],
        ],
        'visit_study_visa' => [
            'title' => 'Visit And Study Visa',
            'icon' => 'bi-globe2',
            'section' => 'Applications',
            'links' => [
                'visa-report.php' => 'Applicant List',
            ],
        ],
        'staff_attendance' => [
            'title' => 'Staff Attendance',
            'icon' => 'bi-calendar-check',
            'section' => 'Applications',
            'links' => [
                'attendance-ui.php' => 'Take attendance',
                'job_todo_list.php' => 'Job Do List',
                'salary.php' => 'Salary Request',
                'admin/contract.php' => 'Sign your contract',
                'leave-request.php' => 'Permission Request',
                'staff_overtime_request.php' => 'Overtime request',
                'my-leaves.php' => 'Check permission status',
                'attendance-report.php' => 'Attendance Report',
                'jobs_report.php' => 'Check job report',
                'cards/generate_staff_card.php' => 'Generate your service card',
            ],
        ],
        'university_portal' => [
            'title' => 'Apply for Student',
            'icon' => 'bi-person-plus',
            'section' => 'Applications',
            'links' => [
                'student-application.php' => 'Apply Now',
                'agent-student-manage.php' => 'Manage Students',
                'userid-search.php' => 'User-id',
            ],
        ],
        'marketing' => [
            'title' => 'Marketing Materials',
            'icon' => 'bi-megaphone',
            'section' => 'Applications',
            'links' => [
                'upload-materials.php' => 'Upload Marketing materials',
                'get-materials.php' => 'Get Marketing materials',
            ],
        ],
        'ticketing' => [
            'title' => 'Air Ticketing Reservation',
            'icon' => 'bi-ticket-perforated',
            'section' => 'Applications',
            'links' => [
                'reservation-report.php' => 'Check Reservation',
            ],
        ],
        'jobsabrod' => [
            'title' => 'Jobs Application',
            'icon' => 'bi-briefcase',
            'section' => 'Applications',
            'links' => [
                'job-applicant.php' => 'Check job Applicants',
            ],
        ],
        'institutions_monitor' => [
            'title' => 'Institution portals',
            'icon' => 'bi-building',
            'section' => 'Applications',
            'links' => [
                'admin-institutions-monitor.php' => 'All registered institutions',
                'admin-institution-overview.php' => 'Institution dashboard',
            ],
        ],
        'platform' => [
            'title' => 'Platforms management',
            'icon' => 'bi-diagram-3',
            'section' => 'Applications',
            'links' => [
                'platforms.php' => 'Platforms management',
            ],
        ],
        'contracts' => [
            'title' => 'Student contract',
            'icon' => 'bi-file-earmark-lock',
            'section' => 'Applications',
            'links' => [
                'admin-generate-student-contract.php' => 'Issue contract link',
                'admin-contracts.php' => 'View students Contracts',
                'admin-generate-student-contract-burundi.php' => 'Issue Burundi contract link',
                'admin-contracts-burundi.php' => 'Burundi contracts',
            ],
        ],
        'prescreening' => [
            'title' => 'Pre-screening',
            'icon' => 'bi-clipboard-check',
            'section' => 'Applications',
            'links' => [
                'prescreening.php' => 'New pre-screening',
                'prescreening-report.php' => 'Pre-screening list',
            ],
        ],
        'chart' => [
            'title' => 'Live Chat Assistant',
            'icon' => 'bi-chat-dots',
            'section' => 'Applications',
            'links' => [
                'admin/chat-dashboard.php' => 'Live chat dashboard',
            ],
        ],
    ];
}

/** Role-based defaults (legacy sidebarAccess). */
function xander_admin_menu_role_defaults(): array
{
    return [
        'superadmin' => [
            'all_admissions', 'loan_applications', 'I-20_applications', 'staff_reporting',
            'commission_request', 'credit_transfer', 'visit_study_visa', 'prescreening', 'staff_attendance',
            'university_portal', 'institutions_monitor', 'marketing', 'ticketing', 'jobsabrod', 'platform', 'contracts', 'chart',
        ],
        'agent' => [
            'staff_attendance', 'university_portal', 'commission_request',
            'all_admissions', 'marketing', 'visit_study_visa',
        ],
        'staff' => [
            'staff_attendance', 'university_portal', 'commission_request',
            'all_admissions', 'loan_applications', 'marketing', 'contracts', 'jobsabrod', 'credit_transfer', 'visit_study_visa',
            'prescreening',
        ],
        'standard' => [
            'loan_applications', 'I-20_applications', 'all_admissions',
            'university_portal', 'commission_request', 'staff_attendance',
            'marketing', 'visit_study_visa',
        ],
        'Catholic university of America' => [
            'marketing', 'visit_study_visa',
        ],
    ];
}
