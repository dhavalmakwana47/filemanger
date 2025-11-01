User Management System
1. Master Admin Functions

Create a Company with Super Admin: The master admin can set up a new company. Along with the company, a user is created with the role of Super Admin by default.
Super Admin Capabilities:
Add Users: The Super Admin can create additional users for the company.
Create Roles: The Super Admin can define different roles within the company.
Assign Permissions: The Super Admin can grant specific permissions to users and roles.
2. Define Permissions:

A list of permissions that can be assigned to users and roles, outlining what actions they can perform within the system.
3. Access to the Master Panel:

The Master Admin has access to all functionalities within the system, including user management, role creation, and permission assignments.

Database Tables
Users: Stores information about all users in the system.
Company: Contains details of each company.
CompanyRole: Lists the different roles that can be assigned to users within a company.
CompanyRolePermission: Maps which permissions are associated with each role.
CompanyUserPermission: Tracks specific permissions assigned to individual users.
Permissions: A catalog of all available permissions that can be granted.
CompanyUserRole: Records which role is assigned to each user within a company.
UserRole: Links users to their respective roles, outlining their permissions and capabilities.
User Journey
Creating a Company: The Master Admin creates a new company. During this process, a default Super Admin user is created with full permissions.
Super Admin Responsibilities: The Super Admin can:
Add new users to the company.
Create new roles that define user capabilities.
Assign permissions to both roles and individual users.
By organizing the system this way, it ensures a clear hierarchy and easy management of users and permissions, making it user-friendly for all administrators involved.