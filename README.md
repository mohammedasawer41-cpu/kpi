# Calendar & To-Do List Task Manager

A web-based task management application built with PHP and MySQL. This application allows users to manage tasks on a calendar, assign tasks to people, and track task status.

## Features

- 📅 **Interactive Calendar View** - See all tasks for a month at a glance
- ✅ **Task Management** - Create, edit, and delete tasks
- 👥 **Team Assignment** - Assign tasks to team members
- 🏷️ **Status Tracking** - Track task status (Not Yet Started, Ongoing, Done, Overdue)
- 📊 **Priority Levels** - Set task priority (Low, Medium, High)
- 🔐 **User Authentication** - Secure login and registration system
- 📋 **Task Filtering** - Filter tasks by status, person, or priority
- 📈 **Quick Stats** - View task statistics for the current month

## Task Status Options

- **Not Yet Started** - Task is scheduled but hasn't begun
- **Ongoing** - Task is currently in progress
- **Done** - Task has been completed
- **Overdue** - Task deadline has passed

## Installation

### Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

### Setup Steps

1. **Download/Clone the Repository**
   ```bash
   git clone https://github.com/zoyhfj/kpi.git
   cd kpi
   ```

2. **Create MySQL Database**
   - The database will be created automatically on first run
   - Default connection uses: `localhost`, username: `root`, password: (empty)
   - Edit `config/db.php` if your MySQL credentials are different

3. **Configure Database Connection**
   Edit `config/db.php` and update:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $database = "kpi_tasks";
   ```

4. **Access the Application**
   - Open `http://localhost/kpi` in your browser
   - Register a new account or use the login page

## File Structure

```
kpi/
├── config/
│   └── db.php              # Database configuration
├── css/
│   └── style.css           # Main stylesheet
├── index.php               # Calendar view (main page)
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # User logout
├── task-form.php           # Create/edit tasks
├── tasks.php               # View all tasks with filters
├── people.php              # Manage team members
├── delete-task.php         # Delete task handler
└── README.md               # This file
```

## Usage

### First Time Setup

1. Register a new account
2. Log in with your credentials
3. Add team members in the "People" section
4. Create your first task in the calendar or via "Add New Task"
5. Assign tasks to team members and track progress

### Creating a Task

1. Click on a date in the calendar or click "Add New Task"
2. Fill in task details:
   - Description (required)
   - Date (required)
   - Assigned person (optional)
   - Status (Not Yet Started, Ongoing, Done, Overdue)
   - Priority (Low, Medium, High)
   - Notes (optional)
3. Click "Create Task"

### Managing Tasks

- **Edit**: Click "Edit" on any task to modify its details
- **Delete**: Click "Delete" to remove a task (confirmation required)
- **Filter**: Use the filters on the "All Tasks" page to view specific tasks
- **View Stats**: Check the sidebar on the calendar for this month's statistics

### Managing People

- **Add**: Enter name and email, click "Add Person"
- **Delete**: Click "Delete" next to any person (tasks will be unassigned)
- **View Workload**: See how many tasks are assigned to each person

## Database Schema

### users table
- id (INT, Primary Key)
- username (VARCHAR 50, Unique)
- password (VARCHAR 255)
- email (VARCHAR 100, Unique)
- created_at (TIMESTAMP)

### people table
- id (INT, Primary Key)
- name (VARCHAR 100)
- email (VARCHAR 100, Unique)
- created_at (TIMESTAMP)

### tasks table
- id (INT, Primary Key)
- description (VARCHAR 255)
- task_date (DATE)
- assigned_to (INT, Foreign Key)
- status (ENUM: Not Yet Started, Ongoing, Done, Overdue)
- priority (ENUM: Low, Medium, High)
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

## Security Features

- Password hashing using bcrypt
- SQL prepared statements to prevent injection
- Session management for user authentication
- Input validation and sanitization

## Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in `config/db.php`
- Ensure MySQL user has CREATE DATABASE privileges

### Tasks Not Showing in Calendar
- Check the task date is within the displayed month
- Verify the task was saved successfully
- Check browser console for JavaScript errors

### Cannot Login
- Ensure you've registered an account first
- Check username and password are correct
- Clear browser cache and try again

## Future Enhancements

- Email notifications for task assignments
- Task comments and activity log
- Multiple calendar views (week, day)
- Recurring tasks
- Task dependencies
- Team collaboration features
- Export to PDF/Excel
- Mobile app

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please open an issue on GitHub.
