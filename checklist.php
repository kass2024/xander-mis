<?php
/**
 * Dynamic Checklist System
 * Allows adding categories and tasks with checkbox completion tracking
 */

// Data file path
$dataFile = __DIR__ . '/checklist_data.json';

// Initialize data file if it doesn't exist or is empty
if (!file_exists($dataFile) || filesize($dataFile) === 0) {
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
            ],
            [
                'id' => 'homepage_ui',
                'name' => 'Homepage UI Enhancement',
                'description' => 'Tasks for improving the homepage interface',
                'created_at' => date('Y-m-d H:i:s'),
                'tasks' => [
                    [
                        'id' => 'task_3',
                        'title' => 'Redesign hero section',
                        'description' => 'Create modern hero section with animations',
                        'completed' => true,
                        'created_at' => date('Y-m-d H:i:s'),
                        'completed_at' => date('Y-m-d H:i:s')
                    ],
                    [
                        'id' => 'task_4',
                        'title' => 'Add responsive design',
                        'description' => 'Ensure mobile compatibility across all sections',
                        'completed' => false,
                        'created_at' => date('Y-m-d H:i:s'),
                        'completed_at' => null
                    ]
                ]
            ]
        ]
    ];
    file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents($dataFile), true);
    
    // Add new category
    if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
        $categoryId = 'cat_' . time() . '_' . rand(1000, 9999);
        $newCategory = [
            'id' => $categoryId,
            'name' => $_POST['category_name'] ?? 'New Category',
            'description' => $_POST['category_description'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'tasks' => []
        ];
        $data['categories'][] = $newCategory;
    }
    
    // Add new task
    if (isset($_POST['action']) && $_POST['action'] === 'add_task') {
        $categoryId = $_POST['category_id'] ?? '';
        $taskId = 'task_' . time() . '_' . rand(1000, 9999);
        
        foreach ($data['categories'] as &$category) {
            if ($category['id'] === $categoryId) {
                $newTask = [
                    'id' => $taskId,
                    'title' => $_POST['task_title'] ?? 'New Task',
                    'description' => $_POST['task_description'] ?? '',
                    'completed' => false,
                    'created_at' => date('Y-m-d H:i:s'),
                    'completed_at' => null
                ];
                $category['tasks'][] = $newTask;
                break;
            }
        }
    }
    
    // Toggle task completion
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_task') {
        $taskId = $_POST['task_id'] ?? '';
        
        foreach ($data['categories'] as &$category) {
            foreach ($category['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['completed'] = !$task['completed'];
                    $task['completed_at'] = $task['completed'] ? date('Y-m-d H:i:s') : null;
                    break 2;
                }
            }
        }
    }
    
    // Delete category
    if (isset($_POST['action']) && $_POST['action'] === 'delete_category') {
        $categoryId = $_POST['category_id'] ?? '';
        $data['categories'] = array_filter($data['categories'], function($cat) use ($categoryId) {
            return $cat['id'] !== $categoryId;
        });
        $data['categories'] = array_values($data['categories']);
    }
    
    // Delete task
    if (isset($_POST['action']) && $_POST['action'] === 'delete_task') {
        $taskId = $_POST['task_id'] ?? '';
        
        foreach ($data['categories'] as &$category) {
            $category['tasks'] = array_filter($category['tasks'], function($task) use ($taskId) {
                return $task['id'] !== $taskId;
            });
            $category['tasks'] = array_values($category['tasks']);
        }
    }
    
    // Developer verification
    if (isset($_POST['action']) && $_POST['action'] === 'developer_verify') {
        $taskId = $_POST['task_id'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        foreach ($data['categories'] as &$category) {
            foreach ($category['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['developer_verified'] = !$task['developer_verified'];
                    $task['developer_verified_at'] = $task['developer_verified'] ? date('Y-m-d H:i:s') : null;
                    $task['developer_comment'] = $comment;
                    break 2;
                }
            }
        }
    }
    
    // Owner verification
    if (isset($_POST['action']) && $_POST['action'] === 'owner_verify') {
        $taskId = $_POST['task_id'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        foreach ($data['categories'] as &$category) {
            foreach ($category['tasks'] as &$task) {
                if ($task['id'] === $taskId) {
                    $task['owner_verified'] = !$task['owner_verified'];
                    $task['owner_verified_at'] = $task['owner_verified'] ? date('Y-m-d H:i:s') : null;
                    $task['owner_comment'] = $comment;
                    
                    // Auto-complete main task when owner approves
                    if ($task['owner_verified']) {
                        $task['completed'] = true;
                        $task['completed_at'] = date('Y-m-d H:i:s');
                    } else {
                        // If owner revokes approval, keep task as completed (manual uncheck needed)
                        // Or optionally uncheck main task too:
                        // $task['completed'] = false;
                        // $task['completed_at'] = null;
                    }
                    break 2;
                }
            }
        }
    }
    
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    
    // Redirect to prevent form resubmission
    header('Location: checklist.php');
    exit;
}

// Load current data
$checklistData = json_decode(file_get_contents($dataFile), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Checklist System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #012F6B;
            --primary-light: #254D81;
            --accent: #F2A65A;
            --accent-dark: #E6892E;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #0F172A;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius: 0.5rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .add-section {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(242, 166, 90, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--bg-secondary);
        }

        .categories {
            display: grid;
            gap: 2rem;
        }

        .category {
            background: var(--bg-primary);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: var(--transition);
        }

        .category:hover {
            box-shadow: var(--shadow-lg);
        }

        .category-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-info h2 {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
        }

        .category-info p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .category-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .task-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .category-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .category-body {
            padding: 1.5rem;
        }

        .add-task-form {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .task-list {
            display: grid;
            gap: 1rem;
        }

        .task {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: var(--transition);
        }

        .task:hover {
            border-color: var(--accent);
            box-shadow: var(--shadow);
        }

        .task.completed {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
            border-color: var(--success);
        }

        .task-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .task-checkbox:hover {
            border-color: var(--accent);
        }

        .task-checkbox.checked {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .task-content {
            flex: 1;
        }

        .task-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--text-primary);
        }

        .task.completed .task-title {
            text-decoration: line-through;
            opacity: 0.7;
        }

        .task-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .task-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        /* Verification Section */
        .verification-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .verification-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.8rem;
            align-items: flex-start;
        }

        .verification-box {
            flex: 1;
            padding: 0.8rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--bg-secondary);
        }

        .verification-box.developer {
            border-color: #3b82f6;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.02));
        }

        .verification-box.owner {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
        }

        .verification-box.verified {
            border-color: var(--success);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
        }

        .verification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .verification-title {
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .verification-title.developer {
            color: #3b82f6;
        }

        .verification-title.owner {
            color: #10b981;
        }

        .verification-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .verification-status.developer {
            border-color: #3b82f6;
        }

        .verification-status.owner {
            border-color: #10b981;
        }

        .verification-status.verified {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .verification-status:hover {
            transform: scale(1.1);
        }

        .verification-comment {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.3rem;
            font-style: italic;
        }

        .verification-time {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }

        .btn-verify {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-verify.developer {
            background: #3b82f6;
            color: white;
        }

        .btn-verify.developer:hover {
            background: #2563eb;
        }

        .btn-verify.owner {
            background: #10b981;
            color: white;
        }

        .btn-verify.owner:hover {
            background: #059669;
        }

        .comment-input {
            width: 100%;
            padding: 0.4rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            resize: vertical;
            min-height: 40px;
        }

        .comment-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(242, 166, 90, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .progress-bar {
            height: 4px;
            background: var(--bg-tertiary);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--accent));
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .category-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .task {
                flex-direction: column;
                text-align: center;
            }

            .task-checkbox {
                margin: 0 auto 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-tasks"></i> Dynamic Checklist System</h1>
            <p>Organize your tasks by categories and track progress efficiently</p>
        </header>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($checklistData['categories']); ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <?php
            $totalTasks = 0;
            $completedTasks = 0;
            foreach ($checklistData['categories'] as $category) {
                $totalTasks += count($category['tasks']);
                foreach ($category['tasks'] as $task) {
                    if ($task['completed']) $completedTasks++;
                }
            }
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalTasks; ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completedTasks; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0; ?>%</div>
                <div class="stat-label">Progress</div>
            </div>
        </div>

        <!-- Add Category Form -->
        <div class="add-section">
            <h3><i class="fas fa-plus-circle"></i> Add New Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="form-row">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" required placeholder="e.g., MSI Development">
                        </div>
                        <div class="form-group">
                            <label for="category_description">Description</label>
                            <input type="text" id="category_description" name="category_description" placeholder="Brief description of this category">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories -->
        <div class="categories">
            <?php if (empty($checklistData['categories'])): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No categories yet</h3>
                    <p>Create your first category to get started with organizing your tasks</p>
                </div>
            <?php else: ?>
                <?php foreach ($checklistData['categories'] as $category): ?>
                    <?php
                    $categoryTaskCount = count($category['tasks']);
                    $categoryCompletedCount = 0;
                    foreach ($category['tasks'] as $task) {
                        if ($task['completed']) $categoryCompletedCount++;
                    }
                    $categoryProgress = $categoryTaskCount > 0 ? ($categoryCompletedCount / $categoryTaskCount) * 100 : 0;
                    ?>
                    <div class="category">
                        <div class="category-header">
                            <div class="category-info">
                                <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                                <p><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $categoryProgress; ?>%"></div>
                                </div>
                            </div>
                            <div class="category-meta">
                                <span class="task-count"><?php echo $categoryCompletedCount; ?>/<?php echo $categoryTaskCount; ?> tasks</span>
                                <div class="category-actions">
                                    <button type="button" class="btn-icon" onclick="toggleAddTaskForm('<?php echo $category['id']; ?>')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category and all its tasks?')">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn-icon">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="category-body">
                            <!-- Add Task Form (Hidden by default) -->
                            <div class="add-task-form" id="add-task-<?php echo $category['id']; ?>" style="display: none;">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_task">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <div class="form-row">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; flex: 1;">
                                            <div class="form-group">
                                                <input type="text" name="task_title" placeholder="Task title" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="text" name="task_description" placeholder="Task description (optional)">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-small">
                                            <i class="fas fa-plus"></i> Add Task
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Tasks -->
                            <?php if (empty($category['tasks'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-list"></i>
                                    <p>No tasks yet. Add your first task to get started.</p>
                                </div>
                            <?php else: ?>
                                <div class="task-list">
                                    <?php foreach ($category['tasks'] as $task): ?>
                                        <div class="task <?php echo $task['completed'] ? 'completed' : ''; ?>">
                                            <div class="task-checkbox <?php echo $task['completed'] ? 'checked' : ''; ?>" 
                                                 onclick="toggleTask('<?php echo $task['id']; ?>')">
                                                <?php if ($task['completed']): ?>
                                                    <i class="fas fa-check"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="task-content">
                                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                                <?php if (!empty($task['description'])): ?>
                                                    <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                                                <?php endif; ?>
                                                <div class="task-meta">
                                                    <span><i class="fas fa-calendar-plus"></i> Created: <?php echo date('M j, Y', strtotime($task['created_at'])); ?></span>
                                                    <?php if ($task['completed'] && $task['completed_at']): ?>
                                                        <span><i class="fas fa-check-circle"></i> Completed: <?php echo date('M j, Y', strtotime($task['completed_at'])); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Verification Section -->
                                                <div class="verification-section">
                                                    <!-- Developer Work Completion -->
                                                    <div class="verification-box developer <?php echo ($task['developer_verified'] ?? false) ? 'verified' : ''; ?>">
                                                        <div class="verification-header">
                                                            <div class="verification-title developer">
                                                                <i class="fas fa-code"></i>
                                                                Developer - Work Completed
                                                            </div>
                                                            <div class="verification-status developer <?php echo ($task['developer_verified'] ?? false) ? 'verified' : ''; ?>" 
                                                                 onclick="toggleVerification('developer', '<?php echo $task['id']; ?>')">
                                                                <?php if ($task['developer_verified'] ?? false): ?>
                                                                    <i class="fas fa-check"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0.5rem 0;">
                                                            Tick when the development work for this task is completed and ready for review
                                                        </p>
                                                        <?php if ($task['developer_verified'] ?? false): ?>
                                                            <?php if (!empty($task['developer_comment'] ?? '')): ?>
                                                                <div class="verification-comment"><?php echo htmlspecialchars($task['developer_comment']); ?></div>
                                                            <?php endif; ?>
                                                            <div class="verification-time">
                                                                <i class="fas fa-clock"></i> Completed: <?php echo date('M j, Y H:i', strtotime($task['developer_verified_at'] ?? 'now')); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <form method="POST" style="margin-top: 0.5rem;">
                                                            <input type="hidden" name="action" value="developer_verify">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <textarea name="comment" class="comment-input" placeholder="Developer notes about the work completed..." rows="2"><?php echo htmlspecialchars($task['developer_comment'] ?? ''); ?></textarea>
                                                            <button type="submit" class="btn-verify developer">
                                                                <i class="fas fa-<?php echo ($task['developer_verified'] ?? false) ? 'times' : 'check'; ?>"></i>
                                                                <?php echo ($task['developer_verified'] ?? false) ? 'Mark Incomplete' : 'Mark Complete'; ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <!-- Owner Final Approval -->
                                                    <div class="verification-box owner <?php echo ($task['owner_verified'] ?? false) ? 'verified' : ''; ?>">
                                                        <div class="verification-header">
                                                            <div class="verification-title owner">
                                                                <i class="fas fa-user-tie"></i>
                                                                Project Owner - Final Approval
                                                            </div>
                                                            <div class="verification-status owner <?php echo ($task['owner_verified'] ?? false) ? 'verified' : ''; ?>" 
                                                                 onclick="toggleVerification('owner', '<?php echo $task['id']; ?>')">
                                                                <?php if ($task['owner_verified'] ?? false): ?>
                                                                    <i class="fas fa-check"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0.5rem 0;">
                                                            Tick as final approval after reviewing the completed work
                                                        </p>
                                                        <?php if (!($task['developer_verified'] ?? false)): ?>
                                                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 0.5rem; border-radius: 4px; margin: 0.5rem 0; font-size: 0.8rem;">
                                                                <i class="fas fa-info-circle" style="color: #f39c12;"></i> 
                                Waiting for developer to complete work first
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($task['owner_verified'] ?? false): ?>
                                                            <?php if (!empty($task['owner_comment'] ?? '')): ?>
                                                                <div class="verification-comment"><?php echo htmlspecialchars($task['owner_comment']); ?></div>
                                                            <?php endif; ?>
                                                            <div class="verification-time">
                                                                <i class="fas fa-clock"></i> Approved: <?php echo date('M j, Y H:i', strtotime($task['owner_verified_at'] ?? 'now')); ?>
                                                            </div>
                                                            <?php if ($task['completed'] ?? false): ?>
                                                                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 0.5rem; border-radius: 4px; margin: 0.5rem 0; font-size: 0.8rem;">
                                                                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                    Main task automatically marked as completed
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <form method="POST" style="margin-top: 0.5rem;">
                                                            <input type="hidden" name="action" value="owner_verify">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <textarea name="comment" class="comment-input" placeholder="Owner approval notes..." rows="2"><?php echo htmlspecialchars($task['owner_comment'] ?? ''); ?></textarea>
                                                            <button type="submit" class="btn-verify owner" <?php echo !($task['developer_verified'] ?? false) ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-<?php echo ($task['owner_verified'] ?? false) ? 'times' : 'check'; ?>"></i>
                                                                <?php echo ($task['owner_verified'] ?? false) ? 'Revoke Approval' : 'Give Final Approval'; ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                                    <input type="hidden" name="action" value="delete_task">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-small">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle task completion
        function toggleTask(taskId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_task">
                <input type="hidden" name="task_id" value="${taskId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Toggle verification (quick toggle without comment)
        function toggleVerification(type, taskId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="${type}_verify">
                <input type="hidden" name="task_id" value="${taskId}">
                <input type="hidden" name="comment" value="">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Toggle add task form
        function toggleAddTaskForm(categoryId) {
            const form = document.getElementById(`add-task-${categoryId}`);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.querySelector('input[name="task_title"]').focus();
            }
        }

        // Auto-hide add task forms after submission
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + N to focus on new category input
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    document.getElementById('category_name').focus();
                }
            });
        });
    </script>
</body>
</html>
