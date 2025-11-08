# ArchAxis: Smart Builds, Bold Leadership

Role-based property development and construction management platform enabling transparent project creation, bidding, hierarchical team formation, progress tracking, inventory oversight, and financial estimation.

## Table of Contents
1. Overview
2. Key Objectives
3. Motivation
4. Tech Stack
5. System Roles & Workflow
6. Database Design Principles
7. Core Features
8. Complex Queries (Samples)
9. Triggers (Samples)
10. Development Phases
11. Summary

## 1. Overview
ArchAxis mirrors real-world construction site hierarchies: clients, managers, engineers, subcontractors, supervisors, logistics, site managers, and site engineers coordinate through structured role-based flows with transparent task, material, and financial tracking.

## 2. Key Objectives
- Dynamic building design with estimation and tax calculation
- Role-based hierarchy (client → manager → contractor → supervisor → worker)
- Project creation, bidding, progress tracking
- Team formation with rank, rating, availability sorting
- Real-time messaging and assignment tree visualization
- Portfolio and performance insights
- Inventory, warehouse, and material usage reporting

## 3. Motivation
Construction projects suffer from fragmented communication, manual tracking, and unclear accountability. ArchAxis centralizes planning, assignment, progress, materials, and financials into a unified, auditable platform.

## 4. Tech Stack
- Database: MySQL (XAMPP)
- Backend: PHP
- Frontend: HTML, CSS, JavaScript
- Visualization: Chart.js
- Diagrams: draw.io

## 5. System Roles
- Admin: Global control panel
- Client: Design building, create project, view budget forecast
- Project Manager: Monitor, assign, confirm materials
- Sub-Contractor: Authorize supervisors, update tasks
- Supervisor: Task completion, manage workers
- Logistics: Supplier enrollment, inventory, orders
- Site Manager: Allocate tasks, request materials
- Site Engineer: Design confirmation, estimation
- (Plus messaging + attendance + finance modules)

## 6. Database Design Principles
- Normalization: Up to 3NF
- Referential Integrity: Foreign keys across relational tables
- Timestamps: created_at, updated_at on all mutable entities
- Complex Queries: Joins, aggregates, filters by role/project
- Triggers: Automate status updates, logging, counters
- Auditability: Task, message, material usage histories

## 7. Core Features
- Sketch-based building designer with engineer export
- Automated cost + tax estimation
- Budget forecasting before execution
- Material & warehouse tracking (stock, usage, procurement)
- Role-based real-time messaging
- Material request & approval workflow
- Attendance & task status logging
- Financial summaries per project
- Supplier performance insights
- Team formation based on availability & ranking

## 8. Sample Complex Queries

Top suppliers by delivered quantity (limit 5):
```sql
SELECT
  s.id AS supplier_id,
  s.name AS supplier_name,
  SUM(m.quantity) AS total_delivered
FROM materials m
INNER JOIN suppliers s ON m.supplier_id = s.id
WHERE m.project_id = :project_id
GROUP BY s.id, s.name
ORDER BY total_delivered DESC
LIMIT 5;
```

Project managers with more than two projects:
```sql
SELECT
  u.id   AS manager_id,
  u.name AS manager_name,
  COUNT(p.id) AS project_count
FROM users u
INNER JOIN projects p ON p.manager_id = u.id
WHERE u.role = 'manager'
GROUP BY u.id, u.name
HAVING COUNT(p.id) > 2
ORDER BY project_count DESC;
```

Finance summary by type:
```sql
SELECT type, SUM(amount) AS total
FROM finance
WHERE project_id = :pid
GROUP BY type;
```

Attendance filtering:
```sql
SELECT a.*, u.name
FROM attendance a
JOIN users u ON a.user_id = u.id
WHERE a.date BETWEEN :start AND :end
  AND u.role IN ('worker','manager')
  AND a.status = :status;
```

Material usage by type:
```sql
SELECT material_type, SUM(quantity) AS total_used
FROM materials
WHERE project_id = :pid
GROUP BY material_type;
```

## 9. Sample Triggers

Message read-status initialization:
```sql
DELIMITER $$
CREATE TRIGGER trg_messages_after_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
  INSERT INTO message_status (message_id, user_id, status, created_at)
  VALUES (NEW.id, NEW.receiver_id, 'unread', NOW());
END$$
DELIMITER ;
```

Increment project task counter:
```sql
DELIMITER $$
CREATE TRIGGER trg_tasks_after_insert
AFTER INSERT ON tasks
FOR EACH ROW
BEGIN
  UPDATE projects
  SET task_count = COALESCE(task_count, 0) + 1
  WHERE id = NEW.project_id;
END$$
DELIMITER ;
```

Log task status transitions:
```sql
DELIMITER $$
CREATE TRIGGER trg_tasks_after_update
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
  IF OLD.status <> NEW.status THEN
    INSERT INTO task_logs (
      task_id, changed_by, old_status, new_status, changed_at
    ) VALUES (
      NEW.id, NEW.assigned_to, OLD.status, NEW.status, NOW()
    );
  END IF;
END$$
DELIMITER ;
```

## 10. Development Phases
| Phase  | Deliverables |
|--------|--------------|
| 1      | Auth (Login/Signup), Role model, DB integration |
| 2      | Role dashboards (Client, Manager, etc.) |
| 3      | Shift tracking, Incident logs |
| 4      | Reports, Analytics, Graphs |
| 5      | UI Styling, Testing, Deployment |

## 11. Summary
ArchAxis unifies multi-role construction operations into a cohesive platform. It enables structured assignment chains, financial transparency, material accountability, and live collaboration—reducing friction and improving delivery confidence.

---
Group ArchAxis  
Members: Ashfakul Ahmed Pial, Rakib Rahyan, Aysha Samira Sami, Nusrat Alam Biva  
Submitted To: Muhammad Anwarul Azim, Lecturer, Dept. of CSE
