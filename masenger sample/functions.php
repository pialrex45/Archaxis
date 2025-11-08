<?php
// Sample data arrays
$projects = [
    0 => ['name' => 'Project 0', 'description' => 'Website Development Project'],
    1 => ['name' => 'Project 1', 'description' => 'Mobile App Development'],
    2 => ['name' => 'Project 2', 'description' => 'Database Migration'],
    3 => ['name' => 'Project 3', 'description' => 'Cloud Infrastructure'],
    4 => ['name' => 'Project 4', 'description' => 'E-commerce Platform'],
    5 => ['name' => 'Project 5', 'description' => 'API Development'],
    6 => ['name' => 'Project 6', 'description' => 'Security Audit'],
    7 => ['name' => 'Project 7', 'description' => 'Performance Optimization']
];

$roles = [
    'client' => 'Client',
    'project-manager' => 'Project Manager',
    'site-engineer' => 'Site Engineer',
    'site-manager' => 'Site Manager',
    'sub-contractor' => 'Sub Contractor',
    'supervisor' => 'Supervisor',
    'logistics-officer' => 'Logistics Officer',
    'finance-officer' => 'Finance Officer'
];

// Role-specific data
$roleData = [
    'client' => [
        'manager_details' => "Client Representative: John Smith\nEmail: john.smith@client.com\nPhone: +1 234-567-8901\nDepartment: Operations",
        'project_details' => "Client Requirements:\n• Budget: $500,000\n• Timeline: 6 months\n• Quality Standards: ISO 9001\n• Regular Updates Required"
    ],
    'project-manager' => [
        'manager_details' => "Project Manager: Sarah Johnson\nEmail: sarah.j@company.com\nPhone: +1 234-567-8902\nExperience: 8 years\nCertification: PMP",
        'project_details' => "Project Status:\n• Phase: Development\n• Progress: 65%\n• Team Size: 12 members\n• Next Milestone: Dec 15"
    ],
    'site-engineer' => [
        'manager_details' => "Site Engineer: Mike Chen\nEmail: mike.chen@company.com\nPhone: +1 234-567-8903\nSpecialty: Civil Engineering\nYears: 5",
        'project_details' => "Site Information:\n• Location: Downtown Area\n• Site Status: Active\n• Safety Record: Excellent\n• Equipment: Operational"
    ],
    'site-manager' => [
        'manager_details' => "Site Manager: Anna Rodriguez\nEmail: anna.r@company.com\nPhone: +1 234-567-8904\nShift: Day Shift\nTeam: 15 workers",
        'project_details' => "Site Management:\n• Daily Reports: Current\n• Safety Inspections: Weekly\n• Resource Status: Adequate\n• Weather Impact: Minimal"
    ],
    'sub-contractor' => [
        'manager_details' => "Sub Contractor: Robert Wilson\nCompany: Wilson Construction\nEmail: rob@wilsonconst.com\nPhone: +1 234-567-8905",
        'project_details' => "Contract Details:\n• Scope: Electrical Work\n• Duration: 3 months\n• Progress: On Schedule\n• Payment: Current"
    ],
    'supervisor' => [
        'manager_details' => "Supervisor: Lisa Brown\nEmail: lisa.brown@company.com\nPhone: +1 234-567-8906\nShift: Morning\nTeam Size: 8",
        'project_details' => "Supervision Areas:\n• Quality Control\n• Safety Compliance\n• Team Coordination\n• Progress Monitoring"
    ],
    'logistics-officer' => [
        'manager_details' => "Logistics Officer: David Kumar\nEmail: david.k@company.com\nPhone: +1 234-567-8907\nDepartment: Supply Chain",
        'project_details' => "Logistics Status:\n• Material Delivery: On Time\n• Inventory: Well Stocked\n• Transportation: Available\n• Warehousing: Organized"
    ],
    'finance-officer' => [
        'manager_details' => "Finance Officer: Jennifer Lee\nEmail: jennifer.lee@company.com\nPhone: +1 234-567-8908\nDepartment: Finance",
        'project_details' => "Financial Status:\n• Budget Used: 60%\n• Remaining: $200,000\n• Cash Flow: Positive\n• Payments: Up to Date"
    ]
];

// Function to get current project
function getCurrentProject() {
    global $projects;
    $projectId = isset($_GET['project']) ? (int)$_GET['project'] : 0;
    return isset($projects[$projectId]) ? $projectId : 0;
}

// Function to get current role
function getCurrentRole() {
    global $roles;
    $role = isset($_GET['role']) ? $_GET['role'] : 'client';
    return array_key_exists($role, $roles) ? $role : 'client';
}

// Function to get role details
function getRoleDetails($role) {
    global $roleData;
    return isset($roleData[$role]) ? $roleData[$role] : $roleData['client'];
}

// Function to load messages (simulate database)
function getMessages($projectId, $role) {
    // In a real application, this would query a database
    // For demo purposes, we'll use session storage
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    
    $key = $projectId . '_' . $role;
    return isset($_SESSION['messages'][$key]) ? $_SESSION['messages'][$key] : [];
}

// Function to add a message
function addMessage($projectId, $role, $message, $sender = 'User') {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    
    $key = $projectId . '_' . $role;
    if (!isset($_SESSION['messages'][$key])) {
        $_SESSION['messages'][$key] = [];
    }
    
    $_SESSION['messages'][$key][] = [
        'sender' => $sender,
        'message' => $message,
        'timestamp' => date('H:i')
    ];
    
    // Add automated response
    if ($sender === 'User') {
        global $roles;
        $roleName = $roles[$role];
        $_SESSION['messages'][$key][] = [
            'sender' => $roleName,
            'message' => "Thank you for your message. This is a simulated response from $roleName.",
            'timestamp' => date('H:i')
        ];
    }
}

// Function to get default chat message
function getDefaultChatMessage($projectId, $role) {
    global $roles, $projects;
    $roleName = $roles[$role];
    $projectName = $projects[$projectId]['name'];
    
    return "Just as when you press the $roleName button, the $roleName's messaging system is activated for $projectName. Similarly, when you activate the rest, the Admin's messaging system is activated along with them.";
}

// Function to generate query string for navigation
function buildQueryString($params = []) {
    $currentParams = [];
    if (isset($_GET['project'])) $currentParams['project'] = $_GET['project'];
    if (isset($_GET['role'])) $currentParams['role'] = $_GET['role'];
    
    $merged = array_merge($currentParams, $params);
    return '?' . http_build_query($merged);
}
?>