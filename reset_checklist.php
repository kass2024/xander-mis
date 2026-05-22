<?php
// Reset checklist data file with default Xander MIS Development tasks
$dataFile = __DIR__ . '/checklist_data.json';

$initialData = [
    'categories' => [
        [
            'id' => 'xander_mis_development',
            'name' => 'Xander MIS Development',
            'description' => 'Critical development tasks for Xander Management Information System',
            'created_at' => date('Y-m-d H:i:s'),
            'tasks' => [
                [
                    'id' => 'mis_task_1',
                    'title' => 'Change "Student Account" to "My Account"',
                    'description' => 'Update all references from "Student Account" to "My Account" throughout the system',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_2',
                    'title' => 'Make valid email and phone number mandatory during sign-up',
                    'description' => 'Add validation for email format and phone number to prevent fake admin accounts',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_3',
                    'title' => 'Add upcoming locations in Marketing Tools and Contracts',
                    'description' => 'Add Uganda, DRC, and Kenya (Coming Soon) to Marketing Tools and Contracts section',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_4',
                    'title' => 'Add currency selection dropdown in Payments',
                    'description' => 'Implement currency selection dropdown in the Payments section for multi-currency support',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_5',
                    'title' => 'Organize Marketing Tools by country with dropdown folders',
                    'description' => 'Restructure Marketing Tools to organize by country with dropdown folders for downloads',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_6',
                    'title' => 'Move "Pre-Screening" to appear first after Dashboard in Admin panel',
                    'description' => 'Reorder Admin panel menu to place "Pre-Screening" immediately after Dashboard',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_7',
                    'title' => 'Set all menu access to "N/A" by default for new users',
                    'description' => 'Configure default menu access to "N/A" for new users - do not grant full access automatically',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_8',
                    'title' => 'Add attendance question to Pre-Screening form',
                    'description' => 'Add "Will you attend online or in person?" question to the Pre-Screening form',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_9',
                    'title' => 'Clean up the front page of the MIS',
                    'description' => 'Perform comprehensive cleanup and optimization of the MIS front page',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_10',
                    'title' => 'Update office locations',
                    'description' => 'Remove the Muhanga office and add the Burundi office with their phone number',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ],
                [
                    'id' => 'mis_task_11',
                    'title' => 'Connect WhatsApp phone number to website live chat',
                    'description' => 'Integrate WhatsApp phone number with the website live chat functionality',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null,
                    'developer_verified' => false,
                    'developer_verified_at' => null,
                    'developer_comment' => '',
                    'owner_verified' => false,
                    'owner_verified_at' => null,
                    'owner_comment' => ''
                ]
            ]
        ]
    ]
];

// Delete existing file if it exists
if (file_exists($dataFile)) {
    unlink($dataFile);
}

// Create new data file
$result = file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "<h2>✅ Success! Checklist data file has been recreated with default values.</h2>";
    echo "<p><strong>Categories created:</strong> 1</p>";
    echo "<p><strong>Total tasks:</strong> 11</p>";
    echo "<p><a href='checklist.php'>Go to Checklist</a></p>";
} else {
    echo "<h2>❌ Error! Could not create checklist data file.</h2>";
    echo "<p>Please check file permissions.</p>";
}
?>
