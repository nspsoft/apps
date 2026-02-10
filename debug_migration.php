<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "Starting debug script...\n";

try {
    echo "Dropping tables if exist...\n";
    Schema::dropIfExists('task_members');
    Schema::dropIfExists('project_members');
    Schema::dropIfExists('project_tasks');
    Schema::dropIfExists('projects');
    echo "Tables dropped successfully.\n";

    echo "Running migrations manually for projects...\n";
    DB::statement("CREATE TABLE projects (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        status VARCHAR(255) DEFAULT 'draft',
        manager_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Projects table created.\n";

    echo "Running migrations manually for project_tasks...\n";
    DB::statement("CREATE TABLE project_tasks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        project_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_date_plan DATE,
        end_date_plan DATE,
        start_date_actual DATE,
        end_date_actual DATE,
        progress DECIMAL(5,2) DEFAULT 0.00,
        status VARCHAR(255) DEFAULT 'todo',
        priority VARCHAR(255) DEFAULT 'medium',
        parent_id BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES project_tasks(id) ON DELETE CASCADE
    )");
    echo "Project Tasks table created.\n";

    echo "Running migrations manually for project_members...\n";
    DB::statement("CREATE TABLE project_members (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        project_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        role VARCHAR(255),
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Project Members table created.\n";

    echo "Running migrations manually for task_members...\n";
    DB::statement("CREATE TABLE task_members (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        project_task_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (project_task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Task Members table created.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
